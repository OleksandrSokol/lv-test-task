<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Wires every folder under app/Modules into the app: its routes and migrations.
 *
 * A module keeps its routes in Http/Routes/{api,web}.php and its migrations in
 * database/migrations; nothing outside the module has to name them.
 */
class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ($this->modulePaths() as $path) {
            $this->registerRoutes($path);
            $this->registerMigrations($path);
        }
    }

    /**
     * @return list<string>
     */
    private function modulePaths(): array
    {
        return glob(app_path('Modules/*'), GLOB_ONLYDIR) ?: [];
    }

    private function registerRoutes(string $path): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        if (is_file($api = "{$path}/Http/Routes/api.php")) {
            Route::prefix('api')->middleware('api')->group($api);
        }

        if (is_file($web = "{$path}/Http/Routes/web.php")) {
            Route::middleware('web')->group($web);
        }
    }

    private function registerMigrations(string $path): void
    {
        if (is_dir($migrations = "{$path}/database/migrations")) {
            $this->loadMigrationsFrom($migrations);
        }
    }
}
