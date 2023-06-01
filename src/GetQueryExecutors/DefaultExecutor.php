<?php

namespace KhanhArtisan\LaravelBackbone\GetQueryExecutors;

use Illuminate\Database\Eloquent\Builder;
use KhanhArtisan\LaravelBackbone\Eloquent\GetData;
use KhanhArtisan\LaravelBackbone\Eloquent\GetQueryExecutorInterface;

class DefaultExecutor implements GetQueryExecutorInterface
{
    /**
     * @inheritDoc
     */
    public function execute(Builder $query): GetData
    {
        return new GetData($query->get(), $query->count());
    }
}