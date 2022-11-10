<?php

use Illuminate\Support\Facades\Config;
use UnipvLecturers\Enums\Day;
use UnipvLecturers\Enums\Time;
use UnipvLecturers\Utils\Helper;
use UnipvLecturers\Models\Lecturer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;


/**
 * Routes for lecturers
 * @prefix lecturers
 */
Route::prefix('/lecturers')->group(function (){

    $view = Config::get('unipvlecturers.template');

    Route::get( '/', function () use ( $view ) {

        $data = Lecturer::with( [ 'info' => fn( $q ) => $q->with( 'teachers' ) ] )
                        ->get()
                        ->map( function ($i) {
                            $i->day_order = Helper::toDayOrderNumber($i->day);
                            return $i;
                        } )
                        ->sortBy( fn ($i) => $i->time )
                        ->sortBy( fn ($i) => $i->day_order );

        $courses = Lecturer::getLecturesDistinctNames();

        if ( ! View::exists($view) )
            return $data;

        return view( $view, [
            'data'    => $data,
            'courses' => $courses
        ] );

    } );

    Route::get( '/{dayOrSlug}', function ( mixed $dayOrSlug ) use ($view) {

        $data = Lecturer::where(function (Builder $query) use ($dayOrSlug){

            $d = Day::tryFrom( $dayOrSlug );

            if ($d instanceof Day){
                return $query->where( 'day', $d->name );
            }

            return $query->where('slug', $dayOrSlug);
        })->with( [ 'info' => fn( $q ) => $q->with( 'teachers' ) ] )
                        ->get()
                        ->map( function ($i) {
                            $i->day_order = Helper::toDayOrderNumber($i->day);
                            return $i;
                        } )
                        ->sortBy(fn ($i) => $i->name)
                        ->sortBy( fn ($i) => $i->time )
                        ->sortBy( fn ($i) => $i->day_order );

        $d = Day::tryFrom( $dayOrSlug );

        $courses = ($d instanceof Day) ?
            Lecturer::getLecturesDistinctNames($d) :
            Lecturer::getLecturesDistinctNames();

        if ( ! View::exists($view) )
            return $data;

        return view( $view, [
            'data'    => $data,
            'day'     => $d,
            'courses' => $courses
        ] );
    } );

    Route::get( '/{day}/{slug}', function ( Day $day, string $secondValue ) use ($view) {

        $data = Lecturer::where( 'day', $day->name )
                        ->where( function ( Builder $query ) use ( $secondValue ) {

                            $d = Time::tryFrom( $secondValue );

                            if ( $d instanceof Time ) {

                                $timeSlot = Helper::toTimeSlot( $d );

                                return $query->where( 'time', $timeSlot );
                            }

                            return $query->where( 'slug', 'like', "$secondValue%" );
                        } )
                        ->with( [ 'info' => fn( $q ) => $q->with( 'teachers' ) ] )
                        ->orderBy( 'time' )
                        ->orderBy( 'name' )
                        ->get();

        $time = Time::tryFrom( $secondValue );

        $courses = [];

        $data = [ 'data' => $data, 'day' => $day, 'courses' => $courses ];

        if ( $time instanceof Time ) {

            $courses = Lecturer::getLecturesByDayAndTimeDistinctNames($day, $time);

            $data['time'] = $time;
            $data['courses'] = $courses;

        }

        if ( ! View::exists($view) )
            return $data;

        return view( $view, $data );
    } );

    Route::get( '/{day}/{time}/{slug}', function ( Day $day, Time $time, string $name ) use ($view) {

        $data = Lecturer::where( 'day', $day->name )
                        ->where( function ( Builder $query ) use ( $time, $name ) {

                            if ( $time instanceof Time ) {

                                $timeSlot = Helper::toTimeSlot( $time );

                                $query->where( 'time', $timeSlot );
                            }

                            $query->where( 'slug', 'like', "$name%" );

                            return $query;
                        } )
                        ->with( [ 'info' => fn( $q ) => $q->with( 'teachers' ) ] )
                        ->orderBy( 'time' )
                        ->orderBy( 'name' )
                        ->get();

        $courses = Lecturer::getLecturesDistinctNames($day);

        if ( ! View::exists($view) )
            return $data;

        return view( $view, [
            'data'    => $data,
            'day'     => $day,
            'time'    => $time,
            'courses' => $courses
        ] );
    } );

});

