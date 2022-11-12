<?php

namespace UnipvLectures\Models;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use UnipvLectures\Enums\Day;
use UnipvLectures\Enums\Time;
use UnipvLectures\Utils\Helper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $code
 * @property int | null $course_id
 * @property string $day
 * @property string $time
 * @property string $room
 * @property string $course
 * @property string $name
 * @property mixed $namecode
 * @property string $slug
 * @method static where( Closure|string|array $string, string|null $code = null )
 * @method static groupBy( string $string )
 * @method static get()
 */
class Lecture extends Model
{
    const COURSE_ID_CONSTRAINT = 'ct_courses';

    use HasFactory;

    protected $fillable = [
        'day',
        'time',
        'name',
        'room',
        'namecode',
        'slug',
        'course',
        'course_id'
    ];

    protected $visible = [
        'name',
        'room',
        'course',
        'day',
        'time',
        'info',
        'course_id'
    ];

    public static function createLecture(string $day, string $time, string $name, string $room, string $course, ?int $course_id): Lecture{

        $code = preg_replace('/\s+/', ' ',$name);
        $code .= $day;
        $code .= $time;
        $code .= $room;
        $code = md5($code);

        $encodedName = Course::getCode( $name );

        $previousInsert = Lecture::where('code', $code)->first();

        if (! is_null($previousInsert))
            return $previousInsert;

        $lecturer = new Lecture();

        $lecturer->code      = $code;
        $lecturer->day       = $day;
        $lecturer->time      = $time;
        $lecturer->name      = $name;
        $lecturer->room      = $room;
        $lecturer->course    = $course;
        $lecturer->namecode  = $encodedName;
        $lecturer->course_id = $course_id;
        $lecturer->slug      = Str::slug($name);

        $lecturer->save();

        return $lecturer;
    }

    public static function getLecturesDistinctNames( Day|null $day = null ) {

        return Lecture::where( function ( Builder $builder ) use ( $day ) {

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

        return Lecture::where( 'day', $day->name )
                      ->where('time', Helper::toTimeSlot($time))
                      ->get( [ 'name', 'slug' ] )
                      ->groupBy( [ 'name' ] )
                      ->map( fn( $l ) => $l->first() );

    }

    public function info(): HasOne {

        return $this->hasOne(Course::class, 'id', 'course_id');

    }

    public static function removeConstraints(){

        Schema::table('lectures', function (Blueprint $table) {

            $table->dropForeign(self::COURSE_ID_CONSTRAINT);

        });

    }

    public static function addConstraints(){

        Schema::table('lectures', function (Blueprint $table) {

            $table->foreign('course_id', self::COURSE_ID_CONSTRAINT)
                  ->references('id')
                  ->on('courses');

        });

    }
}
