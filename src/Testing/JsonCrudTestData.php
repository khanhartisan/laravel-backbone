<?php

namespace KhanhArtisan\LaravelBackbone\Testing;

use Illuminate\Foundation\Auth\User;
use KhanhArtisan\LaravelBackbone\Testing\Traits\TestDestroyData;
use KhanhArtisan\LaravelBackbone\Testing\Traits\TestIndexData;
use KhanhArtisan\LaravelBackbone\Testing\Traits\TestShowData;
use KhanhArtisan\LaravelBackbone\Testing\Traits\TestStoreData;
use KhanhArtisan\LaravelBackbone\Testing\Traits\TestUpdateData;

class JsonCrudTestData
{
    use TestStoreData, TestUpdateData, TestIndexData, TestShowData, TestDestroyData;

    protected User $actingAs;

    public function actingAs(User $user): self
    {
        $this->actingAs = $user;
        return $this;
    }
}