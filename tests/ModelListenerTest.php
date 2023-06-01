<?php

namespace KhanhArtisan\LaravelBackbone\Tests;

use App\Models\Post;
use Illuminate\Support\Str;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListenerManager;
use KhanhArtisan\LaravelBackbone\ModelListener\Observer;
use KhanhArtisan\LaravelBackbone\Tests\ModelListeners\PostSingletonListener;

class ModelListenerTest extends TestCase
{
    public function test_listener_executed_successfully_by_priority()
    {
        $this->_register();

        $post = Post::factory()->create();

        // Listener executed successfully
        $this->assertTrue(!!$post->post_listener_priority_0_executed_at);
        $this->assertTrue(!!$post->post_listener_priority_1_executed_at);

        // priority 1 should be executed first, so we expect timestamp of p0 > p1
        $this->assertTrue($post->post_listener_priority_0_executed_at > $post->post_listener_priority_1_executed_at);
    }

    public function test_listener_singleton()
    {
        $this->_register();

        $post = Post::factory()->create();

        $this->assertEquals(1, $post->post_singleton_listener_count);
        $this->assertEquals(1, $post->post_non_singleton_listener_count);

        $post->title = Str::random();
        $post->save();

        $this->assertEquals(2, $post->post_singleton_listener_count);
        $this->assertEquals(1, $post->post_non_singleton_listener_count);
    }

    public function test_unregister_model_listeners()
    {
        $this->_register();

        $post = Post::factory()->create();

        $this->assertEquals(1, $post->post_singleton_listener_count);

        // Unregister
        /** @var ModelListenerManager $modelListenerManager */
        $modelListenerManager = app()->make(ModelListenerManager::class);
        $modelListenerManager->unregisterModelListeners([
            PostSingletonListener::class
        ]);

        $post->title = Str::random();
        $post->save();

        // Test unregistered successfully
        $this->assertEquals(1, $post->post_singleton_listener_count);

        // Register again
        $modelListenerManager->registerModelListeners([
            PostSingletonListener::class
        ]);

        $post->title = Str::random();
        $post->save();
        $post->title = Str::random();
        $post->save();

        // Confirm listener executed
        $this->assertEquals(2, $post->post_singleton_listener_count);
    }

    protected function _register(): void
    {
        /** @var ModelListenerManager $modelListenerManager */
        $modelListenerManager = app()->make(ModelListenerManager::class);
        $modelListenerManager->registerModelListenersFrom('KhanhArtisan\LaravelBackbone\Tests\ModelListeners', __DIR__.'/ModelListeners');
        Observer::registerModel(Post::class);
    }
}