<?php

namespace KhanhArtisan\LaravelBackbone\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

trait JsonUpdate
{
    use Update;

    /**
     * Update resource
     *
     * @param FormRequest $request
     * @param Model $resource
     * @param null|array $data
     * @return JsonResource
     */
    public function jsonUpdate(FormRequest $request, Model $resource, ?array $data = null): JsonResource
    {
        $data = $data ?? $request->validated();
        $repository = $this->repository();
        $savingVisitors = $this->updateResourceSavingVisitors($request);
        $savedVisitors = $this->updateResourceSavedVisitors($request);

        $result = $this->updateWithTransaction($request)
            ? DB::transaction(fn () => $repository->save($resource, $data, $savingVisitors, $savedVisitors))
            : $repository->save($resource, $data, $savingVisitors, $savedVisitors);

        if (!$result) {
            $this->throwJsonHttpException(500, 'Failed to update resource.');
        }

        $resourceClass = $this->resourceClass();

        /** @var JsonResource $jsonResource */
        $jsonResource = new $resourceClass($resource);

        return $jsonResource->additional($this->updateAdditional($request, $resource));
    }
}