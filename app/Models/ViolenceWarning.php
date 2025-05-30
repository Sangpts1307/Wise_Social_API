<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViolenceWarning extends Model
{
    use HasFactory;

    protected $table = "violence_warnings";

    protected $fillable = [
        'user_id',
        'infringe'
    ];
}
