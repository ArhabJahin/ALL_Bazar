<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function toggle(Request $request, Product $product)
    {
        $existing = Wishlist::where('user_id', $request->user()->id)->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();
            return back()->with('status', 'Product removed from wishlist.');
        }

        Wishlist::create(['user_id' => $request->user()->id, 'product_id' => $product->id]);

        return back()->with('status', 'Product saved to wishlist.');
    }
}
