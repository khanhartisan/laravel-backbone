<?php

namespace KhanhArtisan\LaravelBackbone\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use KhanhArtisan\LaravelBackbone\Eloquent\ResourceVisitorInterface;

trait Update
{
    /**
     * Determine if it should update within a transaction
     *
     * @param Request $request
     * @return bool
     */
    protected function updateWithTransaction(Request $request): bool
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
    protected function updateAdditional(Request $request, Model $resource): array
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
    protected function updateResourceSavingVisitors(Request $request): array
    {
        return [];
    }

    /**
     * @param Request $request
     * @return array<ResourceVisitorInterface>
     */
    protected function updateResourceSavedVisitors(Request $request): array
    {
        // Extending show Visitors by default
        return method_exists($this, 'showResourceVisitors')
            ? $this->showResourceVisitors($request)
            : [];
    }
}