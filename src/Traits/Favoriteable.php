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
     * @deprecated renamed to `hasBeenFavoritedBy`, will be removed at 5.0
     */
    public function isFavoritedBy(Model $model)
    {
        return $this->hasBeenFavoritedBy($model);
    }

    public function hasFavoriter(Model $model): bool
    {
        return $this->hasBeenFavoritedBy($model);
    }

    public function hasBeenFavoritedBy(Model $model): bool
    {
        dd(config('auth.providers.users.model'));
        if (\is_a($model, config('auth.providers.users.model'))) {
            if ($this->relationLoaded('favoriters')) {
                return $this->favoriters->contains($model);
            }

            return ($this->relationLoaded('favorites') ? $this->favorites : $this->favorites())
                    ->where('favoriter_id', $model->getKey())
                    ->where('favoriter_type', $model->getMorphClass())
                    ->count() > 0;
        }

        return false;
    }

    public function favorites(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(config('favorite.favorite_model'), 'favoriteable');
    }

    public function favoriters(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->morphedByMany()
            ->where('favoriteable_type', $this->getMorphClass());
    }
}
