<?php

namespace KhanhArtisan\LaravelBackbone\CascadeDeleteManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use KhanhArtisan\LaravelBackbone\CascadeDeleteManager\CascadeDeletable;
use KhanhArtisan\LaravelBackbone\CascadeDeleteManager\CascadeDeleteDetails;
use KhanhArtisan\LaravelBackbone\CascadeDeleteManager\CascadeDeleteManager;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListenerManager;

abstract class Cascade implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param int $recordsLimit The maximum number of records to be deleted in one job
     * @param int $chunk
     */
    public function __construct(protected int $recordsLimit = 10000, protected int $chunk = 100)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var CascadeDeleteManager $cascadeDeleteManager */
        $cascadeDeleteManager = app()->make(CascadeDeleteManager::class);

        // Loop through all model classes
        foreach ($cascadeDeleteManager->getCascadeDeletableModels() as $modelClass) {

            // Skip if the model class doesn't implement CascadeDeletable interface
            if (!in_array(CascadeDeletable::class, class_implements($modelClass))) {
                continue;
            }

            // Get the soft deleted models and relations_deleted = 0
            /** @var Model $modelClass */
            $instance = new $modelClass;

            // Skip if the model doesn't support soft deleting
            if (!method_exists($instance, 'getDeletedAtColumn')) {
                continue;
            }

            // Get the soft deleted models and relations_deleted = 0
            $query = $modelClass::query();
            $this->buildModelQuery($query);
            $models = $query->get();

            // Loop through all models
            foreach ($models as $model) {
                $this->handleCascadeDeletableModel($model);
            }
        }
    }

    /**
     * Build the model query
     *
     * @param Builder $query
     * @return void
     */
    abstract protected function buildModelQuery(Builder $query): void;

    /**
     * Handle the cascade-deletable model
     *
     * @param CascadeDeletable $model
     * @return void
     */
    abstract protected function handleCascadeDeletableModel(CascadeDeletable $model): void;
}