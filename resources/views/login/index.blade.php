@extends('extra_layout')

@section('style')

@stop

@section('content')
<div class="card">
    <div class="card-body login-card-body">
        @if ($errors->any())
            <div class="alert alert-danger mt-3">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <p class="login-box-msg">Sign in to start your session</p>
        <form action="{{ route('login') }}" method="POST" id="loginForm">
            @csrf
            <div class="input-group mb-3">
                <input type="text" name="username" class="form-control form-input-fields" id="username"
                    placeholder="Username" value="{{ old('username') }}" required>
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-user"></span>
                    </div>
                </div>
            </div>
            <div class="input-group mb-3">
                <input type="password" name="password" class="form-control form-input-fields" id="password"
                    placeholder="Password" required>
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-lock"></span>
                    </div>
                </div>
            </div>
            <div class="row">
                <!-- /.col -->
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                </div>
                <!-- /.col -->
            </div>
        </form>
    </div>
    <!-- /.login-card-body -->
</div>
@stop

@section('script')
    <script>
        $(document).ready(function () {
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
                }
            });
        });
    </script>
@endsection