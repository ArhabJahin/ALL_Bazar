<?php

namespace Database\Seeders;

use App\Models\AdminPermission;
use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Review;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = collect([
            ['name' => 'customer', 'label' => 'Customer'],
            ['name' => 'shop_owner', 'label' => 'Shop Owner'],
            ['name' => 'admin', 'label' => 'Main Admin'],
            ['name' => 'co_admin', 'label' => 'Co-admin'],
        ])->mapWithKeys(fn ($role) => [$role['name'] => Role::create($role)]);

        $admin = User::create([
            'role_id' => $roles['admin']->id,
            'name' => 'AllBazar Admin',
            'email' => 'admin@allbazar.test',
            'phone' => '01700000001',
            'password' => Hash::make('password'),
            'area' => 'Dhaka',
        ]);

        $coAdmin = User::create([
            'role_id' => $roles['co_admin']->id,
            'name' => 'Content Co-admin',
            'email' => 'coadmin@allbazar.test',
            'phone' => '01700000002',
            'password' => Hash::make('password'),
            'area' => 'Dhanmondi',
        ]);

        $customer = User::create([
            'role_id' => $roles['customer']->id,
            'name' => 'Sample Customer',
            'email' => 'customer@allbazar.test',
            'phone' => '01700000003',
            'password' => Hash::make('password'),
            'area' => 'Bashundhara R/A',
            'latitude' => 23.8151,
            'longitude' => 90.4255,
        ]);

        $owner = User::create([
            'role_id' => $roles['shop_owner']->id,
            'name' => 'Sample Shop Owner',
            'email' => 'owner@allbazar.test',
            'phone' => '01700000004',
            'password' => Hash::make('password'),
            'area' => 'Bashundhara R/A',
        ]);

        foreach (['manage_shops', 'manage_products', 'manage_orders', 'manage_users', 'manage_content'] as $permission) {
            AdminPermission::create(['user_id' => $coAdmin->id, 'permission' => $permission, 'allowed' => true]);
        }

        $categories = collect(['Grocery', 'Electronics', 'Fashion', 'Home', 'Beauty', 'Medicine'])
            ->mapWithKeys(fn ($name) => [$name => Category::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $name.' products from verified local shops.',
            ])]);

        $shops = collect([
            ['name' => 'Bashundhara Gadget Hub', 'area' => 'Bashundhara R/A', 'phone' => '01711000001', 'rating' => 4.8, 'address' => 'Block C, Bashundhara R/A, Dhaka', 'latitude' => 23.8151, 'longitude' => 90.4255],
            ['name' => 'Dhanmondi Daily Mart', 'area' => 'Dhanmondi', 'phone' => '01711000002', 'rating' => 4.6, 'address' => 'Road 8A, Dhanmondi, Dhaka', 'latitude' => 23.7465, 'longitude' => 90.3760],
            ['name' => 'Chawk Fashion Corner', 'area' => 'Chawkbazar', 'phone' => '01711000003', 'rating' => 4.4, 'address' => 'Chawkbazar, Old Dhaka', 'latitude' => 23.7162, 'longitude' => 90.3955],
        ])->mapWithKeys(fn ($shop) => [$shop['name'] => Shop::create($shop + [
            'owner_id' => $owner->id,
            'slug' => Str::slug($shop['name']),
            'description' => 'Verified AllBazar shop with local delivery.',
            'status' => 'approved',
        ])]);

        $products = [
            ['shop' => 'Bashundhara Gadget Hub', 'category' => 'Electronics', 'name' => 'Redmi Note 13 Cover', 'price' => 180, 'discount_price' => 160, 'stock' => 34, 'rating' => 4.7],
            ['shop' => 'Chawk Fashion Corner', 'category' => 'Electronics', 'name' => 'Redmi Note 13 Cover', 'price' => 210, 'discount_price' => null, 'stock' => 12, 'rating' => 4.3],
            ['shop' => 'Dhanmondi Daily Mart', 'category' => 'Grocery', 'name' => 'Premium Miniket Rice 5kg', 'price' => 430, 'discount_price' => 410, 'stock' => 52, 'rating' => 4.8],
            ['shop' => 'Chawk Fashion Corner', 'category' => 'Fashion', 'name' => 'Cotton Panjabi', 'price' => 950, 'discount_price' => 875, 'stock' => 9, 'rating' => 4.5],
        ];

        foreach ($products as $row) {
            $product = Product::create([
                'shop_id' => $shops[$row['shop']]->id,
                'category_id' => $categories[$row['category']]->id,
                'name' => $row['name'],
                'slug' => Str::slug($row['name'].' '.$row['shop']),
                'type' => $row['category'],
                'price' => $row['price'],
                'discount_price' => $row['discount_price'],
                'description' => 'Sample product listing for AllBazar marketplace.',
                'specifications' => ['origin' => 'Bangladesh', 'warranty' => 'Shop policy'],
                'stock' => $row['stock'],
                'rating' => $row['rating'],
                'is_featured' => true,
                'published_at' => now(),
            ]);

            ProductImage::create([
                'product_id' => $product->id,
                'path' => 'products/sample-'.$product->id.'.jpg',
                'alt_text' => $product->name,
            ]);

            Review::create([
                'user_id' => $customer->id,
                'product_id' => $product->id,
                'shop_id' => $product->shop_id,
                'rating' => 5,
                'comment' => 'Good product and reliable local delivery.',
            ]);
        }

        $address = Address::create([
            'user_id' => $customer->id,
            'label' => 'Home',
            'recipient_name' => $customer->name,
            'phone' => $customer->phone,
            'address_line' => 'House 12, Road 4',
            'area' => 'Bashundhara R/A',
            'city' => 'Dhaka',
            'is_default' => true,
        ]);

        $firstProduct = Product::first();
        Wishlist::create(['user_id' => $customer->id, 'product_id' => $firstProduct->id]);

        $order = Order::create([
            'user_id' => $customer->id,
            'address_id' => $address->id,
            'order_number' => 'AB-'.now()->format('Ymd').'-1001',
            'status' => 'Processing',
            'subtotal' => 180,
            'delivery_charge' => 49,
            'grand_total' => 229,
            'payment_method' => 'Cash on Delivery',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $firstProduct->id,
            'shop_id' => $firstProduct->shop_id,
            'quantity' => 1,
            'unit_price' => 180,
            'total_price' => 180,
        ]);
    }
}
