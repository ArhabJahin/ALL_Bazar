@extends('layouts.app')

@section('title', $product['name'])

@section('content')
@php($galleryImages = collect($product['gallery_images'] ?? [])->filter()->values())
<section class="product-detail">
    <div class="zoom-gallery">
        <div class="zoom-image product-art {{ $product['image'] }}" data-product-gallery-main>
            @if($galleryImages->isNotEmpty())
                <img src="{{ $galleryImages->first() }}" alt="{{ $product['name'] }}">
            @else
                <span>{{ strtoupper(substr($product['category'], 0, 2)) }}</span>
            @endif
        </div>
        @if($galleryImages->isNotEmpty())
            <div class="image-zoom-pane" data-product-zoom-pane aria-hidden="true">
                <img src="{{ $galleryImages->first() }}" alt="">
            </div>
        @endif
        @if($galleryImages->isNotEmpty())
            <div class="thumb-row" aria-label="Product image gallery">
                @foreach($galleryImages as $imageUrl)
                    <button type="button" class="{{ $loop->first ? 'is-active' : '' }}" data-gallery-thumb data-image-src="{{ $imageUrl }}" aria-label="Show product image {{ $loop->iteration }}">
                        <img src="{{ $imageUrl }}" alt="{{ $product['name'] }} image {{ $loop->iteration }}">
                    </button>
                @endforeach
            </div>
        @endif
    </div>
    <div class="detail-copy">
        <p class="eyebrow">{{ $product['category'] }} - {{ $product['type'] }}</p>
        <h1>{{ $product['name'] }}</h1>
        <div class="price-row big">
            <strong>Tk {{ number_format($product['price']) }}</strong>
            @if($product['old_price']) <del>Tk {{ number_format($product['old_price']) }}</del> @endif
        </div>
        <p>{{ optional($productModel)->description ?: 'Available from a verified AllBazar local shop.' }}</p>
        <div class="spec-grid">
            <span>Stock <strong>{{ $product['stock'] }}</strong></span>
            <span>Rating <strong>{{ $product['rating'] }}</strong></span>
            <span>Delivery <strong>Tk {{ $product['delivery'] }}</strong></span>
            <span>ETA <strong>{{ $product['eta'] }}</strong></span>
        </div>
        <div class="shop-info-card">
            <a class="shop-profile-link" href="{{ route('shops.show', $product['shop_slug']) }}"><span>{{ strtoupper(substr($product['shop'], 0, 1)) }}</span>{{ $product['shop'] }}</a>
            <div class="meta-grid">
                <span>{{ $product['distance'] }} km away</span>
                <span>Total Tk {{ number_format(($product['total_cost'] ?? ($product['price'] + $product['delivery']))) }}</span>
                <span class="verified-badge">Verified shop</span>
            </div>
        </div>
        <div class="purchase-panel">
            <strong>Ready for fast local delivery</strong>
            <div class="buy-actions">
                <form method="post" action="{{ route('cart.add', $product['id']) }}">
                    @csrf
                    <input type="hidden" name="quantity" value="1">
                    <button>Add to cart</button>
                </form>
                <form method="post" action="{{ route('cart.add', $product['id']) }}">
                    @csrf
                    <input type="hidden" name="quantity" value="1">
                    <input type="hidden" name="buy_now" value="1">
                    <button class="buy-now">Buy now</button>
                </form>
                @auth
                    <form method="post" action="{{ route('wishlist.toggle', $product['id']) }}">
                        @csrf
                        <button class="icon-btn" type="submit">Save</button>
                    </form>
                @else
                    <a class="icon-btn" href="{{ route('login') }}">Save</a>
                @endauth
            </div>
        </div>
    </div>
</section>

<section class="section detail-tabs">
    <h2>Specifications</h2>
    <ul>
        <li>Category: {{ $product['category'] }}</li>
        <li>Type: {{ $product['type'] }}</li>
        <li>Delivery charge: Tk {{ $product['delivery'] }}</li>
    </ul>

    <h2>Customer comments</h2>
    @if($productModel && $productModel->reviews->count())
        @foreach($productModel->reviews as $review)
            <div class="review-card">{{ $review->rating }}/5 - {{ $review->comment }} <small>by {{ optional($review->user)->name ?: 'Customer' }}</small></div>
        @endforeach
    @else
        <div class="review-card">No reviews yet.</div>
    @endif

    @auth
        @if($productModel)
            <form class="panel-form" method="post" action="{{ route('reviews.products.store', $productModel) }}">
                @csrf
                <h2>Write a product review</h2>
                <select name="rating">
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Good</option>
                    <option value="3">3 - Average</option>
                    <option value="2">2 - Poor</option>
                    <option value="1">1 - Bad</option>
                </select>
                <textarea name="comment" placeholder="Share your experience"></textarea>
                <button>Submit review</button>
            </form>
        @endif
    @else
        <p><a href="{{ route('login') }}">Login</a> to write a review.</p>
    @endauth
</section>

<section class="section">
    <div class="section-title"><h2>Related products</h2></div>
    <div class="product-grid">
        @foreach($related as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>

@if($recentlyViewed->count())
<section class="section">
    <div class="section-title"><h2>Recently viewed</h2></div>
    <div class="product-grid">
        @foreach($recentlyViewed as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endif
@endsection
