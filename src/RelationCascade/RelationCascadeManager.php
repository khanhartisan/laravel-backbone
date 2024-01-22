<?php

namespace KhanhArtisan\LaravelBackbone\RelationCascade;

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

        if (!in_array($model, $this->cascadeDeletableModels)) {
            $this->cascadeDeletableModels[] = $model;
        }

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