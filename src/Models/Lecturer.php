<?php

namespace UnipvLecturers\Models;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use UnipvLecturers\Enums\Day;
use UnipvLecturers\Enums\Time;
use UnipvLecturers\Utils\Helper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $code
 * @property int | null $lesson_id
 * @property string $day
 * @property string $time
 * @property string $room
 * @property string $course
 * @property string $name
 * @property mixed $namecode
 * @property string $slug
 * @method static where( \Closure|string|array $string, string|null $code = null )
 * @method static groupBy( string $string )
 * @method static get()
 */
class Lecturer extends Model
{
    const LESSON_ID_CONSTRAINT = 'ct_lessons';

    use HasFactory;

    protected $fillable = [
        'day',
        'time',
        'name',
        'room',
        'namecode',
        'slug',
        'course',
        'lesson_id'
    ];

    protected $visible = [
        'name',
        'room',
        'course',
        'day',
        'time',
        'info',
        'lesson_id'
    ];

    public static function createLecturer(string $day, string $time, string $name, string $room, string $course, ?int $lesson_id): Lecturer{

        $code = preg_replace('/\s+/', ' ',$name);
        $code .= $day;
        $code .= $time;
        $code .= $room;
        $code = md5($code);

        $encodedName = Lesson::getCode( $name );

        $previousInsert = Lecturer::where('code', $code)->first();

        if (! is_null($previousInsert))
            return $previousInsert;

        $lecturer = new Lecturer();

        $lecturer->code      = $code;
        $lecturer->day       = $day;
        $lecturer->time      = $time;
        $lecturer->name      = $name;
        $lecturer->room      = $room;
        $lecturer->course    = $course;
        $lecturer->namecode  = $encodedName;
        $lecturer->lesson_id = $lesson_id;
        $lecturer->slug      = Str::slug($name);

        $lecturer->save();

        return $lecturer;
    }

    public static function getLecturesDistinctNames( Day|null $day = null ) {

        return Lecturer::where( function ( Builder $builder ) use ( $day ) {

            if ( ! is_null( $day ) ) {
                return $builder->where( 'day', $day->name );
            } else {
                return $builder;
            }
        } )->get( [ 'name', 'slug' ] )
           ->groupBy( [ 'name' ] )
           ->map( fn( $l ) => $l->first() );

    }

    public static function getLecturesByDayAndTimeDistinctNames( Day $day, Time $time ) {

        return Lecturer::where( 'day', $day->name )
                       ->where('time', Helper::toTimeSlot($time))
                       ->get( [ 'name', 'slug' ] )
                       ->groupBy( [ 'name' ] )
                       ->map( fn( $l ) => $l->first() );

    }

    public function info(): HasOne {

        return $this->hasOne(Lesson::class, 'id', 'lesson_id');

    }

    public static function removeConstraints(){

        Schema::table('lecturers', function (Blueprint $table) {

            $table->dropForeign(self::LESSON_ID_CONSTRAINT);

        });

    }

    public static function addConstraints(){

        Schema::table('lecturers', function (Blueprint $table) {

            $table->foreign('lesson_id', self::LESSON_ID_CONSTRAINT)
                  ->references('id')
                  ->on('lessons');

        });

    }
}
