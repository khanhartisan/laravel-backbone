<?php

namespace KhanhArtisan\LaravelBackbone\RelationCascade;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class RelationCascadeProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->singleton(RelationCascadeManager::class, RelationCascadeManager::class);

        Blueprint::macro('cascadeDeletes', function () {
            $this->boolean('cascade_status')->default(false);
            $this->index(['cascade_status', 'deleted_at']);
        });
    }
}