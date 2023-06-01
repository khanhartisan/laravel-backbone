<?php

namespace KhanhArtisan\LaravelBackbone\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait JsonIndex
{
    use Index;

    /**
     * JsonIndex api, get collection of resources
     *
     * @param Request $request
     * @return ResourceCollection
     */
    public function jsonIndex(Request $request): ResourceCollection
    {
        $getData = $this->indexGetData($request);

        $resourceClass = $this->resourceClass();
        $resourceCollectionClass = $this->resourceCollectionClass();

        /** @var ResourceCollection $resourceData */
        $resourceData = $resourceCollectionClass
            ? new $resourceCollectionClass($getData->getCollection())
            : call_user_func([$resourceClass, 'collection'], $getData->getCollection());

        // Additional data
        $resourceData->additional($this->indexAdditional($request, $getData));

        return $resourceData;
    }
}