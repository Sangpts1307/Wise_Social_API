<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{

    const UNVIEW = 1;
    const VIEWED = 2;
    const STATUS_WAIT = 'wait';
    const STATUS_DONE = 'done';
    const STATUS_FAIL = 'failed';

    use HasFactory;

    protected $table = "notifications";

    protected $fillable = [
        'user_id',
        'actor_id',
        'content',
        'is_view',
        'status',
    ];
}
