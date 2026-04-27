@extends('layouts.app')

@section('title', 'Customer Account')

@section('content')
<section class="dashboard">
    <h1>Customer account</h1>
    <nav class="dashboard-tabs" aria-label="Customer dashboard sections">
        <a href="#profile-settings">Profile</a>
        <a href="#saved-addresses">Addresses</a>
        <a href="#order-history">Orders</a>
        <a href="#wishlist">Wishlist</a>
        <a href="#your-reviews">Reviews</a>
    </nav>
    <div class="dashboard-grid">
        <div><h2>Profile</h2><p>{{ $user->name }}<br>{{ $user->email }}<br>{{ $user->phone }}</p></div>
        <div><h2>Saved addresses</h2><p>{{ $user->addresses->count() }} saved address{{ $user->addresses->count() === 1 ? '' : 'es' }}</p></div>
        <div><h2>Wishlist</h2><p>{{ $wishlists->count() }} saved product{{ $wishlists->count() === 1 ? '' : 's' }}</p></div>
    </div>
</section>

<section class="section" id="profile-settings">
    <div class="section-title"><h2>Profile settings</h2></div>
    <form class="panel-form" method="post" action="{{ route('account.profile.update') }}">
        @csrf
        @method('PATCH')
        <div class="two-col">
            <input name="name" value="{{ old('name', $user->name) }}" placeholder="Name">
            <input name="phone" value="{{ old('phone', $user->phone) }}" placeholder="Phone">
        </div>
        <div class="two-col">
            <input name="area" value="{{ old('area', $user->area) }}" placeholder="Area">
            <input name="latitude" value="{{ old('latitude', $user->latitude) }}" placeholder="Latitude">
        </div>
        <input name="longitude" value="{{ old('longitude', $user->longitude) }}" placeholder="Longitude">
        <div class="two-col">
            <input name="current_password" type="password" placeholder="Current password">
            <input name="password" type="password" placeholder="New password">
        </div>
        <input name="password_confirmation" type="password" placeholder="Confirm new password">
        <button>Save profile</button>
    </form>
</section>

<section class="section" id="saved-addresses">
    <div class="section-title"><h2>Saved addresses</h2></div>
    <form class="panel-form" method="post" action="{{ route('account.addresses.store') }}">
        @csrf
        <div class="two-col">
            <input name="label" placeholder="Label, e.g. Home">
            <input name="recipient_name" value="{{ $user->name }}" placeholder="Recipient name">
        </div>
        <div class="two-col">
            <input name="phone" value="{{ $user->phone }}" placeholder="Phone">
            <input name="area" value="{{ $user->area }}" placeholder="Area">
        </div>
        <textarea name="address_line" placeholder="Full address"></textarea>
        <div class="two-col">
            <input name="city" value="Dhaka" placeholder="City">
            <input name="postal_code" placeholder="Postal code">
        </div>
        <label class="check-row"><input type="checkbox" name="is_default" value="1"> Use as default address</label>
        <button>Add address</button>
    </form>

    <div class="table-wrap mt">
        <table class="data-table">
            <thead><tr><th>Label</th><th>Address</th><th>Phone</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($user->addresses as $address)
                <tr>
                    <td>{{ $address->label }} @if($address->is_default)<small>Default</small>@endif</td>
                    <td>{{ $address->address_line }}, {{ $address->area }}, {{ $address->city }}</td>
                    <td>{{ $address->phone }}</td>
                    <td>
                        <form method="post" action="{{ route('account.addresses.destroy', $address) }}">
                            @csrf
                            @method('DELETE')
                            <button class="danger-btn">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No saved addresses yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="section" id="order-history">
    <div class="section-title"><h2>Order history</h2></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Order</th><th>Status</th><th>Total</th><th>Payment</th><th>Date</th></tr></thead>
            <tbody>
                @forelse($user->orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->status }}</td>
                        <td>Tk {{ number_format($order->grand_total) }}</td>
                        <td>{{ $order->payment_method }}</td>
                        <td>{{ $order->created_at->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="section" id="wishlist">
    <div class="section-title"><h2>Wishlist</h2></div>
    <div class="product-grid">
        @forelse($wishlists as $wishlist)
            @if($wishlist->product)
                @php($product = [
                    'id' => $wishlist->product->id,
                    'name' => $wishlist->product->name,
                    'slug' => $wishlist->product->slug,
                    'type' => $wishlist->product->type ?: optional($wishlist->product->category)->name,
                    'category' => optional($wishlist->product->category)->name ?: 'Product',
                    'price' => $wishlist->product->discount_price ?: $wishlist->product->price,
                    'old_price' => $wishlist->product->discount_price ? $wishlist->product->price : null,
                    'stock' => $wishlist->product->stock,
                    'rating' => $wishlist->product->rating,
                    'shop' => optional($wishlist->product->shop)->name ?: 'Shop',
                    'shop_slug' => optional($wishlist->product->shop)->slug ?: 'shop',
                    'distance' => 0,
                    'delivery' => optional($wishlist->product->shop)->delivery_base_charge ?: 60,
                    'image' => 'default',
                    'eta' => 'Today',
                ])
                @include('partials.product-card', ['product' => $product])
            @endif
        @empty
            <div class="empty-state">No wishlist items yet.</div>
        @endforelse
    </div>
</section>

<section class="section" id="your-reviews">
    <div class="section-title"><h2>Your reviews</h2></div>
    @forelse($reviews as $review)
        <div class="review-card">{{ $review->rating }}/5 - {{ $review->comment }} <small>{{ optional($review->product)->name ?: optional($review->shop)->name }}</small></div>
    @empty
        <div class="review-card">No reviews yet.</div>
    @endforelse
</section>
@endsection
