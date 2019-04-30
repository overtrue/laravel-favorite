<?php

/*
 * This file is part of the overtrue/laravel-favorite.
 *
 * (c) overtrue <anzhengchao@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelFavorite\Traits\CanFavorite;

/**
 * Class User.
 */
class User extends Model
{
    use CanFavorite;

    protected $fillable = ['name'];
}
