@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<!-- sign in start -->
<div class="container-fluid h-100">
    <div class="row h-100 justify-content-center align-items-center" style="padding-top: 3rem; padding-bottom: 3rem;">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <form class="app-form" action="{{ route('login') }}" method="POST" id="loginForm">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4 text-center">
                                    <h2 class="text-primary f-w-600 mb-2">Welcome To E-Kanban!</h2>
                                    <p class="text-muted">Sign in with your credentials to access the system</p>
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
                                    <input type="text" name="username" class="form-control" placeholder="Enter Your Username" id="username" value="{{ old('username') }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="Enter Your Password" id="password" required>
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
                            <div class="text-center text-muted mt-3">
                                <small>
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    Contact administrator if you forgot your password
                                </small>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Footer -->
                <div class="text-center mt-4 pt-3 border-top">
                    <p class="text-muted mb-0">
                        <small>
                            © {{ date('Y') }} 
                            <a href="https://technobit.co.id" target="_blank" class="text-decoration-none">Technobit Indonesia</a>. 
                            All rights reserved.
                        </small>
                    </p>
                </div>
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
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    });
</script>
@endsection