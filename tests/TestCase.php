<?php

namespace KhanhArtisan\LaravelBackbone\Tests;

use App\Http\Controllers\PostJsonController;
use App\Models\Post;
use Illuminate\Routing\Middleware\SubstituteBindings;
use KhanhArtisan\LaravelBackbone\BackboneServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../stubs/database/migrations');
    }

    protected function getBasePath()
    {
        return __DIR__.'/../stubs';
    }

    protected function getPackageProviders($app)
    {
        return [
            BackboneServiceProvider::class
        ];
    }

    protected function defineRoutes($router)
    {
        $router->middleware([
            SubstituteBindings::class
        ])->group(function () use ($router) {

            // Post resource
            $router->resource('posts', PostJsonController::class);
        });
    }
}