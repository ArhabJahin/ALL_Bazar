@extends('layouts.app')

@section('title', ucfirst($page))

@section('content')
<section class="page-head">
    <p class="eyebrow">AllBazar</p>
    <h1>{{ ucfirst($page) }}</h1>
    <p>
        @if($page === 'about')
            AllBazar helps customers compare products from nearby Bangladeshi shops by price, distance, delivery charge, and trust signals.
        @elseif($page === 'support')
            Contact support for orders, delivery issues, shop onboarding, refunds, and account help.
        @elseif($page === 'terms')
            Marketplace terms cover user accounts, seller listings, delivery, payments, reviews, and acceptable conduct.
        @else
            Privacy rules explain how customer, shop, location, order, and review data should be protected.
        @endif
    </p>
</section>

@if($page === 'support')
    <section class="section">
        <div class="section-title"><h2>Contact support</h2></div>
        <form class="panel-form" method="post" action="{{ route('support.store') }}">
            @csrf
            <div class="two-col">
                <input name="name" value="{{ old('name', auth()->user()->name ?? '') }}" placeholder="Your name">
                <input name="email" type="email" value="{{ old('email', auth()->user()->email ?? '') }}" placeholder="Email">
            </div>
            <input name="phone" value="{{ old('phone', auth()->user()->phone ?? '') }}" placeholder="Phone">
            <input name="subject" value="{{ old('subject') }}" placeholder="Subject">
            <textarea name="message" placeholder="Tell us what happened">{{ old('message') }}</textarea>
            <button>Send support message</button>
        </form>
    </section>
@endif
@endsection
