<?php

namespace KhanhArtisan\LaravelBackbone\Testing\Traits;

trait TestUpdateData
{
    protected string $updateUri;
    protected array $updateData = [];
    protected ?array $expectedUpdateResponseData = null;
    protected int $expectedUpdateResponseCode = 200;

    public function setUpdateUri(string $uri): self
    {
        $this->updateUri = $uri;
        return $this;
    }

    public function getUpdateUri(): ?string
    {
        return $this->updateUri ?? null;
    }

    public function setUpdateData(array $data): self
    {
        $this->updateData = $data;
        return $this;
    }

    public function getUpdateData(): array
    {
        return $this->updateData;
    }

    public function setExpectedUpdateResponseData(array $data): self
    {
        $this->expectedUpdateResponseData = $data;
        return $this;
    }

    public function getExpectedUpdateResponseData(): ?array
    {
        return $this->expectedUpdateResponseData;
    }

    public function setExpectedUpdateResponseCode(int $code): self
    {
        $this->expectedUpdateResponseCode = $code;
        return $this;
    }

    public function getExpectedUpdateResponseCode(): int
    {
        return $this->expectedUpdateResponseCode;
    }
}