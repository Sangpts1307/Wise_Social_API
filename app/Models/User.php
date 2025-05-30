<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // defind const instead of using 1 2 3 for better readability
    const ONLINE = 2;
    const OFFLINE = 3;
    const STATUS_ACTIVE = 1;
    const STATUS_BANNED = 0;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'location',
        'city',
        'avatar',
        'banner',
        'overview',
        'online_status',
        'status',
        'login_fail'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Relationship function get follower
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function follower()
    {
        return $this->hasMany('\App\Models\Follow', 'user_id', 'id')
            ->where('user_id', '<>', Auth::user()->id)
            ->where('follow_id', Auth::user()->id);
    }

    /**
     * Relationship function get following
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function follows()
    {
        return $this->hasMany(
            '\App\Models\Follow',
            'user_id',
            'id'
        )
            ->where('user_id', Auth::user()->id);
    }

    /**
     * Relationship function to get the user's experiences.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function experiences()
    {
        return $this->hasMany(
            '\App\Models\Experience',
            'user_id',
            'id'
        );
    }

    /**
     * Relationship function to get the user's skills.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function skills()
    {
        return $this->hasMany(
            '\App\Models\Skill',
            'user_id',
            'id',
        );
    }
}
