<section class="story-banner {{ $class ?? '' }} text-white text-center d-flex align-items-center">
    <div class="container">
        <h2 class="display-6 mb-3">Your story starts here</h2>
        <p class="lead mb-4">Thousands of families trust us to find the right match.</p>
        <a href="{{ auth()->check() ? route('matches') : route('register') }}" class="btn btn-gold btn-lg rounded-pill px-5">{{ auth()->check() ? 'See your matches' : 'Register free' }}</a>
    </div>
</section>
