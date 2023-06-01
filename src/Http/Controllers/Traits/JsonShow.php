<?php

namespace KhanhArtisan\LaravelBackbone\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

trait JsonShow
{
    use Show;

    /**
     * Show resource
     *
     * @param Request $request
     * @param Model $resource
     * @return JsonResource
     */
    public function jsonShow(Request $request, Model $resource): JsonResource
    {
        $repository = $this->repository();
        $repository->applyResourceVisitors($resource, $this->showResourceVisitors($request));

        $resourceClass = $this->resourceClass();

        /** @var JsonResource $jsonResource */
        $jsonResource = new $resourceClass($resource);

        return $jsonResource->additional($this->showAdditional($request, $resource));
    }
}