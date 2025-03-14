<?php

namespace KhanhArtisan\LaravelBackbone\Tests;

use App\Http\Controllers\PostJsonController;
use App\Models\Post;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Routing\Middleware\SubstituteBindings;
use KhanhArtisan\LaravelBackbone\BackboneServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../stubs/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
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

    protected function defineEnvironment($app)
    {
        tap($app->make('config'), function (Repository $config) {

            $config->set('database.default', 'mysql');

            // Mysql database
            $config->set('database.connections.mysql.host', 'laravel.backbone.mysql');
            $config->set('database.connections.mysql.database', 'laravel_backbone');
            $config->set('database.connections.mysql.username', 'dbuser');
            $config->set('database.connections.mysql.password', 'password');

            // Pgsql database
            $config->set('database.connections.pgsql.host', 'laravel.backbone.pgsql');
            $config->set('database.connections.pgsql.database', 'laravel_backbone');
            $config->set('database.connections.pgsql.username', 'dbuser');
            $config->set('database.connections.pgsql.password', 'password');

            // Redis
            $config->set('database.redis.cache.host', 'laravel.backbone.redis');
            $config->set('database.redis.cache.password', null);
        });
    }

    public function seed($class = 'DatabaseSeeder')
    {
        // Implement seed() method.
    }
}