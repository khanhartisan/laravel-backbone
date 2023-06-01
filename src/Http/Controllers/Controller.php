<?php

namespace KhanhArtisan\LaravelBackbone\Http\Controllers;

use KhanhArtisan\LaravelBackbone\Http\Controllers\Traits\Repository;

abstract class Controller extends \Illuminate\Routing\Controller
{
    use Repository;

    /**
     * Return the eloquent model class
     *
     * @return string
     */
    abstract protected function modelClass(): string;
}