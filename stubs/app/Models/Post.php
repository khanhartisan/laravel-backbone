<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['partition_key', 'title'];

    // Model listener testing properties
    public float|null $post_listener_priority_0_executed_at = null;
    public float|null $post_listener_priority_1_executed_at = null;
    public int $post_singleton_listener_count = 0;
    public int $post_non_singleton_listener_count = 0;
}