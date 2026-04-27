@extends('layouts.app')

@section('title', 'Advanced Search')

@section('content')
<section class="page-head">
    <p class="eyebrow">Advanced search</p>
    <h1>Find the best local offer</h1>
</section>

<section class="search-layout">
    <form class="filter-panel" method="get" action="{{ route('advanced-search') }}">
        <label>Product name <input name="product" value="{{ request('product') }}" placeholder="Product name"></label>
        <label>Category
            <select name="category">
                <option value="">Any category</option>
                @foreach($categories as $category)
                    <option @selected(request('category') === $category['name'])>{{ $category['name'] }}</option>
                @endforeach
            </select>
        </label>
        <div class="two-col">
            <label>Min price <input type="number" name="min_price" value="{{ request('min_price') }}"></label>
            <label>Max price <input type="number" name="max_price" value="{{ request('max_price') }}"></label>
        </div>
        <div class="two-col">
            <label>Distance km <input type="number" name="distance" value="{{ request('distance') }}"></label>
            <label>Delivery max <input type="number" name="delivery" value="{{ request('delivery') }}"></label>
        </div>
        <label>Shop rating <input type="number" step="0.1" name="rating" value="{{ request('rating') }}" placeholder="4.0"></label>
        <label>Product rating <input type="number" step="0.1" name="product_rating" value="{{ request('product_rating') }}" placeholder="4.0"></label>
        <label>Available stock <input type="number" name="stock" value="{{ request('stock') }}" placeholder="Minimum stock"></label>
        <label>Location / area <input name="area" value="{{ request('area') }}" placeholder="Dhanmondi, Uttara..."></label>
        <label>Sort by
            <select name="sort">
                <option value="best-overall" @selected(request('sort') === 'best-overall')>Best overall match</option>
                <option value="cheapest" @selected(request('sort', 'cheapest') === 'cheapest')>Cheapest product price</option>
                <option value="cheapest-total" @selected(request('sort') === 'cheapest-total')>Cheapest total cost</option>
                <option value="nearest" @selected(request('sort') === 'nearest')>Nearest</option>
                <option value="fastest-delivery" @selected(request('sort') === 'fastest-delivery')>Fastest delivery</option>
                <option value="best-rated" @selected(request('sort') === 'best-rated')>Best-rated</option>
                <option value="newest" @selected(request('sort') === 'newest')>Newest</option>
            </select>
        </label>
        <button>Apply filters</button>
        <a class="secondary-btn full" href="{{ route('compare', ['product' => request('product')]) }}">Compare matching shops</a>
    </form>

    <div class="product-grid">
        @foreach($filteredProducts as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endsection
