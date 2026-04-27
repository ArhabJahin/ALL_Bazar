@extends('layouts.app')

@section('title', $title)

@section('content')
<section class="page-head">
    <p class="eyebrow">Planned system module</p>
    <h1>{{ $title }}</h1>
    <p>{{ $subtitle }}</p>
</section>

<section class="section feature-page-grid">
    <div class="feature-roadmap">
        @foreach($items as $item)
            <div class="feature-roadmap-item">
                <span>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                <strong>{{ $item }}</strong>
            </div>
        @endforeach
    </div>
    <aside class="summary-card">
        <h2>Implementation note</h2>
        <p>{{ $note }}</p>
        <a class="solid-btn full" href="{{ route('home') }}">Back to marketplace</a>
    </aside>
</section>
@endsection
