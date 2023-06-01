<?php

namespace KhanhArtisan\LaravelBackbone\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

trait JsonDestroy
{
    use Destroy;

    /**
     * Destroy resource
     *
     * @param Request $request
     * @param Model $resource
     * @return JsonResource
     */
    public function jsonDestroy(Request $request, Model $resource): JsonResource
    {
        $repository = $this->repository();
        $deletingVisitors = $this->destroyResourceDeletingVisitors($request);
        $deletedVisitors = $this->destroyResourceDeletedVisitors($request);

        $result = $this->destroyWithTransaction($request)
            ? DB::transaction(fn () => $repository->delete($resource, $deletingVisitors, $deletedVisitors))
            : $repository->delete($resource, $deletingVisitors, $deletedVisitors);

        if (!$result) {
            $this->throwJsonHttpException(500, 'Failed to delete resource.');
        }

        $resourceClass = $this->resourceClass();

        /** @var JsonResource $jsonResource */
        $jsonResource = new $resourceClass($resource);

        return $jsonResource->additional($this->destroyAdditional($request, $resource));
    }
}