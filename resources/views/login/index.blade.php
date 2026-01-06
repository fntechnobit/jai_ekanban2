@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<!-- sign in start -->
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-7 col-xl-8 d-none d-lg-block p-0">
            <div class="image-contentbox">
                <img src="{{ asset('assets/images/login/01.png') }}" class="img-fluid" alt="Login Background">
            </div>
        </div>
        <div class="col-lg-5 col-xl-4 p-0 bg-white">
            <div class="form-container">
                <form class="app-form" action="{{ route('login') }}" method="POST" id="loginForm">
                    @csrf
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-5 text-center text-lg-start">
                                <h2 class="text-primary f-w-600">Welcome To E-Kanban!</h2>
                                <p>Sign in with your credentials to access the system</p>
                            </div>
                        </div>

                        @if ($errors->any())
                            <div class="col-12">
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            </div>
                        @endif

                        <div class="col-12">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                    <input type="text" name="username" class="form-control form-control-sm" placeholder="Enter Your Username" id="username" value="{{ old('username') }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" name="password" class="form-control form-control-sm" placeholder="Enter Your Password" id="password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label text-secondary" for="remember">
                                    Remember me
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fa-solid fa-right-to-bracket me-1"></i> Sign In
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="text-center text-muted mt-4">
                                <small>
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    Contact administrator if you forgot your password
                                </small>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Footer -->
                <div class="mt-auto pt-4 text-center">
                    <p class="text-muted small mb-0">
                        © {{ date('Y') }} <a href="https://technobit.co.id" target="_blank" class="text-decoration-none">Technobit Indonesia</a>. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- sign in end -->
@endsection

@section('script')
<script>
    $(document).ready(function() {
        // Form validation
        $('#loginForm').validate({
            rules: {
                username: {
                    required: true,
                },
                password: {
                    required: true,
                    minlength: 6
                }
            },
            messages: {
                username: {
                    required: "Please enter your username",
                },
                password: {
                    required: "Please enter your password",
                    minlength: "Password must be at least 6 characters"
                }
            },
            errorElement: 'span',
            errorPlacement: function(error, element) {
                error.addClass('invalid-feedback');
                element.closest('.mb-3').append(error);
            },
            highlight: function(element) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function(element) {
                $(element).removeClass('is-invalid');
            }
        });

        // Toggle password visibility
        $('.toggle-password').on('click', function() {
            const input = $(this).siblings('input');
            const icon = $(this).find('i');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('ti-eye').addClass('ti-eye-off');
            } else {
                input.attr('type', 'password');
                icon.removeClass('ti-eye-off').addClass('ti-eye');
            }
        });
    });
</script>
@endsection