<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'max:255'],
            'phone' => ['nullable', 'max:40'],
            'area' => ['nullable', 'max:120'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'current_password' => ['nullable', 'required_with:password'],
            'password' => ['nullable', 'min:6', 'confirmed'],
        ]);

        $user = $request->user();

        if (!empty($data['password'])) {
            if (!Hash::check($data['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is not correct.']);
            }

            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        unset($data['current_password'], $data['password_confirmation']);
        $user->update($data);

        return back()->with('status', 'Profile updated.');
    }

    public function storeAddress(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'max:80'],
            'recipient_name' => ['required', 'max:255'],
            'phone' => ['required', 'max:40'],
            'address_line' => ['required'],
            'area' => ['required', 'max:120'],
            'city' => ['required', 'max:120'],
            'postal_code' => ['nullable', 'max:20'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'is_default' => ['nullable'],
        ]);

        if ($request->boolean('is_default')) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        unset($data['is_default']);
        $request->user()->addresses()->create($data + ['is_default' => $request->boolean('is_default')]);

        return back()->with('status', 'Address saved.');
    }

    public function deleteAddress(Request $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $address->delete();

        return back()->with('status', 'Address deleted.');
    }
}
