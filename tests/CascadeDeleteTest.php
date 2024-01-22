<?php

namespace KhanhArtisan\LaravelBackbone\Tests;

use App\Models\Car;
use App\Models\Manufacturer;
use App\Models\Review;
use KhanhArtisan\LaravelBackbone\RelationCascade\RelationCascadeManager;
use KhanhArtisan\LaravelBackbone\RelationCascade\Jobs\CascadeDelete;

class CascadeDeleteTest extends TestCase
{
    public function test_cascade_delete_simple()
    {
        $this->_register();

        $review = Review::factory()->create();

        $car = $review->car;
        $this->assertFalse($car->cascade_status);

        $manufacturer = $car->manufacturer;
        $this->assertFalse($manufacturer->cascade_status);

        $manufacturer->delete();

        // First time will delete the car
        CascadeDelete::dispatch();

        $manufacturer->refresh();
        $this->assertSoftDeleted($manufacturer);
        $this->assertTrue($manufacturer->cascade_status);

        $car->refresh();
        $this->assertSoftDeleted($car);
        $this->assertFalse($car->cascade_status);

        $this->assertModelExists($review);

        // Second time will delete the review
        CascadeDelete::dispatch();

        $manufacturer->refresh();
        $this->assertSoftDeleted($manufacturer);
        $this->assertTrue($manufacturer->cascade_status);

        $car->refresh();
        $this->assertSoftDeleted($car);
        $this->assertTrue($car->cascade_status);

        $this->assertModelMissing($review);
    }

    // TODO: Add more test cases

    protected function _register()
    {
        /** @var RelationCascadeManager $cascadeDeleteManager */
        $cascadeDeleteManager = app()->make(RelationCascadeManager::class);
        $cascadeDeleteManager->registerModels([
            Car::class,
            Manufacturer::class,
        ]);
    }
}