<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Models\Scopes\PostIdsScope;
use Illuminate\Database\Eloquent\Builder;
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

    protected function indexQueryScopes(Request $request): array
    {
        return [
            'post-ids' => new PostIdsScope(),
            'post-title' => function (Builder $query, Post $post) use ($request) {
                if (!$title = $request->query('title')) {
                    return;
                }

                $query->where('title', 'like', "%$title%");
            },
        ];
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
