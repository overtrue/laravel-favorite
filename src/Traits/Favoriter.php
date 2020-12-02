<?php

namespace Overtrue\LaravelFavorite\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * @property \Illuminate\Database\Eloquent\Collection $favorites
 */
trait Favoriter
{
    public function favorite(Model $object)
    {
        /* @var \Overtrue\LaravelFavorite\Traits\Favoriteable $object */
        if (!$this->hasFavorited($object)) {
            $favorite = app(config('favorite.favorite_model'));
            $favorite->{config('favorite.user_foreign_key')} = $this->getKey();

            $object->favorites()->save($favorite);
        }
    }

    public function unfavorite(Model $object)
    {
        /* @var \Overtrue\LaravelFavorite\Traits\Favoriteable $object */
        $relation = $object->favorites()
            ->where('favoriteable_id', $object->getKey())
            ->where('favoriteable_type', $object->getMorphClass())
            ->where(config('favorite.user_foreign_key'), $this->getKey())
            ->first();

        if ($relation) {
            $relation->delete();
        }
    }

    public function toggleFavorite(Model $object)
    {
        $this->hasFavorited($object) ? $this->unfavorite($object) : $this->favorite($object);
    }

    /**
     * @return bool
     */
    public function hasFavorited(Model $object)
    {
        return ($this->relationLoaded('favorites') ? $this->favorites : $this->favorites())
            ->where('favoriteable_id', $object->getKey())
            ->where('favoriteable_type', $object->getMorphClass())
            ->count() > 0;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function favorites()
    {
        return $this->hasMany(config('favorite.favorite_model'), config('favorite.user_foreign_key'), $this->getKeyName());
    }

    /**
     * Get Query Builder for favorites
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function getFavoriteItems(string $model)
    {
        return app($model)->whereHas(
            'favoriters',
            function ($q) {
                return $q->where(config('favorite.user_foreign_key'), $this->getKey());
            }
        );
    }
}
