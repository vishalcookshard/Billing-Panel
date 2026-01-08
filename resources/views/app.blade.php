<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', config('app.name'))">
    <meta name="keywords" content="@yield('meta_keywords')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --danger: #e74c3c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 1.25rem;
            margin-bottom: 30px;
        }

        /* Cards */
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.15);
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        /* Footer */
        footer {
            background-color: var(--secondary);
            color: white;
            padding: 40px 0;
            margin-top: 60px;
        }

        footer a {
            color: #ecf0f1;
            text-decoration: none;
        }

        footer a:hover {
            color: var(--primary);
        }

        /* Plan Cards */
        .plan-card {
            text-align: center;
            padding: 30px;
        }

        .plan-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 20px 0;
        }

        .plan-features {
            text-align: left;
            margin: 20px 0;
            list-style: none;
        }

        .plan-features li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .plan-features li:before {
            content: "✓ ";
            color: var(--success);
            font-weight: 700;
            margin-right: 10px;
        }

        /* Alert Messages */
        .alert {
            border-radius: 8px;
        }

        /* Container spacing */
        .container {
            max-width: 1200px;
        }

        section {
            padding: 60px 0;
        }
    </style>

    @yield('extra_css')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ url('/') }}">
                <i class="fas fa-cube"></i> {{ config('app.name', 'Billing Panel') }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/shop') }}">Shop</a>
                    </li>
                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                {{ auth()->user()->name ?? auth()->user()->email }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="{{ route('dashboard.index') }}">Dashboard</a></li>
                                <li><a class="dropdown-item" href="{{ route('dashboard.orders') }}">My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                @if(auth()->user()->is_admin ?? false)
                                    <li><a class="dropdown-item" href="{{ route('admin.pages.index') }}">Admin Panel</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                @endif
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button class="dropdown-item" type="submit">Logout</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">Register</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <!-- Messages -->
    @if($message = Session::get('success'))
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    @if($message = Session::get('error'))
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    @yield('content')

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-cube"></i> {{ config('app.name') }}</h5>
                    <p>Professional billing and hosting management platform.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="{{ url('/') }}">Home</a></li>
                        <li><a href="{{ url('/shop') }}">Shop</a></li>
                        <li><a href="{{ url('/pages/privacy') }}">Privacy</a></li>
                        <li><a href="{{ url('/pages/terms') }}">Terms</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="mailto:support@example.com">Contact Support</a></li>
                        <li><a href="{{ url('/pages/faq') }}">FAQ</a></li>
                    </ul>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @yield('extra_js')
</body>
</html>
