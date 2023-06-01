<?php

namespace KhanhArtisan\LaravelBackbone\Tests;

use Illuminate\Support\Str;

class JsonControllerTest extends TestCase
{
    public function test_crud()
    {
        $partitionKey = Str::random();
        $title = Str::random();

        // Test create post
        $storeResponse = $this->postJson('posts', [
            'partition_key' => $partitionKey,
            'title' => $title
        ]);
        $storeResponse->assertStatus(201);
        $storeResponse->assertJson([
            'data' => [
                'partition_key' => $partitionKey,
                'title' => $title
            ]
        ]);
        $storeData = json_decode($storeResponse->getContent())->data;

        // Test get index
        $indexResponse = $this->getJson('posts');
        $indexResponse->assertStatus(200);
        $indexResponse->assertJsonCount(1, 'data');
        $indexResponse->assertJson([
            'data' => [
                [
                    'partition_key' => $partitionKey,
                    'title' => $title
                ]
            ]
        ]);

        // Test show single post
        $showResponse = $this->getJson('posts/'.$storeData->id);
        $showResponse->assertStatus(200);
        $showResponse->assertJson([
            'data' => [
                'partition_key' => $partitionKey,
                'title' => $title
            ]
        ]);

        // Test update post
        $newTitle = Str::random();
        $updateResponse = $this->patchJson('posts/'.$storeData->id, [
            'title' => $newTitle
        ]);
        $updateResponse->assertStatus(200);
        $updateResponse->assertJson([
            'data' => [
                'title' => $newTitle
            ]
        ]);

        // Test delete post
        $destroyResponse = $this->deleteJson('posts/'.$storeData->id);
        $destroyResponse->assertStatus(200);
        $destroyResponse->assertJson([
            'data' => [
                'partition_key' => $partitionKey,
                'title' => $newTitle
            ]
        ]);

        // Confirm deleted
        $this->getJson('posts/'.$storeData->id)->assertStatus(404);
    }
}