<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    public function index()
    {
        return Shop::with('products')->where('status', 'approved')->paginate(24);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'max:255'],
            'address' => ['required'],
            'area' => ['required'],
            'phone' => ['required'],
            'description' => ['nullable'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $shop = Shop::create($data + [
            'owner_id' => optional($request->user())->id,
            'slug' => $this->uniqueSlug($data['name']),
            'status' => ($request->user() && $request->user()->role && in_array($request->user()->role->name, ['admin', 'co_admin'], true)) ? 'approved' : 'pending',
        ]);

        return redirect()->route('owner.dashboard')->with('status', $shop->name.' submitted.');
    }

    public function update(Request $request, Shop $shop)
    {
        $this->authorizeShop($request, $shop);

        $data = $request->validate([
            'name' => ['required', 'max:255'],
            'address' => ['required'],
            'area' => ['required'],
            'phone' => ['required'],
            'description' => ['nullable'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'delivery_base_charge' => ['nullable', 'numeric', 'min:0'],
            'delivery_per_km_charge' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:pending,approved,rejected,suspended'],
        ]);

        if ($shop->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($data['name'], $shop->id);
        }

        $shop->update($data);

        return redirect()->back()->with('status', $shop->name.' updated.');
    }

    public function destroy(Shop $shop)
    {
        $this->authorizeShop(request(), $shop);
        $shop->delete();

        return redirect()->back()->with('status', 'Shop deleted.');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (Shop::where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function authorizeShop(Request $request, Shop $shop): void
    {
        $role = optional($request->user()->role)->name;
        if (in_array($role, ['admin', 'co_admin'], true)) {
            return;
        }

        abort_unless($shop->owner_id === $request->user()->id, 403);
    }
}
