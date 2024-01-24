<?php

namespace KhanhArtisan\LaravelBackbone\ModelListener;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use KhanhArtisan\LaravelBackbone\Support\Finder;

class Observer
{
    /**
     * Store observer instances for model
     * 'modelClass' => $observerInstance
     *
     * @var array
     */
    protected static array $observers = [];

    /**
     * List of registered models
     *
     * @return array
     */
    public static function getRegisteredModels(): array
    {
        return array_keys(static::$observers);
    }

    /**
     * Check if the given model is registered
     *
     * @param string $modelClass
     * @return bool
     */
    public static function isRegistered(string $modelClass): bool
    {
        return in_array($modelClass, static::getRegisteredModels());
    }

    /**
     * Register all models which implement ObservableModel interface from the given path
     *
     * @param string $namespace
     * @param string $path
     * @return void
     */
    public static function registerModelsFrom(string $namespace, string $path): void
    {
        collect((new Finder())->in($path)->getClasses($namespace))
            ->filter(
                fn (string $class)
                    => $reflection = new \ReflectionClass($class)
                        and $reflection->isSubclassOf(Model::class)
                        and $reflection->isSubclassOf(ObservableModel::class)
            )
            ->each(fn (string $class) => static::registerModel($class));
    }

    /**
     * Register a model
     *
     * @param string $modelClass
     * @return void
     */
    public static function registerModel(string $modelClass): void
    {
        if (!class_exists($modelClass)) {
            throw new \InvalidArgumentException('Class '.$modelClass.' does not exists.');
        }

        // Return if modelClass cannot be instantiated (abstract class, interface...)
        $classReflection = new \ReflectionClass($modelClass);
        if (!$classReflection->isInstantiable()) {
            return;
        }

        /** @var Model $model */
        $model = new $modelClass;
        if (!$model instanceof Model) {
            throw new \InvalidArgumentException($modelClass.' is not an instance of '.Model::class);
        }

        // Get observer instance
        $observer = static::getObserverFor($model);

        // Register model events
        /** @var Dispatcher $dispatcher */
        $dispatcher = $modelClass::getEventDispatcher();
        $events = $model->getObservableEvents();
        foreach ($events as $event) {
            $dispatcher->listen("eloquent.{$event}: {$modelClass}", [$observer, $event]);
        }
    }

    /**
     * Get an observer instance for the given model
     *
     * @param string|Model $model
     * @return Observer
     */
    public static function getObserverFor(string|Model $model): Observer
    {
        $model = !is_string($model) ? $model : new $model;
        $modelClass = get_class($model);
        return static::$observers[$modelClass] ?? (static::$observers[$modelClass] = new static($model));
    }

    public function __construct(protected Model $model)
    {

    }

    /**
     * Forward event to the ModelListenerManager
     * Supports all events by model's getObservableEvents()
     * which include all user defined events and default eloquent events like: creating, created, updating, updated...
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __call(string $name, array $arguments = []): mixed
    {
        if (in_array($name, $this->model->getObservableEvents())
            and $resource = $arguments[0] ?? null
            and $resource instanceof Model
        ) {
            /** @var ModelListenerManager $modelListenerManager */
            $modelListenerManager = app()->make(ModelListenerManager::class);
            $modelListenerManager->fireModelEvent($resource, $name);
            return null;
        }

        return null;
    }
}