@extends('app')

@section('title', $category->name . ' - ' . config('app.name'))
@section('meta_description', $category->description)

@section('content')
<!-- Breadcrumb -->
<div style="background-color: #f8f9fa; padding: 20px 0;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/shop') }}">Shop</a></li>
                <li class="breadcrumb-item active">{{ $category->name }}</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Category Header -->
<section class="bg-white">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <h1>{{ $category->name }}</h1>
                <p style="font-size: 1.1rem; color: #666;">{{ $category->description }}</p>
            </div>
            <div class="col-md-4 text-md-end">
                        @if($category->icon_safe)
                    {{-- Safe: icon_safe is sanitized by SafeHtml rule in ServiceCategory model --}}
                    <div style="font-size: 4rem;">
                        {{ $category->icon_safe }}
                    </div>
                @else
                    <i class="fas fa-cube" style="font-size: 4rem; color: var(--primary);"></i>
                @endif
            </div>
        </div>
    </div>
</section>

<!-- Plans -->
<section>
    <div class="container">
        <h2 class="mb-5">Available Plans</h2>
        
        <div class="row">
            @forelse($plans as $plan)
                <div class="col-md-4 mb-4">
                    <div class="card plan-card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">{{ $plan->name }}</h5>
                            <p class="card-text">{{ $plan->description }}</p>

                            <!-- Pricing -->
                            <div class="plan-price">
                                @if($plan->price_monthly)
                                    ${{ number_format($plan->price_monthly, 2) }}<span style="font-size: 1rem; color: #666;">/mo</span>
                                @else
                                    <span style="font-size: 1rem;">Contact us</span>
                                @endif
                            </div>

                            <!-- Features -->
                            @if($plan->features)
                                <ul class="plan-features flex-grow-1">
                                    @foreach($plan->features as $feature)
                                        <li>{{ $feature }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <!-- CTA -->
                            <form action="{{ route('checkout.process', $plan->id) }}" method="POST" class="mt-auto">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Billing Cycle:</label>
                                    <select name="billing_cycle" class="form-select">
                                        @if($plan->price_monthly)
                                            <option value="monthly">Monthly</option>
                                        @endif
                                        @if($plan->price_yearly)
                                            <option value="yearly">Yearly</option>
                                        @endif
                                        @if($plan->price_lifetime)
                                            <option value="lifetime">Lifetime</option>
                                        @endif
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-shopping-cart"></i> Order Now
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No plans available in this category yet.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- CTA -->
<section style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white;">
    <div class="container">
        <div class="text-center">
            <h2>Need a custom solution?</h2>
            <p class="mt-2" style="font-size: 1.1rem;">Contact us for custom packages and enterprise solutions.</p>
            <a href="mailto:sales@example.com" class="btn btn-light mt-3">
                <i class="fas fa-envelope"></i> Get In Touch
            </a>
        </div>
    </div>
</section>
@endsection
