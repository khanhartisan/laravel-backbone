<?php

namespace KhanhArtisan\LaravelBackbone\Tests;

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
}