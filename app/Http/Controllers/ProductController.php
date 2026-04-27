<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->is('manage/products')) {
            return Product::with(['shop', 'category', 'images'])->latest()->paginate(24);
        }

        $customerLocation = $this->customerLocation($request);
        $products = Product::query()
            ->with(['shop', 'category', 'images'])
            ->whereNotNull('published_at')
            ->whereHas('shop', fn ($query) => $query->where('status', 'approved'))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = $request->query('q');
                $query->where(function ($query) use ($term) {
                    $query->where('name', 'like', '%'.$term.'%')
                        ->orWhere('type', 'like', '%'.$term.'%')
                        ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', '%'.$term.'%'))
                        ->orWhereHas('shop', fn ($shopQuery) => $shopQuery->where('name', 'like', '%'.$term.'%')->orWhere('area', 'like', '%'.$term.'%'));
                });
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', $request->query('category')));
            })
            ->get()
            ->map(fn (Product $product) => $this->formatProduct($product, $customerLocation))
            ->filter(fn (array $product) => !$request->boolean('available') || $product['stock'] > 0);

        if ($request->filled('min_price')) {
            $products = $products->filter(fn (array $product) => $product['price'] >= (float) $request->query('min_price'));
        }

        if ($request->filled('max_price')) {
            $products = $products->filter(fn (array $product) => $product['price'] <= (float) $request->query('max_price'));
        }

        if ($request->filled('distance')) {
            $products = $products->filter(fn (array $product) => $product['distance'] <= (float) $request->query('distance'));
        }

        if ($request->filled('delivery')) {
            $products = $products->filter(fn (array $product) => $product['delivery'] <= (float) $request->query('delivery'));
        }

        if ($request->filled('rating')) {
            $products = $products->filter(fn (array $product) => $product['rating'] >= (float) $request->query('rating'));
        }

        $products = $this->scoreProducts($products->values());
        $sort = $request->query('sort', 'best-match');
        $products = match ($sort) {
            'cheapest' => $products->sortBy([
                fn (array $a, array $b) => $a['price'] <=> $b['price'],
                fn (array $a, array $b) => $a['distance'] <=> $b['distance'],
            ]),
            'nearest' => $products->sortBy([
                fn (array $a, array $b) => $a['distance'] <=> $b['distance'],
                fn (array $a, array $b) => $a['price'] <=> $b['price'],
            ]),
            'best-rated' => $products->sortBy([
                fn (array $a, array $b) => $b['rating'] <=> $a['rating'],
                fn (array $a, array $b) => $a['score'] <=> $b['score'],
            ]),
            'newest' => $products->sortByDesc('id'),
            default => $products->sortBy([
                fn (array $a, array $b) => $a['score'] <=> $b['score'],
                fn (array $a, array $b) => $a['price'] <=> $b['price'],
                fn (array $a, array $b) => $a['distance'] <=> $b['distance'],
            ]),
        };

        $categoryOptions = Category::query()->orderBy('name')->pluck('name');
        $categoryColors = ['#27ae60', '#2d9cdb', '#bb6bd9', '#f2994a', '#eb5757', '#00a8a8'];
        $categories = $categoryOptions->values()->map(fn ($name, $index) => [
            'name' => $name,
            'color' => $categoryColors[$index % count($categoryColors)],
        ])->all();

        return view('products.index', [
            'products' => $products->values(),
            'categories' => $categories,
            'categoryOptions' => $categoryOptions,
            'customerLocation' => $customerLocation,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shop_id' => ['required', 'exists:shops,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'description' => ['nullable'],
            'type' => ['nullable', 'max:120'],
            'images.*' => ['nullable', 'image', 'max:2048'],
        ]);
        unset($data['images']);
        $this->authorizeShop($request, (int) $data['shop_id']);

        $product = Product::create($data + [
            'slug' => $this->uniqueSlug($data['name']),
            'published_at' => now(),
            'is_featured' => $request->boolean('is_featured'),
        ]);

        $this->storeImages($request, $product);

        return redirect()->back()->with('status', $product->name.' added.');
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeProduct($request, $product);

        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'max:255'],
            'type' => ['nullable', 'max:120'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable'],
            'stock' => ['required', 'integer', 'min:0'],
            'images.*' => ['nullable', 'image', 'max:2048'],
        ]);
        unset($data['images']);

        if ($product->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        $data['is_featured'] = $request->boolean('is_featured');
        $product->update($data);
        $this->storeImages($request, $product);

        return redirect()->back()->with('status', $product->name.' updated.');
    }

    public function destroy(Product $product)
    {
        $this->authorizeProduct(request(), $product);
        foreach ($product->images as $image) {
            Storage::disk('uploads')->delete($image->path);
            Storage::disk('public')->delete($image->path);
        }
        $product->delete();

        return redirect()->back()->with('status', 'Product deleted.');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (Product::where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function storeImages(Request $request, Product $product): void
    {
        if (!$request->hasFile('images')) {
            return;
        }

        foreach ($request->file('images') as $index => $image) {
            $path = Storage::disk('uploads')->putFile('products', $image);
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'alt_text' => $product->name,
                'sort_order' => $index,
            ]);
        }
    }

    private function authorizeShop(Request $request, int $shopId): void
    {
        $role = optional($request->user()->role)->name;
        if (in_array($role, ['admin', 'co_admin'], true)) {
            return;
        }

        abort_unless($request->user()->shops()->where('id', $shopId)->exists(), 403);
    }

    private function authorizeProduct(Request $request, Product $product): void
    {
        $role = optional($request->user()->role)->name;
        if (in_array($role, ['admin', 'co_admin'], true)) {
            return;
        }

        abort_unless($product->shop && $product->shop->owner_id === $request->user()->id, 403);
    }

    private function customerLocation(Request $request): array
    {
        $user = $request->user();
        $lat = $request->filled('lat') ? (float) $request->query('lat') : (float) ($user->latitude ?? 23.8151);
        $lng = $request->filled('lng') ? (float) $request->query('lng') : (float) ($user->longitude ?? 90.4255);

        return [
            'lat' => $lat ?: 23.8151,
            'lng' => $lng ?: 90.4255,
            'label' => $user->area ?? 'Bashundhara R/A',
        ];
    }

    private function formatProduct(Product $product, array $customerLocation): array
    {
        $shop = $product->shop;
        $distance = $shop ? $this->distanceKm(
            (float) $customerLocation['lat'],
            (float) $customerLocation['lng'],
            (float) $shop->latitude,
            (float) $shop->longitude
        ) : 0.0;
        $price = (float) ($product->discount_price ?: $product->price);
        $delivery = $shop ? round((float) $shop->delivery_base_charge + ($distance * (float) $shop->delivery_per_km_charge)) : 0;
        $image = $product->images->sortBy('sort_order')->first();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'type' => $product->type ?: optional($product->category)->name ?: 'Product',
            'category' => optional($product->category)->name ?: 'Product',
            'price' => $price,
            'old_price' => $product->discount_price ? (float) $product->price : null,
            'stock' => (int) $product->stock,
            'rating' => (float) $product->rating,
            'shop' => optional($shop)->name ?: 'AllBazar Shop',
            'shop_slug' => optional($shop)->slug ?: 'shop',
            'distance' => $distance,
            'delivery' => $delivery,
            'total_cost' => $price + $delivery,
            'image' => $this->imageClass(optional($product->category)->name ?: ''),
            'image_url' => $image && $this->imageExists($image->path) ? asset('storage/'.$image->path) : null,
            'eta' => $distance <= 3 ? '45-60 min' : ($distance <= 7 ? '2-3 hrs' : 'Tomorrow'),
            'created_at' => optional($product->created_at)->timestamp ?? 0,
        ];
    }

    private function imageExists(string $path): bool
    {
        return Storage::disk('uploads')->exists($path)
            || (is_link(public_path('storage')) && Storage::disk('public')->exists($path));
    }

    private function scoreProducts($products)
    {
        $minPrice = (float) $products->min('price');
        $maxPrice = (float) $products->max('price');
        $minDistance = (float) $products->min('distance');
        $maxDistance = (float) $products->max('distance');

        return $products->map(function (array $product) use ($minPrice, $maxPrice, $minDistance, $maxDistance) {
            $normalizedPrice = $this->normalize((float) $product['price'], $minPrice, $maxPrice);
            $normalizedDistance = $this->normalize((float) $product['distance'], $minDistance, $maxDistance);
            $product['score'] = round($normalizedPrice + $normalizedDistance, 4);

            return $product;
        });
    }

    private function normalize(float $value, float $min, float $max): float
    {
        if ($max <= $min) {
            return 0.0;
        }

        return ($value - $min) / ($max - $min);
    }

    private function distanceKm(float $customerLat, float $customerLng, float $shopLat, float $shopLng): float
    {
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
}
