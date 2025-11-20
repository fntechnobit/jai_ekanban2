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
            <input type="text" name="email" class="form-control form-input-fields" id="email" placeholder="Email" value="{{ old('email') }}" required>
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-envelope"></span>
                </div>
            </div>
        </div>
        <div class="input-group mb-3">
            <input type="password" name="password" class="form-control form-input-fields" id="password" placeholder="Password" required>
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
$(document).ready(function() {
    $('#loginForm').validate({
        rules: {
            email: {
                required: true,
                email: true
            },
            password: {
                required: true,
                minlength: 6
            }
        },
        messages: {
            email: {
                required: "Please enter your email",
                email: "Please enter a valid email address"
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
