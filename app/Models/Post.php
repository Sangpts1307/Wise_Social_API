<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    const LIKE = 1;
    const UN_LIKE = 0;

    protected $table = "posts";

    protected $fillable = [
        'user_id',
        'content',
        'timeline_orders',
        'view_count',
        'images'
    ];

    /**
     * Get all of the comments for the Post
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(
            '\App\Models\Comment',
            'post_id',
            'id'
        );
    }

    /**
     * Get all of the likes for the Post
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function likes()
    {
        return $this->hasMany(
            '\App\Models\Like',
            'post_id',
            'id'
        );
    }

    /**
     * Get the author of the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function author()
    {
        return $this->hasOne(
            '\App\Models\User',
            'id',
            'user_id'
        );
    }

    /**
     * Get all of the favorite records for the Post
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function favorites()
    {
        return $this->hasMany(
            '\App\Models\Favorite',
            'post_id',
            'id'
        );
    }
}
