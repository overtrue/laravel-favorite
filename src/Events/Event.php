<?php

namespace Overtrue\LaravelFavorite\Events;

use Illuminate\Database\Eloquent\Model;

class Event
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $favorite;

    /**
     * Event constructor.
     */
    public function __construct(Model $favorite)
    {
        $this->favorite = $favorite;
    }
}
