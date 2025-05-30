<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{

    const APPROVED = 2;
    // avoid using 0 to not confuse with boolean
    const UNAPPROVED = 1;

    use HasFactory;

    protected $table = "friends";

    protected $fillable = [
        'user_id',
        'friend_id',
        'approved',
    ];
}
