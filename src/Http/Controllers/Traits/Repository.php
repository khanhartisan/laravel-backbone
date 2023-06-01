<?php

namespace KhanhArtisan\LaravelBackbone\Http\Controllers\Traits;

use KhanhArtisan\LaravelBackbone\Eloquent\RepositoryInterface;

trait Repository
{
    /**
     * @var RepositoryInterface
     */
    protected RepositoryInterface $repository;

    /**
     * @return RepositoryInterface
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repository ?? $this->repository = new \KhanhArtisan\LaravelBackbone\Eloquent\Repository($this->modelClass());
    }
}