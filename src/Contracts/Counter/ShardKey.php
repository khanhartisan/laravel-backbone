<?php

namespace KhanhArtisan\LaravelBackbone\Contracts\Counter;

final class ShardKey
{
    protected string $key;

    /**
     * @param string|null $data
     * @param int|null $length
     */
    public function __construct(protected ?string $data = null, protected ?int $length = null)
    {
        if ($data and $this->length) {
            $this->key = substr(sha1($data), 0, $this->length);
        }
    }

    /**
     * Set key
     *
     * @param string $key
     * @return static $this
     */
    public function setKey(string $key): ShardKey
    {
        if (!ctype_xdigit($key)
            or strlen($key) > 40
        ) {
            throw new \InvalidArgumentException($key.' is not a valid shard key.');
        }

        $this->key = $key;

        return $this;
    }

    /**
     * Get key
     *
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key ?? null;
    }
}