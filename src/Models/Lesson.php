<?php

namespace UnipvLecturers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @method static where( string $string, string $name )
 */
class Lesson extends Model
{
    use HasFactory;

    protected $visible = [
      'teachers',
      'id'
    ];

    public static function create( string $name ): void {

        $lesson = new Lesson();

        $lesson->code = self::getCode( $name );
        $lesson->name = $name;

        $lesson->save();

    }

    public static function getCode( string $name ): string{

        return md5(str_replace(' ', '', $name));

    }

    public static function getOne(string $name): Lesson | null {

        return Lesson::where( 'name', $name )->first();

    }

    public static function getOneByCode(string $code): Lesson | null {

        return Lesson::where( 'code', $code )->first();

    }

    public static function exists(string $name): bool{

        return ! is_null(Lesson::getOne($name));

    }

    public function lecturers(): BelongsToMany {

        return $this->belongsToMany( Lecturer::class );

    }

    public function teachers(): HasManyThrough {

        return $this->hasManyThrough(
            Teacher::class,
            TeacherLesson::class,
        'lesson_id',
        'id',
        'id',
        'teacher_id');

    }
}
