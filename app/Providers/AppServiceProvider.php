<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->avoidDatabaseBackedStores();
    }

    /**
     * The arcade is intentionally database-free. Production environments that
     * still point sessions, cache, or queues at a database (including a SQLite
     * file that has not been created yet) would otherwise throw a 500 before
     * the first request is handled. Fall back to local file drivers instead.
     */
    private function avoidDatabaseBackedStores(): void
    {
        if (config('session.driver') === 'database') {
            config(['session.driver' => 'file']);
        }

        if (config('cache.default') === 'database') {
            config(['cache.default' => 'file']);
        }

        if (config('queue.default') === 'database') {
            config(['queue.default' => 'sync']);
        }
    }
}
