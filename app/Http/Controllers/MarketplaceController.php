<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Role;
use App\Models\Shop;
use App\Models\SupportMessage;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class MarketplaceController extends Controller
{
    public function home()
    {
        $data = $this->marketplaceData();

        return view('pages.home', $data);
    }

    public function search(Request $request)
    {
        $data = $this->marketplaceData();
        $query = strtolower((string) $request->query('q', ''));
        $products = collect($data['products'])
            ->filter(fn ($product) => $query === '' || str_contains(strtolower($product['name'].' '.$product['category'].' '.$product['type']), $query))
            ->sortBy(fn ($product) => $this->productMatchScore($product))
            ->groupBy(fn ($product) => strtolower($product['name']));

        return view('pages.search', $data + [
            'query' => $request->query('q', ''),
            'groupedProducts' => $products,
        ]);
    }

    public function suggestions(Request $request)
    {
        $term = strtolower((string) $request->query('q', ''));

        $products = Product::query()
            ->when($term !== '', fn ($query) => $query->where('name', 'like', '%'.$term.'%'))
            ->orderBy('name')
            ->limit(8)
            ->pluck('name');

        $categories = Category::query()
            ->when($term !== '', fn ($query) => $query->where('name', 'like', '%'.$term.'%'))
            ->orderBy('name')
            ->limit(5)
            ->pluck('name');

        return response()->json($products->merge($categories)->unique()->values());
    }

    public function advancedSearch(Request $request)
    {
        $data = $this->marketplaceData();
        $products = collect($data['products']);

        if ($request->filled('product')) {
            $term = strtolower($request->product);
            $products = $products->filter(fn ($product) => str_contains(strtolower($product['name']), $term));
        }

        if ($request->filled('category')) {
            $products = $products->where('category', $request->category);
        }

        if ($request->filled('min_price')) {
            $products = $products->where('price', '>=', (float) $request->min_price);
        }

        if ($request->filled('max_price')) {
            $products = $products->where('price', '<=', (float) $request->max_price);
        }

        if ($request->filled('distance')) {
            $products = $products->where('distance', '<=', (float) $request->distance);
        }

        if ($request->filled('delivery')) {
            $products = $products->where('delivery', '<=', (float) $request->delivery);
        }

        if ($request->filled('rating')) {
            $products = $products->where('rating', '>=', (float) $request->rating);
        }

        if ($request->filled('product_rating')) {
            $products = $products->where('rating', '>=', (float) $request->product_rating);
        }

        if ($request->filled('stock')) {
            $products = $products->where('stock', '>=', (int) $request->stock);
        }

        if ($request->filled('area')) {
            $area = strtolower($request->area);
            $products = $products->filter(fn ($product) => str_contains(strtolower($product['shop'].' '.$product['shop_slug'].' '.($product['shop_area'] ?? '')), $area));
        }

        $sort = $request->query('sort', 'cheapest');
        $products = match ($sort) {
            'cheapest-product' => $products->sortBy('price'),
            'cheapest-total' => $products->sortBy(fn ($product) => $this->productTotalCost($product)),
            'nearest' => $products->sortBy('distance'),
            'fastest-delivery' => $products->sortBy(fn ($product) => $this->etaRank($product['eta'] ?? 'Tomorrow')),
            'best-rated' => $products->sortByDesc('rating'),
            'best-overall' => $products->sortBy(fn ($product) => $this->productMatchScore($product)),
            'newest' => $products->sortByDesc('id'),
            default => $products->sortBy('price'),
        };

        return view('pages.advanced-search', $data + [
            'filteredProducts' => $products->values(),
        ]);
    }

    public function compare(Request $request)
    {
        $data = $this->marketplaceData();
        $term = strtolower((string) $request->query('product', ''));
        $products = collect($data['products'])
            ->when($term !== '', fn ($items) => $items->filter(fn ($product) => str_contains(strtolower($product['name'].' '.$product['category'].' '.$product['type']), $term)));

        $groups = $products->groupBy(fn ($product) => strtolower($product['name']));
        $selectedGroup = $groups->first(fn ($items) => $items->count() > 1) ?? $groups->first() ?? collect();
        $comparisonProducts = collect($selectedGroup)->sortBy(fn ($product) => $this->productTotalCost($product))->values();
        $recommended = $comparisonProducts->first();

        return view('pages.compare', $data + compact('term', 'comparisonProducts', 'recommended'));
    }

    public function product(Request $request, string $slug)
    {
        $data = $this->marketplaceData();
        $product = collect($data['products'])->firstWhere('slug', $slug) ?? $data['products'][0];
        $productModel = Product::with(['reviews.user', 'shop'])->where('slug', $slug)->first();
        $recent = collect($request->session()->get('recently_viewed', []))->reject(fn ($item) => $item === $slug)->prepend($slug)->take(8)->values()->all();
        $request->session()->put('recently_viewed', $recent);
        $related = collect($data['products'])
            ->where('category', $product['category'])
            ->where('slug', '!=', $product['slug'])
            ->take(4);
        $recentlyViewed = collect($data['products'])->whereIn('slug', $recent)->where('slug', '!=', $slug)->take(4);

        return view('products.show', $data + compact('product', 'productModel', 'related', 'recentlyViewed'));
    }

    public function shop(string $slug)
    {
        $data = $this->marketplaceData();
        $shop = collect($data['shops'])->firstWhere('slug', $slug) ?? $data['shops'][0];
        $shopModel = Shop::with(['reviews.user'])->where('slug', $slug)->first();
        $shopProducts = collect($data['products'])->where('shop_slug', $shop['slug'])->values();

        return view('shops.show', $data + compact('shop', 'shopModel', 'shopProducts'));
    }

    public function map(Request $request)
    {
        $data = $this->marketplaceData();
        $shops = collect($data['shops']);

        if ($request->filled('shop')) {
            $term = strtolower($request->shop);
            $shops = $shops->filter(fn ($shop) => str_contains(strtolower($shop['name'].' '.$shop['area']), $term));
        }

        if ($request->filled('area')) {
            $area = strtolower($request->area);
            $shops = $shops->filter(fn ($shop) => str_contains(strtolower($shop['area']), $area));
        }

        if ($request->filled('rating')) {
            $shops = $shops->where('rating', '>=', (float) $request->rating);
        }

        if ($request->filled('category')) {
            $category = $request->category;
            $products = collect($data['products'])->where('category', $category)->pluck('shop_slug')->unique();
            $shops = $shops->whereIn('slug', $products);
        }

        if ($request->filled('distance')) {
            $shops = $shops->where('distance', '<=', (float) $request->distance);
        }

        $shops = $shops->values();
        $mapShops = $shops->map(function ($shop) use ($data) {
            $shopProducts = collect($data['products'])->where('shop_slug', $shop['slug']);

            return $shop + [
                'url' => route('shops.show', $shop['slug']),
                'products_count' => $shopProducts->count(),
                'categories' => $shopProducts->pluck('category')->unique()->values()->all(),
            ];
        })->values();

        return view('shops.map', array_merge($data, [
            'shops' => $shops,
            'mapShops' => $mapShops,
            'customerLocation' => $this->customerLocation(),
        ]));
    }

    public function notifications()
    {
        return view('pages.feature', $this->marketplaceData() + $this->featurePageData(
            'Notification Center',
            'Order, stock, support, review, and shop approval alerts for every role.',
            ['Order placed', 'Order accepted', 'Rider picked up', 'Order delivered', 'Low stock', 'New review', 'Support reply', 'Shop verification decision'],
            'This page is ready for database-backed notifications, email/SMS hooks, and push notification settings.'
        ));
    }

    public function riderDashboard()
    {
        return view('pages.feature', $this->marketplaceData() + $this->featurePageData(
            'Rider Dashboard',
            'Delivery riders can review assigned orders, accept deliveries, update status, and track earnings.',
            ['Assigned delivery queue', 'Pickup shop and customer address', 'Accept or reject delivery', 'Picked up / on the way / delivered updates', 'Delivery earnings summary'],
            'The next backend step is adding rider roles, assignment records, and delivery status history.'
        ));
    }

    public function shopVerification()
    {
        return view('pages.feature', $this->marketplaceData() + $this->featurePageData(
            'Shop Verification',
            'Shop owners submit trade license, NID, phone verification, and profile documents for admin approval.',
            ['Document upload checklist', 'Admin review status', 'Rejected reason', 'Verified shop badge', 'Trust score signals'],
            'The current shop status system can be extended with document storage and review decisions.'
        ));
    }

    public function activityLog()
    {
        return view('pages.feature', $this->marketplaceData() + $this->featurePageData(
            'Admin Activity Log',
            'A security and audit page for tracking important admin and co-admin actions.',
            ['Role changes', 'Shop approval changes', 'Product moderation', 'Payment/refund actions', 'Support status updates'],
            'The next backend step is writing audit rows whenever privileged actions are performed.'
        ));
    }

    public function coupons()
    {
        return view('pages.feature', $this->marketplaceData() + $this->featurePageData(
            'Coupon Management',
            'Create marketplace-wide and shop-specific promotions with checkout validation.',
            ['Coupon code', 'First-order discount', 'Free delivery coupon', 'Minimum order amount', 'Expiry date', 'Usage limit'],
            'Checkout can later validate coupons and store discount records on each order.'
        ));
    }

    public function returns()
    {
        return view('pages.feature', $this->marketplaceData() + $this->featurePageData(
            'Returns and Refunds',
            'Customers can request cancellations, returns, and refunds with status tracking.',
            ['Cancel order request', 'Return reason', 'Shop/admin approval', 'Refund status', 'Refund transaction ID'],
            'The next backend step is adding return request records and connecting them to payments.'
        ));
    }

    public function paymentSuccess()
    {
        return view('pages.feature', $this->marketplaceData() + $this->featurePageData(
            'Payment Successful',
            'Payment confirmation page for bKash, Nagad, Rocket, SSLCommerz, and card payments.',
            ['Payment status', 'Transaction ID', 'Paid amount', 'Order number', 'Receipt download'],
            'Gateway callbacks can redirect here after storing the transaction record.'
        ));
    }

    public function paymentFailed()
    {
        return view('pages.feature', $this->marketplaceData() + $this->featurePageData(
            'Payment Failed',
            'Failure page that explains unsuccessful or cancelled gateway payments.',
            ['Failure reason', 'Retry checkout', 'Choose another method', 'Support contact', 'Pending payment review'],
            'Gateway callbacks can redirect here with a failure code and retry instructions.'
        ));
    }

    public function orderDetails()
    {
        return view('pages.order-details', $this->marketplaceData() + [
            'orderNumber' => 'AB-DEMO-1001',
            'statuses' => ['Pending', 'Accepted', 'Processing', 'Out for Delivery', 'Delivered'],
            'currentStatus' => 'Out for Delivery',
        ]);
    }

    public function invoice()
    {
        $data = $this->marketplaceData();
        $items = collect($data['products'])->take(3)->values();
        $subtotal = $items->sum('price');
        $delivery = $items->sum('delivery');

        return view('pages.invoice', $data + compact('items', 'subtotal', 'delivery'));
    }

    public function cart()
    {
        $data = $this->marketplaceData();
        $cartItems = collect($data['products'])->take(3)->map(function ($product, $index) {
            $product['quantity'] = $index + 1;
            return $product;
        });

        return view('cart.index', $data + compact('cartItems'));
    }

    public function checkout()
    {
        $data = $this->marketplaceData();

        return view('cart.checkout', $data);
    }

    public function account()
    {
        $user = auth()->user()->load(['addresses', 'orders.items', 'role']);
        $wishlists = Wishlist::with('product.shop')->where('user_id', $user->id)->latest()->get();
        $reviews = Review::with(['product', 'shop'])->where('user_id', $user->id)->latest()->get();

        return view('account.dashboard', array_merge($this->marketplaceData(), compact('user', 'wishlists', 'reviews')));
    }

    public function owner()
    {
        $user = auth()->user();
        $shops = Shop::with(['products.category', 'products.images'])->when($user->role && $user->role->name === 'shop_owner', fn ($query) => $query->where('owner_id', $user->id))->get();
        $categories = Category::orderBy('name')->get();
        $orders = Order::with(['items.product', 'items.shop', 'user'])
            ->when($user->role && $user->role->name === 'shop_owner', function ($query) use ($user) {
                $shopIds = $user->shops()->pluck('id');
                $query->whereHas('items', fn ($itemQuery) => $itemQuery->whereIn('shop_id', $shopIds));
            })
            ->latest()
            ->take(20)
            ->get();

        return view('owner.dashboard', array_merge($this->marketplaceData(), compact('shops', 'categories', 'orders')));
    }

    public function admin()
    {
        $users = User::with('role')->latest()->get();
        $shops = Shop::with('owner')->latest()->get();
        $products = Product::with(['shop', 'category', 'images'])->latest()->get();
        $categoryRecords = Category::with('parent')->orderBy('name')->get();
        $orders = Order::with('user')->latest()->get();
        $roles = Role::orderBy('name')->get();
        $supportMessages = SupportMessage::with('user')->latest()->take(30)->get();

        return view('admin.dashboard', array_merge($this->marketplaceData(), compact('users', 'shops', 'products', 'categoryRecords', 'orders', 'roles', 'supportMessages')));
    }

    public function page(string $page)
    {
        abort_unless(in_array($page, ['about', 'support', 'terms', 'privacy'], true), 404);

        return view('pages.static', $this->marketplaceData() + ['page' => $page]);
    }

    private function marketplaceData(): array
    {
        if (Schema::hasTable('products') && Product::query()->exists()) {
            return $this->databaseMarketplaceData();
        }

        return $this->fallbackMarketplaceData();
    }

    private function databaseMarketplaceData(): array
    {
        $categoryColors = ['#27ae60', '#2d9cdb', '#bb6bd9', '#f2994a', '#eb5757', '#00a8a8'];
        $categories = Category::query()->orderBy('name')->get()->values()->map(function (Category $category, int $index) use ($categoryColors) {
            return [
                'name' => $category->name,
                'icon' => $category->icon ?: strtolower($category->name),
                'color' => $categoryColors[$index % count($categoryColors)],
            ];
        })->all();

        $shops = Shop::query()
            ->where('status', 'approved')
            ->withCount('products')
            ->orderByDesc('rating')
            ->get()
            ->map(fn (Shop $shop) => $this->formatShop($shop))
            ->values()
            ->all();

        $products = Product::query()
            ->with(['shop', 'category', 'images'])
            ->whereNotNull('published_at')
            ->get()
            ->map(fn (Product $product) => $this->formatProduct($product))
            ->sortBy(fn ($product) => $this->productMatchScore($product))
            ->values()
            ->all();

        return [
            'categories' => $categories,
            'shops' => $shops,
            'products' => $products,
            'popularProducts' => collect($products)->sortByDesc('rating')->take(4),
            'nearbyProducts' => collect($products)->sortBy('distance')->take(4),
            'orderStatuses' => ['Pending', 'Accepted', 'Processing', 'Out for Delivery', 'Delivered', 'Cancelled'],
        ];
    }

    private function formatShop(Shop $shop): array
    {
        $distance = $this->distanceKm((float) $shop->latitude, (float) $shop->longitude);

        return [
            'id' => $shop->id,
            'name' => $shop->name,
            'slug' => $shop->slug,
            'area' => $shop->area,
            'distance' => $distance,
            'rating' => (float) $shop->rating,
            'phone' => $shop->phone,
            'address' => $shop->address,
            'description' => $shop->description,
            'logo' => collect(explode(' ', $shop->name))->map(fn ($part) => substr($part, 0, 1))->take(2)->implode(''),
            'banner' => 'linear-gradient(120deg,#16a085,#118ab2)',
            'lat' => (float) $shop->latitude,
            'lng' => (float) $shop->longitude,
        ];
    }

    private function formatProduct(Product $product): array
    {
        $shop = $product->shop;
        $distance = $shop ? $this->distanceKm((float) $shop->latitude, (float) $shop->longitude) : 0;
        $displayPrice = $product->discount_price ?: $product->price;
        $delivery = $shop ? round((float) $shop->delivery_base_charge + ($distance * (float) $shop->delivery_per_km_charge)) : 60;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'type' => $product->type ?: optional($product->category)->name,
            'category' => optional($product->category)->name ?: 'Product',
            'price' => (float) $displayPrice,
            'old_price' => $product->discount_price ? (float) $product->price : null,
            'stock' => $product->stock,
            'rating' => (float) $product->rating,
            'shop' => optional($shop)->name ?: 'AllBazar Shop',
            'shop_slug' => optional($shop)->slug ?: 'shop',
            'shop_area' => optional($shop)->area ?: '',
            'distance' => $distance,
            'delivery' => $delivery,
            'total_cost' => (float) $displayPrice + $delivery,
            'match_score' => $this->productMatchScore([
                'price' => (float) $displayPrice,
                'delivery' => $delivery,
                'distance' => $distance,
                'rating' => (float) $product->rating,
            ]),
            'image' => $this->imageClass($product->category ? $product->category->name : ''),
            'image_url' => $this->productImageUrls($product)->first(),
            'gallery_images' => $this->productImageUrls($product)->all(),
            'eta' => $distance <= 3 ? '45-60 min' : ($distance <= 7 ? '2-3 hrs' : 'Tomorrow'),
        ];
    }

    private function productImageUrls(Product $product)
    {
        return $product->images
            ->sortBy('sort_order')
            ->pluck('path')
            ->filter(fn ($path) => $this->imageExists($path))
            ->map(fn ($path) => asset('storage/'.$path))
            ->values();
    }

    private function imageExists(string $path): bool
    {
        return Storage::disk('uploads')->exists($path)
            || (is_link(public_path('storage')) && Storage::disk('public')->exists($path));
    }

    private function distanceKm(float $shopLat, float $shopLng): float
    {
        $customerLat = 23.8151;
        $customerLng = 90.4255;

        if (!$shopLat || !$shopLng) {
            return 5.0;
        }

        $earthRadius = 6371;
        $latDelta = deg2rad($shopLat - $customerLat);
        $lngDelta = deg2rad($shopLng - $customerLng);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($customerLat)) * cos(deg2rad($shopLat)) * sin($lngDelta / 2) ** 2;

        return round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }

    private function customerLocation(): array
    {
        return [
            'lat' => 23.8151,
            'lng' => 90.4255,
            'label' => 'Bashundhara R/A',
        ];
    }

    private function productTotalCost(array $product): float
    {
        return (float) ($product['total_cost'] ?? ((float) $product['price'] + (float) ($product['delivery'] ?? 0)));
    }

    private function productMatchScore(array $product): float
    {
        return $this->productTotalCost($product)
            + ((float) ($product['distance'] ?? 0) * 18)
            - ((float) ($product['rating'] ?? 0) * 20);
    }

    private function etaRank(string $eta): int
    {
        $eta = strtolower($eta);

        if (str_contains($eta, '45') || str_contains($eta, '60')) {
            return 60;
        }

        if (str_contains($eta, '90')) {
            return 90;
        }

        if (str_contains($eta, '2-3')) {
            return 180;
        }

        if (str_contains($eta, '2-4')) {
            return 240;
        }

        return 1440;
    }

    private function withProductMetrics(array $product): array
    {
        $product['total_cost'] = $this->productTotalCost($product);
        $product['match_score'] = $this->productMatchScore($product);

        return $product;
    }

    private function featurePageData(string $title, string $subtitle, array $items, string $note): array
    {
        return compact('title', 'subtitle', 'items', 'note');
    }

    private function imageClass(string $category): string
    {
        return match ($category) {
            'Electronics' => 'phone-case',
            'Grocery' => 'rice',
            'Fashion' => 'panjabi',
            'Beauty' => 'aloe',
            'Home' => 'lamp',
            default => 'default',
        };
    }

    private function fallbackMarketplaceData(): array
    {
        $categories = [
            ['name' => 'Grocery', 'icon' => 'basket', 'color' => '#27ae60'],
            ['name' => 'Electronics', 'icon' => 'phone', 'color' => '#2d9cdb'],
            ['name' => 'Fashion', 'icon' => 'shirt', 'color' => '#bb6bd9'],
            ['name' => 'Home', 'icon' => 'home', 'color' => '#f2994a'],
            ['name' => 'Beauty', 'icon' => 'sparkle', 'color' => '#eb5757'],
            ['name' => 'Medicine', 'icon' => 'plus', 'color' => '#00a8a8'],
        ];

        $shops = [
            ['id' => 1, 'name' => 'Bashundhara Gadget Hub', 'slug' => 'bashundhara-gadget-hub', 'area' => 'Bashundhara R/A', 'distance' => 1.1, 'rating' => 4.8, 'phone' => '01711000001', 'address' => 'Block C, Bashundhara R/A, Dhaka', 'description' => 'Phones, accessories, and smart devices from trusted local sellers.', 'logo' => 'BG', 'banner' => 'linear-gradient(120deg,#1f8a70,#bedb39)', 'lat' => 23.8151, 'lng' => 90.4255],
            ['id' => 2, 'name' => 'Dhanmondi Daily Mart', 'slug' => 'dhanmondi-daily-mart', 'area' => 'Dhanmondi', 'distance' => 3.4, 'rating' => 4.6, 'phone' => '01711000002', 'address' => 'Road 8A, Dhanmondi, Dhaka', 'description' => 'Fresh pantry items, household goods, and fast neighborhood delivery.', 'logo' => 'DD', 'banner' => 'linear-gradient(120deg,#ff6b6b,#ffd166)', 'lat' => 23.7465, 'lng' => 90.3760],
            ['id' => 3, 'name' => 'Chawk Fashion Corner', 'slug' => 'chawk-fashion-corner', 'area' => 'Chawkbazar', 'distance' => 6.8, 'rating' => 4.4, 'phone' => '01711000003', 'address' => 'Chawkbazar, Old Dhaka', 'description' => 'Colorful local fashion, panjabi, saree, kidswear, and accessories.', 'logo' => 'CF', 'banner' => 'linear-gradient(120deg,#8e44ad,#f1c40f)', 'lat' => 23.7162, 'lng' => 90.3955],
            ['id' => 4, 'name' => 'Sylhet Beauty & Care', 'slug' => 'sylhet-beauty-care', 'area' => 'Uttara', 'distance' => 8.2, 'rating' => 4.7, 'phone' => '01711000004', 'address' => 'Sector 7, Uttara, Dhaka', 'description' => 'Skincare, cosmetics, health-care essentials, and gift packs.', 'logo' => 'SB', 'banner' => 'linear-gradient(120deg,#ff7eb3,#65d6ce)', 'lat' => 23.8759, 'lng' => 90.3795],
        ];

        $products = [
            ['id' => 1, 'name' => 'Redmi Note 13 Cover', 'slug' => 'redmi-note-13-cover-bgh', 'type' => 'Phone accessories', 'category' => 'Electronics', 'price' => 180, 'old_price' => 240, 'stock' => 34, 'rating' => 4.7, 'shop' => 'Bashundhara Gadget Hub', 'shop_slug' => 'bashundhara-gadget-hub', 'distance' => 1.1, 'delivery' => 49, 'image' => 'phone-case', 'eta' => '45-60 min'],
            ['id' => 2, 'name' => 'Redmi Note 13 Cover', 'slug' => 'redmi-note-13-cover-cfc', 'type' => 'Phone accessories', 'category' => 'Electronics', 'price' => 210, 'old_price' => null, 'stock' => 12, 'rating' => 4.3, 'shop' => 'Chawk Fashion Corner', 'shop_slug' => 'chawk-fashion-corner', 'distance' => 6.8, 'delivery' => 90, 'image' => 'phone-case-alt', 'eta' => '2-3 hrs'],
            ['id' => 3, 'name' => 'Premium Miniket Rice 5kg', 'slug' => 'premium-miniket-rice-5kg', 'type' => 'Rice', 'category' => 'Grocery', 'price' => 430, 'old_price' => 465, 'stock' => 52, 'rating' => 4.8, 'shop' => 'Dhanmondi Daily Mart', 'shop_slug' => 'dhanmondi-daily-mart', 'distance' => 3.4, 'delivery' => 60, 'image' => 'rice', 'eta' => '60-90 min'],
            ['id' => 4, 'name' => 'Cotton Panjabi', 'slug' => 'cotton-panjabi-chawk', 'type' => 'Menswear', 'category' => 'Fashion', 'price' => 950, 'old_price' => 1250, 'stock' => 9, 'rating' => 4.5, 'shop' => 'Chawk Fashion Corner', 'shop_slug' => 'chawk-fashion-corner', 'distance' => 6.8, 'delivery' => 85, 'image' => 'panjabi', 'eta' => 'Tomorrow'],
            ['id' => 5, 'name' => 'Aloe Vera Gel', 'slug' => 'aloe-vera-gel-sylhet', 'type' => 'Skincare', 'category' => 'Beauty', 'price' => 260, 'old_price' => 320, 'stock' => 27, 'rating' => 4.6, 'shop' => 'Sylhet Beauty & Care', 'shop_slug' => 'sylhet-beauty-care', 'distance' => 8.2, 'delivery' => 95, 'image' => 'aloe', 'eta' => '2-4 hrs'],
            ['id' => 6, 'name' => 'LED Study Lamp', 'slug' => 'led-study-lamp-bgh', 'type' => 'Lighting', 'category' => 'Home', 'price' => 690, 'old_price' => 790, 'stock' => 16, 'rating' => 4.4, 'shop' => 'Bashundhara Gadget Hub', 'shop_slug' => 'bashundhara-gadget-hub', 'distance' => 1.1, 'delivery' => 55, 'image' => 'lamp', 'eta' => '45-60 min'],
        ];

        return [
            'categories' => $categories,
            'shops' => $shops,
            'products' => collect($products)->map(fn ($product) => $this->withProductMetrics($product))->sortBy(fn ($product) => $this->productMatchScore($product))->values()->all(),
            'popularProducts' => collect($products)->map(fn ($product) => $this->withProductMetrics($product))->sortByDesc('rating')->take(4),
            'nearbyProducts' => collect($products)->map(fn ($product) => $this->withProductMetrics($product))->sortBy('distance')->take(4),
            'orderStatuses' => ['Pending', 'Accepted', 'Processing', 'Out for Delivery', 'Delivered', 'Cancelled'],
        ];
    }
}
