@extends('layouts.app')

@section('title', 'Home')

@section('content')
@php
    $productCount = collect($products)->count();
    $shopCount = collect($shops)->count();
    $startingPrice = collect($products)->min('price') ?? 0;
@endphp

<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">Bangladesh local-shop marketplace</p>
        <h1>AllBazar</h1>
        <p>Search once and compare nearby shops by price, stock, delivery time, customer rating, and distance.</p>
        <form class="hero-search" action="{{ route('search') }}" method="get">
            <input name="q" placeholder="Try: Redmi cover, rice, panjabi" data-suggestions>
            <button type="submit">Search products</button>
        </form>
        <div class="quick-category-row" aria-label="Quick category shortcuts">
            @foreach(collect($categories)->take(5) as $category)
                <a style="--tile-color: {{ $category['color'] }}" href="{{ route('products.browse', ['category' => $category['name']]) }}">
                    <span>{{ substr($category['name'], 0, 1) }}</span>
                    {{ $category['name'] }}
                </a>
            @endforeach
        </div>
        <div class="hero-actions">
            <a class="solid-btn" href="{{ route('products.browse') }}">Browse products</a>
            <a class="solid-btn" href="{{ route('advanced-search') }}">Advanced filters</a>
            <a class="ghost-btn" href="{{ route('shops.map') }}">Browse shops</a>
        </div>
        <div class="hero-metrics" aria-label="Marketplace highlights">
            <div><strong>{{ $shopCount }}+</strong><span>local shops</span></div>
            <div><strong>{{ $productCount }}+</strong><span>listed products</span></div>
            <div><strong>Tk {{ number_format($startingPrice) }}</strong><span>starting price</span></div>
        </div>
    </div>

    <div class="hero-panel marketplace-preview">
        <div class="preview-toolbar">
            <span class="live-dot"></span>
            <strong>Nearby offers</strong>
            <small>Bashundhara R/A</small>
        </div>
        @foreach($nearbyProducts as $product)
            <a class="mini-result" href="{{ route('products.show', $product['slug']) }}">
                <span>{{ $loop->iteration }}</span>
                <strong>{{ $product['name'] }}</strong>
                <em>Tk {{ number_format($product['price']) }} - {{ $product['distance'] }} km - {{ $product['eta'] ?? 'Today' }}</em>
            </a>
        @endforeach
        <div class="delivery-card">
            <div>
                <strong>Smart checkout</strong>
                <span>Delivery charge and ETA are shown before you order.</span>
            </div>
            <a href="{{ route('cart.index') }}">View cart</a>
        </div>
    </div>
</section>

<section class="feature-strip" aria-label="Shopping benefits">
    <div class="feature-card">
        <span>01</span>
        <strong>Compare local offers</strong>
        <p>Same product, multiple shops, one ranked result list.</p>
    </div>
    <div class="feature-card">
        <span>02</span>
        <strong>Filter by area</strong>
        <p>Find shops close to home, office, campus, or pickup point.</p>
    </div>
    <div class="feature-card">
        <span>03</span>
        <strong>See real costs</strong>
        <p>Price, delivery, stock, rating, and ETA stay visible.</p>
    </div>
    <div class="feature-card">
        <span>04</span>
        <strong>Buy with confidence</strong>
        <p>Verified sellers, reviews, wishlist, and order tracking.</p>
    </div>
</section>

<section class="section deal-section">
    <div class="deal-banner">
        <div class="deal-copy">
            <p class="eyebrow">Today spotlight</p>
            <h2>Better deals from shops near you</h2>
            <p>Start from popular searches, then narrow the result by distance, rating, delivery fee, and stock.</p>
            <div class="deal-pills">
                <a href="{{ route('advanced-search', ['sort' => 'cheapest']) }}">Cheapest first</a>
                <a href="{{ route('advanced-search', ['sort' => 'nearest']) }}">Nearest shops</a>
                <a href="{{ route('advanced-search', ['sort' => 'best-rated']) }}">Best rated</a>
            </div>
        </div>
        <div class="deal-stack" aria-label="Featured deals">
            @foreach($popularProducts->take(3) as $product)
                <a class="deal-row" href="{{ route('products.show', $product['slug']) }}">
                    <span class="deal-rank">{{ $loop->iteration }}</span>
                    <span>
                        <strong>{{ $product['name'] }}</strong>
                        <small>{{ $product['shop'] }} - {{ $product['distance'] }} km</small>
                    </span>
                    <em>Tk {{ number_format($product['price']) }}</em>
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="section-title">
        <h2>Product categories</h2>
        <a href="{{ route('advanced-search') }}">Advanced filters</a>
    </div>
    <div class="category-grid">
        @foreach($categories as $category)
            <a class="category-tile" style="--tile-color: {{ $category['color'] }}" href="{{ route('advanced-search', ['category' => $category['name']]) }}">
                <span>{{ substr($category['name'], 0, 1) }}</span>
                <strong>{{ $category['name'] }}</strong>
            </a>
        @endforeach
    </div>
</section>

<section class="section">
    <div class="section-title">
        <h2>Featured shops</h2>
        <a href="{{ route('shops.map') }}">Open shop map</a>
    </div>
    <div class="shop-row">
        @foreach($shops as $shop)
            <a class="shop-card" href="{{ route('shops.show', $shop['slug']) }}">
                <span class="shop-logo">{{ $shop['logo'] }}</span>
                <strong>{{ $shop['name'] }}</strong>
                <small>{{ $shop['area'] }} - {{ $shop['distance'] }} km - Rating {{ $shop['rating'] }}</small>
            </a>
        @endforeach
    </div>
</section>

<section class="section">
    <div class="section-title">
        <h2>Popular products</h2>
        <span>Cheaper and nearer products are shown first in search.</span>
    </div>
    <div class="product-grid">
        @foreach($popularProducts as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>

<section class="section">
    <div class="section-title">
        <h2>Nearby products</h2>
        <span>Ranked by customer location</span>
    </div>
    <div class="product-grid">
        @foreach($nearbyProducts as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endsection
