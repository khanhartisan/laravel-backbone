<?php

namespace App\ModelListeners\Post;

use App\Models\Post;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListener;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListenerInterface;

class SamplePostListener extends ModelListener implements ModelListenerInterface
{
    public function priority(): int
    {
        return 0;
    }

    public function isSingleton(): bool
    {
        return true;
    }

    public function events(): array
    {
        return ['saving', 'deleting'];
    }

    public function modelClass(): string
    {
        return Post::class;
    }

    protected function _handle(Post $post, string $event)
    {
        //
    }
}