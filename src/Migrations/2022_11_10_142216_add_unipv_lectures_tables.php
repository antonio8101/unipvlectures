<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use UnipvLectures\Models\Lecture;
use UnipvLectures\Models\TeacherCourse;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lectures', function (Blueprint $table) {
            $table->id();

            $table->string('code')->nullable();
            $table->string('namecode');
            $table->string('day');
            $table->string('time');
            $table->string('name');
            $table->string('slug');
            $table->string('room');
            $table->string('course');

            $table->unsignedBigInteger('course_id')->nullable();

            $table->timestamps();
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('profile')->nullable();

            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code');

            $table->timestamps();
        });

        Schema::create('teacher_courses', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('course_id');

            $table->timestamps();
        });

        Lecture::addConstraints();

	    TeacherCourse::addConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Lecture::removeConstraints();

	    TeacherCourse::removeConstraints();

        Schema::dropIfExists('lectures');

        Schema::dropIfExists('teacher_courses');

        Schema::dropIfExists('teachers');

        Schema::dropIfExists('courses');
    }
};
