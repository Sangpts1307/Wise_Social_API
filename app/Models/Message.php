<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{

    const UNVIEW = 1;
    const VIEWED = 2;
    
    use HasFactory;

    protected $table = "messages";

    protected $fillable = [
        'user_id',
        'friend_id',
        'message',
        'is_view'
    ];
}
