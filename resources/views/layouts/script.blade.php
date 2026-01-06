<!-- latest jquery-->
<script src="{{ asset('assets/js/jquery-3.6.3.min.js') }}"></script>

<!-- Bootstrap js-->
<script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>

<!-- Simple bar js-->
<script src="{{ asset('assets/vendor/simplebar/simplebar.js') }}"></script>

<!-- Customizer js-->
<script src="{{ asset('assets/js/customizer.js') }}"></script>

<!-- prism js-->
<script src="{{ asset('assets/vendor/prism/prism.min.js') }}"></script>

<!-- Moment js (for daterangepicker) -->
<script src="{{ asset('assets/vendor/moment/moment.min.js') }}"></script>

<!-- DateRangePicker js -->
<script src="{{ asset('assets/vendor/daterangepicker/daterangepicker.min.js') }}"></script>

<!-- DataTables js -->
<script src="{{ asset('assets/vendor/datatable/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/vendor/datatable/dataTables.bs5.min.js') }}"></script>
<script src="{{ asset('assets/vendor/datatable/dataTables.responsive.min.js') }}"></script>

<!-- Select2 js -->
<script src="{{ asset('assets/vendor/select2/select2.min.js') }}"></script>

<!-- SweetAlert2 js -->
<script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.min.js') }}"></script>

<!-- jQuery Validation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/additional-methods.min.js"></script>

<!-- jQuery UI (for sortable/draggable) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>

<!-- App js-->
<script src="{{ asset('assets/js/script.js') }}"></script>

<script>
// Setup AJAX CSRF token
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Global Toast notification function
function showToast(type, message, title = null) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    Toast.fire({
        icon: type,
        title: title || message,
        text: title ? message : null
    });
}

// Initialize Select2 with Bootstrap 5 theme
$(document).ready(function() {
    if($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }
});
</script>

@yield('script')
@stack('scripts')
