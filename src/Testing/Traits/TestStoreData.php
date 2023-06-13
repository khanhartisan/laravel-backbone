<?php

namespace KhanhArtisan\LaravelBackbone\Testing\Traits;

trait TestStoreData
{
    protected string $storeUri;
    protected array $storeData = [];
    protected ?array $expectedStoreResponseData = null;
    protected int $expectedStoreResponseCode = 201;

    public function setStoreUri(string $uri): self
    {
        $this->storeUri = $uri;
        return $this;
    }

    public function getStoreUri(): ?string
    {
        return $this->storeUri ?? null;
    }

    public function setStoreData(array $data): self
    {
        $this->storeData = $data;
        return $this;
    }

    public function getStoreData(): array
    {
        return $this->storeData;
    }

    public function setExpectedStoreResponseData(array $data): self
    {
        $this->expectedStoreResponseData = $data;
        return $this;
    }

    public function getExpectedStoreResponseData(): ?array
    {
        return $this->expectedStoreResponseData;
    }

    public function setExpectedStoreResponseCode(int $code): self
    {
        $this->expectedStoreResponseCode = $code;
        return $this;
    }

    public function getExpectedStoreResponseCode(): int
    {
        return $this->expectedStoreResponseCode;
    }
}