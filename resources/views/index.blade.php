@extends('app')

@section('title', 'Home - ' . config('app.name'))

@section('content')
<!-- Hero Section -->
<div class="hero">
    <div class="container">
        <h1>Welcome to {{ config('app.name') }}</h1>
        <p>Professional hosting and billing management platform</p>
        <a href="{{ url('/shop') }}" class="btn btn-light btn-lg">
            <i class="fas fa-store"></i> Start Shopping
        </a>
    </div>
</div>

<!-- Featured Categories -->
<section class="bg-white">
    <div class="container">
        <h2 class="text-center mb-5">Our Services</h2>
        <div class="row">
            @forelse($categories as $category)
                <div class="col-md-4 mb-4">
                    <div class="card plan-card">
                        <div class="card-body">
                            @if($category->icon)
                                {{-- Safe: icon_safe is sanitized by SafeHtml rule in ServiceCategory model --}}
                                <div style="font-size: 3rem; margin-bottom: 15px;">
                                    {{ $category->icon_safe }}
                                </div>
                            @else
                                <i class="fas fa-cube" style="font-size: 3rem; color: var(--primary); margin-bottom: 15px;"></i>
                            @endif
                            <h5 class="card-title">{{ $category->name }}</h5>
                            <p class="card-text">{{ Str::limit($category->description, 100) }}</p>
                            <a href="{{ url('/shop/' . $category->slug) }}" class="btn btn-primary">
                                View Plans <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <p class="text-center text-muted">No services available yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- CTA Section -->
<section style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white;">
    <div class="container">
        <div class="text-center">
            <h2>Ready to get started?</h2>
            <p class="mt-3" style="font-size: 1.1rem;">Browse our full service catalog and find the perfect plan for your needs.</p>
            <a href="{{ url('/shop') }}" class="btn btn-light btn-lg mt-3">
                <i class="fas fa-shopping-cart"></i> Browse All Services
            </a>
        </div>
    </div>
</section>
@endsection
