<?php

namespace KhanhArtisan\LaravelBackbone\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Interval;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Recorder;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Store;

class CounterTest extends TestCase
{
    public function test_redis_recorder()
    {
        $this->_testRecorder('redis');
    }

    private function _testRecorder(string $driver)
    {
        $recorder = $this->makeRecorder($driver);

        foreach (Interval::cases() as $interval) {

            $partitionKey = Str::random();
            $reference = Str::random();
            $value = rand(123, 9999);
            $time = time();

            // Test record successfully
            $this->assertTrue($recorder->record(
                $partitionKey,
                $interval,
                $reference,
                $value,
                1000,
                $time
            ));

            // Test get shard key for reference
            $this->assertNotNull($shardKey = $recorder->getShardKeyForReference(
                $partitionKey,
                $interval,
                $time,
                $reference
            ));

            // Test get max shard key length
            $this->assertEquals(1, $recorder->getMaxShardKeyLength(
                $partitionKey,
                $interval,
                $time
            ));

            // Test shard size
            $this->assertEquals(1, $recorder->getShardSize(
                $partitionKey,
                $interval,
                $time,
                $shardKey
            ));

            // Test get shard records
            $records = $recorder->getShardRecords(
                $partitionKey,
                $interval,
                $time,
                $shardKey
            );
            $this->assertCount(1, $records);
            $this->assertEquals($value, $records[0]->getValue());

            // Test flush
            $this->assertTrue($recorder->flush(
                $partitionKey,
                $interval,
                $time,
                $shardKey
            ));

            // Test flushed
            $this->assertCount(0, $recorder->getShardRecords(
                $partitionKey,
                $interval,
                $time,
                $shardKey
            ));
        }
    }

    private function makeRecorder(?string $driver = null): Recorder
    {
        return \KhanhArtisan\LaravelBackbone\Support\Facades\Counter\Recorder::driver($driver);
    }

    private function makeStore(?string $driver = null): Store
    {
        return \KhanhArtisan\LaravelBackbone\Support\Facades\Counter\Store::driver($driver);
    }
}
