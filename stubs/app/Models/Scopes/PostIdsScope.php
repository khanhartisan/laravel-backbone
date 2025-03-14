<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PostIdsScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (!$ids = request()->query('ids')) {
            return;
        }

        $builder->whereIn('id', explode(',', $ids));
    }
}