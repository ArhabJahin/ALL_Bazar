<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPermission;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function promoteToCoAdmin(Request $request, User $user)
    {
        $data = $request->validate(['permissions' => ['array']]);
        $user->role()->associate(Role::where('name', 'co_admin')->first())->save();

        foreach ($data['permissions'] ?? [] as $permission) {
            AdminPermission::updateOrCreate(
                ['user_id' => $user->id, 'permission' => $permission],
                ['allowed' => true]
            );
        }

        return redirect()->back()->with('status', $user->name.' is now a co-admin.');
    }

    public function removeCoAdmin(User $user)
    {
        $user->role()->associate(Role::where('name', 'customer')->first())->save();
        AdminPermission::where('user_id', $user->id)->delete();

        return redirect()->back()->with('status', 'Co-admin access removed from '.$user->name.'.');
    }

    public function updateUserRole(Request $request, User $user)
    {
        $data = $request->validate(['role_id' => ['required', 'exists:roles,id']]);
        $user->update($data);

        return redirect()->back()->with('status', $user->name.' role updated.');
    }

    public function updateShopStatus(Request $request, Shop $shop)
    {
        $data = $request->validate(['status' => ['required', 'in:pending,approved,rejected,suspended']]);
        $shop->update($data);

        return redirect()->back()->with('status', $shop->name.' marked '.$shop->status.'.');
    }
}
