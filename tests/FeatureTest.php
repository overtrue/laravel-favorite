<?php

namespace Tests;

use Illuminate\Support\Facades\Event;
use Overtrue\LaravelFavorite\Events\Favorited;
use Overtrue\LaravelFavorite\Events\Unfavorited;
use Overtrue\LaravelFavorite\Favorite;

class FeatureTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config(['auth.providers.users.model' => User::class]);
    }

    public function testBasicFeatures()
    {
        $user = User::create(['name' => 'overtrue']);
        $post = Post::create(['title' => 'Hello world!']);

        $user->favorite($post);

        Event::assertDispatched(Favorited::class, function ($event) use ($user, $post) {
            return $event->favorite->favoriteable instanceof Post
                && $event->favorite->user instanceof User
                && $event->favorite->user->id === $user->id
                && $event->favorite->favoriteable->id === $post->id;
        });

        $this->assertTrue($user->hasFavorited($post));
        $this->assertTrue($post->isFavoritedBy($user));

        $user->unfavorite($post);

        Event::assertDispatched(Unfavorited::class, function ($event) use ($user, $post) {
            return $event->favorite->favoriteable instanceof Post
                && $event->favorite->user instanceof User
                && $event->favorite->user->id === $user->id
                && $event->favorite->favoriteable->id === $post->id;
        });
    }

    public function test_unfavorite_features()
    {
        $user1 = User::create(['name' => 'overtrue']);
        $user2 = User::create(['name' => 'allen']);
        $user3 = User::create(['name' => 'taylor']);

        $post = Post::create(['title' => 'Hello world!']);

        $user2->favorite($post);
        $user3->favorite($post);
        $user1->favorite($post);

        $user1->unfavorite($post);

        $this->assertFalse($user1->hasFavorited($post));
        $this->assertTrue($user2->hasFavorited($post));
        $this->assertTrue($user3->hasFavorited($post));
    }

    public function test_aggregations()
    {
        $user = User::create(['name' => 'overtrue']);

        $post1 = Post::create(['title' => 'Hello world!']);
        $post2 = Post::create(['title' => 'Hello everyone!']);
        $book1 = Book::create(['title' => 'Learn laravel.']);
        $book2 = Book::create(['title' => 'Learn symfony.']);

        $user->favorite($post1);
        $user->favorite($post2);
        $user->favorite($book1);
        $user->favorite($book2);

        $this->assertSame(4, $user->favorites()->count());
        $this->assertSame(2, $user->favorites()->withType(Book::class)->count());
    }

    public function test_object_favoriters()
    {
        $user1 = User::create(['name' => 'overtrue']);
        $user2 = User::create(['name' => 'allen']);
        $user3 = User::create(['name' => 'taylor']);

        $post = Post::create(['title' => 'Hello world!']);

        $user1->favorite($post);
        $user2->favorite($post);

        $this->assertCount(2, $post->favoriters);
        $this->assertSame('overtrue', $post->favoriters[0]['name']);
        $this->assertSame('allen', $post->favoriters[1]['name']);

        // start recording
        $sqls = $this->getQueryLog(function () use ($post, $user1, $user2, $user3) {
            $this->assertTrue($post->isFavoritedBy($user1));
            $this->assertTrue($post->isFavoritedBy($user2));
            $this->assertFalse($post->isFavoritedBy($user3));
        });

        $this->assertEmpty($sqls->all());
    }

    public function test_eager_loading()
    {
        $user = User::create(['name' => 'overtrue']);

        $post1 = Post::create(['title' => 'Hello world!']);
        $post2 = Post::create(['title' => 'Hello everyone!']);
        $book1 = Book::create(['title' => 'Learn laravel.']);
        $book2 = Book::create(['title' => 'Learn symfony.']);

        $user->favorite($post1);
        $user->favorite($post2);
        $user->favorite($book1);
        $user->favorite($book2);

        // start recording
        $sqls = $this->getQueryLog(function () use ($user) {
            $user->load('favorites.favoriteable');
        });

        $this->assertSame(3, $sqls->count());

        // from loaded relations
        $sqls = $this->getQueryLog(function () use ($user, $post1) {
            $user->hasFavorited($post1);
        });
        $this->assertEmpty($sqls->all());
    }

    public function test_eager_loading_error()
    {
        // hasFavorited
        $post1 = Post::create(['title' => 'post1']);
        $post2 = Post::create(['title' => 'post2']);

        $user = User::create(['name' => 'user']);

        $user->favorite($post2);

        $this->assertFalse($user->hasFavorited($post1));
        $this->assertTrue($user->hasFavorited($post2));

        $user->load('favorites');

        $this->assertFalse($user->hasFavorited($post1));
        $this->assertTrue($user->hasFavorited($post2));

        // isFavoritedBy
        $user1 = User::create(['name' => 'user1']);
        $user2 = User::create(['name' => 'user2']);

        $post = Post::create(['title' => 'Hello world!']);

        $user2->favorite($post);

        $this->assertFalse($post->isFavoritedBy($user1));
        $this->assertTrue($post->isFavoritedBy($user2));

        $post->load('favorites');

        $this->assertFalse($post->isFavoritedBy($user1));
        $this->assertTrue($post->isFavoritedBy($user2));
    }

    public function test_favoriter_can_attach_favorite_status_to_votable_collection()
    {
        /* @var \Tests\Post $post1 */
        $post1 = Post::create(['title' => 'Post title1']);
        /* @var \Tests\Post $post2 */
        $post2 = Post::create(['title' => 'Post title2']);
        /* @var \Tests\Post $post3 */
        $post3 = Post::create(['title' => 'Post title3']);

        /* @var \Tests\User $user */
        $user = User::create(['name' => 'overtrue']);

        $user->favorite($post1);
        $user->favorite($post2);

        // Model
        $post1->refresh();
        $this->assertNull($post1['has_favorited']);

        $user->attachFavoriteStatus($post1);
        $this->assertTrue($post1['has_favorited']);

        // Collection
        $posts = Post::oldest('id')->get();
        $this->assertSame($posts, $user->attachFavoriteStatus($posts));
        $this->assertTrue($posts[0]['has_favorited']);
        $this->assertTrue($posts[1]['has_favorited']);
        $this->assertFalse($posts[2]['has_favorited']);

        // LazyCollection
        $posts = Post::oldest('id')->cursor();
        $user->attachFavoriteStatus($posts);
        $posts = $posts->toArray();
        $this->assertTrue($posts[0]['has_favorited']);
        $this->assertTrue($posts[1]['has_favorited']);
        $this->assertFalse($posts[2]['has_favorited']);

        // Paginator
        $posts = Post::oldest('id')->paginate(20);
        $postsWithFavoriteStatus = $user->attachFavoriteStatus($posts);
        $this->assertSame($posts, $postsWithFavoriteStatus);
        $this->assertTrue($posts[0]['has_favorited']);
        $this->assertTrue($posts[1]['has_favorited']);
        $this->assertFalse($posts[2]['has_favorited']);

        // https://github.com/overtrue/laravel-favorite/issues/33
        $this->assertTrue(method_exists($postsWithFavoriteStatus, 'links'));

        // cursor paginator
        $posts = Post::oldest('id')->cursorPaginate(20);
        $this->assertSame($posts, $user->attachFavoriteStatus($posts));
        $this->assertTrue($posts[0]['has_favorited']);
        $this->assertTrue($posts[1]['has_favorited']);
        $this->assertFalse($posts[2]['has_favorited']);

        // array
        $posts = Post::oldest('id')->get()->all();
        $posts = $user->attachFavoriteStatus($posts);
        $this->assertTrue($posts[0]['has_favorited']);
        $this->assertTrue($posts[1]['has_favorited']);
        $this->assertFalse($posts[2]['has_favorited']);
    }


    protected function getQueryLog(\Closure $callback): \Illuminate\Support\Collection
    {
        $sqls = \collect([]);
        \DB::listen(function ($query) use ($sqls) {
            $sqls->push(['sql' => $query->sql, 'bindings' => $query->bindings]);
        });

        $callback();

        return $sqls;
    }
}
