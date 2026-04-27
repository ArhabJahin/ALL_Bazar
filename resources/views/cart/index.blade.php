@extends('layouts.app')

@section('title', 'Cart')

@section('content')
<section class="page-head"><h1>Your cart</h1></section>
<section class="cart-layout">
    <div class="cart-list">
        @forelse($cartItems as $item)
            <div class="cart-item">
                <div class="product-art {{ $item['product']->category ? strtolower($item['product']->category->name) : 'default' }}">
                    <span>{{ strtoupper(substr($item['product']->name, 0, 2)) }}</span>
                </div>
                <div class="cart-item-main">
                    <div>
                    <strong>{{ $item['product']->name }}</strong>
                    <p>{{ optional($item['product']->shop)->name }} - Stock {{ $item['product']->stock }}</p>
                    <small>Tk {{ number_format($item['unit_price']) }} each</small>
                    </div>
                </div>
                <form method="post" action="{{ route('cart.update', $item['product']) }}" class="inline-actions">
                    @csrf
                    @method('PATCH')
                    <div class="qty-stepper" data-quantity-stepper>
                        <button class="qty-btn secondary-btn" type="button" data-step="-1">-</button>
                        <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="{{ $item['product']->stock }}">
                        <button class="qty-btn secondary-btn" type="button" data-step="1">+</button>
                    </div>
                    <button>Update</button>
                </form>
                <strong>Tk {{ number_format($item['line_total']) }}</strong>
                <form method="post" action="{{ route('cart.remove', $item['product']) }}">
                    @csrf
                    @method('DELETE')
                    <button class="danger-btn">Remove</button>
                </form>
            </div>
        @empty
            <div class="empty-state">Your cart is empty. Add products from the homepage or search results.</div>
        @endforelse
    </div>
    <aside class="summary-card">
        <h2>Order summary</h2>
        <p>Subtotal <strong>Tk {{ number_format($summary['subtotal'] ?? 0) }}</strong></p>
        <p>Delivery <strong>Tk {{ number_format($summary['delivery'] ?? 0) }}</strong></p>
        <p>Discount <strong>Tk {{ number_format($summary['discount'] ?? 0) }}</strong></p>
        <p>Total <strong>Tk {{ number_format($summary['total'] ?? 0) }}</strong></p>
        @if($cartItems->isNotEmpty())
            <a class="solid-btn full" href="{{ route('cart.checkout') }}">Checkout</a>
        @endif
    </aside>
</section>
@endsection
