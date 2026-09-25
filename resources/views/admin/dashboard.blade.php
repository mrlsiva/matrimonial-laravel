@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="row g-3 mb-4">
    @foreach([
        ['Total members', number_format($stats['users']), '+'.$stats['users_today'].' today', 'bi-people', 'primary', route('admin.users.index')],
        ['Pending approvals', $stats['pending_profiles'], $stats['pending_photos'].' photos pending', 'bi-hourglass-split', 'warning', route('admin.profiles.index')],
        ['Active subscriptions', $stats['active_subscriptions'], 'Paid members', 'bi-gem', 'success', route('admin.subscriptions.index', ['status' => 'active'])],
        ['Revenue (this month)', '₹'.number_format($stats['revenue_month']), 'Total ₹'.number_format($stats['revenue_total']), 'bi-currency-rupee', 'danger', route('admin.payments.index', ['status' => 'paid'])],
    ] as [$label, $value, $sub, $icon, $color, $link])
        <div class="col-sm-6 col-xl-3">
            <a href="{{ $link }}" class="text-decoration-none text-dark">
                <div class="card stat-card h-100"><div class="card-body d-flex gap-3 align-items-center">
                    <div class="icon bg-{{ $color }}-subtle text-{{ $color }}"><i class="bi {{ $icon }}"></i></div>
                    <div><div class="small text-muted">{{ $label }}</div><div class="h4 mb-0">{{ $value }}</div><div class="small text-muted">{{ $sub }}</div></div>
                </div></div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card stat-card h-100"><div class="card-body">
            <h2 class="h6">Registrations &amp; revenue (last 6 months)</h2>
            <canvas id="trendChart" height="110" aria-label="Registrations and revenue chart" role="img"></canvas>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card stat-card h-100"><div class="card-body">
            <h2 class="h6">Members overview</h2>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between px-0">Grooms<strong>{{ $stats['males'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between px-0">Brides<strong>{{ $stats['females'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between px-0">Approved profiles<strong>{{ $stats['approved_profiles'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between px-0">Blocked users<strong>{{ $stats['blocked'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between px-0">Failed payments<strong>{{ $stats['payments_failed'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between px-0"><a href="{{ route('admin.messages.index', ['status' => 'new']) }}">New support messages</a><strong>{{ $stats['new_messages'] }}</strong></li>
            </ul>
            <h3 class="h6 mt-3">Active plans</h3>
            @forelse($planBreakdown as $plan => $count)
                <div class="d-flex justify-content-between small"><span>{{ $plan }}</span><strong>{{ $count }}</strong></div>
            @empty
                <div class="small text-muted">No active paid subscriptions.</div>
            @endforelse
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex justify-content-between"><h2 class="h6">Latest registrations</h2><a href="{{ route('admin.users.index') }}" class="small">View all</a></div>
            <div class="table-responsive"><table class="table table-sm small mb-0">
                <thead><tr><th>Name</th><th>Profile</th><th>Status</th><th>Joined</th></tr></thead>
                <tbody>
                @foreach($latestUsers as $u)
                    <tr>
                        <td><a href="{{ route('admin.users.show', $u) }}">{{ $u->name }}</a></td>
                        <td>{{ $u->profile?->profile_code ?? '—' }}</td>
                        <td><span class="badge bg-{{ ['approved' => 'success', 'rejected' => 'danger'][$u->profile?->approval_status] ?? 'warning' }}">{{ $u->profile?->approval_status ?? 'no profile' }}</span></td>
                        <td>{{ $u->created_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex justify-content-between"><h2 class="h6">Latest payments</h2><a href="{{ route('admin.payments.index') }}" class="small">View all</a></div>
            <div class="table-responsive"><table class="table table-sm small mb-0">
                <thead><tr><th>Member</th><th>Plan</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($latestPayments as $p)
                    <tr>
                        <td><a href="{{ route('admin.payments.show', $p) }}">{{ $p->user?->name }}</a></td>
                        <td>{{ $p->plan?->name }}</td>
                        <td>₹{{ number_format($p->amount) }}</td>
                        <td><span class="badge bg-{{ $p->statusBadge() }}">{{ $p->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center">No payments yet.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const chart = @json($chart);
new Chart(document.getElementById('trendChart'), {
    data: {
        labels: chart.labels,
        datasets: [
            { type: 'bar', label: 'Registrations', data: chart.registrations, backgroundColor: 'rgba(176,23,75,.75)', borderRadius: 4, yAxisID: 'y' },
            { type: 'line', label: 'Revenue (₹)', data: chart.revenue, borderColor: '#d4a017', backgroundColor: '#d4a017', tension: .3, yAxisID: 'y1' },
        ],
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Registrations' } },
            y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Revenue (₹)' } },
        },
    },
});
</script>
@endpush
