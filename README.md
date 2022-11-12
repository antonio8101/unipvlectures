# Unipv Engineering Lectures Laravel Package
##  unipv/lectures

This package allows to retrieve data from the engineering websites of the University of Pavia.

### Installation

```console

composer require abruno/unipvlectures  

```

Once the package is installed package assets must be published by using the artisan command ``` vendor:publish ```

```console

php artisan vendor:publish --provider UnipvLectures\Providers\UnipvLecturerProvider

```

In order to be able to use package assets, package service provider should be registered in your application.
Go to the file ```config/app.php```.

```php

  'providers' => [
   
   // other service providers
   
   /*
   * Package Service Providers...
   */
   UnipvLectures\Providers\UnipvLectureProvider::class,

   // other Service Providers...
   
   ],

```

After service provider is configured, and package assets are published, migration should be run.

```console

php artisan migrate

```

Migration will create ```lectures, courses, teachers, teacher_courses``` in your database.
Once table are created command ```unipv:import-lectures``` must be called.

Provide then the right configuration in ```config/unipvlectures.php```, variables ```courses_urls``` and ```lectures_urls```.

```console

php artisan unipv:lecturer-import

```

Wait till the command ends the import.

After data is imported you can start by creating a ```blade template```. Once it has been created provide its name still in the configuration  ```config/unipvlectures.php```,  ```template```.


```php

  return [

    /*
    |--------------------------------------------------------------------------
    | Default Template
    |--------------------------------------------------------------------------
    |
    | This option controls the template used by the root to display data.
    |
    */

    'template' => '',

    /*
    |--------------------------------------------------------------------------
    | Urls used to retrieve Classes
    |--------------------------------------------------------------------------
    |
    | This option sets the url where to retrieve course names.
    |
    */

    'courses_urls' => [],

    /*
    |--------------------------------------------------------------------------
    | Urls used to retrieve Lecturers
    |--------------------------------------------------------------------------
    |
    | This option sets the url where to retrieve the lectures.
    |
    */

    'lectures_urls' => [],
];

```

To see the timetable open the linK:

```

http://localhost:8000/lectures

```