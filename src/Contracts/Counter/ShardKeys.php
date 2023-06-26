<?php

namespace KhanhArtisan\LaravelBackbone\Contracts\Counter;

class ShardKeys implements \Iterator
{
    protected array $hexChars = [
        'a', 'b', 'c', 'd', 'e', 'f',
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'
    ];

    protected array $currentKeys = [];

    protected int $currentKeyPosition;

    protected bool $finished = false;

    public function __construct(protected int $length = 1)
    {
        if ($this->length < 1 or $this->length > 6) {
            throw new \InvalidArgumentException('Invalid length.');
        }

        for ($i = 1; $i <= $this->length; $i++) {
            $this->currentKeys[$i] = 0;
        }

        $this->currentKeyPosition = $this->length;
    }

    public function current(): mixed
    {
        return new ShardKey($this->key());
    }

    public function key(): mixed
    {
        $keyString = '';

        foreach ($this->currentKeys as $position) {
            $keyString .= $this->hexChars[$position];
        }

        return $keyString;
    }

    public function next(): void
    {
        $currentValue = $this->currentKeys[$this->currentKeyPosition];

        if ($currentValue < 15) {
            $this->currentKeys[$this->currentKeyPosition]++;
            if ($this->currentKeyPosition < $this->length) {
                for ($i = $this->currentKeyPosition + 1; $i <= $this->length; $i++) {
                    $this->currentKeys[$i] = 0;
                }
                $this->currentKeyPosition = $this->length;
            }
            return;
        }

        if ($currentValue === 15) {
            if ($this->currentKeyPosition > 1) {
                $this->currentKeyPosition--;
                $this->next();
                return;
            }
        }

        $this->finished = true;
    }

    public function rewind(): void
    {
        // no rewind
    }

    public function valid(): bool
    {
        return !$this->finished;
    }
}