<?php

namespace UnipvLectures\Providers;

use Illuminate\Support\ServiceProvider;
use UnipvLectures\Commands\RunLecturerDataImport;

class UnipvLectureProvider extends ServiceProvider
{
    protected array $package_commands = [
        RunLecturerDataImport::class
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void {

        $this->commands($this->package_commands);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void {

        $this->publishes([
            __DIR__.'/../Config/unipvlectures.php' => config_path('unipvlectures.php')
        ]);

        $this->loadRoutesFrom(__DIR__.'/../Routes/lectures.php');
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
    }
}
