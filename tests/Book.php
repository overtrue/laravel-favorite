<?php

namespace Overtrue\LaravelFavorite\Tests;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelFavorite\Traits\Favoriteable;

class Book extends Model
{
    use Favoriteable;

    protected $fillable = ['title'];
}