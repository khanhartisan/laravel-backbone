<?php

namespace KhanhArtisan\LaravelBackbone\Eloquent;

use Illuminate\Database\Eloquent\Builder;

interface GetQueryExecutorInterface
{
    /**
     * Execute the query and return
     *
     * @param Builder $query
     * @return GetData
     */
    public function execute(Builder $query): GetData;
}