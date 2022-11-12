<?php

namespace UnipvLectures\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * @property int $id
 * @property string $teacher_id
 * @property string $course_id
 * @method static where( string $string, string $name )
 */
class TeacherCourse extends Model
{
    use HasFactory;

    const TEACHER_CONSTRAINT = "t_l_teacher";
    const COURSE_CONSTRAINT = "t_l_course";

    public static function getOne(int $teacher_id, int $course_id): TeacherCourse | null {

        return TeacherCourse::where( 'teacher_id', $teacher_id )
                            ->where('course_id', $course_id)
                            ->first();

    }

    public static function exists(int $teacher_id, int $course_id): bool{

        return ! is_null(self::getOne($teacher_id, $course_id));

    }

    public static function removeConstraints(){

        Schema::table('teacher_courses', function (Blueprint $table) {

            $table->dropForeign(self::TEACHER_CONSTRAINT);

            $table->dropForeign(self::COURSE_CONSTRAINT);

        });

    }

    public static function addConstraints(){

        Schema::table('teacher_courses', function (Blueprint $table) {

            $table->foreign( 'teacher_id', self::TEACHER_CONSTRAINT)
                  ->references('id')
                  ->on('teachers');

            $table->foreign( 'course_id', self::COURSE_CONSTRAINT)
                  ->references('id')
                  ->on('courses');

        });

    }
}
