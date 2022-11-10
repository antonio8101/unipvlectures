<?php

namespace UnipvLecturers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * @property int $id
 * @property string $teacher_id
 * @property string $lesson_id
 * @method static where( string $string, string $name )
 */
class TeacherLesson extends Model
{
    use HasFactory;

    const TEACHER_CONSTRAINT = "t_l_teacher";
    const LESSON_CONSTRAINT = "t_l_lesson";

    public static function getOne(int $teacher_id, int $lesson_id): TeacherLesson | null {

        return TeacherLesson::where( 'teacher_id', $teacher_id )
                            ->where('lesson_id', $lesson_id)
                            ->first();

    }

    public static function exists(int $teacher_id, int $lesson_id): bool{

        return ! is_null(self::getOne($teacher_id, $lesson_id));

    }

    public static function removeConstraints(){

        Schema::table('teacher_lessons', function (Blueprint $table) {

            $table->dropForeign(TeacherLesson::TEACHER_CONSTRAINT);

            $table->dropForeign(TeacherLesson::LESSON_CONSTRAINT);

        });

    }

    public static function addConstraints(){

        Schema::table('teacher_lessons', function (Blueprint $table) {

            $table->foreign( 'teacher_id', TeacherLesson::TEACHER_CONSTRAINT)
                  ->references('id')
                  ->on('teachers');

            $table->foreign( 'lesson_id', TeacherLesson::LESSON_CONSTRAINT)
                  ->references('id')
                  ->on('lessons');

        });

    }
}
