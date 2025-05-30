<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserView extends Model
{
    use HasFactory;

    protected $table = "user_views";
    protected $fillable = [
        'user_id',
        'view_id',
    ];
}
