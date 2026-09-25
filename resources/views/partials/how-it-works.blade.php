        <div class="row g-4 text-center">
            @foreach([
                ['bi-person-plus', 'Register', 'Create your profile in minutes with photos, education, family and horoscope details.'],
                ['bi-shield-check', 'Get verified', 'Our team reviews every profile and photo before it goes live.'],
                ['bi-search-heart', 'Find matches', 'Use advanced filters or let us suggest matches based on your preferences.'],
                ['bi-chat-heart', 'Connect', 'Send interests, chat securely and take the next step with your family.'],
            ] as [$icon, $title, $text])
                <div class="col-6 col-lg-3">
                    <div class="rounded-circle bg-brand-soft d-inline-flex align-items-center justify-content-center mb-3" style="width:72px;height:72px">
                        <i class="bi {{ $icon }} fs-2 text-brand"></i>
                    </div>
                    <h3 class="h6 fw-semibold">{{ $title }}</h3>
                    <p class="small text-muted">{{ $text }}</p>
                </div>
            @endforeach
        </div>
