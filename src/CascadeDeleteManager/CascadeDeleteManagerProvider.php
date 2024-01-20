<?php

namespace KhanhArtisan\LaravelBackbone\CascadeDeleteManager;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class CascadeDeleteManagerProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->singleton(CascadeDeleteManager::class, CascadeDeleteManager::class);

        Blueprint::macro('cascadeDeletes', function () {
            $this->boolean('relations_deleted')->default(false);
            $this->index(['relations_deleted', 'deleted_at']);
        });
    }
}