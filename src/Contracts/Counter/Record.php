<?php

namespace KhanhArtisan\LaravelBackbone\Contracts\Counter;

class Record
{
    public function __construct(protected string $partitionKey,
                                protected Interval $interval,
                                protected int $time,
                                protected string|int $reference,
                                protected int $value,
                                protected string|int|null $storeReference = null)
    {
        $this->time = TimeHelper::startTime($interval, $time);
    }

    /**
     * @return string
     */
    public function getPartitionKey(): string
    {
        return $this->partitionKey;
    }

    /**
     * @return Interval
     */
    public function getInterval(): Interval
    {
        return $this->interval;
    }

    /**
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }

    /**
     * @return string|int
     */
    public function getReference(): string|int
    {
        return $this->reference;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Get record reference id in the store
     *
     * @return string|int|null
     */
    public function getStoreReference(): null|string|int
    {
        return $this->storeReference;
    }
}