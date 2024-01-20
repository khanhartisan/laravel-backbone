<?php

namespace KhanhArtisan\LaravelBackbone\Tests;

use App\Models\Car;
use App\Models\Manufacturer;
use App\Models\Review;
use KhanhArtisan\LaravelBackbone\CascadeDeleteManager\CascadeDeleteManager;
use KhanhArtisan\LaravelBackbone\CascadeDeleteManager\Jobs\CascadeDelete;

class CascadeDeleteTest extends TestCase
{
    public function test_cascade_delete_simple()
    {
        $this->_register();

        $review = Review::factory()->create();

        $car = $review->car;
        $this->assertFalse($car->relations_deleted);

        $manufacturer = $car->manufacturer;
        $this->assertFalse($manufacturer->relations_deleted);

        $manufacturer->delete();

        // First time will delete the car
        CascadeDelete::dispatch();

        $manufacturer->refresh();
        $this->assertSoftDeleted($manufacturer);
        $this->assertTrue($manufacturer->relations_deleted);

        $car->refresh();
        $this->assertSoftDeleted($car);
        $this->assertFalse($car->relations_deleted);

        $this->assertModelExists($review);

        // Second time will delete the review
        CascadeDelete::dispatch();

        $manufacturer->refresh();
        $this->assertSoftDeleted($manufacturer);
        $this->assertTrue($manufacturer->relations_deleted);

        $car->refresh();
        $this->assertSoftDeleted($car);
        $this->assertTrue($car->relations_deleted);

        $this->assertModelMissing($review);
    }

    // TODO: Add more test cases

    protected function _register()
    {
        /** @var CascadeDeleteManager $cascadeDeleteManager */
        $cascadeDeleteManager = app()->make(CascadeDeleteManager::class);
        $cascadeDeleteManager->registerModels([
            Car::class,
            Manufacturer::class,
        ]);
    }
}