<?php

namespace KhanhArtisan\LaravelBackbone\RelationCascade;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * This trait helps to implement ShouldCascade interface.
 */
trait Cascades
{
    use SoftDeletes;

    /**
     * Boot the cascade delete trait for a model.
     *
     * @return void
     */
    public static function bootCascades(): void
    {
        $updateModel = function (self $model, CascadeStatus $status) {
            $query = $model->setKeysForSaveQuery($model->newModelQuery());

            $columns = [
                $model->getCascadeStatusColumn() => $status,
                $model->getCascadeUpdatedAtColumn() => now(),
            ];

            $query->update($columns);

            $model->{$model->getCascadeStatusColumn()} = $status;
            $model->{$model->getCascadeUpdatedAtColumn()} = now();
        };

        // Change cascade status to deleting when the model is soft-deleted
        static::deleted(function (self $model) use ($updateModel) {
            if ($model->isForceDeleting()) {
                return;
            }

            $updateModel($model, CascadeStatus::DELETING);
        });

        // Change cascade status to restoring when the model is restored
        static::restored(function (self $model) use ($updateModel) {
            $updateModel($model, CascadeStatus::RESTORING);
        });
    }

    /**
     * Initialize the model for cascade deletes.
     *
     * @return void
     */
    public function initializeCascades(): void
    {
        if (!isset($this->casts[$this->getCascadeStatusColumn()])) {
            $this->casts[$this->getCascadeStatusColumn()] = CascadeStatus::class;
        }

        if (!isset($this->casts[$this->getCascadeUpdatedAtColumn()])) {
            $this->casts[$this->getCascadeUpdatedAtColumn()] = 'datetime';
        }
    }

    public function getCascadeStatusColumn(): string
    {
        return 'cascade_status';
    }

    public function getCascadeUpdatedAtColumn(): string
    {
        return 'cascade_updated_at';
    }

    public function autoForceDeleteWhenAllRelationsAreDeleted(): bool
    {
        return false;
    }
}