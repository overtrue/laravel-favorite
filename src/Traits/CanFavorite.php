<?php

/*
 * This file is part of the overtrue/laravel-favorite.
 *
 * (c) overtrue <anzhengchao@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Overtrue\LaravelFavorite\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Overtrue\LaravelFavorite\Events\Favorited;
use Overtrue\LaravelFavorite\Events\Unfavorited;

/**
 * Trait CanBeFavorited.
 */
trait CanFavorite
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     */
    public function favorite(Model $object)
    {
        if (!$this->hasFavorited($object)) {
            $favorite = app(config('favorite.favorite_model'));
            $favorite->{config('favorite.user_foreign_key')} = $this->getKey();

            $object->favorites()->save($favorite);

            Event::dispatch(new Favorited($this, $object));
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     */
    public function unfavorite(Model $object)
    {
        $relation = $object->favorites()
            ->where('favoriteable_id', $object->getKey())
            ->where('favoriteable_type', $object->getMorphClass())
            ->where(config('favorite.user_foreign_key'), $this->getKey())
            ->first();

        if ($relation) {
            $relation->delete();
            Event::dispatch(new Unfavorited($this, $object));
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     */
    public function toggleLike(Model $object)
    {
        $this->hasFavorited($object) ? $this->unfavorite($object) : $this->favorite($object);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     *
     * @return bool
     */
    public function hasFavorited(Model $object)
    {
        return tap($this->relationLoaded('favorites') ? $this->favorites : $this->favorites())
            ->where('favoriteable_id', $object->getKey())
            ->where('favoriteable_type', $object->getMorphClass())
            ->count() > 0;
    }

    /**
     * Return favorite.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function favorites()
    {
        return $this->hasMany(config('favorite.favorite_model'), config('favorite.user_foreign_key'), $this->getKeyName());
    }

    /**
     * @param string|null $model
     *
     * @return mixed
     */
    public function favoritedItems(string $model = null)
    {
        $this->load(['favorites' => function ($query) use ($model) {
            $model && $query->where('favoriteable_type', app($model)->getMorphClass());
        }, 'favorites.favoriteable']);

        return $this->favorites->pluck('favoriteable');
    }
}
