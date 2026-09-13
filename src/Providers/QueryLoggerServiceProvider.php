<?php

namespace Godmode\QueryLogger\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Throwable;

class QueryLoggerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/query-logger.php', 'query-logger');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'query-logger');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'query-logger');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/query-logger.php' => config_path('query-logger.php'),
            ], 'query-logger-config');
        }

        DB::listen(function (QueryExecuted $query): void {
            $sql = ltrim($query->sql);

            if (str_contains(strtolower($sql), 'query_logs') || preg_match('/^explain(?:\s|$)/i', $sql) === 1) {
                return;
            }

            $request      = $this->app->bound('request') ? request() : null;
            $callerClass  = null;
            $callerMethod = null;

            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
                if (! isset($frame['class'], $frame['function']) || $frame['class'] === self::class) {
                    continue;
                }

                if (str_starts_with($frame['class'], 'App\\')
                    || str_starts_with($frame['class'], 'Godmode\\QueryLogger\\Http\\Controllers\\')) {
                    $callerClass  = $frame['class'];
                    $callerMethod = $frame['function'];

                    break;
                }
            }

            try {
                DB::table('query_logs')->insert([
                    'connection'    => $query->connectionName,
                    'sql'           => $query->toRawSql(),
                    'bindings'      => json_encode($query->bindings),
                    'time_ms'       => $query->time,
                    'route_name'    => $request?->route()?->getName(),
                    'url'           => $request?->fullUrl(),
                    'ip_address'    => $request?->ip(),
                    'caller_class'  => $callerClass,
                    'caller_method' => $callerMethod,
                    'created_at'    => now(),
                ]);
            } catch (Throwable) {
                // Query logging must never break the query being observed.
            }
        });
    }
}
