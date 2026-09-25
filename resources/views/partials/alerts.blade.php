@php($messages = collect(['success' => 'success', 'error' => 'danger', 'info' => 'info', 'warning' => 'warning'])->filter(fn ($t, $k) => session($k)))
@if($messages->isNotEmpty() || ($errors->any() && ($showErrors ?? true)))
    <div class="{{ ($container ?? false) ? 'container pt-3' : '' }}">
        @foreach($messages as $key => $type)
            <div class="alert alert-{{ $type }} alert-dismissible fade show" role="alert">
                {{ session($key) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endforeach
        @if($errors->any() && ($showErrors ?? true))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Please fix the following:</strong>
                <ul class="mb-0 mt-1 small">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>
@endif
