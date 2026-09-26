@extends('layouts.admin')

@section('title', 'Bulk upload members')

@section('content')
<a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-light border mb-3"><i class="bi bi-arrow-left"></i> Users</a>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card stat-card mb-3"><div class="card-body">
            <h2 class="h6"><span class="badge rounded-pill bg-primary me-2">1</span>Download the template</h2>
            <p class="small text-muted mb-2">Open it in Excel or Google Sheets. Keep the first (header) row, replace the example row with your members — one member per row.</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.users.import.template') }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Download template (.csv)</a>
                <a href="{{ route('admin.users.index', ['export' => 'csv']) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i>Download all members</a>
            </div>
            <p class="small text-muted mt-2 mb-0">To change existing members in bulk, download all members, edit the sheet and upload it with <em>Update existing members</em> ticked.</p>
        </div></div>

        <form method="POST" action="{{ route('admin.users.import.store') }}" enctype="multipart/form-data" class="card stat-card">
            @csrf
            <div class="card-body">
                <h2 class="h6"><span class="badge rounded-pill bg-primary me-2">2</span>Upload the filled file</h2>
                <div class="mb-3">
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,text/csv" required>
                    @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Save from Excel as <strong>CSV UTF-8 (Comma delimited)</strong>. Up to {{ number_format($maxRows) }} rows, 5 MB.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-medium" for="default_profile_status">New profiles are</label>
                    <select name="default_profile_status" id="default_profile_status" class="form-select form-select-sm">
                        <option value="approved" @selected(old('default_profile_status') !== 'pending')>Approved — visible right away</option>
                        <option value="pending" @selected(old('default_profile_status') === 'pending')>Pending — review each one first</option>
                    </select>
                    <div class="form-text">A <code>profile_status</code> value in a row overrides this.</div>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="update_existing" value="1" id="update_existing" @checked(old('update_existing'))>
                    <label class="form-check-label small" for="update_existing">Update existing members (matched by email). Blank cells keep the current value.</label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="mark_verified" value="1" id="mark_verified" @checked(old('mark_verified', true))>
                    <label class="form-check-label small" for="mark_verified">Treat new members' email &amp; mobile as verified</label>
                </div>
                <button class="btn btn-primary w-100"><i class="bi bi-upload me-1"></i>Upload &amp; import</button>
                <p class="small text-muted mt-2 mb-0">Rows with mistakes are skipped and listed with the reason; all other rows are saved. Fix the listed rows and upload just those again.</p>
            </div>
        </form>
    </div>

    <div class="col-lg-7">
        @if($result && $result['errors'])
            <div class="card stat-card border-warning mb-3"><div class="card-body">
                <h2 class="h6 text-warning-emphasis"><i class="bi bi-exclamation-triangle me-1"></i>{{ count($result['errors']) }} rows were not imported</h2>
                <div class="table-responsive" style="max-height: 420px">
                    <table class="table table-sm small mb-0">
                        <thead class="table-light"><tr><th>Row</th><th>Email</th><th>What to fix</th></tr></thead>
                        <tbody>
                        @foreach($result['errors'] as $error)
                            <tr>
                                <td>{{ $error['row'] }}</td>
                                <td class="text-nowrap">{{ $error['email'] ?: '—' }}</td>
                                <td><ul class="mb-0 ps-3">@foreach($error['messages'] as $m)<li>{{ $m }}</li>@endforeach</ul></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div></div>
        @endif

        <div class="card stat-card"><div class="card-body">
            <h2 class="h6">Columns</h2>
            <p class="small text-muted">Only <strong>name</strong>, <strong>email</strong>, <strong>gender</strong> and <strong>date_of_birth</strong> are required for a new member. Religion, caste, education, occupation, state and city are written as names, exactly as they appear under Master Data. Fill <strong>plan</strong> to record a manual payment and activate that plan.</p>
            <div class="table-responsive" style="max-height: 480px">
                <table class="table table-sm small mb-0">
                    <tbody>
                    @foreach($columns as $column => $help)
                        <tr><td class="text-nowrap"><code>{{ $column }}</code></td><td class="text-muted">{{ $help }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>
@endsection
