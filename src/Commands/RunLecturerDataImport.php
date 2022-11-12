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
	protected string $signature = 'unipv:lecturer-import {--R|refresh}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected string $description = 'Run spiders to get data of courses from engineering courses..';

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle(): int {

		$refreshOption = $this->argument( 'refresh' );

		$skipCleaningOperation = $refreshOption;

		$this->execute( 'cleaning DB', fn() => $this->truncateTables(), $skipCleaningOperation );

		$this->execute( 'import classes', fn() => Roach::startSpider( UniPvEngineeringClassesSpider::class ) );

		$this->execute( 'import teacher email', fn() => Roach::startSpider( UniPvEngineeringTeacherEmailSpider::class ) );

		$this->execute( 'import lecturers', fn() => Roach::startSpider( UniPvEngineeringCoursesSpider::class ) );

		return Command::SUCCESS;
	}

	private function execute( string $operation, Closure $operationCallback, bool $skip = false ): void {

		if ( $skip ) {

			$this->info( "SKIPPING | $operation....." );

			return;
		}

		$this->info( "START | $operation....." );

		$operationCallback();

		$this->info( "END | $operation....." );
	}

	private function truncateTables() {

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
