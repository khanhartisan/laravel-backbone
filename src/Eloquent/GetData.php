<?php

namespace KhanhArtisan\LaravelBackbone\Eloquent;

use Illuminate\Database\Eloquent\Collection;

class GetData
{
    /**
     * @param Collection $collection
     * @param int|null $total
     * @param array $additional
     */
    public function __construct(protected Collection $collection,
                                protected null|int $total = null,
                                protected array $additional = []
                            )
    {

    }

    /**
     * Get resource collection
     *
     * @return Collection
     */
    public function getCollection(): Collection
    {
        return $this->collection;
    }

    /**
     * Total resources count
     *
     * @return int|null
     */
    public function total(): ?int
    {
        return $this->total;
    }

    /**
     * Additional data
     *
     * @return array
     */
    public function additional(): array
    {
        return $this->additional;
    }
}