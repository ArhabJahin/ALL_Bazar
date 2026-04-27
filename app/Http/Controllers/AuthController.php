<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('account.login');
    }

    public function showRegister()
    {
        return view('account.register');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account.dashboard'))->with('status', 'Logged in successfully.');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'max:30'],
            'area' => ['nullable', 'max:120'],
            'account_type' => ['required', 'in:customer,shop_owner'],
            'password' => ['required', 'min:6', 'confirmed'],
        ]);

        $role = Role::where('name', $data['account_type'])->firstOrFail();

        $user = User::create([
            'role_id' => $role->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'area' => $data['area'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);

        return redirect()->route($data['account_type'] === 'shop_owner' ? 'owner.dashboard' : 'account.dashboard')
            ->with('status', 'Account created successfully.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Logged out successfully.');
    }
}
