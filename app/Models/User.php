<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $guarded = [];

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
        'password' => 'hashed',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'photo_url',
        'name',
        'store_id'
    ];

    /**
     * @return int
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function image(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the profile photo URL attribute.
     *
     * @return string
     */
    public function getPhotoUrlAttribute()
    {
        if ($this->image) {
            return $this->image->src;
        } else {
            return vsprintf('https://www.gravatar.com/avatar/%s.jpg?s=200&d=%s', [
                md5(strtolower($this->username)),
                $this->name ? urlencode("https://ui-avatars.com/api/$this->name") : 'mp',
            ]);
        }
    }

    public function getNameAttribute() {
        $name = [];
        if($this->first_name) $name[] = $this->first_name;
        if($this->last_name) $name[] = $this->last_name;
        return $name ? implode(' ', $name) : $this->username;
    }

    public function getStoreIdAttribute() {
        if (!$this->company) return '';
        $firstStore = $this->company->stores()->first();
        return $firstStore ? $firstStore->id : '';
    }
}
