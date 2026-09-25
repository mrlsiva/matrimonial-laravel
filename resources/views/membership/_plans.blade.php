{{-- Expects: $plans, $subscription (nullable) --}}
<div class="row g-4 justify-content-center">
    @foreach($plans as $plan)
        @php($isCurrent = $subscription && $subscription->membership_plan_id === $plan->id)
        <div class="col-md-6 col-lg-4">
            <div class="card plan-card h-100 {{ $plan->profile_highlight ? 'featured' : '' }}">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-{{ $plan->badge_color }} rounded-pill px-3 py-2">{{ $plan->name }}</span>
                        @if($plan->profile_highlight)<span class="small text-gold fw-semibold"><i class="bi bi-star-fill"></i> Most popular</span>@endif
                    </div>
                    <div class="my-3">
                        @if($plan->isFree())
                            <span class="price">Free</span>
                        @else
                            <span class="price">₹{{ number_format($plan->price) }}</span>
                            <span class="text-muted">/ {{ $plan->duration_days }} days</span>
                        @endif
                    </div>
                    <ul class="list-unstyled small flex-grow-1">
                        <li class="mb-2"><i class="bi {{ $plan->can_chat ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }} me-2"></i>{{ $plan->can_chat ? 'Unlimited chat with any member' : 'Chat only after interest is accepted' }}</li>
                        <li class="mb-2"><i class="bi {{ $plan->contact_views_limit ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }} me-2"></i>{{ $plan->contact_views_limit ? 'View '.$plan->contact_views_limit.' contact numbers' : 'No contact number views' }}</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>{{ $plan->daily_interest_limit === null ? 'Unlimited interests' : $plan->daily_interest_limit.' interests per day' }}</li>
                        <li class="mb-2"><i class="bi {{ $plan->can_view_horoscope ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }} me-2"></i>View horoscopes</li>
                        @foreach($plan->features ?? [] as $feature)
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>{{ $feature }}</li>
                        @endforeach
                    </ul>
                    @if($plan->isFree())
                        <a href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="btn btn-outline-primary w-100 rounded-pill">{{ auth()->check() ? 'Included' : 'Register free' }}</a>
                    @elseif(! auth()->check())
                        <a href="{{ route('login') }}" class="btn btn-primary w-100 rounded-pill">Login to upgrade</a>
                    @elseif(auth()->user()->isAdmin())
                        <button class="btn btn-secondary w-100 rounded-pill" disabled>Admin account</button>
                    @else
                        <button class="btn {{ $plan->profile_highlight ? 'btn-gold' : 'btn-primary' }} w-100 rounded-pill js-buy-plan"
                                data-checkout="{{ route('payment.checkout', $plan) }}" data-plan="{{ $plan->name }}">
                            {{ $isCurrent ? 'Renew '.$plan->name : 'Upgrade to '.$plan->name }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
