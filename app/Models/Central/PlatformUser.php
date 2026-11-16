<?php

namespace App\Models\Central;

use Illuminate\Foundation\Auth\User as Authenticatable;

/** Staff of the platform (Fida Technologies), not a user of any Sarafi. */
class PlatformUser extends Authenticatable
{
    protected $connection = 'central';

    protected $fillable = ['name', 'email', 'password', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean'];
    }
}
