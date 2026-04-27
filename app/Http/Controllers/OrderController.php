<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $this->visibleOrders($request)->latest()->paginate(20);

        return $orders;
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        $data = $request->validate(['status' => ['required', 'in:Pending,Accepted,Processing,Out for Delivery,Delivered,Cancelled']]);
        $order->update($data);

        return redirect()->back()->with('status', 'Order '.$order->order_number.' status updated.');
    }

    public function visibleOrders(Request $request)
    {
        $role = optional($request->user()->role)->name;
        $query = Order::with(['items.product', 'items.shop', 'user']);

        if ($role === 'shop_owner') {
            $shopIds = $request->user()->shops()->pluck('id');
            $query->whereHas('items', fn ($itemQuery) => $itemQuery->whereIn('shop_id', $shopIds));
        }

        return $query;
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        $role = optional($request->user()->role)->name;
        if (in_array($role, ['admin', 'co_admin'], true)) {
            return;
        }

        $shopIds = $request->user()->shops()->pluck('id')->all();
        abort_unless($order->items()->whereIn('shop_id', $shopIds)->exists(), 403);
    }
}
