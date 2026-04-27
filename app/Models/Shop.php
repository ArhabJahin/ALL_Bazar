<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = [
        'owner_id', 'name', 'slug', 'logo', 'banner', 'address', 'area', 'phone',
        'description', 'latitude', 'longitude', 'rating', 'delivery_base_charge',
        'delivery_per_km_charge', 'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'rating' => 'decimal:2',
        'delivery_base_charge' => 'decimal:2',
        'delivery_per_km_charge' => 'decimal:2',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
