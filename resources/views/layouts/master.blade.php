<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <!-- All meta and title start-->
    @include('layouts.head')
    <!-- meta and title end-->

    <!-- css start-->
    @include('layouts.css')
    <!-- css end-->
</head>

<body data-sidebar="dark" data-layout="ltr" text="medium-text">
    <!-- Loader start-->
    <div class="app-wrapper warm">
        <div class="loader-wrapper">
            <div class="app-loader">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        <!-- Loader end-->

        <!-- Menu Navigation start -->
        @include('layouts.sidebar')
        <!-- Menu Navigation end -->

        <div class="app-content">
            <!-- Header Section start -->
            @include('layouts.header')
            <!-- Header Section end -->

            <!-- Main Section start -->
            <main class="p-0">
                {{-- Main content --}}
                @yield('content')
            </main>
            <!-- Main Section end -->
        </div>

        <!-- tap on top -->
        <div class="go-top">
            <span class="progress-value">
                <i class="fa-solid fa-arrow-up"></i>
            </span>
        </div>

        <!-- Footer Section start -->
        @include('layouts.footer')
        <!-- Footer Section end -->
    </div>

    <!-- scripts start-->
    @include('layouts.script')
    <!-- scripts end-->
</body>

</html>
