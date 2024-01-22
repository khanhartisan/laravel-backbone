<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use KhanhArtisan\LaravelBackbone\RelationCascade\ShouldCascade;
use KhanhArtisan\LaravelBackbone\RelationCascade\CascadeDetails;
use KhanhArtisan\LaravelBackbone\RelationCascade\Cascades;

class Car extends Model implements ShouldCascade
{
    use HasFactory;
    use Cascades;

    protected $fillable = ['name'];

    public function getCascadeDeleteDetails(): CascadeDetails|array
    {
        return [
            (new CascadeDetails($this->reviews()))
                ->setShouldDeletePerItem(false)
        ];
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}