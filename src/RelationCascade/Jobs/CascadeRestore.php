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
use KhanhArtisan\LaravelBackbone\RelationCascade\ShouldCascade;
use KhanhArtisan\LaravelBackbone\RelationCascade\CascadeDetails;
use KhanhArtisan\LaravelBackbone\RelationCascade\RelationCascadeManager;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListenerManager;

class CascadeRestore extends Cascade implements ShouldQueue
{
    protected function buildModelQuery(Builder $query): void
    {
        // TODO: Implement buildModelQuery() method.
    }

    protected function handleCascadeDeletableModel(ShouldCascade $model): void
    {
        // TODO: Implement handleCascadeDeletableModel() method.
    }
}
