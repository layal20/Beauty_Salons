<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\HasPermissions;
use Laravel\Passport\HasApiTokens;

class Customer extends Authenticatable
{
    use HasFactory, HasRoles, HasPermissions, HasApiTokens;

    protected $hidden = ['created_at', 'updated_at', 'password'];


    protected $fillable = [
        'name',
        'email',
        'image',
        'password',
        'phone_number'
    ];
    protected $guard_name = "customer";
   
    /**
     * Get all of the carts for the Customer
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class, 'customer_id', 'id');
    }

    /**
     * Get all of the appointments for the Customer
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(AppointmentDetails::class, 'customer_id', 'id');
    }
}
