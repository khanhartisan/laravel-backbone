<?php

namespace KhanhArtisan\LaravelBackbone\Tests\ModelListeners;

use App\Models\Post;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListener;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListenerInterface;

class PostNonSingletonListener extends ModelListener implements ModelListenerInterface
{
    protected int $count = 0;

    public function priority(): int
    {
        return 0;
    }

    public function modelClass(): string
    {
        return Post::class;
    }

    public function events(): array
    {
        return ['saved'];
    }

    public function isSingleton(): bool
    {
        return false;
    }

    protected function _handle(Post $post, string $event): void
    {
        $this->count++;
        $post->post_non_singleton_listener_count = $this->count;
    }
}