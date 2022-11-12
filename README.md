# Unipv Engineering Lectures Laravel Package
##  unipv/lectures

This package allows to retrieve data from the engineering websites of the University of Pavia.

### Installation

```bash

composer require abruno/unipvlectures  

```

Once the package is installed package assets must be published by using the artisan command ``` vendor:publish ```

```bash

php artisan vendor:publish --provider UnipvLecturers\Providers\UnipvLecturerProvider

```

In order to be able to use package assets, package service provider should be registered in your application.
Go to the file ```config/app.php```.

```php

  'providers' => [
   
   // other service providers
   
   /*
   * Package Service Providers...
   */
   UnipvLecturers\Providers\UnipvLecturerProvider::class,

   // other Service Providers...
   
   ],

```

After service provider is configured, and package assets are published, migration should be run.

```bash

php artisan migrate

```

Migration will create ```lectures, lessons, teachers, teacher_lessons``` in your database.
Once table are created command ```unipv:import-lectures``` must be called.

```bash

php artisan unipv:import-lectures

```

