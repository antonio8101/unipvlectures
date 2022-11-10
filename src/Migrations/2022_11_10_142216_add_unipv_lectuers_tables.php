<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use UnipvLecturers\Models\Lecturer;
use UnipvLecturers\Models\TeacherLesson;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lecturers', function (Blueprint $table) {
            $table->id();

            $table->string('code')->nullable();
            $table->string('namecode');
            $table->string('day');
            $table->string('time');
            $table->string('name');
            $table->string('slug');
            $table->string('room');
            $table->string('course');

            $table->unsignedBigInteger('lesson_id')->nullable();

            $table->timestamps();
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('profile')->nullable();

            $table->timestamps();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code');

            $table->timestamps();
        });

        Schema::create('teacher_lessons', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('lesson_id');

            $table->timestamps();
        });

        Lecturer::addConstraints();

        TeacherLesson::addConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Lecturer::removeConstraints();

        TeacherLesson::removeConstraints();

        Schema::dropIfExists('lecturers');

        Schema::dropIfExists('teacher_lessons');

        Schema::dropIfExists('teachers');

        Schema::dropIfExists('lessons');
    }
};
