<?php

namespace KhanhArtisan\LaravelBackbone\Tests;

use App\Models\Car;
use App\Models\Manufacturer;
use App\Models\Review;
use KhanhArtisan\LaravelBackbone\RelationCascade\CascadeStatus;
use KhanhArtisan\LaravelBackbone\RelationCascade\RelationCascadeManager;
use KhanhArtisan\LaravelBackbone\RelationCascade\Jobs\CascadeDelete;

class CascadeDeleteTest extends TestCase
{
    public function test_cascade_delete_simple()
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
    }

    // TODO: Add more test cases

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