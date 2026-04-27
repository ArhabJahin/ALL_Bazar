@extends('layouts.app')

@section('title', 'Register')

@section('content')
<section class="auth-card">
    <h1>Create account</h1>
    <form method="post" action="{{ route('register.store') }}" class="stack-form">
        @csrf
        <input name="name" value="{{ old('name') }}" placeholder="Full name">
        <input name="email" type="email" value="{{ old('email') }}" placeholder="Email">
        <input name="phone" value="{{ old('phone') }}" placeholder="Phone">
        <input name="area" value="{{ old('area') }}" placeholder="Area">
        <select name="account_type">
            <option value="customer">Customer</option>
            <option value="shop_owner" @selected(old('account_type') === 'shop_owner')>Shop owner</option>
        </select>
        <input name="password" type="password" placeholder="Password">
        <input name="password_confirmation" type="password" placeholder="Confirm password">
        <button>Register</button>
    </form>
</section>
@endsection
