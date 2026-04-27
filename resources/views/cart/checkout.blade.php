@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
<section class="page-head"><h1>Checkout</h1></section>
<section class="search-layout">
    <form class="filter-panel wide checkout-form" method="post" action="{{ route('cart.place-order') }}">
        @csrf
        <div class="checkout-section">
            <h2>Delivery address</h2>
            <label>Recipient name <input name="recipient_name" value="{{ old('recipient_name', auth()->user()->name ?? '') }}"></label>
            <label>Phone <input name="phone" value="{{ old('phone', auth()->user()->phone ?? '') }}"></label>
            <label>Delivery address <textarea name="address_line">{{ old('address_line') }}</textarea></label>
            <div class="two-col">
                <label>Area <input name="area" value="{{ old('area', auth()->user()->area ?? '') }}"></label>
                <label>City <input name="city" value="{{ old('city', 'Dhaka') }}"></label>
            </div>
        </div>

        <div class="checkout-section">
            <h2>Payment method</h2>
            <div class="payment-grid">
                @foreach(['Cash on Delivery', 'Mobile Banking', 'Card'] as $method)
                    <label class="payment-card">
                        <input type="radio" name="payment_method" value="{{ $method }}" @checked(old('payment_method', 'Cash on Delivery') === $method)>
                        <span>{{ $method }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="checkout-section">
            <h2>Order notes</h2>
            <label>Notes <textarea name="notes">{{ old('notes') }}</textarea></label>
        </div>
        <button>Place order</button>
    </form>
    <aside class="summary-card">
        <h2>Order summary</h2>
        @foreach($cartItems as $item)
            <p>{{ $item['product']->name }} x {{ $item['quantity'] }} <strong>Tk {{ number_format($item['line_total']) }}</strong></p>
        @endforeach
        <p>Subtotal <strong>Tk {{ number_format($summary['subtotal']) }}</strong></p>
        <p>Delivery <strong>Tk {{ number_format($summary['delivery']) }}</strong></p>
        <p>Total <strong>Tk {{ number_format($summary['total']) }}</strong></p>
        <h2>Tracking flow</h2>
        @foreach(['Pending', 'Accepted', 'Processing', 'Out for Delivery', 'Delivered', 'Cancelled'] as $status)
            <div class="status-step">{{ $status }}</div>
        @endforeach
    </aside>
</section>
@endsection
