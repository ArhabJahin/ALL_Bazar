<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function destroy(Request $request, ProductImage $image)
    {
        $this->authorizeOwnerOrManager($request, $image);

        Storage::disk('uploads')->delete($image->path);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('status', 'Product image deleted.');
    }

    private function authorizeOwnerOrManager(Request $request, ProductImage $image): void
    {
        $user = $request->user();
        $role = optional($user->role)->name;

        if (in_array($role, ['admin', 'co_admin'], true)) {
            return;
        }

        abort_unless($image->product && $image->product->shop && $image->product->shop->owner_id === $user->id, 403);
    }
}
