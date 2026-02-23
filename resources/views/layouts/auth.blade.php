<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="JAI E-Kanban - Electronic Kanban Management System">
    <meta name="author" content="Technobit Indonesia">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('assets/images/logo/favicon.png') }}" type="image/x-icon">
    <title>@yield('title', 'Login') | JAI E-Kanban</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tabler icons-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/tabler-icons/tabler-icons.css') }}">

    <!-- Font Awesome -->
    <link rel="stylesheet" type="text/css" href="{{ asset('plugins/fontawesome-free-6.5.2-web/css/fontawesome.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('plugins/fontawesome-free-6.5.2-web/css/solid.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('plugins/fontawesome-free-6.5.2-web/css/regular.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('plugins/fontawesome-free-6.5.2-web/css/brands.min.css') }}">

    <!-- Bootstrap css-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">

    <!-- Main Style css -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.css') }}">

    <style>
        /* Font Awesome Override untuk Auth Pages */
        i[class*="fa-"],
        i[class^="fa-"] {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            display: inline-block;
            font-style: normal;
            font-variant: normal;
            text-rendering: auto;
            line-height: 1;
        }

        .fa-regular {
            font-weight: 400 !important;
        }

        .fa-brands {
            font-family: "Font Awesome 6 Brands" !important;
            font-weight: 400 !important;
        }

        /* Login page styles */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .app-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: white;
            border-radius: 1rem !important;
        }

        .card-body {
            padding: 2.5rem !important;
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem !important;
            }
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }

        .form-control {
            border-left: none;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: none;
        }

        .form-control:focus + .input-group-text {
            border-color: #667eea;
        }
    </style>

    @yield('css')
</head>

<body>
    <div class="app-wrapper d-block">
        <div class="">
            <!-- Body main section starts -->
            <main class="w-100">
                @yield('content')
            </main>
            <!-- Body main section ends -->
        </div>
    </div>

    <!-- Bootstrap js-->
    <script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    
    <!-- jQuery -->
    <script src="{{ asset('assets/js/jquery-3.6.3.min.js') }}"></script>
    
    <!-- jQuery Validation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>

    @yield('script')
</body>

</html>
