<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use KhanhArtisan\LaravelBackbone\CascadeDeleteManager\CascadeDeletable;
use KhanhArtisan\LaravelBackbone\CascadeDeleteManager\CascadeDeleteDetails;
use KhanhArtisan\LaravelBackbone\CascadeDeleteManager\CascadeDeletes;

class Manufacturer extends Model implements CascadeDeletable
{
    use HasFactory;
    use CascadeDeletes;

    protected $fillable = ['name'];

    public function getCascadeDeleteDetails(): CascadeDeleteDetails|array
    {
        return [
            new CascadeDeleteDetails($this->cars())
        ];
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }
}