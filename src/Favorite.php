<?php

namespace Overtrue\LaravelFavorite;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Overtrue\LaravelFavorite\Events\Favorited;
use Overtrue\LaravelFavorite\Events\Unfavorited;

/**
 * @property \Illuminate\Database\Eloquent\Model $user
 * @property \Illuminate\Database\Eloquent\Model $favoriter
 * @property \Illuminate\Database\Eloquent\Model $favoriteable
 */
class Favorite extends Model
{
    protected $guarded = [];

    /**
     * @var string[]
     */
    protected $dispatchesEvents = [
        'created' => Favorited::class,
        'deleted' => Unfavorited::class,
    ];

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
            $favorite->{$userForeignKey} = $favorite->{$userForeignKey} ?: auth()->id();

            if (\config('favorite.uuids')) {
                $favorite->{$favorite->getKeyName()} = $favorite->{$favorite->getKeyName()} ?: (string) Str::orderedUuid();
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function favoriteable()
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\config('auth.providers.users.model'), \config('favorite.user_foreign_key'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function favoriter()
    {
        return $this->user();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithType(Builder $query, string $type)
    {
        return $query->where('favoriteable_type', app($type)->getMorphClass());
    }
}
