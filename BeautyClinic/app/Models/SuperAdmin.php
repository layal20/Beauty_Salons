<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\HasPermissions;
use Laravel\Passport\HasApiTokens;

class SuperAdmin extends Authenticatable
{
    use HasFactory, HasRoles, HasPermissions, HasApiTokens;
    protected $guard_name = "super_admin";
    protected $hidden = ['password', 'created_at', 'deleted_at'];
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Get all of the salon for the super_admin
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function salons(): HasMany
    {
        return $this->hasMany(Salon::class, 'super_admin_id', 'id');
    }


    /**
     * Get all of the admins for the super_admin
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class, 'admin_id', 'id');
    }
}
