<?php

namespace KhanhArtisan\LaravelBackbone\Http\Controllers\Traits;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

trait JsonStore
{
    use Store;

    /**
     * Store resource
     *
     * @param FormRequest $request
     * @param array|null $data
     * @return JsonResource
     */
    public function jsonStore(FormRequest $request, ?array $data = null): JsonResource
    {
        $data = $data ?? $request->validated();
        $repository = $this->repository();
        $savingVisitors = $this->storeResourceSavingVisitors($request);
        $savedVisitors = $this->storeResourceSavedVisitors($request);

        $resource = $this->storeWithTransaction($request)
            ? DB::transaction(fn() => $repository->create($data, $savingVisitors, $savedVisitors))
            : $repository->create($data, $savingVisitors, $savedVisitors);

        $resourceClass = $this->resourceClass();

        /** @var JsonResource $jsonResource */
        $jsonResource = new $resourceClass($resource);

        return $jsonResource->additional($this->storeAdditional($request, $resource));
    }
}