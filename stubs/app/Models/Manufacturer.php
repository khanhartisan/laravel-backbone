<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use KhanhArtisan\LaravelBackbone\RelationCascade\ShouldCascade;
use KhanhArtisan\LaravelBackbone\RelationCascade\CascadeDetails;
use KhanhArtisan\LaravelBackbone\RelationCascade\Cascades;

class Manufacturer extends Model implements ShouldCascade
{
    use HasFactory;
    use Cascades;

    protected $fillable = ['name'];

    public function getCascadeDetails(): CascadeDetails|array
    {
        return [
            (new CascadeDetails($this->cars()))
                ->setShouldDelete(!str_contains($this->name, 'SHOULD-NOT-DELETE-CARS'))
                ->setShouldRestore(!str_contains($this->name, 'SHOULD-NOT-RESTORE-CARS'))
                ->setShouldForceDelete(str_contains($this->name, 'SHOULD-FORCE-DELETE-CARS'))
                ->setShouldDeletePerItem(!str_contains($this->name, 'SHOULD-DELETE-CARS-BY-BATCH'))
        ];
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    public function autoForceDeleteWhenAllRelationsAreDeleted(): bool
    {
        return str_contains($this->name, 'SHOULD-AUTO-FORCE-DELETE');
    }
}