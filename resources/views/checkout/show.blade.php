@extends('app')

@section('title', 'Checkout - ' . config('app.name'))

@section('content')
<div style="padding: 40px 0;">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <h2>Checkout</h2>
                <hr>

                <!-- Plan Summary -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h6>{{ $plan->category->name }}</h6>
                                <h4>{{ $plan->name }}</h4>
                                <p class="text-muted">{{ $plan->description }}</p>

                                @if($plan->features)
                                    <strong>Features:</strong>
                                    <ul class="mt-2">
                                        @foreach($plan->features as $feature)
                                            <li>{{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <div class="col-md-4 text-md-end">
                                <p class="text-muted">{{ ucfirst($billingCycle) }} Billing</p>
                                <h3 style="color: var(--primary);">${{ number_format($price, 2) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Checkout Form -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Complete Your Order</h5>
                    </div>
                    <div class="card-body">
                        @auth
                            <p class="mb-3">Logged in as: <strong>{{ auth()->user()->email }}</strong></p>

                            <form action="{{ route('checkout.process', $plan->id) }}" method="POST">
                                @csrf

                                <input type="hidden" name="billing_cycle" value="{{ $billingCycle }}">

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Note:</strong> Payment gateway integration coming soon. 
                                    For now, your order will be activated immediately.
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="agree" required>
                                    <label class="form-check-label" for="agree">
                                        I agree to the <a href="{{ url('/pages/terms') }}" target="_blank">Terms of Service</a>
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-success btn-lg w-100">
                                    <i class="fas fa-lock"></i> Complete Order - ${{ number_format($price, 2) }}
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Please log in to continue.</strong>
                            </div>

                            <p class="mb-3">You need to be logged in to place an order.</p>

                            <a href="{{ route('login', ['plan_id' => $plan->id, 'cycle' => $billingCycle]) }}" class="btn btn-primary me-2">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>

                            <a href="{{ route('register') }}" class="btn btn-secondary">
                                <i class="fas fa-user-plus"></i> Create Account
                            </a>
                        @endauth
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Order Details -->
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Order Details</h5>
                    </div>
                    <div class="card-body">
                        <table class="w-100">
                            <tr>
                                <td>Plan:</td>
                                <td class="text-end"><strong>{{ $plan->name }}</strong></td>
                            </tr>
                            <tr>
                                <td>Category:</td>
                                <td class="text-end">{{ $plan->category->name }}</td>
                            </tr>
                            <tr>
                                <td>Billing Cycle:</td>
                                <td class="text-end">{{ ucfirst($billingCycle) }}</td>
                            </tr>
                            <tr style="border-top: 2px solid #eee; border-bottom: 2px solid #eee;">
                                <td><strong>Total:</strong></td>
                                <td class="text-end"><strong style="color: var(--primary); font-size: 1.2rem;">${{ number_format($price, 2) }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
