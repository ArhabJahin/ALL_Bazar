@extends('layouts.app')

@section('title', 'Products')

@section('content')
<section class="page-head">
    <p class="eyebrow">Products</p>
    <h1>Browse marketplace products</h1>
    <p>Products are ranked by normalized price and normalized shop distance, so cheaper nearby offers appear first by default.</p>
</section>

<section class="section products-browse">
    <form class="products-filter-panel" method="get" action="{{ route('products.browse') }}">
        <label>Search
            <input name="q" value="{{ request('q') }}" placeholder="Product, category, shop, area..." data-suggestions>
        </label>
        <label>Category
            <select name="category">
                <option value="">All categories</option>
                @foreach($categoryOptions as $category)
                    <option @selected(request('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
        </label>
        <label>Min price
            <input type="number" name="min_price" value="{{ request('min_price') }}" min="0">
        </label>
        <label>Max price
            <input type="number" name="max_price" value="{{ request('max_price') }}" min="0">
        </label>
        <label>Distance km
            <input type="number" name="distance" value="{{ request('distance') }}" min="1" step="0.1">
        </label>
        <label>Delivery max
            <input type="number" name="delivery" value="{{ request('delivery') }}" min="0">
        </label>
        <label>Rating
            <input type="number" name="rating" value="{{ request('rating') }}" min="0" max="5" step="0.1" placeholder="4.0">
        </label>
        <label>Sort
            <select name="sort">
                <option value="best-match" @selected(request('sort', 'best-match') === 'best-match')>Best match</option>
                <option value="cheapest" @selected(request('sort') === 'cheapest')>Cheapest first</option>
                <option value="nearest" @selected(request('sort') === 'nearest')>Nearest first</option>
                <option value="best-rated" @selected(request('sort') === 'best-rated')>Best rated</option>
                <option value="newest" @selected(request('sort') === 'newest')>Newest</option>
            </select>
        </label>
        <label class="check-row products-stock-filter">
            <input type="checkbox" name="available" value="1" @checked(request()->boolean('available'))>
            Available stock only
        </label>
        <div class="products-filter-actions">
            <button type="submit">Apply filters</button>
            <a class="secondary-btn" href="{{ route('products.browse') }}">Reset</a>
        </div>
    </form>

    @if(request()->hasAny(['q', 'category', 'min_price', 'max_price', 'distance', 'delivery', 'rating', 'available', 'sort']))
        <div class="active-filter-row" aria-label="Active filters">
            @if(request('q')) <span class="filter-chip">Search: {{ request('q') }}</span> @endif
            @if(request('category')) <span class="filter-chip">Category: {{ request('category') }}</span> @endif
            @if(request('min_price')) <span class="filter-chip">Min Tk {{ request('min_price') }}</span> @endif
            @if(request('max_price')) <span class="filter-chip">Max Tk {{ request('max_price') }}</span> @endif
            @if(request('distance')) <span class="filter-chip">Within {{ request('distance') }} km</span> @endif
            @if(request('delivery')) <span class="filter-chip">Delivery up to Tk {{ request('delivery') }}</span> @endif
            @if(request('rating')) <span class="filter-chip">Rating {{ request('rating') }}+</span> @endif
            @if(request()->boolean('available')) <span class="filter-chip">In stock</span> @endif
            @if(request('sort')) <span class="filter-chip">Sort: {{ str_replace('-', ' ', request('sort')) }}</span> @endif
            <a class="secondary-btn" href="{{ route('products.browse') }}">Clear filters</a>
        </div>
    @endif

    <div class="products-result-head">
        <div>
            <strong>{{ $products->count() }} product{{ $products->count() === 1 ? '' : 's' }} found</strong>
            <span>Ranking score = normalized price + normalized distance from {{ $customerLocation['label'] }}</span>
        </div>
        <a class="secondary-btn" href="{{ route('compare', ['product' => request('q')]) }}">Compare products</a>
    </div>

    <div class="product-grid">
        @forelse($products as $product)
            @include('partials.product-card', ['product' => $product])
        @empty
            <div class="empty-state">No products match these filters. Try removing a price, distance, or stock filter.</div>
        @endforelse
    </div>
</section>
@endsection
