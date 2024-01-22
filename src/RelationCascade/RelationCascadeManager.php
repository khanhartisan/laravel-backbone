<?php

namespace KhanhArtisan\LaravelBackbone\RelationCascade;

use Illuminate\Database\Eloquent\Model;
use KhanhArtisan\LaravelBackbone\Support\Finder;

class RelationCascadeManager
{
    protected array $cascadeDeletableModels = [];

    /**
     * Get cascade deletable models
     *
     * @return array
     */
    public function getCascadeDeletableModels(): array
    {
        return $this->cascadeDeletableModels;
    }

    /**
     * Register model
     *
     * @param string $model
     * @return bool
     */
    public function registerModel(string $model): bool
    {
        // Check if model implements ShouldCascade
        if (!in_array(ShouldCascade::class, class_implements($model))) {
            return false;
        }

        // Unset if already registered
        if (in_array($model, $this->cascadeDeletableModels)) {
            unset($this->cascadeDeletableModels[array_search($model, $this->cascadeDeletableModels)]);
        }

        $this->cascadeDeletableModels[] = $model;

        return true;
    }

    /**
     * Register models
     *
     * @param array $models
     * @return void
     */
    public function registerModels(array $models): void
    {
        foreach ($models as $model) {
            $this->registerModel($model);
        }
    }

    /**
     * Register all models that implement ShouldCascade interface from the given path
     *
     * @param string $namespace
     * @param string $path
     * @return void
     */
    public function registerModelsFrom(string $namespace, string $path): void
    {
        $listenerClassNames = (new Finder())->in($path)->getClasses($namespace);
        $listenerClassNames = collect($listenerClassNames)
            ->filter(
                fn (string $class) =>
                    in_array(ShouldCascade::class, class_implements($class))
                    and is_subclass_of($class, Model::class)
            )
            ->values()
            ->toArray();

        $this->registerModels($listenerClassNames);
    }

    /**
     * Unregister models
     *
     * @param string $model
     * @return void
     */
    public function unregisterModel(string $model): void
    {
        if (!$key = array_search($model, $this->cascadeDeletableModels)) {
            return;
        }

        unset($this->cascadeDeletableModels[$key]);
    }

    /**
     * Unregister all models
     *
     * @return void
     */
    public function unregisterAllModels(): void
    {
        $this->cascadeDeletableModels = [];
    }
}