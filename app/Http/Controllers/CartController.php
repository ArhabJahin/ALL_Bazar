<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = $this->cartItems();
        $summary = $this->summary($cartItems);

        return view('cart.index', compact('cartItems', 'summary'));
    }

    public function add(Request $request, Product $product)
    {
        if ($product->stock < 1) {
            return back()->withErrors(['cart' => $product->name.' is out of stock.']);
        }

        $quantity = max(1, (int) $request->input('quantity', 1));

        if ($request->user()) {
            $cart = Cart::firstOrNew(['user_id' => $request->user()->id, 'product_id' => $product->id]);
            $cart->quantity = min($product->stock, ($cart->exists ? $cart->quantity : 0) + $quantity);
            $cart->save();
        } else {
            $cart = session('cart', []);
            $cart[$product->id] = min($product->stock, ($cart[$product->id] ?? 0) + $quantity);
            session(['cart' => $cart]);
        }

        if ($request->boolean('buy_now')) {
            return redirect()->route('cart.checkout')->with('status', 'Product added. Complete checkout to place the order.');
        }

        return back()->with('status', 'Product added to cart.');
    }

    public function update(Request $request, Product $product)
    {
        if ($product->stock < 1) {
            $this->removeCartProduct($request, $product);

            return back()->withErrors(['cart' => $product->name.' is out of stock and was removed from your cart.']);
        }

        $quantity = max(1, min($product->stock, (int) $request->input('quantity', 1)));

        if ($request->user()) {
            Cart::updateOrCreate(
                ['user_id' => $request->user()->id, 'product_id' => $product->id],
                ['quantity' => $quantity]
            );
        } else {
            $cart = session('cart', []);
            $cart[$product->id] = $quantity;
            session(['cart' => $cart]);
        }

        return back()->with('status', 'Cart updated.');
    }

    public function remove(Request $request, Product $product)
    {
        $this->removeCartProduct($request, $product);

        return back()->with('status', 'Product removed from cart.');
    }

    public function checkout(Request $request)
    {
        $cartItems = $this->cartItems();
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Your cart is empty.']);
        }

        $summary = $this->summary($cartItems);
        $addresses = $request->user() ? $request->user()->addresses()->latest()->get() : collect();

        return view('cart.checkout', compact('cartItems', 'summary', 'addresses'));
    }

    public function placeOrder(Request $request)
    {
        $data = $request->validate([
            'recipient_name' => ['required', 'max:255'],
            'phone' => ['required', 'max:40'],
            'address_line' => ['required'],
            'area' => ['required', 'max:120'],
            'city' => ['required', 'max:120'],
            'payment_method' => ['required', 'in:Cash on Delivery,Mobile Banking,Card'],
            'notes' => ['nullable'],
        ]);

        $cartItems = $this->cartItems();
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Your cart is empty.']);
        }

        foreach ($cartItems as $item) {
            $item['product']->refresh();
            if ($item['product']->stock < $item['quantity']) {
                return redirect()->route('cart.index')
                    ->withErrors(['cart' => $item['product']->name.' does not have enough stock. Please update your cart.']);
            }
        }

        $summary = $this->summary($cartItems);
        $order = DB::transaction(function () use ($request, $data, $cartItems, $summary) {
            $address = null;
            if ($request->user()) {
                $address = Address::create([
                    'user_id' => $request->user()->id,
                    'label' => 'Checkout',
                    'recipient_name' => $data['recipient_name'],
                    'phone' => $data['phone'],
                    'address_line' => $data['address_line'],
                    'area' => $data['area'],
                    'city' => $data['city'],
                    'is_default' => false,
                ]);
            }

            $order = Order::create([
                'user_id' => optional($request->user())->id,
                'address_id' => optional($address)->id,
                'order_number' => 'AB-'.now()->format('Ymd-His').'-'.random_int(100, 999),
                'status' => 'Pending',
                'subtotal' => $summary['subtotal'],
                'delivery_charge' => $summary['delivery'],
                'discount_total' => $summary['discount'],
                'grand_total' => $summary['total'],
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($cartItems as $item) {
                $item['product']->refresh();

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'shop_id' => $item['product']->shop_id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['line_total'],
                ]);

                $item['product']->decrement('stock', $item['quantity']);
            }

            if ($request->user()) {
                Cart::where('user_id', $request->user()->id)->delete();
            } else {
                session()->forget('cart');
            }

            return $order;
        });

        $redirect = $request->user()
            ? redirect()->route('account.dashboard')
            : redirect()->route('home');

        return $redirect->with('status', 'Order '.$order->order_number.' placed successfully.');
    }

    private function cartItems()
    {
        if (auth()->check()) {
            $rows = Cart::with('product.shop')->where('user_id', auth()->id())->get();
            return $rows->map(fn (Cart $row) => $this->lineItem($row->product, $row->quantity))->filter();
        }

        $cart = session('cart', []);
        return Product::with('shop')->whereIn('id', array_keys($cart))->get()
            ->map(fn (Product $product) => $this->lineItem($product, $cart[$product->id] ?? 1))->filter();
    }

    private function lineItem(?Product $product, int $quantity): ?array
    {
        if (!$product) {
            return null;
        }

        if ($product->stock < 1) {
            return null;
        }

        $unitPrice = (float) ($product->discount_price ?: $product->price);
        $oldPrice = (float) $product->price;
        $quantity = max(1, min($quantity, $product->stock));

        return [
            'product' => $product,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'old_price' => $oldPrice,
            'line_total' => $unitPrice * $quantity,
            'delivery' => $product->shop ? (float) $product->shop->delivery_base_charge : 60,
        ];
    }

    private function summary($cartItems): array
    {
        $subtotal = $cartItems->sum('line_total');
        $delivery = $cartItems->groupBy(fn ($item) => $item['product']->shop_id)->sum(fn ($items) => $items->first()['delivery']);
        $discount = $cartItems->sum(fn ($item) => max(0, $item['old_price'] - $item['unit_price']) * $item['quantity']);

        return [
            'subtotal' => $subtotal,
            'delivery' => $delivery,
            'discount' => $discount,
            'total' => $subtotal + $delivery,
        ];
    }

    private function removeCartProduct(Request $request, Product $product): void
    {
        if ($request->user()) {
            Cart::where('user_id', $request->user()->id)->where('product_id', $product->id)->delete();

            return;
        }

        $cart = session('cart', []);
        unset($cart[$product->id]);
        session(['cart' => $cart]);
    }
}
