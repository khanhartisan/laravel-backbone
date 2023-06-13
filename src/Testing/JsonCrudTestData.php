<?php

namespace KhanhArtisan\LaravelBackbone\Testing;

use KhanhArtisan\LaravelBackbone\Testing\Traits\TestDestroyData;
use KhanhArtisan\LaravelBackbone\Testing\Traits\TestIndexData;
use KhanhArtisan\LaravelBackbone\Testing\Traits\TestShowData;
use KhanhArtisan\LaravelBackbone\Testing\Traits\TestStoreData;
use KhanhArtisan\LaravelBackbone\Testing\Traits\TestUpdateData;

class JsonCrudTestData
{
    use TestStoreData, TestUpdateData, TestIndexData, TestShowData, TestDestroyData;
}