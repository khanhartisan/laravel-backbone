<?php

namespace KhanhArtisan\LaravelBackbone\Eloquent;

use Illuminate\Database\Eloquent\Model;

interface ResourceVisitorInterface
{
    /**
     * Handle a model resource
     *
     * @param Model $resource
     * @return void
     */
    public function apply(Model $resource): void;
}