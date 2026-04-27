<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'AllBazar') - Bangladesh Local Marketplace</title>
    <link rel="stylesheet" href="{{ asset('css/allbazar.css') }}">
    @stack('styles')
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to content</a>

    <header class="topbar" data-site-header>
        <div class="topbar-left">
            <button class="menu-toggle" type="button" aria-label="Open menu" aria-controls="site-sidebar" aria-expanded="false" data-sidebar-toggle>
                <span></span>
                <span></span>
                <span></span>
            </button>
            <a class="brand" href="{{ route('home') }}" aria-label="AllBazar home">
                <span class="brand-mark">AB</span>
                <span>AllBazar</span>
            </a>
        </div>

        <form class="top-search" action="{{ route('search') }}" method="get" role="search">
            <span class="search-glyph" aria-hidden="true"></span>
            <input name="q" value="{{ request('q') }}" placeholder="Search rice, phone cover, panjabi..." autocomplete="off" data-suggestions>
            <a class="advanced-btn" href="{{ route('advanced-search') }}" title="Advanced search">Filters</a>
        </form>

        <nav class="nav-actions" aria-label="Main navigation">
            <a class="nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">Home</a>
            <a class="nav-link {{ request()->routeIs('products.browse', 'products.show') ? 'is-active' : '' }}" href="{{ route('products.browse') }}">Products</a>
            <a class="nav-link {{ request()->routeIs('shops.*') ? 'is-active' : '' }}" href="{{ route('shops.map') }}">Map</a>
            <a class="nav-link {{ request()->routeIs('cart.*') ? 'is-active' : '' }}" href="{{ route('cart.index') }}">Cart</a>
            @auth
                <a class="nav-link {{ request()->routeIs('account.*') ? 'is-active' : '' }}" href="{{ route('account.dashboard') }}">Account</a>
                @if(auth()->user()->role && auth()->user()->role->name === 'shop_owner')
                    <a class="nav-link {{ request()->routeIs('owner.*') ? 'is-active' : '' }}" href="{{ route('owner.dashboard') }}">Shop</a>
                @endif
                @if(auth()->user()->role && in_array(auth()->user()->role->name, ['admin', 'co_admin'], true))
                    <a class="nav-link {{ request()->routeIs('admin.*') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">Admin</a>
                @endif
                <form action="{{ route('logout') }}" method="post" class="inline-form">
                    @csrf
                    <button class="ghost-btn" type="submit">Logout</button>
                </form>
            @else
                <a class="ghost-btn" href="{{ route('login') }}">Login</a>
                <a class="solid-btn" href="{{ route('register') }}">Register</a>
            @endauth
        </nav>
    </header>

    <div class="drawer-backdrop" data-sidebar-close></div>
    <aside class="site-sidebar" id="site-sidebar" aria-label="Browse menu" aria-hidden="true">
        <div class="sidebar-head">
            <a class="brand" href="{{ route('home') }}" aria-label="AllBazar home">
                <span class="brand-mark">AB</span>
                <span>AllBazar</span>
            </a>
            <button class="sidebar-close" type="button" aria-label="Close menu" data-sidebar-close>
                <span></span>
                <span></span>
            </button>
        </div>

        <div class="sidebar-panel">
            <p class="sidebar-kicker">Marketplace</p>
            <nav class="side-nav" aria-label="Sidebar navigation">
                <a class="side-link {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}"><span>Home</span><small>Featured deals</small></a>
                <a class="side-link {{ request()->routeIs('products.browse', 'products.show') ? 'is-active' : '' }}" href="{{ route('products.browse') }}"><span>Products</span><small>Browse all items</small></a>
                <a class="side-link {{ request()->routeIs('search') ? 'is-active' : '' }}" href="{{ route('search') }}"><span>Smart search</span><small>Compare shops</small></a>
                <a class="side-link {{ request()->routeIs('advanced-search') ? 'is-active' : '' }}" href="{{ route('advanced-search') }}"><span>Advanced filters</span><small>Price, stock, area</small></a>
                <a class="side-link {{ request()->routeIs('compare') ? 'is-active' : '' }}" href="{{ route('compare') }}"><span>Product comparison</span><small>Total cost ranking</small></a>
                <a class="side-link {{ request()->routeIs('shops.*') ? 'is-active' : '' }}" href="{{ route('shops.map') }}"><span>Shop map</span><small>Browse nearby</small></a>
                <a class="side-link {{ request()->routeIs('cart.*') ? 'is-active' : '' }}" href="{{ route('cart.index') }}"><span>Cart</span><small>Checkout basket</small></a>
                <a class="side-link {{ request()->routeIs('notifications') ? 'is-active' : '' }}" href="{{ route('notifications') }}"><span>Notifications</span><small>Order and stock alerts</small></a>
                <a class="side-link {{ request()->routeIs('rider.dashboard') ? 'is-active' : '' }}" href="{{ route('rider.dashboard') }}"><span>Rider dashboard</span><small>Delivery workflow</small></a>
            </nav>
        </div>

        @isset($categories)
            <div class="sidebar-panel">
                <p class="sidebar-kicker">Popular categories</p>
                <div class="side-category-grid">
                    @foreach($categories as $category)
                        <a class="side-category" style="--tile-color: {{ $category['color'] }}" href="{{ route('advanced-search', ['category' => $category['name']]) }}">
                            <span>{{ substr($category['name'], 0, 1) }}</span>
                            {{ $category['name'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endisset

        <div class="sidebar-panel">
            <p class="sidebar-kicker">Account</p>
            <div class="side-actions">
                @auth
                    <a class="solid-btn full" href="{{ route('account.dashboard') }}">Account dashboard</a>
                    @if(auth()->user()->role && auth()->user()->role->name === 'shop_owner')
                        <a class="ghost-btn full" href="{{ route('owner.dashboard') }}">Manage shop</a>
                    @endif
                    @if(auth()->user()->role && in_array(auth()->user()->role->name, ['admin', 'co_admin'], true))
                        <a class="ghost-btn full" href="{{ route('admin.dashboard') }}">Admin panel</a>
                    @endif
                    <form action="{{ route('logout') }}" method="post">
                        @csrf
                        <button class="ghost-btn full" type="submit">Logout</button>
                    </form>
                @else
                    <a class="solid-btn full" href="{{ route('register') }}">Create account</a>
                    <a class="ghost-btn full" href="{{ route('login') }}">Login</a>
                @endauth
            </div>
        </div>
    </aside>

    <main id="main-content" class="site-main">
        @if(session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="flash error">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="footer">
        <div class="footer-brand">
            <strong>AllBazar</strong>
            <p>Local shops, fair prices, fast Bangladesh delivery.</p>
        </div>
        <div class="footer-mini">
            <span>Verified shops</span>
            <span>Price comparison</span>
            <span>Area-based delivery</span>
        </div>
        <div class="footer-links">
            <a href="{{ route('pages.static', 'about') }}">About</a>
            <a href="{{ route('pages.static', 'support') }}">Support</a>
            <a href="{{ route('pages.static', 'terms') }}">Terms</a>
            <a href="{{ route('pages.static', 'privacy') }}">Privacy</a>
        </div>
    </footer>
    <script src="{{ asset('js/allbazar.js') }}"></script>
    @stack('scripts')
</body>
</html>
