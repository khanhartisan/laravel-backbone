<?php

namespace KhanhArtisan\LaravelBackbone\CascadeDeleteManager;

use Illuminate\Contracts\Database\Eloquent\Builder;
use KhanhArtisan\LaravelBackbone\ModelListener\ObservableModel;

/**
 * Models that implement this interface must support Laravel soft deleting
 * and have the column "relations_deleted" with type tinyInteger.
 *
 * If the model is force deleted, the cascade deletion will be ignored.
 *
 * A composite index of ['relations_deleted', 'deleted_at'] is recommended.
 */
interface CascadeDeletable
{
    /**
     * Get relations that will be deleted when the model is deleted
     *
     * @return CascadeDeleteDetails|array<CascadeDeleteDetails>
     */
    public function getCascadeDeleteDetails(): CascadeDeleteDetails|array;

    /**
     * Get the column name of the "relations_deleted" column.
     * This column is a flag (boolean) to indicate whether the model's relations are deleted or not.
     *
     * @return string
     */
    public function getRelationsDeletedColumn(): string;

    /**
     * Determine if the model resource should be force deleted automatically when all relations are deleted.
     *
     * @return bool
     */
    public static function autoForceDeleteWhenAllRelationsAreDeleted(): bool;

    /**
     * Determine if the model resource should be force deleted per item or by batch.
     *
     * @return bool true: force delete per item, false: force delete by batch
     */
    public static function autoForceDeletePerItem(): bool;
}