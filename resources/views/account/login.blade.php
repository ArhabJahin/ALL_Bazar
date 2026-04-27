@extends('layouts.app')

@section('title', 'Login')

@section('content')
<section class="auth-card">
    <h1>Login</h1>
    <form method="post" action="{{ route('login.store') }}" class="stack-form">
        @csrf
        <input name="email" type="email" value="{{ old('email') }}" placeholder="Email">
        <input name="password" type="password" placeholder="Password">
        <label class="check-row"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button>Login</button>
    </form>
</section>
@endsection
