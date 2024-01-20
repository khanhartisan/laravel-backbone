<?php

namespace KhanhArtisan\LaravelBackbone\CascadeDeleteManager;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * This trait helps to implement CascadeDeletable interface.
 */
trait CascadeDeletes
{
    use SoftDeletes;

    /**
     * Initialize the model for cascade deletes.
     *
     * @return void
     */
    public function initializeCascadeDeletes(): void
    {
        if (!isset($this->casts[$this->getRelationsDeletedColumn()])) {
            $this->casts[$this->getRelationsDeletedColumn()] = 'boolean';
        }
    }

    public function getRelationsDeletedColumn(): string
    {
        return 'relations_deleted';
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