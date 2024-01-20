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

class CascadeRestore extends Cascade implements ShouldQueue
{
    protected function buildModelQuery(Builder $query): void
    {
        // TODO: Implement buildModelQuery() method.
    }

    protected function handleCascadeDeletableModel(CascadeDeletable $model): void
    {
        // TODO: Implement handleCascadeDeletableModel() method.
    }
}
