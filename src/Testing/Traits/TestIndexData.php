<?php

namespace KhanhArtisan\LaravelBackbone\Testing\Traits;

trait TestIndexData
{
    protected string $indexUri;
    protected ?array $expectedIndexResponseData = null;
    protected int $expectedIndexResponseCode = 200;

    public function setIndexUri(string $uri): self
    {
        $this->indexUri = $uri;
        return $this;
    }

    public function getIndexUri(): ?string
    {
        return $this->indexUri ?? null;
    }

    public function setExpectedIndexResponseData(array $data): self
    {
        $this->expectedIndexResponseData = $data;
        return $this;
    }

    public function getExpectedIndexResponseData(): ?array
    {
        return $this->expectedIndexResponseData;
    }

    public function setExpectedIndexResponseCode(int $code): self
    {
        $this->expectedIndexResponseCode = $code;
        return $this;
    }

    public function getExpectedIndexResponseCode(): int
    {
        return $this->expectedIndexResponseCode;
    }
}