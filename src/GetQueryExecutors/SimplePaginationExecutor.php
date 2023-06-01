<?php

namespace KhanhArtisan\LaravelBackbone\GetQueryExecutors;

use Illuminate\Database\Eloquent\Builder;
use KhanhArtisan\LaravelBackbone\Eloquent\GetData;
use KhanhArtisan\LaravelBackbone\Eloquent\GetQueryExecutorInterface;

class SimplePaginationExecutor implements GetQueryExecutorInterface
{
    /**
     * @inheritDoc
     */
    public function execute(Builder $query): GetData
    {
        // Todo: Add laravel pagination data
        return new GetData($query->get());
    }
}