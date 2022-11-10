<?php

namespace UnipvLecturers\Spiders;

use Generator;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\Extensions\StatsCollectorExtension;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Support\Configurable;

class UniPvEngineeringTeacherEmailSpider extends BasicSpider
{
    public array $itemProcessors = [
        UpdateEmailOnTeacherItemProcessor::class
    ];

    public array $extensions = [
        LoggerExtension::class,
        StatsCollectorExtension::class,
    ];

    public int $concurrency = 2;

    public int $requestDelay = 1;

    protected function initialRequests(): array
    {
        $requests = [];

        $teachers = \UnipvLecturers\Models\Teacher::whereNotNull('profile')->get();

        foreach ($teachers as $t){

            $requests[] = new Request(
                'GET',
                $t->profile,
                [$this, 'parse']
            );

        }

        return $requests;
    }

    /**
     * @param Response $response
     *
     * @return Generator
     */
    public function parse( Response $response ): Generator {

        $uri = $response->getUri();

        $linkHtmlItem = $response->filter( 'table:nth-child(2) tr:nth-child(9) td.contenuto a' );

        $email = $linkHtmlItem->text(); # parse to text

        yield $this->item( [
            "email" => $email,
            "uri"   => $uri
        ] );
    }
}

class UpdateEmailOnTeacherItemProcessor implements ItemProcessorInterface{

    use Configurable;

    public function processItem( ItemInterface $item ): ItemInterface {

        $teacher = \UnipvLecturers\Models\Teacher::getOneByProfile( $item['uri'] );

        if ( $teacher->email !== $item['email'] ) {

            $teacher->email = $item['email'];

            $teacher->save();

        }

        return $item;
    }
}
