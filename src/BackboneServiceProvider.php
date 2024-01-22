<?php

namespace KhanhArtisan\LaravelBackbone;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use KhanhArtisan\LaravelBackbone\RelationCascade\RelationCascadeProvider;
use KhanhArtisan\LaravelBackbone\Console\Commands\ModelListener\MakeCommand;
use KhanhArtisan\LaravelBackbone\Console\Commands\ModelListener\ShowCommand;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Recorder;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Store;
use KhanhArtisan\LaravelBackbone\Counter\RecorderManager;
use KhanhArtisan\LaravelBackbone\Counter\StoreManager;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListenerManager;
use KhanhArtisan\LaravelBackbone\ModelListener\Observer;

class BackboneServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        // Config
        $this->mergeConfigFrom(__DIR__ . '/../config/counter.php', 'counter');

        // Register model listener manager
        $this->app->singleton(ModelListenerManager::class, ModelListenerManager::class);

        // Register RelationCascadeProvider
        $this->app->register(RelationCascadeProvider::class);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeCommand::class,
                ShowCommand::class
            ]);
        }

        $this->registerModelObservers();
        $this->registerModelListeners();
        $this->registerCounter();
    }

    /**
     * Register model observers
     *
     * @return void
     */
    protected function registerModelObservers(): void
    {
        // Default path to register
        $path = app_path('Models');

        // Skip if dir not found
        if (!is_dir($path)) {
            return;
        }

        // Register models
        Observer::registerModelsFrom($this->app->getNamespace().'Models', $path);
    }

    /**
     * Register default model listeners
     *
     * @return void
     * @throws BindingResolutionException
     */
    protected function registerModelListeners(): void
    {
        // Default path to register
        $path = app_path('ModelListeners');

        // Skip if dir not found
        if (!is_dir($path)) {
            return;
        }

        /** @var ModelListenerManager $manager */
        $manager = $this->app->make(ModelListenerManager::class);
        $manager->registerModelListenersFrom($this->app->getNamespace().'ModelListeners', $path);
    }

    /**
     * Register counter
     *
     * @return void
     */
    protected function registerCounter(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/counter.php' => config_path('counter.php')
        ], 'laravel-backbone-counter-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations/2023_07_01_164322_create_counter_table.php' => database_path('migrations')
        ], 'laravel-backbone-counter-migration');

        $this->app->singleton(Store::class, StoreManager::class);
        $this->app->singleton(Recorder::class, RecorderManager::class);
    }
}