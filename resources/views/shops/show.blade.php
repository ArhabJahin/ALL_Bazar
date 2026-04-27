@extends('layouts.app')

@section('title', $shop['name'])

@section('content')
<section class="shop-hero" style="--shop-banner: {{ $shop['banner'] }}">
    <div class="shop-logo large">{{ $shop['logo'] }}</div>
    <div>
        <p class="eyebrow">{{ $shop['area'] }} - {{ $shop['distance'] }} km</p>
        <div class="shop-title-row">
            <h1>{{ $shop['name'] }}</h1>
            <span class="verified-badge">Verified shop</span>
        </div>
        <p>{{ $shop['description'] }}</p>
        <div class="meta-grid">
            <span>Rating {{ $shop['rating'] }}</span>
            <span>Delivery available</span>
            <span>{{ $shop['phone'] }}</span>
            <span>{{ $shop['address'] }}</span>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-title">
        <h2>Shop products</h2>
        <span>{{ $shopProducts->count() }} listed items</span>
    </div>
    <div class="product-grid">
        @foreach($shopProducts as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>

<section class="section split">
    <div>
        <h2>Categories</h2>
        <div class="chip-row">
            @foreach($shopProducts->pluck('category')->unique() as $category)
                <span>{{ $category }}</span>
            @endforeach
        </div>
        <h2>Reviews</h2>
        @if($shopModel && $shopModel->reviews->count())
            @foreach($shopModel->reviews as $review)
                <div class="review-card">{{ $review->rating }}/5 - {{ $review->comment }} <small>by {{ optional($review->user)->name ?: 'Customer' }}</small></div>
            @endforeach
        @else
            <div class="review-card">No shop reviews yet.</div>
        @endif

        @auth
            @if($shopModel)
                <form class="panel-form" method="post" action="{{ route('reviews.shops.store', $shopModel) }}">
                    @csrf
                    <h2>Write a shop review</h2>
                    <select name="rating">
                        <option value="5">5 - Excellent</option>
                        <option value="4">4 - Good</option>
                        <option value="3">3 - Average</option>
                        <option value="2">2 - Poor</option>
                        <option value="1">1 - Bad</option>
                    </select>
                    <textarea name="comment" placeholder="Share your shop experience"></textarea>
                    <button>Submit review</button>
                </form>
            @endif
        @else
            <p><a href="{{ route('login') }}">Login</a> to write a shop review.</p>
        @endauth
    </div>
    <div class="map-card">
        <strong>Shop location</strong>
        <div class="map-pin" style="left: {{ 30 + ($shop['id'] * 12) }}%; top: {{ 28 + ($shop['id'] * 10) }}%">{{ $shop['logo'] }}</div>
    </div>
</section>
@endsection
