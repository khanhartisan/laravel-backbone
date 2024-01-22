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
        $restored = 0;
        $cascadeDetails
            ->getRelation()
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
        return $cascadeDetails->getRelation()->onlyTrashed()->doesntExist();
    }

    protected function onFinished(ShouldCascade $model): void
    {
        $model->setAttribute($model->getCascadeStatusColumn(), CascadeStatus::IDLE);
    }
}
