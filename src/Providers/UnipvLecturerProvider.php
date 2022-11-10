<?php

namespace UnipvLecturers\Providers;

use Illuminate\Support\ServiceProvider;
use UnipvLecturers\Commands\RunLecturerDataImport;

class UnipvLecturerProvider extends ServiceProvider
{
    public const _UNIPV_LECTURERS_TEMPLATE_ = "UNIPV_LECTURERS_TEMPLATE";

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
            __DIR__.'/../Config/unipvlecturers.php' => config_path('unipvlecturers.php')
        ]);

        $this->loadRoutesFrom(__DIR__.'/../Routes/lecturers.php');
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
    }
}
