<?php

namespace KhanhArtisan\LaravelBackbone\Contracts\Counter;

class ShardKey
{
    /**
     * @param string $key
     */
    public function __construct(protected string $key)
    {
        if (!ctype_xdigit($this->key)) {
            throw new \InvalidArgumentException($key.' is not a valid shard key.');
        }
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }
}