<?php

namespace KhanhArtisan\LaravelBackbone\Tests;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use KhanhArtisan\LaravelBackbone\Testing\JsonApiTest;
use KhanhArtisan\LaravelBackbone\Testing\JsonCrudTestData;

class JsonControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_crud()
    {
        $jsonApiTest = new JsonApiTest($this);
        $jsonApiTest->testBasicCrud(
            'posts',
            (new JsonCrudTestData())
                ->setStoreData([
                    'partition_key' => Str::random(),
                    'title' => Str::random()
                ])->setUpdateData([
                    'title' => Str::random()
                ])
        );
    }

    public function test_post_ids_scope()
    {
        $posts = Post::factory(10)->create();

        // Pick random 5 posts
        $randomPosts = $posts->random(5);

        $jsonApiTest = new JsonApiTest($this);
        $jsonApiTest->testIndex('/posts?ids='.$randomPosts->pluck('id')->join(','))
            ->assertJsonCount(5, 'data')
            ->assertJson([
                'data' => $randomPosts->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'partition_key' => $post->partition_key,
                        'title' => $post->title,
                    ];
                })->all()
            ]);
    }

    public function test_title_search_scope()
    {
        $posts = Post::factory(10)->create();

        // Pick random 1 post
        $randomPost = $posts->random();

        $jsonApiTest = new JsonApiTest($this);
        $jsonApiTest->testIndex('/posts?title='.$randomPost->title)
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $randomPost->id,
                    ]
                ]
            ]);
    }
}