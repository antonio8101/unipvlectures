<?php

namespace UnipvLectures\Spiders;

use Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use RoachPHP\Downloader\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\Extensions\StatsCollectorExtension;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Support\Configurable;
use Symfony\Component\DomCrawler\Crawler;
use UnipvLectures\Models\Lecture;
use UnipvLectures\Models\Course;
use UnipvLectures\Utils\Helper;

class UniPvEngineeringLecturesSpider extends BasicSpider {

    protected function initialRequests(): array
    {
        $requests = [];

        $urls = Config::get('unipvlectures.lectures_urls') ?? [];

        foreach ($urls as $url){

            $requests[] = new Request(
                'GET',
                $url,
                [$this, 'parse']
            );

        }

        return $requests;
    }

    public array $downloaderMiddleware = [
        RequestDeduplicationMiddleware::class,
    ];

    public array $spiderMiddleware = [
        //
    ];

    public array $itemProcessors = [
        SaveToDatabaseLectureProcessor::class
    ];

    public array $extensions = [
        LoggerExtension::class,
        StatsCollectorExtension::class,
    ];

    public int $concurrency = 2;

    public int $requestDelay = 1;

    /**
     * @param Response $response
     *
     * @return Generator
     */
    public function parse( Response $response ): Generator {

        $course = $response->filter( 'h1' )->text();

        $timeSlotGroups = $this->getTimeSlotGroup( $response, $course );
        $timeSlotGroups = array_filter( $timeSlotGroups, fn( $i ) => ! is_null( $i ) );
        $lecturers = $this->flattenGroups( $timeSlotGroups );

        foreach ($lecturers as $lecturer){
            yield $this->item( [ $lecturer ] );
        }
    }

    private array $timeSlots = Helper::LECTURERS_TIME_SLOTS;

    private array $daySlots = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday'
    ];

    private function flattenGroups( array $daySlotGroups ): Collection {
        $dayLessons = collect();

        foreach ( $daySlotGroups as $dg ) {
            $dayLessons = $dayLessons->concat( $dg );
        }

        return $dayLessons;
    }

    private function getTimeSlotGroup( Response $response, string $course ): array {
        return $response->filter( 'table tr:not(tr:first-child)' )->each( function ( Crawler $tableRowNode, $l ) use ( $course ) {

            if ( strlen( $tableRowNode->text() ) < 1 ) {
                return null;
            }

            $time = $this->timeSlots[ $l ];

            $daySlotGroups = $this->getDaySlotGroups( $tableRowNode, $time, $course );

            return $this->flattenGroups( $daySlotGroups );
        } );
    }

    private function getDaySlotGroups( Crawler $tableRowNode, string $time, string $course ): array {

        $daySlots = $this->daySlots;

        return $tableRowNode->children( 'td:not(td:first-child)' )->each( function ( Crawler $crawler, $i ) use ( $time, $daySlots, $course ) {

            $crawler->text();
            $htmlContent = $crawler->html();

            $separator = 'mso-spacerun';

            $contents = explode( '<br>', $htmlContent );

            $timeLessonsGroup = collect( [] );

            foreach ( $contents as $content ) {

                $lesson = trim( Str::before( $content, $separator ) );
                $lesson = Str::substr( $lesson, 0, Str::length( $lesson ) - 13 );
                $room   = trim( Str::after( $content, $separator ) );
                $room   = trim( Str::after( $room, '-' ) );
                $room   = Str::replace('spacerun:yes">  </span>- ', '', $room);

                if ( strlen( $lesson ) < 3 ) {
                    continue;
                }

                $lessonItem = $this->buildLessonItem( $daySlots[ $i ], $time, $lesson, $room, $course );

                $timeLessonsGroup->push( $lessonItem );
            }

            return $timeLessonsGroup;
        } );
    }

    private function buildLessonItem( $day, $time, $title, $room, $course ): LectureModel {

        $lecture = new LectureModel();

        $lecture->daySlot = $day;
        $lecture->timeSlot = $time;
        $title = str_replace (array("\r\n", "\n", "\r"), '', $title);
        $title = str_replace ('  ', ' ', $title);
        $lecture->name = $title;
        $lecture->room = $room;
        $course = str_replace(' ', ' ', $course);
        $lecture->course = $course;

        return $lecture;
    }
}

class LectureModel {

    public string $daySlot;
    public string $timeSlot;
    public string $name;
    public string $room;
    public string $course;

}

class SaveToDatabaseLectureProcessor implements ItemProcessorInterface{

    use Configurable;

    public function processItem( ItemInterface $item ): ItemInterface {

        $lecture = $this->getLecture( $item );

        $lessonCode = Course::getCode( $lecture->name );
        $course     = Course::getOneByCode( $lessonCode ) ?? new Course();
        $course_id  = $course->id;

        Lecture::createLecture(
            $lecture->daySlot,
            $lecture->timeSlot,
            $lecture->name,
            $lecture->room,
            $lecture->course,
            $course_id
        );

        return $item;
    }

    private function getLecture( mixed $item ): LectureModel {

        return $item[0];

    }
}
