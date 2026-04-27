<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function product(Request $request, Product $product)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'max:1000'],
        ]);

        Review::create($data + [
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'shop_id' => $product->shop_id,
            'status' => 'approved',
        ]);

        $product->update(['rating' => round($product->reviews()->avg('rating'), 2)]);

        return back()->with('status', 'Product review submitted.');
    }

    public function shop(Request $request, Shop $shop)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'max:1000'],
        ]);

        Review::create($data + [
            'user_id' => $request->user()->id,
            'shop_id' => $shop->id,
            'status' => 'approved',
        ]);

        $shop->update(['rating' => round($shop->reviews()->avg('rating'), 2)]);

        return back()->with('status', 'Shop review submitted.');
    }
}
