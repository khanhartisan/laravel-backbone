<?php

namespace KhanhArtisan\LaravelBackbone\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use KhanhArtisan\LaravelBackbone\Http\Controllers\Traits\JsonStore;
use KhanhArtisan\LaravelBackbone\Http\Controllers\Traits\JsonDestroy;
use KhanhArtisan\LaravelBackbone\Http\Controllers\Traits\JsonIndex;
use KhanhArtisan\LaravelBackbone\Http\Controllers\Traits\JsonShow;
use KhanhArtisan\LaravelBackbone\Http\Controllers\Traits\JsonUpdate;

abstract class JsonController extends Controller
{
    use AuthorizesRequests;
    use JsonIndex;
    use JsonShow;
    use JsonStore;
    use JsonUpdate;
    use JsonDestroy;

    /**
     * @return string
     */
    protected function resourceClass(): string
    {
        return JsonResource::class;
    }

    /**
     * @return null|string
     */
    protected function resourceCollectionClass(): ?string
    {
        return null;
    }

    /**
     * Return json error response
     *
     * @param int $code
     * @param string|null $message
     * @return void
     */
    protected function throwJsonHttpException(int $code, ?string $message = null): void
    {
        abort($code, $message);
    }
}