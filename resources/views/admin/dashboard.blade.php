@extends('layouts.app')

@section('title', 'Admin Panel')

@section('content')
<section class="dashboard">
    <h1>Admin panel</h1>
    <nav class="dashboard-tabs" aria-label="Admin dashboard sections">
        <a href="#shop-approvals">Shops</a>
        <a href="#category-management">Categories</a>
        <a href="#product-management">Products</a>
        <a href="#user-management">Users</a>
        <a href="#support-inbox">Support</a>
        <a href="#orders">Orders</a>
    </nav>
    <div class="dashboard-grid">
        <div><h2>Users</h2><p>{{ $users->count() }} members</p></div>
        <div><h2>Shops</h2><p>{{ $shops->count() }} shops</p></div>
        <div><h2>Orders</h2><p>{{ $orders->count() }} orders</p></div>
    </div>
</section>

<section class="section" id="shop-approvals">
    <div class="section-title"><h2>Shop approvals</h2></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Shop</th><th>Owner</th><th>Status</th><th>Change status</th></tr></thead>
            <tbody>
            @foreach($shops as $shop)
                <tr>
                    <td>{{ $shop->name }}<br><small>{{ $shop->area }}</small></td>
                    <td>{{ optional($shop->owner)->name ?: 'No owner' }}</td>
                    <td>{{ $shop->status }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.shops.status', $shop) }}" class="inline-actions">
                            @csrf
                            @method('PATCH')
                            <select name="status">
                                @foreach(['pending','approved','rejected','suspended'] as $status)
                                    <option @selected($shop->status === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                            <button>Save</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>

<section class="section" id="category-management">
    <div class="section-title"><h2>Category management</h2></div>
    <form class="panel-form" method="post" action="{{ route('categories.store') }}">
        @csrf
        <div class="two-col">
            <input name="name" placeholder="Category name">
            <select name="parent_id">
                <option value="">No parent</option>
                @foreach($categoryRecords as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <input name="icon" placeholder="Icon keyword">
        <textarea name="description" placeholder="Description"></textarea>
        <button>Add category</button>
    </form>
    <div class="table-wrap mt">
        <table class="data-table">
            <thead><tr><th>Name</th><th>Parent</th><th>Edit</th><th>Delete</th></tr></thead>
            <tbody>
            @foreach($categoryRecords as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ optional($category->parent)->name ?: '-' }}</td>
                    <td>
                        <form method="post" action="{{ route('categories.update', $category) }}" class="inline-actions">
                            @csrf
                            @method('PATCH')
                            <input name="name" value="{{ $category->name }}">
                            <select name="parent_id">
                                <option value="">No parent</option>
                                @foreach($categoryRecords->where('id', '!=', $category->id) as $parent)
                                    <option value="{{ $parent->id }}" @selected($category->parent_id === $parent->id)>{{ $parent->name }}</option>
                                @endforeach
                            </select>
                            <input name="icon" value="{{ $category->icon }}" placeholder="Icon">
                            <input name="description" value="{{ $category->description }}" placeholder="Description">
                            <button>Save</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" action="{{ route('categories.destroy', $category) }}">
                            @csrf
                            @method('DELETE')
                            <button class="danger-btn">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>

<section class="section" id="product-management">
    <div class="section-title"><h2>Product management</h2></div>
    <form class="panel-form" method="post" action="{{ route('products.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="two-col">
            <select name="shop_id">
                @foreach($shops as $shop)
                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                @endforeach
            </select>
            <select name="category_id">
                @foreach($categoryRecords as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="two-col">
            <input name="name" placeholder="Product name">
            <input name="type" placeholder="Type">
        </div>
        <div class="two-col">
            <input name="price" type="number" step="0.01" placeholder="Price">
            <input name="discount_price" type="number" step="0.01" placeholder="Discount price">
        </div>
        <input name="stock" type="number" min="0" placeholder="Stock">
        <input name="images[]" type="file" multiple accept="image/*">
        <textarea name="description" placeholder="Description"></textarea>
        <label class="check-row"><input type="checkbox" name="is_featured" value="1"> Feature on homepage</label>
        <button>Add product as admin</button>
    </form>
    <div class="table-wrap mt">
        <table class="data-table">
            <thead><tr><th>Product</th><th>Shop</th><th>Edit</th><th>Images</th></tr></thead>
            <tbody>
            @foreach($products as $product)
                <tr>
                    <td>{{ $product->name }}<br><small>Stock {{ $product->stock }}</small></td>
                    <td>{{ optional($product->shop)->name }}</td>
                    <td>
                        <form method="post" action="{{ route('products.update', $product) }}" class="inline-actions" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')
                            <input name="name" value="{{ $product->name }}">
                            <select name="category_id">
                                @foreach($categoryRecords as $category)
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
                        <form method="post" action="{{ route('products.destroy', $product) }}" class="inline-form">
                            @csrf
                            @method('DELETE')
                            <button class="danger-btn">Delete product</button>
                        </form>
                    </td>
                    <td>
                        <div class="image-admin-row">
                            @forelse($product->images as $image)
                                <form method="post" action="{{ route('product-images.destroy', $image) }}">
                                    @csrf
                                    @method('DELETE')
                                    <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt_text }}">
                                    <button class="danger-btn">Delete image</button>
                                </form>
                            @empty
                                <small>No image</small>
                            @endforelse
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>

<section class="section" id="user-management">
    <div class="section-title"><h2>User and co-admin management</h2></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>User</th><th>Role</th><th>Change role</th><th>Co-admin</th></tr></thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->name }}<br><small>{{ $user->email }}</small></td>
                    <td>{{ optional($user->role)->label }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.users.role', $user) }}" class="inline-actions">
                            @csrf
                            @method('PATCH')
                            <select name="role_id">
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" @selected($user->role_id === $role->id)>{{ $role->label }}</option>
                                @endforeach
                            </select>
                            <button>Save</button>
                        </form>
                    </td>
                    <td>
                        @if(optional($user->role)->name === 'co_admin')
                            <form method="post" action="{{ route('admin.co-admin.remove', $user) }}">
                                @csrf
                                @method('DELETE')
                                <button class="danger-btn">Remove co-admin</button>
                            </form>
                        @else
                            <form method="post" action="{{ route('admin.co-admin.promote', $user) }}" class="inline-actions">
                                @csrf
                                <input type="hidden" name="permissions[]" value="manage_shops">
                                <input type="hidden" name="permissions[]" value="manage_products">
                                <input type="hidden" name="permissions[]" value="manage_orders">
                                <input type="hidden" name="permissions[]" value="manage_users">
                                <button>Make co-admin</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>

<section class="section" id="support-inbox">
    <div class="section-title"><h2>Support inbox</h2></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($supportMessages as $message)
                <tr>
                    <td>{{ $message->name }}<br><small>{{ $message->email }} {{ $message->phone }}</small></td>
                    <td>{{ $message->subject }}</td>
                    <td>{{ $message->message }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.support.status', $message) }}" class="inline-actions">
                            @csrf
                            @method('PATCH')
                            <select name="status">
                                @foreach(['open', 'in_progress', 'resolved'] as $status)
                                    <option @selected($message->status === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                            <button>Save</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No support messages yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="section" id="orders">
    <div class="section-title"><h2>Orders</h2></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Total</th><th>Update</th></tr></thead>
            <tbody>
            @foreach($orders as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ optional($order->user)->name ?: 'Guest' }}</td>
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
