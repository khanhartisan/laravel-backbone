<?php

namespace KhanhArtisan\LaravelBackbone\Tests;

use Illuminate\Support\Str;
use KhanhArtisan\LaravelBackbone\Testing\JsonApiTest;
use KhanhArtisan\LaravelBackbone\Testing\JsonCrudTestData;

class JsonControllerTest extends TestCase
{
    use JsonApiTest;

    public function test_basic_crud()
    {
        $this->_testBasicCrud(
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