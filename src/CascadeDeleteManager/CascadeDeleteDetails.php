<?php

namespace KhanhArtisan\LaravelBackbone\CascadeDeleteManager;

use Illuminate\Contracts\Database\Eloquent\Builder;

class CascadeDeleteDetails
{
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
    public function __construct(protected Builder $relation)
    {

    }

    /**
     * @return Builder
     */
    public function getRelation(): Builder
    {
        return $this->relation;
    }

    // Generate getters & setters

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