@extends('layouts.app')

@section('title', 'Smart Search')

@section('content')
<section class="page-head">
    <p class="eyebrow">Smart search</p>
    <h1>Results for "{{ $query ?: 'all products' }}"</h1>
    <p>Exact and similar product matches are grouped together. Price and distance influence the visual order from top-left to bottom-right.</p>
</section>

<section class="section">
    @forelse($groupedProducts as $name => $items)
        <div class="result-group">
            <div class="section-title compact">
                <h2>{{ ucwords($name) }}</h2>
                <span>{{ $items->count() }} shop match{{ $items->count() > 1 ? 'es' : '' }}</span>
            </div>
            <div class="product-grid ranked">
                @foreach($items as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
    @empty
        <div class="empty-state">No product found. Try a shorter name or use advanced search.</div>
    @endforelse
</section>
@endsection
