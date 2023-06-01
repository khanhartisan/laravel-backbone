<?php

namespace KhanhArtisan\LaravelBackbone\Tests\ModelListeners;

use App\Models\Post;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListener;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListenerInterface;

class PostListenerPriority0 extends ModelListener implements ModelListenerInterface
{
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

    protected function _handle(Post $post, string $event): void
    {
        usleep(100);
        $post->post_listener_priority_0_executed_at = microtime(true);
    }
}