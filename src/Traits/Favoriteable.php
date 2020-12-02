<?php

namespace Overtrue\LaravelFavorite\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * @property \Illuminate\Database\Eloquent\Collection $favoriters
 * @property \Illuminate\Database\Eloquent\Collection $favorites
 */
trait Favoriteable
{
    /**
     * @return bool
     */
    public function isFavoritedBy(Model $user)
    {
        if (\is_a($user, config('auth.providers.users.model'))) {
            if ($this->relationLoaded('favoriters')) {
                return $this->favoriters->contains($user);
            }

            return ($this->relationLoaded('favorites') ? $this->favorites : $this->favorites())
                    ->where(\config('favorite.user_foreign_key'), $user->getKey())->count() > 0;
        }

        return false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function favorites()
    {
        return $this->morphMany(config('favorite.favorite_model'), 'favoriteable');
    }

    /**
     * Return followers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function favoriters()
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('favorite.favorites_table'),
            'favoriteable_id',
            config('favorite.user_foreign_key')
        )
            ->where('favoriteable_type', $this->getMorphClass());
    }
}
