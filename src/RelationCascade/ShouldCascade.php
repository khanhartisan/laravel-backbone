<?php

namespace KhanhArtisan\LaravelBackbone\RelationCascade;

use Illuminate\Contracts\Database\Eloquent\Builder;
use KhanhArtisan\LaravelBackbone\ModelListener\ObservableModel;

/**
 * Models that implement this interface must support Laravel soft deleting
 * and have the column "cascade_status" with type tinyInteger.
 *
 * If the model is force deleted, the cascade deletion will be ignored.
 *
 * A composite index of ['cascade_status', 'deleted_at'] is recommended.
 */
interface ShouldCascade
{
    /**
     * Get relations that will be deleted when the model is deleted
     *
     * @return CascadeDetails|array<CascadeDetails>
     */
    public function getCascadeDeleteDetails(): CascadeDetails|array;

    /**
     * Get the column name of the "cascade_status" column.
     * This column is a flag (boolean) to indicate whether the model's relations are deleted or not.
     *
     * @return string
     */
    public function getCascadeStatusColumn(): string;

    /**
     * Get the column name of the "cascade_updated_at" column.
     * This column is a timestamp to indicate the last activity of cascading actions.
     *
     * @return string
     */
    public function getCascadeUpdatedAtColumn(): string;

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