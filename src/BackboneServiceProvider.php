<?php

namespace KhanhArtisan\LaravelBackbone;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use KhanhArtisan\LaravelBackbone\Console\Commands\ModelListener\MakeCommand;
use KhanhArtisan\LaravelBackbone\Console\Commands\ModelListener\ShowCommand;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListenerManager;
use KhanhArtisan\LaravelBackbone\ModelListener\Observer;

class BackboneServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        // Register model listener manager
        $this->app->singleton(ModelListenerManager::class, ModelListenerManager::class);
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
}