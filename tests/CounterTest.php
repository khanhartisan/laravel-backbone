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
use KhanhArtisan\LaravelBackbone\Contracts\Counter\ShardKey;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Store;
use KhanhArtisan\LaravelBackbone\Contracts\Counter\TimeHelper;
use KhanhArtisan\LaravelBackbone\Counter\Jobs\ClearData;
use KhanhArtisan\LaravelBackbone\Counter\Jobs\StoreData;

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

    public function test_jobs_redis_recorder_mysql_store()
    {
        /** @var Repository $config */
        $config = app()->make('config');
        $this->refreshDatabase('mysql');
        $config->set('counter.stores.database.connection', 'mysql');

        $this->_testJobs();
    }

    protected function _testJobs(string $recorderDriver = 'redis', string $storeDriver = 'database')
    {
        $recorder = $this->makeRecorder($recorderDriver);
        $store = $this->makeStore($storeDriver);

        $partitionKey = Str::random();
        $interval = Interval::ONE_MINUTE;
        $intervalSeconds = TimeHelper::intervalToSeconds($interval);
        $recordStartTime = now()->subMinutes(15);

        $recordFrom = $recordStartTime->clone();
        $recordedCount = 0;
        $maxRecordedCount = 1000;
        while ($recordFrom->lt(now())) {

            // Keep recording until max recorded count reached or shard key length is 2
            while ($recorder->getMaxShardKeyLength(
                    $partitionKey,
                    $interval,
                    $recordFrom->getTimestamp()
                ) < 2
            ) {

                // Throw exception if max recorded count reached
                if ($recordedCount >= $maxRecordedCount) {
                    throw new \Exception('Max recorded count reached');
                }

                // Record a random reference
                $recorder->record(
                    $partitionKey,
                    $interval,
                    $ref = 'ref-'.Str::random(10),
                    rand(1, 9999),
                    1,
                    $recordFrom->getTimestamp()
                );
                $recordedCount++;
            }

            // Confirm the shard key length is 2
            $this->assertEquals(2, $recorder->getMaxShardKeyLength(
                $partitionKey,
                $interval,
                $recordFrom->getTimestamp()
            ));

            // Increase the time by the interval seconds
            $recordFrom->addSeconds($intervalSeconds);
        }

        // Confirm recorded
        $this->assertTrue($recordedCount >= 15);

        // Confirm store is empty
        $scanFrom = $recordStartTime->clone();
        while ($scanFrom->lt(now())) {
            $this->assertEquals(0, count($store->getRecordsByTime($partitionKey, $interval, $scanFrom->getTimestamp())));
            $scanFrom->addSeconds($intervalSeconds);
        }

        // Run the store data job
        StoreData::dispatchSync($partitionKey, $interval, now()->subMinutes(16));

        // Confirm stored successfully
        $scanFrom = $recordStartTime->clone();
        $storedCount = 0;
        while ($scanFrom->lt(now())) {

            // Confirm stored successfully
            $records = $store->getRecordsByTime($partitionKey, $interval, $scanFrom->getTimestamp());
            $count = count($records);
            $this->assertTrue($count > 0);
            $storedCount += $count;

            // Confirm max shard key length is reset to 0
            $this->assertEquals(0, $recorder->getMaxShardKeyLength(
                $partitionKey,
                $interval,
                $scanFrom->getTimestamp()
            ));

            // Increase the scan time by the interval seconds
            $scanFrom->addSeconds($intervalSeconds);
        }

        // Confirm stored count equal to recorded count
        $this->assertEquals($recordedCount, $storedCount);

        // Run the clear data job
        $pruneFrom = $recordStartTime->clone();
        while ($pruneFrom ->lt(now())) {
            $this->assertNotEmpty($store->getRecordsByTime($partitionKey, $interval, $pruneFrom->getTimestamp()));

            ClearData::dispatchSync($interval, $pruneFrom->getTimestamp(), $partitionKey);

            // Confirm pruned successfully
            $this->assertEmpty($store->getRecordsByTime($partitionKey, $interval, $pruneFrom->getTimestamp()));

            $pruneFrom->addSeconds($intervalSeconds);
        }
    }

    protected function _testRecorder(string $driver)
    {
        $recorder = $this->makeRecorder($driver);

        foreach (Interval::cases() as $interval) {

            $partitionKey = Str::random();
            $reference = '1'; // 35
            $reference2 = '2aaaaaaaaa'; // 32
            $reference3 = '2aaaaaaaaae'; // 39
            $value = rand(123, 9999);
            $value2 = rand(1, 9999);
            $time = time();

            // Test record successfully
            $this->assertTrue($recorder->record(
                $partitionKey,
                $interval,
                $reference,
                $value,
                2,
                $time
            ));

            // Test 2nd record
            $this->assertTrue($recorder->record(
                $partitionKey,
                $interval,
                $reference,
                $value2,
                2,
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

            // Upsert 2 other records
            $this->assertTrue($recorder->record(
                $partitionKey,
                $interval,
                $reference2,
                $value,
                2,
                $time
            ));
            $this->assertTrue($recorder->record(
                $partitionKey,
                $interval,
                $reference3,
                $value,
                2,
                $time
            ));

            // Test get ShardKey for ref 3
            $this->assertNotNull($shardKey3 = $recorder->getShardKeyForReference(
                $partitionKey,
                $interval,
                $time,
                $reference3
            ));
            $this->assertEquals('39', $shardKey3->getKey());

            // Test get shard3 records
            $this->assertCount(1, $shard3Records = $recorder->getShardRecords($partitionKey, $interval, $time, $shardKey3));
            $this->assertEquals($reference3, $shard3Records[0]->getReference());
            $this->assertEquals($value, $shard3Records[0]->getValue());

            // Recheck max shard key length (should be 2)
            $this->assertEquals(2, $recorder->getMaxShardKeyLength(
                $partitionKey,
                $interval,
                $time
            ));

            // Recheck shard size (should be 2)
            $this->assertEquals(2, $recorder->getShardSize(
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
            $this->assertCount(2, $records);
            foreach ($records as $record) {
                if ($record->getReference() === $reference) {
                    $this->assertEquals($value + $value2, $record->getValue());
                } elseif ($record->getReference() === $reference2) {
                    $this->assertEquals($value, $record->getValue());
                } else {
                    $this->assertTrue(false);
                }
            }

            // Test flush
            $this->assertTrue($recorder->flush(
                $partitionKey,
                $interval,
                $time
            ));

            // Test flushed
            $this->assertCount(0, $recorder->getShardRecords(
                $partitionKey,
                $interval,
                $time,
                $shardKey
            ));
            $this->assertEquals(0, $recorder->getShardSize(
                $partitionKey,
                $interval,
                $time,
                $shardKey
            ));
            $this->assertEquals(0, $recorder->getMaxShardKeyLength(
                $partitionKey,
                $interval,
                $time
            ));
        }
    }

    protected function _testStore(string $driver)
    {
        $store = $this->makeStore($driver);

        $interval = Interval::ONE_MINUTE;

        // Test upsert single record
        $partitionKey = Str::random();
        $time = TimeHelper::startTime(Interval::FIVE_MINUTES, time()) - 150;
        $record1 = new Record(
            $partitionKey,
            $interval,
            $time,
            'ref-1',
            rand(1, 9999)
        );
        $this->assertTrue($store->upsert($record1));

        // Test upsert multiple record
        $record2 = new Record(
            $partitionKey,
            $interval,
            $time,
            'ref-2',
            rand(1, 9999)
        );
        $record3 = new Record(
            $partitionKey,
            $interval,
            $time,
            'ref-3',
            rand(1, 9999)
        );
        $this->assertTrue($store->upsert([$record1, $record2, $record3]));

        // Test get record 1
        $this->assertNotNull($findRecord1 = $store->getRecord($partitionKey, $interval, $time, $record1->getReference()));
        $this->assertEquals($record1->getValue() * 2, $findRecord1->getValue());

        // Test get record 2 & 3
        $this->assertNotNull($findRecord2 = $store->getRecord($partitionKey, $interval, $time, $record2->getReference()));
        $this->assertEquals($record2->getValue(), $findRecord2->getValue());
        $this->assertNotNull($findRecord3 = $store->getRecord($partitionKey, $interval, $time, $record3->getReference()));
        $this->assertEquals($record3->getValue(), $findRecord3->getValue());

        // Test sync interval records
        $record1Previous = new Record(
            $record1->getPartitionKey(),
            $record1->getInterval(),
            $record1->getTime() - TimeHelper::intervalToSeconds($record1->getInterval()),
            'ref-1',
            rand(1, 9999)
        );
        $this->assertTrue($store->upsert($record1Previous));
        $this->assertCount(4, $store->rollupIntervalRecords(Interval::ONE_MINUTE, Interval::FIVE_MINUTES));

        // Test get records by time
        $this->assertCount(3, $recordsByTime = $store->getRecordsByTime($partitionKey, Interval::FIVE_MINUTES, TimeHelper::startTime(Interval::FIVE_MINUTES, $time)));
        foreach ($recordsByTime as $record) {
            if ($record->getReference() === 'ref-1') {
                $this->assertEquals(
                    // Because record1 is inserted twice
                    $record1->getValue() * 2 + $record1Previous->getValue(),
                    $record->getValue()
                );
                continue;
            }
            if ($record->getReference() === 'ref-2') {
                $this->assertEquals($record2->getValue(), $record->getValue());
                continue;
            }
            if ($record->getReference() === 'ref-3') {
                $this->assertEquals($record3->getValue(), $record->getValue());
                continue;
            }
            throw new \Exception('Failed to test getRecordsByTime()');
        }

        // Test get records by reference
        $this->assertCount(2, $recordsByReference = collect($store->getRecordsByReference($partitionKey, Interval::ONE_MINUTE, $record1->getReference(), time() - 86400, time())));
        $this->assertCount(1, (clone $recordsByReference)->filter(function (Record $record) use ($record1) {
            if ($record->getTime() === $record1->getTime()) {
                $this->assertEquals($record1->getValue() * 2, $record->getValue());
                return true;
            }
            return false;
        }));
        $this->assertCount(1, $recordsByReference->filter(function (Record $record) use ($record1Previous) {
            if ($record->getTime() === $record1Previous->getTime()) {
                $this->assertEquals($record1Previous->getValue(), $record->getValue());
                return true;
            }
            return false;
        }));

        // Test get records and execute once
        $this->assertCount(4, $store->getRecordsAndExecuteOnce(
            $partitionKey,
            Interval::ONE_MINUTE,
            100,
            function (array $records, \Closure $complete) {
                $complete();
            }
        ));
        $this->assertCount(0, $store->getRecordsAndExecuteOnce(
            $partitionKey,
            Interval::ONE_MINUTE,
            100,
            function () {
                // Executed
            }
        ));

        // Test get a single record
        $this->assertNotNull($record = $store->getRecord($partitionKey, Interval::FIVE_MINUTES, $time, 'ref-2'));
        $this->assertEquals($record2->getReference(), $record->getReference());
        $this->assertEquals($record2->getValue(), $record->getValue());

        // Test delete a single record
        $this->assertTrue($store->delete($findRecord1->getStoreReference()));
        $this->assertNull($store->getRecord($partitionKey, $interval, $time, $record1->getReference()));

        // Test delete multi records
        $this->assertTrue($store->delete([$findRecord2->getStoreReference(), $findRecord3->getStoreReference()]));
        $this->assertNull($store->getRecord($partitionKey, $interval, $time, $record2->getReference()));
        $this->assertNull($store->getRecord($partitionKey, $interval, $time, $record3->getReference()));

        // Test prune
        $pruneInterval = Interval::FIVE_MINUTES;

        // Test prune all
        $this->assertTrue($store->prune($pruneInterval, time()));

        $pruneTime = time();
        $pruneRefPrefix = 'prune-ref-';
        $prunePartition = Str::random();
        for ($i = 1; $i <= 10; $i++) {
            $this->assertTrue($store->upsert(new Record(
                $prunePartition,
                $pruneInterval,
                $pruneTime,
                $pruneRefPrefix.$i,
                rand(1, 9999)
            )));
        }
        $this->assertCount(10, $store->getRecordsByTime($prunePartition, $pruneInterval, $pruneTime));

        // Prune with partition
        $this->assertTrue($store->prune($pruneInterval, $pruneTime, $prunePartition, 1));
        $this->assertCount(9, $store->getRecordsByTime($prunePartition, $pruneInterval, $pruneTime));

        // Prune with time
        $this->assertTrue($store->prune($pruneInterval, $pruneTime, null, 2));
        $this->assertCount(7, $store->getRecordsByTime($prunePartition, $pruneInterval, $pruneTime));
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
            '--path' => '../../../../database/migrations/2023_07_01_164322_create_counter_table.php'
        ]);
    }
}
