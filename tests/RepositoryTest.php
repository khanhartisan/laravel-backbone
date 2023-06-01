<?php

namespace KhanhArtisan\LaravelBackbone\Tests;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use KhanhArtisan\LaravelBackbone\Eloquent\Repository;
use KhanhArtisan\LaravelBackbone\Eloquent\RepositoryInterface;

class RepositoryTest extends TestCase
{
    public function test_basic()
    {
        $repository = $this->repository();

        // Test create
        $post = $repository->create([
            'title' => Str::random()
        ]);
        $this->assertTrue($post->id > 0);

        // Test update
        $partitionKey = Str::random();
        $newTitle = Str::random();
        $this->assertTrue($repository->save($post, [
            'partition_key' => $partitionKey,
            'title' => $newTitle
        ]));

        // Test find one
        $this->assertNotNull($post = $repository->find($post->id));
        $this->assertEquals($newTitle, $post->title);
        $this->assertEquals($partitionKey, $post->partition_key);

        // Test get
        $getData = $repository->get(Post::query()->where('partition_key', $partitionKey));
        $this->assertEquals(1, $getData->getCollection()->count());
        $this->assertEquals($post->id, $getData->getCollection()->first()->id);

        // Test delete
        $this->assertTrue($repository->delete($post));
        $this->assertNull($repository->find($post->id));
    }

    public function test_mass_delete()
    {
        $partitionKey = Str::random();
        Post::factory()->count(10)->create([
            'partition_key' => $partitionKey
        ]);

        $this->assertEquals(10, Post::query()->where('partition_key', $partitionKey)->count());

        // Test mass delete
        $repository = $this->repository();
        $this->assertTrue($repository->massDelete(Post::query()->where('partition_key', $partitionKey)));

        $this->assertEquals(0, Post::query()->where('partition_key', $partitionKey)->count());
    }

    public function test_find_with_query_scope()
    {
        $partitionKey = Str::random();
        $title = Str::random();
        $post = Post::factory()->create([
            'partition_key' => $partitionKey,
            'title' => $title
        ]);

        $repository = $this->repository();

        $this->assertNotNull($repository->find($post->id));
        $this->assertNull($repository->find($post->id, [
            function (Builder $query, Model $postModel) {
                $query->where($postModel->getTable().'.title', 'bullshit');
            }
        ]));
    }

    public function test_find_with_resource_visitor()
    {
        $post = Post::factory()->create();
        $repository = $this->repository();

        $find1 = $repository->find($post->id);
        $this->assertEquals($post->title, $find1->title);

        $find2 = $repository->find($post->id, [], [
            function (Post $post) {
                $post->title = 'XXX';
            }
        ]);
        $this->assertEquals('XXX', $find2->title);
    }

    public function test_get_with_query_scope()
    {
        $partitionKey = Str::random();
        for ($i = 1; $i <= 10; $i++) {
            Post::factory()->create([
                'partition_key' => $partitionKey,
                'title' => $i
            ]);
        }

        $repository = $this->repository();

        $data = $repository->get(Post::query()->where('partition_key', $partitionKey));
        $this->assertEquals(
            10,
            $data->total()
        );

        $data2 = $repository->get(
            Post::query()->where('partition_key', $partitionKey),
            null,
            [
                fn (Builder $query, Post $postModel) => $query->whereIn($postModel->getTable().'.title', [1, 3, 5, 7, 9])
            ]
        );
        $this->assertEquals(5, $data2->total());
        foreach ($data2->getCollection() as $post) {
            $this->assertContains($post->title, ['1', '3', '5', '7', '9']);
        }
    }

    public function test_get_with_collection_visitor()
    {
        $partitionKey = Str::random();
        Post::factory()->count(10)->create([
            'partition_key' => $partitionKey
        ]);

        $repository = $this->repository();
        $data = $repository->get(Post::query()->where('partition_key', $partitionKey), null, [], [
            fn (Collection $posts) => $posts->each(fn (Post $post) => $post->title = 'modified')
        ]);

        $this->assertEquals(10, $data->total());
        foreach ($data->getCollection() as $post) {
            $this->assertEquals('modified', $post->title);
        }
    }

    public function test_create_with_resource_visitor()
    {
        $repository = $this->repository();

        $post = $repository->create([
            'partition_key' => Str::random(),
            'title' => 12345
        ], [
            fn (Post $post) => $post->title = 23456
        ], [
            fn (Post $post) => $post->title = 'test'
        ]);
        $this->assertEquals('test', $post->title);

        $realPost = $repository->find($post->id);
        $this->assertEquals($post->id, $realPost->id);
        $this->assertEquals(23456, $realPost->title);
    }

    public function test_update_with_resource_visitor()
    {
        $repository = $this->repository();

        $post = Post::factory()->create();

        $this->assertTrue($repository->save($post, [
            'title' => 'updated'
        ], [
            fn (Post $post) => $post->title = 'updated by visitor'
        ], [
            fn (Post $post) => $post->title = 'modified'
        ]));

        $this->assertEquals('modified', $post->title);

        $realPost = $repository->find($post->id);
        $this->assertEquals($post->id, $realPost->id);
        $this->assertEquals('updated by visitor', $realPost->title);
    }

    public function test_delete_with_resource_visitor()
    {
        $repository = $this->repository();

        $post = Post::factory()->create();

        $this->assertTrue($repository->delete($post, [
            fn (Post $post) => $post->title = 'modified'
        ]));

        $this->assertEquals('modified', $post->title);
    }

    public function test_mass_delete_with_query_scope()
    {
        $partitionKey = Str::random();
        $repository = $this->repository();
        
        for ($i = 1; $i <= 10; $i++) {
            Post::factory()->create([
                'partition_key' => $partitionKey,
                'title' => $i
            ]);
        }
        
        $this->assertTrue($repository->massDelete(Post::query()->where('partition_key', $partitionKey), [
            fn (Builder $query, Post $model) => $query->whereIn($model->getTable().'.title', ['1', '3', '5', '7', '9'])
        ]));

        $this->assertEquals(5, Post::query()->where('partition_key', $partitionKey)->count());

        $posts = $repository->get(Post::query()->where('partition_key', $partitionKey));

        foreach ($posts as $post) {
            $this->assertContains($post->title, ['2', '4', '6', '8', '10']);
        }
    }

    protected function repository(): RepositoryInterface
    {
        return new Repository(Post::class);
    }
}