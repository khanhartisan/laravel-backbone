<?php

namespace KhanhArtisan\LaravelBackbone\Contracts\Counter;

final class ShardKey
{
    protected string $key;

    /**
     * @param string $data
     * @param int $length
     */
    public function __construct(protected string $data, protected int $length)
    {
        $this->key = substr(sha1($data), 0, $this->length);
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }
}