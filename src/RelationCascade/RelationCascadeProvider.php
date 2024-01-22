<?php

namespace KhanhArtisan\LaravelBackbone\RelationCascade;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class RelationCascadeProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->singleton(RelationCascadeManager::class, RelationCascadeManager::class);

        Blueprint::macro('cascades', function () {
            $this->tinyInteger('cascade_status')->unsigned()->default(CascadeStatus::IDLE->value);
            $this->datetime('cascade_updated_at')->nullable();
            $this->index(['cascade_status', 'cascade_updated_at']);
        });

        // Auto register /app/Models by default
        $this->app->make(RelationCascadeManager::class)->registerModelsFrom(
            $this->app->getNamespace() . 'Models',
            app_path('Models')
        );
    }
}