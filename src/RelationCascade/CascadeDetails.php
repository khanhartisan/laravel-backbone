<?php

namespace KhanhArtisan\LaravelBackbone\RelationCascade;

use Illuminate\Contracts\Database\Eloquent\Builder;

class CascadeDetails
{
    /**
     * @var Builder
     */
    protected Builder $relation;

    /**
     * Determine whether the relations should be deleted when the model is deleted.
     *
     * @var bool
     */
    protected bool $shouldDelete = true;

    /**
     * Determine whether the relations should be restored when the model is restored.
     *
     * @var bool
     */
    protected bool $shouldRestore = true;

    /**
     * Determine whether the relations should be force deleted when the model is deleted.
     *
     * @var bool
     */
    protected bool $shouldForceDelete = false;

    /**
     * Relations should be deleted per item or by batch.
     *
     * @var bool
     */
    protected bool $shouldDeletePerItem = true;

    /**
     * Whether the relations should be deleted in a transaction.
     *
     * @var bool
     */
    protected bool $shouldUseTransaction = true;

    /**
     * @param Builder $relation
     */
    public function __construct(Builder $relation)
    {
        $this->relation = clone $relation;
    }

    /**
     * Return a new original relation
     *
     * @return Builder
     */
    public function getRelation(): Builder
    {
        return clone $this->relation;
    }

    // Generate getters & setters

    /**
     * @return bool
     */
    public function shouldDelete(): bool
    {
        return $this->shouldDelete;
    }

    /**
     * @param bool $shouldDelete
     * @return $this
     */
    public function setShouldDelete(bool $shouldDelete): static
    {
        $this->shouldDelete = $shouldDelete;
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldRestore(): bool
    {
        return $this->shouldRestore;
    }

    /**
     * @param bool $shouldRestore
     * @return $this
     */
    public function setShouldRestore(bool $shouldRestore): static
    {
        $this->shouldRestore = $shouldRestore;
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldForceDelete(): bool
    {
        return $this->shouldForceDelete;
    }

    /**
     * @param bool $shouldForceDelete
     * @return $this
     */
    public function setShouldForceDelete(bool $shouldForceDelete): static
    {
        $this->shouldForceDelete = $shouldForceDelete;
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldDeletePerItem(): bool
    {
        return $this->shouldDeletePerItem;
    }

    /**
     * @param bool $shouldDeletePerItem
     * @return $this
     */
    public function setShouldDeletePerItem(bool $shouldDeletePerItem): static
    {
        $this->shouldDeletePerItem = $shouldDeletePerItem;
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldUseTransaction(): bool
    {
        return $this->shouldUseTransaction;
    }

    /**
     * @param bool $shouldUseTransaction
     * @return $this
     */
    public function setShouldUseTransaction(bool $shouldUseTransaction): static
    {
        $this->shouldUseTransaction = $shouldUseTransaction;
        return $this;
    }
}