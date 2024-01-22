<?php

namespace KhanhArtisan\LaravelBackbone\RelationCascade\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use KhanhArtisan\LaravelBackbone\RelationCascade\CascadeStatus;
use KhanhArtisan\LaravelBackbone\RelationCascade\ShouldCascade;
use KhanhArtisan\LaravelBackbone\RelationCascade\CascadeDetails;
use KhanhArtisan\LaravelBackbone\RelationCascade\RelationCascadeManager;
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
        /** @var RelationCascadeManager $cascadeDeleteManager */
        $cascadeDeleteManager = app()->make(RelationCascadeManager::class);

        // Loop through all model classes
        foreach ($cascadeDeleteManager->getCascadeDeletableModels() as $modelClass) {

            // Get the soft deleted models and cascade_status = 0
            /** @var Model $modelClass */
            $instance = new $modelClass;

            // Skip if the model doesn't support soft deleting
            if (!method_exists($instance, 'getDeletedAtColumn')) {
                continue;
            }

            // Get the soft deleted models and cascade_status = 0
            $query = $modelClass::query();
            $this->buildModelQuery($query);
            $models = $query->get();

            // Loop through all models
            foreach ($models as $model) {

                // Return if the limit is reached
                if ($this->recordsLimit <= 0) {
                    return;
                }

                if ($this->handleCascadeModel($model)) {
                    $this->onAllRelationsFinished($model);
                    $model->setAttribute($model->getCascadeUpdatedAtColumn(), now());
                    $model->save();
                }
            }
        }
    }

    /**
     * Handle a cascade model, return true if all relations are settled, false otherwise
     *
     * @param ShouldCascade $model
     * @return bool
     */
    protected function handleCascadeModel(ShouldCascade $model): bool
    {
        // Get the cascade delete details
        $cascadeDeleteDetails = $model->getCascadeDetails();
        $cascadeDeleteDetails = is_array($cascadeDeleteDetails) ? $cascadeDeleteDetails : [$cascadeDeleteDetails];

        // Loop through all cascade delete details
        foreach ($cascadeDeleteDetails as $details) {

            // Skip if it should not delete
            if (!$this->shouldProceed($details)) {
                continue;
            }

            if (!$this->handleCascadeDetails($details)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param CascadeDetails $cascadeDetails
     * @return bool true if all the relations are settled, false otherwise
     */
    protected function handleCascadeDetails(CascadeDetails $cascadeDetails): bool
    {
        // Return if the limit is reached
        if ($this->recordsLimit <= 0) {
            return false;
        }

        $limit = min($this->chunk, $this->recordsLimit);
        $handledCount = 0;

        if ($cascadeDetails->shouldUseTransaction()) {
            DB::transaction(function () use ($cascadeDetails, $limit, &$recordsLimit, &$handledCount) {
                $handledCount = $this->handleRelations($cascadeDetails, $limit);
            });
        } else {
            $handledCount = $this->handleRelations($cascadeDetails, $limit);
        }

        // Update records limit
        $this->recordsLimit -= $handledCount;

        // Detect if maybe not all relations are deleted
        if ($handledCount === $limit) {
            return false;
        }

        return $this->isFinished($cascadeDetails);
    }

    /**
     * Build the model query
     *
     * @param Builder $query
     * @return void
     */
    abstract protected function buildModelQuery(Builder $query): void;

    /**
     * Determine whether the job should proceed with the given cascade details
     *
     * @param CascadeDetails $cascadeDetails
     * @return bool
     */
    abstract protected function shouldProceed(CascadeDetails $cascadeDetails): bool;

    /**
     * Tell if the job is finished with the given cascade details
     *
     * @param CascadeDetails $cascadeDetails
     * @return bool
     */
    abstract protected function isFinished(CascadeDetails $cascadeDetails): bool;

    /**
     * This function will be triggered when all the relations of a model are settled
     *
     * @param ShouldCascade $model
     * @return void
     */
    abstract protected function onAllRelationsFinished(ShouldCascade $model): void;

    /**
     * Handle the relations
     *
     * @param CascadeDetails $cascadeDetails
     * @param int $limit
     * @return int
     */
    abstract protected function handleRelations(CascadeDetails $cascadeDetails, int $limit): int;
}