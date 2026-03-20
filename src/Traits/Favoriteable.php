<?php

namespace Overtrue\LaravelFavorite\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property Collection $favoriters
 * @property Collection $favorites
 */
trait Favoriteable
{
    /**
     * @deprecated renamed to `hasBeenFavoritedBy`, will be removed at 5.0
     */
    public function isFavoritedBy(Model $user): bool
    {
        return $this->hasBeenFavoritedBy($user);
    }

    public function hasFavoriter(Model $user): bool
    {
        return $this->hasBeenFavoritedBy($user);
    }

    public function hasBeenFavoritedBy(Model $user): bool
    {
        if (! \is_a($user, config('favorite.favoriter_model'))) {
            return false;
        }

        if ($this->relationLoaded('favoriters')) {
            return $this->favoriters->contains($user);
        }

        return ($this->relationLoaded('favorites') ? $this->favorites : $this->favorites())
            ->where(\config('favorite.user_foreign_key'), $user->getKey())->count() > 0;
    }

    public function favorites(): MorphMany
    {
        return $this->morphMany(config('favorite.favorite_model'), 'favoriteable');
    }

    public function favoriters(): BelongsToMany
    {
        return $this->belongsToMany(
            config('favorite.favoriter_model'),
            config('favorite.favorites_table'),
            'favoriteable_id',
            config('favorite.user_foreign_key')
        )
            ->where('favoriteable_type', $this->getMorphClass());
    }
}
