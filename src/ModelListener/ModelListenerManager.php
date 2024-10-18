<?php

namespace KhanhArtisan\LaravelBackbone\ModelListener;

use Illuminate\Database\Eloquent\Model;
use KhanhArtisan\LaravelBackbone\Support\Finder;

class ModelListenerManager
{
    /**
     * Format:
     *
     * '{{ modelClass }}' => [
     *      '{{ event }}' => [
     *          'sortKey1' => 'ListenerClassName1',
     *          'sortKey2' => 'ListenerClassName2',
     *          ...
     *      ]
     * ]
     *
     * @var array
     */
    protected array $map = [];

    /**
     * Internal container for model listener instances
     * Format:
     * [
     *     'modelListenerClass' => instance
     * ]
     *
     * @var array
     */
    protected array $instances = [];

    /**
     * Get the event map
     *
     * @return array
     */
    public function getMap(): array
    {
        return $this->map;
    }

    /**
     * Register an absolute path to let the manager knows where to load model listener files
     *
     * @param string $namespace
     * @param string $path
     * @return void
     */
    public function registerModelListenersFrom(string $namespace, string $path): void
    {
        $listenerClassNames = (new Finder())->in($path)->getClasses($namespace);
        $listenerClassNames = collect($listenerClassNames)
            ->filter(
                fn (string $class) => in_array(ModelListenerInterface::class, class_implements($class))
            )
            ->values()
            ->toArray();
        $this->registerModelListeners($listenerClassNames);
    }

    /**
     * Register the given model listeners
     *
     * @param array<string> $modelListenerClasses
     * @return void
     */
    public function registerModelListeners(array $modelListenerClasses): void
    {
        foreach ($modelListenerClasses as $index => $listenerClass) {

            if (!class_exists($listenerClass)) {
                throw new \InvalidArgumentException('Listener #'.$index.': Class '.$listenerClass.' does not exists.');
            }

            // Skip if it is not instantiable
            $reflection = new \ReflectionClass($listenerClass);
            if (!$reflection->isInstantiable()) {
                continue;
            }

            $listener = new $listenerClass();

            // Check is valid listener
            if (!$listener instanceof ModelListenerInterface) {
                throw new \InvalidArgumentException('Listener #'.$index.' is not an instance of '.ModelListenerInterface::class);
            }

            // Register instance
            if (!isset($this->instances[$listenerClass])) {
                $this->instances[$listenerClass] = $listener;
            }

            // Register model class to the map
            $modelClass = $listener->modelClass();
            if (!isset($this->map[$modelClass])) {
                $this->map[$modelClass] = [];
            }

            // Register model events
            $listenerSortKey = $this->getModelListenerSortKey($listener);
            foreach ($listener->events() as $event) {
                if (!isset($this->map[$modelClass][$event])) {
                    $this->map[$modelClass][$event] = [];
                }

                // Check if listener isn't registered
                if (!isset($this->map[$modelClass][$event][$listenerSortKey])) {

                    // Register listener
                    $this->map[$modelClass][$event][$listenerSortKey] = $listenerClass;

                    // Sort listeners by sort key, reversed
                    krsort($this->map[$modelClass][$event]);
                }
            }
        }
    }

    /**
     * Unregister the given model listeners
     *
     * @param array<string> $modelListenerClasses
     * @return void
     */
    public function unregisterModelListeners(array $modelListenerClasses): void
    {
        // Unregister from instances cache
        foreach ($modelListenerClasses as $modelListenerClass) {
            if (isset($this->instances[$modelListenerClass])) {
                unset($this->instances[$modelListenerClass]);
            }
        }

        // Unregister from the map
        foreach ($this->map as $modelClass => $data) {
            foreach ($data as $event => $registeredModelListenerClasses) {
                foreach ($modelListenerClasses as $key => $modelListenerClass) {
                    if (in_array($modelListenerClass, $registeredModelListenerClasses)) {
                        unset($this->map[$modelClass][$event][$key]);
                    }
                }
            }
        }
    }

    /**
     * Trigger an event
     *
     * @param Model $resource
     * @param string $event
     * @return void
     */
    public function fireModelEvent(Model $resource, string $event): void
    {
        if (!in_array($event, $resource->getObservableEvents())) {
            throw new \InvalidArgumentException($event.' is not a valid event.');
        }

        $modelClass = get_class($resource);
        foreach (($this->map[$modelClass][$event] ?? []) as $listenerClass) {
            if ($listener = $this->getListener($listenerClass)) {
                $listener->handle($resource, $event);
            }
        }
    }

    /**
     * Get sort key by priority for the given listener
     *
     * @param ModelListenerInterface $listener
     * @return string
     */
    protected function getModelListenerSortKey(ModelListenerInterface $listener): string
    {
        $classHash = substr(sha1(get_class($listener)), 0, 6);
        $classHash = (float) ('0.'.hexdec($classHash));
        return $listener->priority() + $classHash;
    }

    /**
     * Get model listener instance by its class name
     *
     * @param string $class
     * @return ModelListenerInterface|null
     */
    protected function getListener(string $class): ?ModelListenerInterface
    {
        // Return null if listener isn't registered
        if (!isset($this->instances[$class])) {
            return null;
        }

        /** @var ModelListenerInterface $instance */
        $instance = $this->instances[$class];

        // Return saved instance if singleton
        if ($instance->isSingleton()) {
            return $instance;
        }

        // Unset the saved instance
        unset($instance);

        // Create new instance, save it, and return
        return $this->instances[$class] = new $class;
    }
}