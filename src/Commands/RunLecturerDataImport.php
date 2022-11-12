<?php

namespace UnipvLecturers\Commands;

use Closure;
use Illuminate\Console\Command;
use RoachPHP\Roach;
use UnipvLecturers\Models\Lecturer;
use UnipvLecturers\Models\Lesson;
use UnipvLecturers\Models\Teacher;
use UnipvLecturers\Models\TeacherLesson;
use UnipvLecturers\Spiders\UniPvEngineeringClassesSpider;
use UnipvLecturers\Spiders\UniPvEngineeringCoursesSpider;
use UnipvLecturers\Spiders\UniPvEngineeringTeacherEmailSpider;

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

		$refreshOption = $this->option('refresh');;

		$skipCleaningOperation = $refreshOption;

		$this->executeOperation( 'cleaning DB', fn() => $this->truncateTables(), $skipCleaningOperation );

		$this->executeOperation( 'import classes', fn() => Roach::startSpider( UniPvEngineeringClassesSpider::class ) );

		$this->executeOperation( 'import teacher email', fn() => Roach::startSpider( UniPvEngineeringTeacherEmailSpider::class ) );

		$this->executeOperation( 'import lecturers', fn() => Roach::startSpider( UniPvEngineeringCoursesSpider::class ) );

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
