@php
    $rank = $loop->iteration ?? 1;
    $hasDeal = !empty($product['old_price']) && $product['old_price'] > $product['price'];
    $discount = $hasDeal ? round((($product['old_price'] - $product['price']) / $product['old_price']) * 100) : null;
    $stockPercent = min(100, max(8, (int) ($product['stock'] ?? 0) * 2));
@endphp
<article class="product-card rank-{{ min($rank, 6) }}">
    <a class="product-art {{ $product['image'] ?? 'default' }}" href="{{ route('products.show', $product['slug']) }}">
        @if(!empty($product['image_url']))
            <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}">
        @else
            <span>{{ strtoupper(substr($product['category'], 0, 2)) }}</span>
        @endif
        <span class="product-badges">
            @if($rank === 1)
                <span>Best match</span>
            @endif
            @if($hasDeal)
                <span>{{ $discount }}% off</span>
            @endif
            <span>{{ $product['eta'] ?? 'Today' }}</span>
        </span>
    </a>
    <div class="card-body">
        <div class="card-kicker">{{ $product['category'] }} - {{ $product['type'] }}</div>
        <a class="card-title" href="{{ route('products.show', $product['slug']) }}">{{ $product['name'] }}</a>
        <div class="price-row">
            <strong>Tk {{ number_format($product['price']) }}</strong>
            @if(!empty($product['old_price']))
                <del>Tk {{ number_format($product['old_price']) }}</del>
            @endif
        </div>
        <div class="stock-meter" aria-label="{{ $product['stock'] ?? 0 }} in stock">
            <span style="width: {{ $stockPercent }}%"></span>
        </div>
        <a class="shop-mini" href="{{ route('shops.show', $product['shop_slug']) }}">
            <span>{{ strtoupper(substr($product['shop'], 0, 1)) }}</span>{{ $product['shop'] }}
        </a>
        <div class="meta-grid">
            <span>{{ $product['distance'] }} km away</span>
            <span>Rating {{ $product['rating'] }}</span>
            <span>Delivery Tk {{ $product['delivery'] }}</span>
            <span>Total Tk {{ number_format($product['total_cost'] ?? ($product['price'] + $product['delivery'])) }}</span>
            <span>{{ $product['stock'] ?? 0 }} in stock</span>
        </div>
        <div class="card-actions">
            <form method="post" action="{{ route('cart.add', $product['id']) }}" class="cart-action">
                @csrf
                <input type="hidden" name="quantity" value="1">
                <button type="submit">Add to cart</button>
            </form>
            <a class="secondary-btn card-link" href="{{ route('products.show', $product['slug']) }}">Details</a>
            @auth
                <form method="post" action="{{ route('wishlist.toggle', $product['id']) }}">
                    @csrf
                    <button class="icon-btn" type="submit" title="Wishlist">Save</button>
                </form>
            @else
                <a class="icon-btn" href="{{ route('login') }}" title="Login to save">Save</a>
            @endauth
        </div>
    </div>
</article>
