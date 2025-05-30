<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $table = "comments";

    protected $fillable = [
        'user_id',
        'post_id',
        'comment',
        'parent_id',
    ];

    /**
     * Get the child comments (replies) for the comment.
     */
    public function child()
    {
        return $this->hasMany(
            '\App\Models\Comment',
            'parent_id',
            'id',
        );
    }

    /**
     * Get the author of the comment.
     */
    public function author()
    {
        return $this->hasOne(
            '\App\Models\User',
            'id',
            'user_id',
        );
    }
}
