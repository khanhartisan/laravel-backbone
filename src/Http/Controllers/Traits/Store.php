<?php

namespace KhanhArtisan\LaravelBackbone\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use KhanhArtisan\LaravelBackbone\Eloquent\ResourceVisitorInterface;

trait Store
{
    /**
     * Determine if it should create within a transaction
     *
     * @param Request $request
     * @return bool
     */
    protected function storeWithTransaction(Request $request): bool
    {
        return true;
    }

    /**
     * Additional data will be added to response
     *
     * @param Request $request
     * @param Model $resource
     * @return array
     */
    protected function storeAdditional(Request $request, Model $resource): array
    {
        // Extending show additional by default
        return method_exists($this, 'showAdditional')
            ? $this->showAdditional($request, $resource)
            : [];
    }

    /**
     * @param Request $request
     * @return array<ResourceVisitorInterface>
     */
    protected function storeResourceSavingVisitors(Request $request): array
    {
        return [];
    }

    /**
     * @param Request $request
     * @return array<ResourceVisitorInterface>
     */
    protected function storeResourceSavedVisitors(Request $request): array
    {
        // Extending show Visitors by default
        return method_exists($this, 'showResourceVisitors')
            ? $this->showResourceVisitors($request)
            : [];
    }
}