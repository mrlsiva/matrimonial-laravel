@extends('layouts.admin')

@section('title', 'Payment Transactions')

@section('content')
<form method="GET" class="card stat-card mb-3">
    <div class="card-body row g-2 align-items-end small">
        <div class="col-md-3"><label class="form-label mb-1">Search</label><input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Invoice, order/payment ID, member"></div>
        <div class="col-6 col-md-2"><label class="form-label mb-1">Status</label>
            <select name="status" class="form-select form-select-sm"><option value="">All</option>@foreach(['paid', 'failed', 'created'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
        <div class="col-6 col-md-2"><label class="form-label mb-1">Plan</label>
            <select name="plan" class="form-select form-select-sm"><option value="">All</option>@foreach($plans as $id => $name)<option value="{{ $id }}" @selected(request('plan') == $id)>{{ $name }}</option>@endforeach</select></div>
        <div class="col-6 col-md-2"><label class="form-label mb-1">From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm"></div>
        <div class="col-6 col-md-2"><label class="form-label mb-1">To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm"></div>
        <div class="col-md-1 d-grid gap-1">
            <button class="btn btn-primary btn-sm">Filter</button>
        </div>
    </div>
</form>

<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="small">Paid total for current filter: <strong>₹{{ number_format($totalPaid, 2) }}</strong></div>
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-download me-1"></i>Export CSV</a>
</div>

<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 small">
            <thead class="table-light"><tr><th>Date</th><th>Member</th><th>Plan</th><th>Amount</th><th>Invoice</th><th>Razorpay IDs</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($payments as $p)
                <tr onclick="location='{{ route('admin.payments.show', $p) }}'" style="cursor:pointer">
                    <td>{{ $p->created_at->format('d M Y, h:i A') }}</td>
                    <td>{{ $p->user?->name }}<div class="text-muted">{{ $p->user?->email }}</div></td>
                    <td>{{ $p->plan?->name }}</td>
                    <td>₹{{ number_format($p->amount, 2) }}</td>
                    <td>{{ $p->invoice_no ?? '—' }}</td>
                    <td class="text-muted">{{ $p->razorpay_order_id }}<br>{{ $p->razorpay_payment_id }}</td>
                    <td><span class="badge bg-{{ $p->statusBadge() }}">{{ $p->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No transactions found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $payments->links() }}</div>
@endsection
