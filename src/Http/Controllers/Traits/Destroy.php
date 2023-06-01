<?php

namespace KhanhArtisan\LaravelBackbone\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use KhanhArtisan\LaravelBackbone\Eloquent\ResourceVisitorInterface;

trait Destroy
{
    /**
     *  Determine if it should delete within a transaction
     *
     * @param Request $request
     * @return bool
     */
    protected function destroyWithTransaction(Request $request): bool
    {
        return true;
    }

    /**
     * @param Request $request
     * @param Model $resource
     * @return array
     */
    protected function destroyAdditional(Request $request, Model $resource): array
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
    protected function destroyResourceDeletingVisitors(Request $request): array
    {
        return [];
    }

    /**
     * @param Request $request
     * @return array<ResourceVisitorInterface>
     */
    protected function destroyResourceDeletedVisitors(Request $request): array
    {
        // Extending show Visitors by default
        return method_exists($this, 'showResourceVisitors')
            ? $this->showResourceVisitors($request)
            : [];
    }
}