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
        // TODO: Register event listeners to handle cascade restore
    }

    /**
     * Initialize the model for cascade deletes.
     *
     * @return void
     */
    public function initializeCascades(): void
    {
        if (!isset($this->casts[$this->getCascadeStatusColumn()])) {
            $this->casts[$this->getCascadeStatusColumn()] = 'boolean';
        }
    }

    public function getCascadeStatusColumn(): string
    {
        return 'cascade_status';
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