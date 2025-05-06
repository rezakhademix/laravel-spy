<?php

namespace Farayaz\LaravelSpy;

use Illuminate\Support\ServiceProvider;

class LaravelSpyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        LaravelSpy::boot();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/spy.php' => config_path('spy.php'),
            ], 'config');

            $migrationName = 'create_spy_http_logs_table.php';
            $targetPath = $this->getMigrationFileName($migrationName);
            $this->publishes([
                __DIR__ . '/../database/migrations/' . $migrationName . '.stub' => $targetPath,
            ], 'migrations');
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/spy.php', 'spy');
    }

    protected function getMigrationFileName(string $file): string
    {
        foreach (glob(database_path('migrations/*_' . $file)) as $existing) {
            return $existing;
        }

        return database_path('migrations/' . date('Y_m_d_His') . '_' . $file);
    }
}
