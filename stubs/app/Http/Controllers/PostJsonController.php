<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Http\Request;
use KhanhArtisan\LaravelBackbone\Http\Controllers\JsonController;

class PostJsonController extends JsonController
{
    protected function modelClass(): string
    {
        return Post::class;
    }

    public function show(Request $request, Post $post)
    {
        return $this->jsonShow($request, $post);
    }

    public function index(Request $request)
    {
        return $this->jsonIndex($request);
    }

    public function store(StorePostRequest $request)
    {
        return $this->jsonStore($request);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        return $this->jsonUpdate($request, $post);
    }

    public function destroy(Request $request, Post $post)
    {
        return $this->jsonDestroy($request, $post);
    }
}
