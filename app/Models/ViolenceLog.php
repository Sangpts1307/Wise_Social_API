<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViolenceLog extends Model
{
    use HasFactory;

    protected $table = "violence_logs";
    protected $fillable = [
        'message_id',
        'post_id',
        'comment_id'
    ];
}
