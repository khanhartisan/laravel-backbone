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

class CascadeDelete extends Cascade implements ShouldQueue
{
    /**
     * Build the model query
     *
     * @param Builder $query
     * @return void
     */
    protected function buildModelQuery(Builder $query): void
    {
        /** @var ShouldCascade $instance */
        $instance = $query->getModel();

        $query->where($instance->qualifyColumn($instance->getCascadeStatusColumn()), CascadeStatus::DELETING)
                ->orderBy($instance->getCascadeUpdatedAtColumn())
                ->limit($this->chunk)
                ->withTrashed();
    }

    /**
     * @inheritDoc
     */
    protected function isFinished(CascadeDetails $cascadeDetails): bool
    {
        // Detect if at least 1 relation resource is not deleted
        $relationModel = $cascadeDetails->getRelation()->getModel();
        if ($relationModel instanceof ShouldCascade) {

            if ($cascadeDetails
                ->getRelation()
                ->whereIn(
                    $relationModel->qualifyColumn($relationModel->getCascadeStatusColumn()),
                    collect(CascadeStatus::cases())
                        ->filter(fn (CascadeStatus $status) => $status !== CascadeStatus::DELETED)
                        ->all()
                )
                ->withTrashed()
                ->exists()
            ) {
                return false;
            }

            return true;
        }

        return $cascadeDetails->getRelation()->doesntExist();
    }

    /**
     * @inheritDoc
     */
    protected function onAllRelationsFinished(ShouldCascade $model): void
    {
        // If auto force delete is enabled -> force delete and finish
        if ($model->autoForceDeleteWhenAllRelationsAreDeleted()) {
            $model->forceDelete();
            return;
        }

        // Update the cascade_status
        $model->setAttribute($model->getCascadeStatusColumn(), CascadeStatus::DELETED);
    }

    /**
     * Delete the relations
     *
     * @param CascadeDetails $cascadeDetails
     * @param int $limit
     * @return int
     */
    protected function handleRelations(CascadeDetails $cascadeDetails, int $limit): int
    {
        // Batch delete
        if (!$cascadeDetails->shouldDeletePerItem()) {
            return $cascadeDetails->getRelation()->take($limit)->delete();
        }

        // Per item delete
        $deleteAction = $cascadeDetails->shouldForceDelete() ? 'forceDelete' : 'delete';
        $deleted = 0;
        $cascadeDetails
            ->getRelation()
            ->take($limit)
            ->get()
            ->each(function (Model $model) use (&$deleted, $deleteAction) {
                if ($model->{$deleteAction}()) {
                    $deleted++;
                }
            });

        return $deleted;
    }

    /**
     * @inheritDoc
     */
    protected function shouldProceed(CascadeDetails $cascadeDetails): bool
    {
        return $cascadeDetails->shouldDelete();
    }
}