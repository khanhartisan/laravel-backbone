<?php

namespace KhanhArtisan\LaravelBackbone\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Http\Request;
use KhanhArtisan\LaravelBackbone\Eloquent\CollectionVisitorInterface;
use KhanhArtisan\LaravelBackbone\Eloquent\GetData;
use KhanhArtisan\LaravelBackbone\Eloquent\GetQueryExecutorInterface;
use KhanhArtisan\LaravelBackbone\GetQueryExecutors\SimplePaginationExecutor;

trait Index
{
    /**
     * Additional data
     *
     * @param Request $request
     * @param GetData $getData
     * @return array
     */
    protected function indexAdditional(Request $request, GetData $getData): array
    {
        return $getData->additional();
    }

    /**
     * @param Request $request
     * @return GetQueryExecutorInterface
     */
    protected function indexGetQueryExecutor(Request $request): GetQueryExecutorInterface
    {
        return new SimplePaginationExecutor();
    }

    /**
     * @param Request $request
     * @return array<Scope>
     */
    protected function indexQueryScopes(Request $request): array
    {
        return [];
    }

    /**
     * @param Request $request
     * @return array<CollectionVisitorInterface>
     */
    protected function indexCollectionVisitors(Request $request): array
    {
        return [];
    }

    /**
     * Get a collection of resource for the given http request
     *
     * @param Request $request
     * @return GetData
     */
    protected function indexGetData(Request $request): GetData
    {
        $repository = $this->repository();

        // Build query
        $query = $repository->query();

        return $repository->get(
            $query,
            $this->indexGetQueryExecutor($request),
            $this->indexQueryScopes($request),
            $this->indexCollectionVisitors($request)
        );
    }
}