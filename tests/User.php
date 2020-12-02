<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelFavorite\Traits\Favoriter;

class User extends Model
{
    use Favoriter;

    protected $fillable = ['name'];
}
