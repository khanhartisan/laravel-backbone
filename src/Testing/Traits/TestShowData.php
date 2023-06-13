<?php

namespace KhanhArtisan\LaravelBackbone\Testing\Traits;

trait TestShowData
{
    protected string $showUri;
    protected ?array $expectedShowResponseData = null;
    protected int $expectedShowResponseCode = 200;

    public function setShowUri(string $uri): self
    {
        $this->showUri = $uri;
        return $this;
    }

    public function getShowUri(): ?string
    {
        return $this->showUri ?? null;
    }

    public function setExpectedShowResponseData(array $data): self
    {
        $this->expectedShowResponseData = $data;
        return $this;
    }

    public function getExpectedShowResponseData(): ?array
    {
        return $this->expectedShowResponseData;
    }

    public function setExpectedShowResponseCode(int $code): self
    {
        $this->expectedShowResponseCode = $code;
        return $this;
    }

    public function getExpectedShowResponseCode(): int
    {
        return $this->expectedShowResponseCode;
    }
}