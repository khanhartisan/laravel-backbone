<?php

namespace KhanhArtisan\LaravelBackbone\GetQueryExecutors;

use Illuminate\Database\Eloquent\Builder;
use KhanhArtisan\LaravelBackbone\Eloquent\GetData;
use KhanhArtisan\LaravelBackbone\Eloquent\GetQueryExecutorInterface;
use Illuminate\Database\Eloquent\Collection;

class SimplePaginationExecutor implements GetQueryExecutorInterface
{
    /**
     * @inheritDoc
     */
    public function execute(Builder $query): GetData
    {
        $pagination = $query->paginate();
        return new GetData(Collection::make($pagination->items()), $pagination->total());
    }
}
