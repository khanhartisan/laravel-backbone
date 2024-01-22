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
        // Change cascade status to deleting when the model is being soft-deleted
        static::deleted(function (self $model) {
            if ($model->isForceDeleting()) {
                return;
            }

            $query = $model->setKeysForSaveQuery($model->newModelQuery());

            $columns = [
                $model->getCascadeStatusColumn() => CascadeStatus::DELETING,
                $model->getCascadeUpdatedAtColumn() => now(),
            ];

            $query->update($columns);

            $model->{$model->getCascadeStatusColumn()} = CascadeStatus::DELETING;
            $model->{$model->getCascadeUpdatedAtColumn()} = now();
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

    public static function autoForceDeleteWhenAllRelationsAreDeleted(): bool
    {
        return false;
    }

    public static function autoForceDeletePerItem(): bool
    {
        return true;
    }
}