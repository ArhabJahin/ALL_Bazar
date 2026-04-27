@extends('layouts.app')

@section('title', 'Product Comparison')

@section('content')
<section class="page-head">
    <p class="eyebrow">Product comparison</p>
    <h1>Compare the same product across shops</h1>
    <p>AllBazar recommends the best value by comparing product price, delivery charge, total cost, distance, stock, rating, and ETA.</p>
</section>

<section class="section compare-search">
    <form class="hero-search" action="{{ route('compare') }}" method="get">
        <input name="product" value="{{ request('product') }}" placeholder="Try: Redmi Note 13 Cover" data-suggestions>
        <button type="submit">Compare product</button>
    </form>
</section>

<section class="section">
    @if($comparisonProducts->isNotEmpty())
        <div class="comparison-summary">
            <div>
                <p class="eyebrow">Recommended</p>
                <h2>{{ $recommended['shop'] }}</h2>
                <p>{{ $recommended['name'] }} has the lowest total estimated cost in this comparison.</p>
            </div>
            <div class="summary-card compact-summary">
                <p>Product price <strong>Tk {{ number_format($recommended['price']) }}</strong></p>
                <p>Delivery <strong>Tk {{ number_format($recommended['delivery']) }}</strong></p>
                <p>Total cost <strong>Tk {{ number_format($recommended['total_cost'] ?? ($recommended['price'] + $recommended['delivery'])) }}</strong></p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table comparison-table">
                <thead>
                    <tr>
                        <th>Shop</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Delivery</th>
                        <th>Total</th>
                        <th>Distance</th>
                        <th>Rating</th>
                        <th>Stock</th>
                        <th>ETA</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comparisonProducts as $product)
                        <tr class="{{ $loop->first ? 'recommended-row' : '' }}">
                            <td>
                                <a class="shop-mini" href="{{ route('shops.show', $product['shop_slug']) }}">
                                    <span>{{ strtoupper(substr($product['shop'], 0, 1)) }}</span>{{ $product['shop'] }}
                                </a>
                            </td>
                            <td><strong>{{ $product['name'] }}</strong><br><small>{{ $product['category'] }} - {{ $product['type'] }}</small></td>
                            <td>Tk {{ number_format($product['price']) }}</td>
                            <td>Tk {{ number_format($product['delivery']) }}</td>
                            <td><strong>Tk {{ number_format($product['total_cost'] ?? ($product['price'] + $product['delivery'])) }}</strong></td>
                            <td>{{ $product['distance'] }} km</td>
                            <td>{{ $product['rating'] }}</td>
                            <td>{{ $product['stock'] }}</td>
                            <td>{{ $product['eta'] ?? 'Today' }}</td>
                            <td><a class="solid-btn" href="{{ route('products.show', $product['slug']) }}">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">No comparable products found. Try searching by a shorter product name.</div>
    @endif
</section>
@endsection
