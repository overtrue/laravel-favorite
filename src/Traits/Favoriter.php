<?php

namespace Overtrue\LaravelFavorite\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\LazyCollection;

/**
 * @property \Illuminate\Database\Eloquent\Collection $favorites
 */
trait Favoriter
{
    public function favorite(Model $object): void
    {
        /* @var \Overtrue\LaravelFavorite\Traits\Favoriteable $object */
        if (!$this->hasFavorited($object)) {
            $favorite = app(config('favorite.favorite_model'));
            $favorite->{config('favorite.user_foreign_key')} = $this->getKey();

            $object->favorites()->save($favorite);
        }
    }

    public function unfavorite(Model $object): void
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

    public function toggleFavorite(Model $object): void
    {
        $this->hasFavorited($object) ? $this->unfavorite($object) : $this->favorite($object);
    }

    public function hasFavorited(Model $object): bool
    {
        return ($this->relationLoaded('favorites') ? $this->favorites : $this->favorites())
            ->where('favoriteable_id', $object->getKey())
            ->where('favoriteable_type', $object->getMorphClass())
            ->count() > 0;
    }

    public function favorites(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(config('favorite.favorite_model'), config('favorite.user_foreign_key'), $this->getKeyName());
    }

    public function attachFavoriteStatus($favoriteables, callable $resolver = null)
    {
        $returnFirst = false;
        $toArray = false;

        switch (true) {
            case $favoriteables instanceof Model:
                $returnFirst = true;
                $favoriteables = \collect([$favoriteables]);
                break;
            case $favoriteables instanceof LengthAwarePaginator:
                $favoriteables = $favoriteables->getCollection();
                break;
            case $favoriteables instanceof Paginator:
            case $favoriteables instanceof CursorPaginator:
                $favoriteables = \collect($favoriteables->items());
                break;
            case $favoriteables instanceof LazyCollection:
                $favoriteables = \collect($favoriteables->all());
                break;
            case \is_array($favoriteables):
                $favoriteables = \collect($favoriteables);
                $toArray = true;
                break;
        }

        \abort_if(!($favoriteables instanceof Enumerable), 422, 'Invalid $favoriteables type.');

        $favorited = $this->favorites()->get()->keyBy(function ($item) {
            return \sprintf('%s:%s', $item->favoriteable_type, $item->favoriteable_id);
        });

        $favoriteables->map(function ($favoriteable) use ($favorited, $resolver) {
            $resolver = $resolver ?? fn ($m) => $m;
            $favoriteable = $resolver($favoriteable);

            if ($favoriteable && \in_array(Favoriteable::class, \class_uses_recursive($favoriteable))) {
                $key = \sprintf('%s:%s', $favoriteable->getMorphClass(), $favoriteable->getKey());
                $favoriteable->setAttribute('has_favorited', $favorited->has($key));
            }
        });

        return $returnFirst ? $favoriteables->first() : ($toArray ? $favoriteables->all() : $favoriteables);
    }

    public function getFavoriteItems(string $model): Builder
    {
        return app($model)->whereHas(
            'favoriters',
            function ($q) {
                return $q->where(config('favorite.user_foreign_key'), $this->getKey());
            }
        );
    }
}
