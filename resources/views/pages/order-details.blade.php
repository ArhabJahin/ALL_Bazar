@extends('layouts.app')

@section('title', 'Order Details')

@section('content')
<section class="page-head">
    <p class="eyebrow">Order tracking</p>
    <h1>Order {{ $orderNumber }}</h1>
    <p>Customer, shop owner, rider, and admin views can share the same status timeline.</p>
</section>

<section class="section order-detail-grid">
    <div class="panel-form">
        <h2>Live order timeline</h2>
        <div class="timeline">
            @foreach($statuses as $status)
                <div class="timeline-step {{ $status === $currentStatus ? 'is-current' : '' }}">
                    <span>{{ $loop->iteration }}</span>
                    <strong>{{ $status }}</strong>
                    <small>{{ $status === $currentStatus ? 'Current status' : 'Tracked update' }}</small>
                </div>
            @endforeach
        </div>
    </div>
    <aside class="summary-card">
        <h2>Order actions</h2>
        <p>Status <strong>{{ $currentStatus }}</strong></p>
        <p>Rider <strong>Not assigned</strong></p>
        <p>Payment <strong>Cash on Delivery</strong></p>
        <a class="solid-btn full" href="{{ route('orders.invoice') }}">Download invoice</a>
    </aside>
</section>
@endsection
