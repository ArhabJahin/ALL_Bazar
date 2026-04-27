@extends('layouts.app')

@section('title', 'Invoice')

@section('content')
<section class="page-head">
    <p class="eyebrow">Receipt</p>
    <h1>Invoice AB-DEMO-1001</h1>
    <p>Printable receipt layout for checkout confirmation, payment records, and customer order history.</p>
</section>

<section class="section">
    <div class="invoice-card">
        <div class="invoice-head">
            <div>
                <strong>AllBazar</strong>
                <p>Local marketplace invoice</p>
            </div>
            <div>
                <span>Invoice date</span>
                <strong>{{ now()->format('d M Y') }}</strong>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Product</th><th>Shop</th><th>Price</th><th>Delivery</th><th>Total</th></tr></thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $item['name'] }}</td>
                            <td>{{ $item['shop'] }}</td>
                            <td>Tk {{ number_format($item['price']) }}</td>
                            <td>Tk {{ number_format($item['delivery']) }}</td>
                            <td>Tk {{ number_format($item['total_cost'] ?? ($item['price'] + $item['delivery'])) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="invoice-total">
            <p>Subtotal <strong>Tk {{ number_format($subtotal) }}</strong></p>
            <p>Delivery <strong>Tk {{ number_format($delivery) }}</strong></p>
            <p>Grand total <strong>Tk {{ number_format($subtotal + $delivery) }}</strong></p>
        </div>
    </div>
</section>
@endsection
