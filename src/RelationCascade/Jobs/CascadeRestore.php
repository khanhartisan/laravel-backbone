<?php

namespace KhanhArtisan\LaravelBackbone\RelationCascade\Jobs;

use App\Models\Car;
use App\Models\Manufacturer;
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

class CascadeRestore extends Cascade implements ShouldQueue
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

        $query->where($instance->qualifyColumn($instance->getCascadeStatusColumn()), CascadeStatus::RESTORING)
                ->orderBy($instance->getCascadeUpdatedAtColumn())
                ->limit($this->chunk)
                ->withTrashed();
    }

    protected function handleRelations(CascadeDetails $cascadeDetails, int $limit): int
    {
        // Skip if the relation model doesn't support soft deleting
        $relationModel = $cascadeDetails->getRelation()->getModel();
        if (!method_exists($relationModel, 'getDeletedAtColumn')) {
            return 0;
        }

        $restored = 0;
        $cascadeDetails
            ->getRelation()
            ->onlyTrashed()
            ->take($limit)
            ->get()
            ->each(function (Model $model) use (&$restored) {
                if ($model->restore()) {
                    $restored++;
                }
            });

        return $restored;
    }

    protected function shouldProceed(CascadeDetails $cascadeDetails): bool
    {
        return $cascadeDetails->shouldRestore();
    }

    protected function isFinished(CascadeDetails $cascadeDetails): bool
    {
        $relationModel = $cascadeDetails->getRelation()->getModel();
        if ($relationModel instanceof ShouldCascade) {

            if ($cascadeDetails
                ->getRelation()
                ->whereIn(
                    $relationModel->qualifyColumn($relationModel->getCascadeStatusColumn()),
                    collect(CascadeStatus::cases())
                        ->filter(fn (CascadeStatus $status) => $status !== CascadeStatus::IDLE)
                        ->all()
                )
                ->withTrashed()
                ->exists()
            ) {
                return false;
            }

            return true;
        }

        // If relation model uses soft delete -> check if all relations are deleted
        if (method_exists($relationModel, 'getDeletedAtColumn')) {
            return $cascadeDetails->getRelation()->onlyTrashed()->doesntExist();
        }

        return true;
    }

    protected function onAllRelationsFinished(ShouldCascade $model): void
    {
        $model->setAttribute($model->getCascadeStatusColumn(), CascadeStatus::IDLE);
    }
}
