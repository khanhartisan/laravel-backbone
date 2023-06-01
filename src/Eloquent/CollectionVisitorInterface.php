<?php

namespace KhanhArtisan\LaravelBackbone\Eloquent;

use Illuminate\Database\Eloquent\Collection;

interface CollectionVisitorInterface
{
    /**
     * Handle an eloquent collection
     *
     * @param Collection $collection
     * @return void
     */
    public function apply(Collection $collection): void;
}