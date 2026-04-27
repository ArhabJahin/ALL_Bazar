@extends('layouts.app')

@section('title', 'Shop Map')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@section('content')
<section class="page-head">
    <p class="eyebrow">Real shop map</p>
    <h1>Browse nearby shops on OpenStreetMap</h1>
    <p>Detect your location, view shop markers, open shop previews, and compare distance, rating, category, and delivery area.</p>
</section>

<section class="map-layout">
    <form class="filter-panel" method="get" action="{{ route('shops.map') }}">
        <label>Search shops <input name="shop" value="{{ request('shop') }}" placeholder="Shop name or area"></label>
        <label>Area <input name="area" value="{{ request('area') }}" placeholder="Dhanmondi, Uttara..."></label>
        <label>Category
            <select name="category">
                <option value="">Any category</option>
                @foreach($categories as $category)
                    <option @selected(request('category') === $category['name'])>{{ $category['name'] }}</option>
                @endforeach
            </select>
        </label>
        <label>Rating <input name="rating" value="{{ request('rating') }}" type="number" step="0.1" placeholder="4.0"></label>
        <label>Distance km <input name="distance" value="{{ request('distance') }}" type="number" min="1" max="50"></label>
        <button type="submit">Filter shops</button>
        <button class="secondary-btn" type="button" data-locate-user>Detect my location</button>
    </form>

    <div class="map-shell">
        <div class="map-toolbar">
            <div>
                <strong>{{ $shops->count() }} shop{{ $shops->count() === 1 ? '' : 's' }} found</strong>
                <span>Default location: {{ $customerLocation['label'] }}</span>
            </div>
            <a class="secondary-btn" href="{{ route('advanced-search') }}">Search products</a>
        </div>
        <div id="shop-map" class="real-map" data-empty="{{ $shops->isEmpty() ? '1' : '0' }}"></div>
        <div class="map-list">
            @forelse($shops as $shop)
                <a class="map-shop-row" href="{{ route('shops.show', $shop['slug']) }}">
                    <span class="shop-logo">{{ $shop['logo'] }}</span>
                    <span>
                        <strong>{{ $shop['name'] }}</strong>
                        <small>{{ $shop['area'] }} - {{ $shop['distance'] }} km - Rating {{ $shop['rating'] }}</small>
                    </span>
                    <em>Open</em>
                </a>
            @empty
                <div class="empty-state">No shops match these filters.</div>
            @endforelse
        </div>
    </div>
</section>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (() => {
            const mapElement = document.getElementById('shop-map');
            if (!mapElement || !window.L) return;

            const shops = @json($mapShops);
            const defaultCustomer = @json($customerLocation);
            const defaultCenter = [defaultCustomer.lat, defaultCustomer.lng];
            const map = L.map(mapElement, { scrollWheelZoom: true }).setView(defaultCenter, 12);
            let customerMarker = null;
            let routeLine = null;
            let customerPosition = defaultCenter;
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char]));

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const setCustomerMarker = (lat, lng, label = 'Your location') => {
                customerPosition = [lat, lng];
                if (customerMarker) {
                    customerMarker.setLatLng(customerPosition);
                } else {
                    customerMarker = L.circleMarker(customerPosition, {
                        radius: 9,
                        color: '#14213d',
                        fillColor: '#f7b32b',
                        fillOpacity: 1,
                        weight: 3
                    }).addTo(map);
                }
                customerMarker.bindPopup(label);
            };

            const drawRoute = (shop) => {
                if (routeLine) map.removeLayer(routeLine);
                routeLine = L.polyline([customerPosition, [shop.lat, shop.lng]], {
                    color: '#0f8b6f',
                    weight: 4,
                    dashArray: '8 8'
                }).addTo(map);
                map.fitBounds(routeLine.getBounds(), { padding: [40, 40] });
            };

            setCustomerMarker(defaultCenter[0], defaultCenter[1], defaultCustomer.label);

            const bounds = L.latLngBounds([defaultCenter]);
            shops.forEach((shop) => {
                if (!shop.lat || !shop.lng) return;

                const marker = L.marker([shop.lat, shop.lng]).addTo(map);
                const categories = (shop.categories || []).join(', ') || 'Mixed products';
                marker.bindPopup(`
                    <div class="map-popup">
                        <strong>${escapeHtml(shop.name)}</strong>
                        <span>${escapeHtml(shop.area)} - ${escapeHtml(shop.distance)} km - Rating ${escapeHtml(shop.rating)}</span>
                        <span>${escapeHtml(categories)}</span>
                        <span>${escapeHtml(shop.products_count)} listed item${shop.products_count === 1 ? '' : 's'}</span>
                        <a href="${shop.url}">Open shop</a>
                    </div>
                `);
                marker.on('click', () => drawRoute(shop));
                bounds.extend([shop.lat, shop.lng]);
            });

            if (shops.length) map.fitBounds(bounds, { padding: [36, 36] });

            document.querySelector('[data-locate-user]')?.addEventListener('click', () => {
                if (!navigator.geolocation) return;

                navigator.geolocation.getCurrentPosition((position) => {
                    const { latitude, longitude } = position.coords;
                    setCustomerMarker(latitude, longitude);
                    map.setView([latitude, longitude], 13);
                });
            });
        })();
    </script>
@endpush
