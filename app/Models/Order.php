<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const STATUSES = ['Pending', 'Accepted', 'Processing', 'Out for Delivery', 'Delivered', 'Cancelled'];

    protected $fillable = [
        'user_id', 'address_id', 'order_number', 'status', 'subtotal', 'delivery_charge',
        'discount_total', 'grand_total', 'payment_method', 'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }
}
