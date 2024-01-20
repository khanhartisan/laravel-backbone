<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use KhanhArtisan\LaravelBackbone\CascadeDeleteManager\CascadeDeletable;
use KhanhArtisan\LaravelBackbone\CascadeDeleteManager\CascadeDeleteDetails;
use KhanhArtisan\LaravelBackbone\CascadeDeleteManager\CascadeDeletes;

class Car extends Model implements CascadeDeletable
{
    use HasFactory;
    use CascadeDeletes;

    protected $fillable = ['name'];

    public function getCascadeDeleteDetails(): CascadeDeleteDetails|array
    {
        return [
            (new CascadeDeleteDetails($this->reviews()))
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