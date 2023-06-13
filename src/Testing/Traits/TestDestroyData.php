<?php

namespace KhanhArtisan\LaravelBackbone\Testing\Traits;

trait TestDestroyData
{
    protected string $destroyUri;
    protected ?array $expectedDestroyResponseData = null;
    protected int $expectedDestroyResponseCode = 200;

    public function setDestroyUri(string $uri): self
    {
        $this->destroyUri = $uri;
        return $this;
    }

    public function getDestroyUri(): ?string
    {
        return $this->destroyUri ?? null;
    }

    public function setExpectedDestroyResponseData(array $data): self
    {
        $this->expectedDestroyResponseData = $data;
        return $this;
    }

    public function getExpectedDestroyResponseData(): ?array
    {
        return $this->expectedDestroyResponseData;
    }

    public function setExpectedDestroyResponseCode(int $code): self
    {
        $this->expectedDestroyResponseCode = $code;
        return $this;
    }

    public function getExpectedDestroyResponseCode(): int
    {
        return $this->expectedDestroyResponseCode;
    }
}