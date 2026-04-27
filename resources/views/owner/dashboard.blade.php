@extends('layouts.app')

@section('title', 'Shop Owner')

@section('content')
<section class="dashboard">
    <h1>Shop owner dashboard</h1>
    <nav class="dashboard-tabs" aria-label="Shop owner dashboard sections">
        <a href="#shop-form">Shop setup</a>
        <a href="#add-product">Add product</a>
        <a href="#your-products">Products</a>
        <a href="#recent-orders">Orders</a>
    </nav>
    <div class="dashboard-grid">
        <div><h2>Shops</h2><p>{{ $shops->count() }} shop{{ $shops->count() === 1 ? '' : 's' }}</p></div>
        <div><h2>Products</h2><p>{{ $shops->sum(fn($shop) => $shop->products->count()) }} listed products</p></div>
        <div><h2>Orders</h2><p>{{ $orders->count() }} recent orders</p></div>
    </div>
</section>

<section class="section" id="shop-form">
    <div class="section-title"><h2>Register or update shop</h2></div>
    <form class="panel-form" method="post" action="{{ route('shops.store') }}">
        @csrf
        <div class="two-col">
            <input name="name" placeholder="Shop name">
            <input name="phone" placeholder="Phone">
        </div>
        <div class="two-col">
            <input name="area" placeholder="Area">
            <input name="address" placeholder="Full address">
        </div>
        <div class="two-col">
            <input name="latitude" placeholder="Latitude">
            <input name="longitude" placeholder="Longitude">
        </div>
        <textarea name="description" placeholder="Shop description"></textarea>
        <button>Submit shop</button>
    </form>
</section>

<section class="section" id="add-product">
    <div class="section-title"><h2>Add product</h2></div>
    <form class="panel-form" method="post" action="{{ route('products.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="two-col">
            <select name="shop_id">
                @foreach($shops as $shop)
                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                @endforeach
            </select>
            <select name="category_id">
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="two-col">
            <input name="name" placeholder="Product name">
            <input name="type" placeholder="Product type">
        </div>
        <div class="two-col">
            <input name="price" type="number" step="0.01" placeholder="Price">
            <input name="discount_price" type="number" step="0.01" placeholder="Discount price">
        </div>
        <input name="stock" type="number" min="0" placeholder="Stock">
        <label>Product images <input name="images[]" type="file" multiple accept="image/*"></label>
        <textarea name="description" placeholder="Description"></textarea>
        <label class="check-row"><input type="checkbox" name="is_featured" value="1"> Feature on homepage</label>
        <button>Add product</button>
    </form>
</section>

<section class="section" id="your-products">
    <div class="section-title"><h2>Your products</h2></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Product</th><th>Shop</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($shops->flatMap->products as $product)
                <tr>
                    <td colspan="5">
                        <form method="post" action="{{ route('products.update', $product) }}" class="inline-actions" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')
                            <input name="name" value="{{ $product->name }}">
                            <select name="category_id">
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected($product->category_id === $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <input name="type" value="{{ $product->type }}" placeholder="Type">
                            <input name="price" type="number" step="0.01" value="{{ $product->price }}">
                            <input name="discount_price" type="number" step="0.01" value="{{ $product->discount_price }}">
                            <input name="stock" type="number" min="0" value="{{ $product->stock }}">
                            <input name="images[]" type="file" multiple accept="image/*">
                            <textarea name="description" placeholder="Description">{{ $product->description }}</textarea>
                            <label class="check-row"><input type="checkbox" name="is_featured" value="1" @checked($product->is_featured)> Featured</label>
                            <button>Save</button>
                        </form>
                        @if($product->images->count())
                            <div class="image-admin-row">
                                @foreach($product->images as $image)
                                    <form method="post" action="{{ route('product-images.destroy', $image) }}">
                                        @csrf
                                        @method('DELETE')
                                        <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt_text }}">
                                        <button class="danger-btn">Delete image</button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                        <form method="post" action="{{ route('products.destroy', $product) }}" class="inline-form">
                            @csrf
                            @method('DELETE')
                            <button class="danger-btn">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No products yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="section" id="recent-orders">
    <div class="section-title"><h2>Recent orders</h2></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Order</th><th>Status</th><th>Total</th><th>Update</th></tr></thead>
            <tbody>
            @foreach($orders as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->status }}</td>
                    <td>Tk {{ number_format($order->grand_total) }}</td>
                    <td>
                        <form method="post" action="{{ route('orders.status', $order) }}" class="inline-actions">
                            @csrf
                            @method('PATCH')
                            <select name="status">
                                @foreach(['Pending','Accepted','Processing','Out for Delivery','Delivered','Cancelled'] as $status)
                                    <option @selected($order->status === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                            <button>Update</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection
