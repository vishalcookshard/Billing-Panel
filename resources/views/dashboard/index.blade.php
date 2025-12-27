@extends('app')

@section('title', 'Dashboard - ' . config('app.name'))

@section('content')
<div class="container mt-5">
    @if(Auth::user() && Auth::user()->password_change_required)
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Security Warning!</strong> You are using default credentials. 
        <a href="{{ route('password.change') }}" class="alert-link">Change your password now</a>.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    <div class="row">
        <div class="col-md-3">
            <!-- Sidebar -->
            <div class="list-group">
                <a href="{{ route('dashboard.index') }}" class="list-group-item list-group-item-action active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="{{ route('dashboard.orders') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-shopping-bag"></i> My Orders
                </a>
                <a href="{{ url('/shop') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-store"></i> Browse Services
                </a>
            </div>
        </div>

        <div class="col-md-9">
            <!-- Welcome -->
            <h1>Welcome, {{ $user->name ?? $user->email }}!</h1>
            <hr>

            <!-- Stats -->
            <div class="row mb-5">
                <div class="col-md-4 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 style="color: var(--primary);">{{ $orders->count() }}</h3>
                            <p class="text-muted">Active Orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 style="color: var(--success);">${{ number_format($orders->sum('amount'), 2) }}</h3>
                            <p class="text-muted">Total Spent</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 style="color: var(--primary);">{{ auth()->user()->created_at->diffInDays(now()) }}</h3>
                            <p class="text-muted">Days Member</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <h3>Recent Orders</h3>
            @if($orders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders->take(5) as $order)
                                <tr>
                                    <td>{{ $order->plan->name }}</td>
                                    <td>{{ $order->plan->category->name }}</td>
                                    <td>${{ number_format($order->amount, 2) }}</td>
                                    <td>
                                        @if($order->status === 'active')
                                            <span class="badge bg-success">Active</span>
                                        @elseif($order->status === 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($order->status === 'suspended')
                                            <span class="badge bg-danger">Suspended</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($order->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('dashboard.orders') }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($orders->count() > 5)
                    <div class="text-center">
                        <a href="{{ route('dashboard.orders') }}" class="btn btn-outline-primary">
                            View All Orders
                        </a>
                    </div>
                @endif
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    You don't have any orders yet. <a href="{{ url('/shop') }}">Browse our services</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
