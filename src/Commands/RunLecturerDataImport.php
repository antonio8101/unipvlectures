<?php

namespace UnipvLecturers\Commands;

use Illuminate\Console\Command;
use RoachPHP\Roach;
use UnipvLecturers\Models\Lecturer;
use UnipvLecturers\Models\Lesson;
use UnipvLecturers\Models\Teacher;
use UnipvLecturers\Models\TeacherLesson;
use UnipvLecturers\Spiders\UniPvEngineeringClassesSpider;
use UnipvLecturers\Spiders\UniPvEngineeringCoursesSpider;
use UnipvLecturers\Spiders\UniPvEngineeringTeacherEmailSpider;

class RunLecturerDataImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unipv:lecturer-import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run spiders to get data of courses from engineering courses..';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('START | cleaning DB.....');
        $this->truncateTables();
        $this->info('END | cleaning DB.....');

        $this->info('START | import classes.....');
        Roach::startSpider(UniPvEngineeringClassesSpider::class);
        $this->info('END | import classes.....');

        $this->info('START | import teacher email.....');
        Roach::startSpider(UniPvEngineeringTeacherEmailSpider::class);
        $this->info('END | import teacher email.....');

        $this->info('START | import lecturers.....');
        Roach::startSpider(UniPvEngineeringCoursesSpider::class);
        $this->info('END | import lecturers.....');

        return Command::SUCCESS;
    }

    private function truncateTables(){

        TeacherLesson::removeConstraints();
        Lecturer::removeConstraints();

        TeacherLesson::query()->truncate();
        Lecturer::query()->truncate();
        Lesson::query()->truncate();
        Teacher::query()->truncate();

        Lecturer::addConstraints();
        TeacherLesson::addConstraints();
    }
}
