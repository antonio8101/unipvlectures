<?php

namespace UnipvLectures\Spiders;

use Illuminate\Support\Facades\Config;
use RoachPHP\Http\Request;
use UnipvLectures\Models\Course;
use UnipvLectures\Models\TeacherCourse;
use Exception;
use Generator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RoachPHP\Downloader\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\Extensions\StatsCollectorExtension;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Support\Configurable;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Create Teacher
 * Create Class
 * Create Teacher-Class intermediate entity
 */
class UniPvEngineeringCoursesSpider extends BasicSpider
{
    protected function initialRequests(): array
    {
        $requests = [];

        $urls = Config::get('unipvlectures.courses_urls') ?? [];

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
        BreakLineItemProcessor::class,
        GetClassNameFromInternalNodeItemProcessor::class,
        FormatTeachersNodeInternalNodeItemProcessor::class,
        SaveTeacherItemProcessor::class,
        SaveLessonItemProcessor::class
    ];

    public array $extensions = [
        LoggerExtension::class,
        StatsCollectorExtension::class,
    ];

    public int $concurrency = 2;

    public int $requestDelay = 1;

    public function parse( Response $response ): Generator {
        $nodes = $response->filter( '.page h2+p' )->each( function ( Crawler $node ) {
            return $node;
        } );

        foreach ( $nodes as $node ) {
            yield $this->item( [
                $node
            ] );
        }
    }
}


class BreakLineItemProcessor implements ItemProcessorInterface{

    use Configurable;

    public function processItem( ItemInterface $item ): ItemInterface {

        $node = $this->getNode($item);

        $node = $node->html();

        $itemInternalNodes = [];

        foreach (explode('<br>', $node) as $nodePart){

            $itemInternalNodes[] = new Crawler($nodePart);

        }

        $item->set('internal_nodes', $itemInternalNodes);


        return $item;
    }

    private function getNode( mixed $item ): Crawler {

        return $item[0];

    }
}

class GetClassNameFromInternalNodeItemProcessor implements  ItemProcessorInterface{

    use Configurable;

    public function processItem( ItemInterface $item ): ItemInterface {

        $internal_nodes_separated = [];
        $counter = 0;

        foreach ($item['internal_nodes'] as $internal_node){

            $node_html_value = $internal_node->html();

            if (Str::contains($node_html_value, '<a name')){
                unset($internal_node);
                continue;
            }

            $node_html_value = Str::replace('(n.d.)', '', $node_html_value);
            $node_html_value = Str::replace('(Nonlinear Part)', '- Nonlinear Part', $node_html_value);
            $node_html_value = Str::replace('(Optimization Part)', '- Optimization Part', $node_html_value);

            $class_name = Str::before($node_html_value, " (");
            $class_name = str_replace('<body><p>', '', $class_name);
            $internal_nodes_separated[$counter]['class_name'] = $class_name;

            $teachers = Str::after($node_html_value, " (");
            $teachers = str_replace(")", '', $teachers);
            $teachers = str_replace("</p></body>", '', $teachers);
            $teachers = explode(',', $teachers);
            $internal_nodes_separated[$counter]['teachers'] = $teachers;

            $counter++;
        }

        $internal_nodes_separated = array_filter($internal_nodes_separated, function ($nodeValueItem){

            if (count($nodeValueItem) > 0){
                return true;
            }

            return false;
        });

        $item->set('internal_nodes_separated', $internal_nodes_separated);

        return $item;
    }
}

class FormatTeachersNodeInternalNodeItemProcessor implements ItemProcessorInterface {

    use Configurable;

    public function processItem( ItemInterface $item ): ItemInterface {

        $item['internal_nodes_separated'] = array_map(function($internal_node_separated) {

            $internal_node_separated['teachers'] = array_map(
                fn ( $teacher ) => $this->getProperTeacherFormat( $teacher ),
                $internal_node_separated['teachers']
            );

            return $internal_node_separated;
        }, $item['internal_nodes_separated']);

        return $item;
    }

    private function getProperTeacherFormat(string $teacherWithLink): Teacher{

        $node = new Crawler($teacherWithLink);

        $link = $this->getProfileUrl( $node );

        $name = trim($node->text());
        $name = str_replace(' ', '', $name);

        $teacher = new Teacher();

        $teacher->name = $name;

        $teacher->profile = $link;

        return $teacher;
    }

    private function getProfileUrl( Crawler $profileLink ): ?string {

        try {

            $profileLink = $profileLink->filter('a');

            return $profileLink->attr( 'href' );

        } catch ( Exception $e){

            Log::debug($e->getMessage());

            return null;
        }
    }
}

class SaveTeacherItemProcessor implements ItemProcessorInterface {

    use Configurable;

    public function processItem( ItemInterface $item ): ItemInterface {

        foreach ($item['internal_nodes_separated'] as $internalNodesSeparated){

            foreach ($internalNodesSeparated['teachers'] as $t){

                if (\UnipvLectures\Models\Teacher::exists($t->name, $t->profile))
                    continue;

                $teacher = new \UnipvLectures\Models\Teacher();

                $teacher->name = $t->name;
                $teacher->profile = $t->profile;
                $teacher->email = "";

                $teacher->save();

            }
        }

        return $item;
    }
}

class SaveLessonItemProcessor implements ItemProcessorInterface{

    use Configurable;

    public function processItem( ItemInterface $item ): ItemInterface {

        foreach ( $item['internal_nodes_separated'] as $internalNodesSeparated ) {

            $className = $internalNodesSeparated['class_name'];

            if ( ! Course::exists( $className ) ) {

                $this->createLesson( $className );

            }

            $lesson = Course::getOne( $className );

            $teachers = $this->getTeachers( $internalNodesSeparated['teachers'] );

            foreach ($teachers as $t){

                if (TeacherCourse::exists($t->id, $lesson->id))
                    continue;

                $this->createTeacherLesson( $lesson, $t );
            }
        }

        return $item;
    }

    private function getTeachers( array $t ): array {

        return array_map( fn( $t ) => \UnipvLectures\Models\Teacher::getOneByNameAndProfile( $t->name, $t->profile ), $t );

    }

    private function createLesson( mixed $className ): void {

        Course::create( $className );

    }

    private function createTeacherLesson( ?Course $lessonItem, mixed $t ): void {

        $teacherLesson = new TeacherCourse();

        $teacherLesson->course_id  = $lessonItem->id;
        $teacherLesson->teacher_id = $t->id;

        $teacherLesson->save();

    }
}

class Teacher {

    public string $name;

    public string | null $profile;

}
