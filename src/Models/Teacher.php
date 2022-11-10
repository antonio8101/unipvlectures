<?php

namespace UnipvLecturers\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $profile
 * @method static where( string $string, string $name )
 * @method static whereNotNull( string $string )
 */
class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
      'name', 'email', 'profile'
    ];

    protected $visible = [
      'name', 'email', 'profile'
    ];

    public static function getOneByProfile(string $profile): Teacher | null{

        return Teacher::where('profile', $profile)->first();

    }

    public static function getOneByNameAndProfile(string $name, string | null $profile): Teacher | null {

        return Teacher::where( 'name', $name )
                      ->where( function ( Builder $query ) use ( $profile ) {

                          if ( ! is_null( $profile ) ) {
                              return $query->where( 'profile', $profile );
                          }

                          return $query->whereNull( $profile );
                      } )
                      ->first();

    }

    public static function exists(string $name, string | null $profile): bool{

        return ! is_null(Teacher::getOneByNameAndProfile($name, $profile));

    }
}
