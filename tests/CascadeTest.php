<?php

namespace KhanhArtisan\LaravelBackbone\Tests;

use App\Models\Car;
use App\Models\Manufacturer;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use KhanhArtisan\LaravelBackbone\RelationCascade\CascadeStatus;
use KhanhArtisan\LaravelBackbone\RelationCascade\Jobs\CascadeRestore;
use KhanhArtisan\LaravelBackbone\RelationCascade\RelationCascadeManager;
use KhanhArtisan\LaravelBackbone\RelationCascade\Jobs\CascadeDelete;

class CascadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cascade_simple()
    {
        $this->_register();

        $review = Review::factory()->create();

        $car = $review->car;
        $this->assertEquals(CascadeStatus::IDLE, $car->cascade_status);

        $manufacturer = $car->manufacturer;
        $this->assertEquals(CascadeStatus::IDLE, $manufacturer->cascade_status);

        $manufacturer->delete();

        // First time will delete the car
        CascadeDelete::dispatch();

        $manufacturer->refresh();
        $this->assertSoftDeleted($manufacturer);
        $this->assertEquals(CascadeStatus::DELETING, $manufacturer->cascade_status);

        $car->refresh();
        $this->assertSoftDeleted($car);
        $this->assertEquals(CascadeStatus::DELETING, $car->cascade_status);

        $this->assertModelExists($review);

        // Second time will delete the review
        CascadeDelete::dispatch();

        // The review should be deleted and missing
        $this->assertModelMissing($review);

        // Now the car status should be DELETED
        $car->refresh();
        $this->assertSoftDeleted($car);
        $this->assertEquals(CascadeStatus::DELETED, $car->cascade_status);

        // And the manufacturer status should be DELETED too
        $manufacturer->refresh();
        $this->assertSoftDeleted($manufacturer);
        $this->assertEquals(CascadeStatus::DELETED, $manufacturer->cascade_status);

        // Now restore the manufacturer
        $manufacturer->restore();
        $this->assertEquals(CascadeStatus::RESTORING, $manufacturer->cascade_status);

        $car->refresh();
        $this->assertEquals(CascadeStatus::DELETED, $car->cascade_status);
        $this->assertSoftDeleted($car);

        // Run the restore job
        CascadeRestore::dispatch();

        // Now the car should be restoring
        $car->refresh();
        $this->assertEquals(CascadeStatus::RESTORING, $car->cascade_status);
        $this->assertModelExists($car);

        // And the manufacturer should be restoring too
        $manufacturer->refresh();
        $this->assertEquals(CascadeStatus::RESTORING, $manufacturer->cascade_status);
        $this->assertModelExists($manufacturer);

        // Run the restore job again
        CascadeRestore::dispatch();

        // Now the car should be restored
        $car->refresh();
        $this->assertEquals(CascadeStatus::IDLE, $car->cascade_status);
        $this->assertModelExists($car);

        // And the manufacturer should be restored too
        $manufacturer->refresh();
        $this->assertEquals(CascadeStatus::IDLE, $manufacturer->cascade_status);
        $this->assertModelExists($manufacturer);
    }

    public function test_job_limit()
    {
        $this->_register();

        $manufacturer = Manufacturer::factory()->create();

        $carsCount = 5;
        $cars = Car::factory()->count($carsCount)->create([
            'manufacturer_id' => $manufacturer,
        ]);

        $manufacturer->delete();
        $limit = 3;
        CascadeDelete::dispatch($limit);

        $manufacturer->refresh();
        $this->assertSoftDeleted($manufacturer);
        $this->assertEquals(CascadeStatus::DELETING, $manufacturer->cascade_status);

        $this->assertEquals($limit, $manufacturer->cars()->onlyTrashed()->count());
        $this->assertEquals($carsCount - $limit, $manufacturer->cars()->count());
    }

    public function test_should_not_delete()
    {
        $this->_register();

        $manufacturer = Manufacturer::factory()->create([
            'name' => 'SHOULD-NOT-DELETE-CARS-'.'-'.uniqid(),
        ]);
        $car = Car::factory()->create([
            'manufacturer_id' => $manufacturer,
        ]);

        $manufacturer->delete();

        CascadeDelete::dispatch();

        $manufacturer->refresh();
        $this->assertSoftDeleted($manufacturer);
        $this->assertEquals(CascadeStatus::DELETED, $manufacturer->cascade_status);

        $this->assertNotSoftDeleted($car);

        // Restore
        $manufacturer->restore();

        CascadeRestore::dispatch();

        $manufacturer->refresh();
        $this->assertEquals(CascadeStatus::IDLE, $manufacturer->cascade_status);
        $this->assertModelExists($manufacturer);
        $this->assertNotSoftDeleted($manufacturer);

        $car->refresh();
        $this->assertNotSoftDeleted($car);
        $this->assertEquals(CascadeStatus::IDLE, $car->cascade_status);
    }

    public function test_should_force_delete()
    {
        $this->_register();

        $manufacturer = Manufacturer::factory()->create([
            'name' => 'SHOULD-FORCE-DELETE-CARS-'.'-'.uniqid(),
        ]);
        $car = Car::factory()->create([
            'manufacturer_id' => $manufacturer,
        ]);

        $manufacturer->delete();

        CascadeDelete::dispatch();

        $manufacturer->refresh();
        $this->assertSoftDeleted($manufacturer);
        $this->assertEquals(CascadeStatus::DELETED, $manufacturer->cascade_status);

        $this->assertModelMissing($car);
    }

    public function test_should_not_delete_per_item()
    {
        $this->_register();

        $manufacturer = Manufacturer::factory()->create([
            'name' => 'SHOULD-DELETE-CARS-BY-BATCH-'.uniqid(),
        ]);

        $carsCount = 5;
        $cars = Car::factory()->count($carsCount)->create([
            'manufacturer_id' => $manufacturer,
        ]);

        $manufacturer->delete();

        // First delete will delete all the cars but status is deleting
        CascadeDelete::dispatch();

        $manufacturer->refresh();
        $this->assertEquals(CascadeStatus::DELETING, $manufacturer->cascade_status);
        $this->assertSoftDeleted($manufacturer);

        $this->assertEquals(0, $manufacturer->cars()->count());
        $this->assertEquals($carsCount, $manufacturer->cars()->onlyTrashed()->where('cascade_status', CascadeStatus::DELETING)->count());

        // Second delete will mark all statuses to "deleted"
        CascadeDelete::dispatch();

        $manufacturer->refresh();
        $this->assertEquals(CascadeStatus::DELETED, $manufacturer->cascade_status);
        $this->assertSoftDeleted($manufacturer);

        $this->assertEquals(0, $manufacturer->cars()->count());
        $this->assertEquals($carsCount, $manufacturer->cars()->onlyTrashed()->where('cascade_status', CascadeStatus::DELETED)->count());
    }

    public function test_should_not_restore()
    {
        $this->_register();

        $manufacturer = Manufacturer::factory()->create([
            'name' => 'SHOULD-NOT-RESTORE-CARS-'.'-'.uniqid(),
        ]);
        $car = Car::factory()->create([
            'manufacturer_id' => $manufacturer,
        ]);

        $manufacturer->delete();

        CascadeDelete::dispatch();
        CascadeDelete::dispatch();

        $manufacturer->refresh();
        $this->assertSoftDeleted($manufacturer);
        $this->assertEquals(CascadeStatus::DELETED, $manufacturer->cascade_status);

        $this->assertSoftDeleted($car);

        // Restore
        $manufacturer->restore();

        CascadeRestore::dispatch();
        CascadeRestore::dispatch();

        $manufacturer->refresh();
        $this->assertEquals(CascadeStatus::IDLE, $manufacturer->cascade_status);
        $this->assertModelExists($manufacturer);
        $this->assertNotSoftDeleted($manufacturer);
        $this->assertEquals(0, $manufacturer->cars()->count());

        $car->refresh();
        $this->assertSoftDeleted($car);
    }

    public function test_auto_force_delete_when_all_relations_are_deleted()
    {
        $this->_register();

        $manufacturer = Manufacturer::factory()->create([
            'name' => 'SHOULD-AUTO-FORCE-DELETE-'.uniqid(),
        ]);
        $car = Car::factory()->create([
            'manufacturer_id' => $manufacturer,
        ]);

        $manufacturer->delete();
        $this->assertSoftDeleted($manufacturer);
        $this->assertModelExists($car);

        CascadeDelete::dispatch();
        CascadeDelete::dispatch();

        $this->assertModelMissing($manufacturer);
        $this->assertSoftDeleted($car);
    }

    protected function _register(): void
    {
        /** @var RelationCascadeManager $cascadeDeleteManager */
        $cascadeDeleteManager = app()->make(RelationCascadeManager::class);
        $cascadeDeleteManager->registerModels([
            Car::class,
            Manufacturer::class,
        ]);
    }
}