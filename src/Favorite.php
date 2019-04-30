<?php

/*
 * This file is part of the overtrue/laravel-favorite.
 *
 * (c) overtrue <anzhengchao@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Overtrue\LaravelFavorite;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Like8.
 */
class Favorite extends Model
{
    /**
     * Like constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = \config('favorite.favorites_table');

        parent::__construct($attributes);
    }

    protected static function boot()
    {
        parent::boot();

        self::saving(function ($favorite) {
            $userForeignKey = \config('favorite.user_foreign_key');
            $favorite->{$userForeignKey} = $favorite->{$userForeignKey} ?: auth()->user()->getKey();
        });
    }

    public function favoriteable()
    {
        return $this->morphTo();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $type
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithType(Builder $query, string $type)
    {
        return $query->where('favoriteable_type', app($type)->getMorphClass());
    }
}
