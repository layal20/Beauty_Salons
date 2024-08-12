<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\HasPermissions;
use Laravel\Passport\HasApiTokens;

use Laravel\Sanctum\HasApiTokens as SanctumHasApiTokens;

class Admin extends Authenticatable
{
    use HasFactory, HasRoles, HasPermissions, HasApiTokens;

    protected $guard_name = "admin";

    protected $hidden = ['created_at', 'updated_at', 'password', 'super_admin_id'];
    protected $fillable = [
        'user_name',
        'password',
        'super_admin_id',
        'salon_id'
    ];

    /**
     * Get the salon that owns the admin
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'salon_id', 'id');
    }
    /**
     * Get the super_admin that owns the admin
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function super_admin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'super_admin_id', 'id');
    }

    /**
     * Get all of the product for the admin
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'admin_products', 'admin_id', 'product_id', 'id');
    }

    /**
     * Get all of the services for the admin
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'admin_services', 'admin_id', 'service_id', 'id');
    }
    /**
     * Get all of the employee for the admin
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'admin_id', 'id');
    }

    protected static function booted()
    {
        static::creating(function ($admin) {
            $admin->super_admin_id = 1;
        });
    }

  


}
