<?php

use App\Models\User;
use Overtrue\LaravelFavorite\Favorite;

return [
    /**
     * Use uuid as primary key.
     */
    'uuids' => false,

    /*
     * User tables foreign key name.
     */
    'user_foreign_key' => 'user_id',

    /*
     * Table name for favorites records.
     */
    'favorites_table' => 'favorites',

    /*
     * Model name for favorite record.
     */
    'favorite_model' => Favorite::class,

    /*
     * Model name for favoriter model.
     */
    'favoriter_model' => User::class,
];
