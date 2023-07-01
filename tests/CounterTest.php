<?php

namespace KhanhArtisan\LaravelBackbone\Tests;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Interval;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Record;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Recorder;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Store;

class CounterTest extends TestCase
{
    public function test_redis_recorder()
    {
        $this->_testRecorder('redis');
    }

    public function test_mysql_store()
    {
        /** @var Repository $config */
        $config = app()->make('config');

        // Test mysql
        $this->refreshDatabase('mysql');
        $config->set('counter.stores.database.connection', 'mysql');
        $this->_testStore('database');
    }

    public function test_pgsql_store()
    {
        /** @var Repository $config */
        $config = app()->make('config');

        // Test pgsql
        $this->refreshDatabase('pgsql');
        $config->set('counter.stores.database.connection', 'pgsql');
        $this->_testStore('database');
    }

    protected function _testRecorder(string $driver)
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

    protected function _testStore(string $driver)
    {
        $store = $this->makeStore($driver);

        // Test upsert single record
        $time = time();
        $record = new Record(
            Str::random(),
            Interval::FIVE_MINUTES,
            $time,
            Str::random(),
            rand(123, 999)
        );
        $this->assertTrue($store->upsert($record));
    }

    protected function makeRecorder(?string $driver = null): Recorder
    {
        \KhanhArtisan\LaravelBackbone\Support\Facades\Counter\Recorder::forgetDrivers();
        return \KhanhArtisan\LaravelBackbone\Support\Facades\Counter\Recorder::driver($driver);
    }

    protected function makeStore(?string $driver = null): Store
    {
        \KhanhArtisan\LaravelBackbone\Support\Facades\Counter\Store::forgetDrivers();
        return \KhanhArtisan\LaravelBackbone\Support\Facades\Counter\Store::driver($driver);
    }

    protected function refreshDatabase(string $database): void
    {
        Artisan::call('migrate:refresh', [
            '--database' => $database,
            '--path' => '../database/migrations/2023_07_01_164322_create_counter_table.php'
        ]);
    }
}
