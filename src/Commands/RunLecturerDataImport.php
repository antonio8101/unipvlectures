<?php

namespace UnipvLectures\Commands;

use Closure;
use Illuminate\Console\Command;
use RoachPHP\Roach;
use UnipvLectures\Models\Lecture;
use UnipvLectures\Models\Course;
use UnipvLectures\Models\Teacher;
use UnipvLectures\Models\TeacherCourse;
use UnipvLectures\Spiders\UniPvEngineeringCoursesSpider;
use UnipvLectures\Spiders\UniPvEngineeringLecturesSpider;
use UnipvLectures\Spiders\UniPvEngineeringTeacherEmailSpider;

class RunLecturerDataImport extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'unipv:lecturer-import {--R|refresh}';

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
	public function handle() {

		$refreshOption = $this->option('refresh');

		$skipCleaningOperation = $refreshOption == 1;

		$this->executeOperation( 'cleaning DB', fn() => $this->truncateTables(), $skipCleaningOperation );

		$this->executeOperation( 'import courses', fn() => Roach::startSpider( UniPvEngineeringCoursesSpider::class ) );

		$this->executeOperation( 'import teacher email', fn() => Roach::startSpider( UniPvEngineeringTeacherEmailSpider::class ) );

		$this->executeOperation( 'import lectures', fn() => Roach::startSpider( UniPvEngineeringLecturesSpider::class ) );

		return Command::SUCCESS;
	}

	private function executeOperation( string $operation, Closure $operationCallback, bool $skip = false ): void {

		if ( $skip ) {

			$this->info( "SKIPPING | $operation....." );

			return;
		}

		$this->info( "START | $operation....." );

		$operationCallback();

		$this->info( "END | $operation....." );
	}

	private function truncateTables(): void {

		TeacherCourse::removeConstraints();
		Lecture::removeConstraints();

		TeacherCourse::query()->truncate();
		Lecture::query()->truncate();
		Course::query()->truncate();
		Teacher::query()->truncate();

		Lecture::addConstraints();
		TeacherCourse::addConstraints();
	}
}
