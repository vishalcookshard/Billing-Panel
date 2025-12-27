@extends('app')

@section('title', 'Shop - ' . config('app.name'))

@section('content')
<!-- Hero -->
<div style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; padding: 40px 0;">
    <div class="container">
        <h1 style="font-size: 2.5rem;">Our Services</h1>
        <p>Choose from a variety of hosting and service options</p>
    </div>
</div>

<!-- Categories -->
<section class="bg-white">
    <div class="container">
        <div class="row">
            @forelse($categories as $category)
                <div class="col-md-4 mb-4">
                    <div class="card plan-card">
                        <div class="card-body">
                            @if($category->icon)
                                {{-- Safe: icon_safe is sanitized by SafeHtml rule in ServiceCategory model --}}
                                <div style="font-size: 3rem; margin-bottom: 15px;">
                                    {!! $category->icon_safe !!}
                                </div>
                            @else
                                <i class="fas fa-cube" style="font-size: 3rem; color: var(--primary); margin-bottom: 15px;"></i>
                            @endif
                            <h5 class="card-title">{{ $category->name }}</h5>
                            <p class="card-text">{{ Str::limit($category->description, 120) }}</p>
                            <p style="color: #666; font-size: 0.9rem;">
                                <i class="fas fa-box"></i>
                                {{ $category->plans->where('is_active', true)->count() }} 
                                {{ Str::plural('plan', $category->plans->where('is_active', true)->count()) }}
                            </p>
                            <a href="{{ url('/shop/' . $category->slug) }}" class="btn btn-primary">
                                Shop Now <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No services available yet. Please check back soon!
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
