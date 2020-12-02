<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelFavorite\Traits\Favoriteable;

class Post extends Model
{
    use Favoriteable;

    protected $fillable = ['title'];
}
