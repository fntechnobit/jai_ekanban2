
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>JAI E-Kanban</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ url('plugins/fontawesome-free/css/all.min.css') }}">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="{{ url('plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">

  <!-- Data Tables -->
  <link rel="stylesheet" href="{{ url('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ url('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ url('plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">

  <!-- Select2 -->
  <link rel="stylesheet" href="{{ url('plugins/select2/css/select2.min.css') }}">

  <!-- SweetAlert -->
  <link rel="stylesheet" href="{{ url('plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">

  <!-- Date Range Picker CSS -->
  <link rel="stylesheet" href="{{ url('plugins/daterangepicker/daterangepicker.css') }}">

  <!-- Date Time Picker CSS -->
  <link rel="stylesheet" href="{{ url('plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">

  <!-- Theme style -->
  <link rel="stylesheet" href="{{ url('css/adminlte.min.css') }}">
  <link rel="stylesheet" href="{{ url('css/custom.css') }}">

  <meta name="csrf-token" content="{{ csrf_token() }}">

  <style>
.bg-ok {
    background-color: #d4edda !important;
}
.bg-not-ok {
    background-color: #dc3545 !important;
}
.bg-in-progress {
    background-color: #fff3cd !important;
}
</style>
  @yield('css')
  @stack('styles')
</head>
<body class="hold-transition sidebar-mini sidebar-collapse layout-fixed layout-navbar-fixed layout-footer-fixed accent-primary">
<!-- Site wrapper -->
<div class="wrapper">
  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-primary navbar-dark">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown">
        <a class="nav-link text-white" data-toggle="dropdown" href="#">
          <i class="fas fa-user"></i> {{ Auth::user()->name }}
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <a href="#" class="dropdown-item">
            <i class="fas fa-user mr-2"></i> Profile
          </a>
          <div class="dropdown-divider"></div>
          <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="dropdown-item">
              <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </button>
          </form>
        </div>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  @include('side_nav')

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    @yield('content')
  </div>
  <!-- /.content-wrapper -->

  <footer class="main-footer">
    <div class="float-right d-none d-sm-block">
      <b>Version</b> 1.0.0
    </div>
    <strong>Copyright &copy; 2025 <a href="https://technobit.co.id">Technobit Indonesia</a>.</strong> All rights reserved.
  </footer>
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="{{ url('plugins/jquery/jquery.min.js') }}"></script>
<!-- jQuery UI -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/ui-lightness/jquery-ui.min.css">
<!-- Bootstrap 4 -->
<script src="{{ url('plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<!-- overlayScrollbars -->
<script src="{{ url('plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ url('js/adminlte.min.js') }}"></script>
<!-- Data Tables -->
<script src="{{ url('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ url('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<!-- JqueryValidation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/additional-methods.min.js"></script>

<!-- Select2 -->
<script src="{{ url('plugins/select2/js/select2.full.min.js') }}"></script>

<!-- Date Range Picker JS -->
<script src="{{ url('plugins/moment/moment.min.js') }}"></script>
<script src="{{ url('plugins/daterangepicker/daterangepicker.js') }}"></script>

<!-- Date Time Picker JS -->
<script src="{{ url('plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>

<!-- SweetAlert -->
<script src="{{ url('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Setup AJAX CSRF token
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
</script>

@yield('script')
@stack('scripts')
</body>
</html>
