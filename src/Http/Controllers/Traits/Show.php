<?php

namespace KhanhArtisan\LaravelBackbone\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use KhanhArtisan\LaravelBackbone\Eloquent\ResourceVisitorInterface;

trait Show
{
    /**
     * Additional data will be added to response
     *
     * @param Request $request
     * @param Model $resource
     * @return array
     */
    protected function showAdditional(Request $request, Model $resource): array
    {
        return [];
    }

    /**
     * @param Request $request
     * @return array<ResourceVisitorInterface>
     */
    protected function showResourceVisitors(Request $request): array
    {
        return [];
    }
}