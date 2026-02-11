<!-- Animation css -->
<link rel="stylesheet" href="{{ asset('assets/vendor/animation/animate.min.css') }}">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<!-- Weather icon css-->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/weather/weather-icons.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/weather/weather-icons-wind.css') }}">

<!--font-awesome-css-->
<link rel="stylesheet" href="{{ asset('plugins/fontawesome-free-6.5.2-web/css/all.min.css') }}?v=6.5.2">

<!--Flag Icon css-->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/flag-icons-master/flag-icon.css') }}">

<!-- Tabler icons-->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/tabler-icons/tabler-icons.css') }}">

<!-- Prism css-->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/prism/prism.min.css') }}">

<!-- Bootstrap css-->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">

<!-- Simplebar css-->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/simplebar/simplebar.css') }}">

<!-- DataTables css -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/datatable/dataTables.bs5.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/datatable/datatable.responsive.min.css') }}">

<!-- Select2 css -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/select2/select2.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/select2/select2-bootstrap-5-theme.min.css') }}">

<!-- Daterangepicker css -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/daterangepicker/daterangepicker.css') }}">

<!-- SweetAlert2 css -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/sweetalert2/sweetalert2.min.css') }}">

<!-- Main Style css -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.css') }}">

<!-- Custom overrides -->
<style>
    /* Ensure breadcrumb nav is not affected by sidebar nav styles */
    nav[aria-label="breadcrumb"],
    .page-header-breadcrumb nav {
        width: auto !important;
        height: auto !important;
        display: block !important;
        position: static !important;
        background-color: transparent !important;
        z-index: auto !important;
    }
    
    /* Soft Button Styles for DataTable Actions */
    .btn-soft-primary {
        color: #4154f1;
        background-color: rgba(65, 84, 241, 0.1);
        border-color: transparent;
    }
    .btn-soft-primary:hover {
        color: #fff;
        background-color: #4154f1;
        border-color: #4154f1;
    }
    .btn-soft-secondary {
        color: #6c757d;
        background-color: rgba(108, 117, 125, 0.1);
        border-color: transparent;
    }
    .btn-soft-secondary:hover {
        color: #fff;
        background-color: #6c757d;
        border-color: #6c757d;
    }
    .btn-soft-success {
        color: #198754;
        background-color: rgba(25, 135, 84, 0.1);
        border-color: transparent;
    }
    .btn-soft-success:hover {
        color: #fff;
        background-color: #198754;
        border-color: #198754;
    }
    .btn-soft-danger {
        color: #dc3545;
        background-color: rgba(220, 53, 69, 0.1);
        border-color: transparent;
    }
    .btn-soft-danger:hover {
        color: #fff;
        background-color: #dc3545;
        border-color: #dc3545;
    }
    .btn-soft-warning {
        color: #ffc107;
        background-color: rgba(255, 193, 7, 0.1);
        border-color: transparent;
    }
    .btn-soft-warning:hover {
        color: #000;
        background-color: #ffc107;
        border-color: #ffc107;
    }
    .btn-soft-info {
        color: #0dcaf0;
        background-color: rgba(13, 202, 240, 0.1);
        border-color: transparent;
    }
    .btn-soft-info:hover {
        color: #000;
        background-color: #0dcaf0;
        border-color: #0dcaf0;
    }
    
    /* Button group styling for DataTable actions */
    .btn-group {
        display: inline-flex;
        flex-wrap: nowrap;
        gap: 2px;
    }
    .btn-group .btn-sm {
        padding: 0.35rem 0.5rem;
        font-size: 0.8rem;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    .btn-group .btn-sm i {
        font-size: 1rem;
        line-height: 1;
    }
    
    /* Ensure buttons don't wrap text and icons */
    .btn-sm {
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    .btn-sm i {
        flex-shrink: 0;
    }
    
    /* DataTables action column */
    table.dataTable td .btn-group,
    table.dataTable td .btn-sm {
        white-space: nowrap;
    }
    
    /* Sidebar Active Menu Styling */
    nav .app-nav .main-nav > li:not(.menu-title) > a.active,
    nav .app-nav .main-nav > li.no-sub > a.active {
        color: var(--white) !important;
        background: rgba(var(--primary), 1) !important;
        border-radius: 5px;
    }
    nav .app-nav .main-nav > li:not(.menu-title) > a.active i,
    nav .app-nav .main-nav > li.no-sub > a.active i {
        color: var(--white) !important;
    }
    nav .app-nav .main-nav > li:not(.menu-title) ul li a.active {
        color: var(--white) !important;
        background: rgba(var(--primary), 1) !important;
        border-radius: 5px;
        display: block;
        padding: 0.5rem 1rem;
    }
    nav .app-nav .main-nav > li:not(.menu-title) ul li a.active::before {
        color: var(--white) !important;
    }
    
    /* Dark sidebar active state */
    nav.dark-sidebar .app-nav .main-nav > li:not(.menu-title) > a.active,
    nav.dark-sidebar .app-nav .main-nav > li.no-sub > a.active {
        background: rgba(var(--primary), 1) !important;
        color: var(--white) !important;
    }
    
    /* Font Awesome Icons - CRITICAL FIX for Sidebar and All Elements */
    /* Override tabler-icons with higher specificity */
    
    /* Fix for sidebar menu icons */
    nav .app-nav .main-nav > li:not(.menu-title) > a i,
    nav .app-nav .main-nav > li:not(.menu-title) ul li > a i,
    nav .app-nav .main-nav > li:not(.menu-title) ul li.another-level > a i,
    nav.dark-sidebar .app-nav .main-nav > li:not(.menu-title) > a i,
    nav.dark-sidebar .app-nav .main-nav > li:not(.menu-title) ul li > a i {
        font-family: "Font Awesome 6 Free" !important;
        font-weight: 900 !important;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        display: inline-block !important;
        font-style: normal !important;
        font-variant: normal !important;
        text-rendering: auto !important;
        line-height: 1 !important;
    }
    
    /* Global Font Awesome solid icons */
    i.fa-solid,
    i.fas,
    .fa-solid,
    .fas {
        font-family: "Font Awesome 6 Free" !important;
        font-weight: 900 !important;
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
        display: inline-block !important;
        font-style: normal !important;
        font-variant: normal !important;
        text-rendering: auto !important;
    }
    
    /* Global Font Awesome regular icons */
    i.fa-regular,
    i.far,
    .fa-regular,
    .far {
        font-family: "Font Awesome 6 Free" !important;
        font-weight: 400 !important;
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
        display: inline-block !important;
        font-style: normal !important;
        font-variant: normal !important;
        text-rendering: auto !important;
    }
    
    /* Global Font Awesome brands icons */
    i.fa-brands,
    i.fab,
    .fa-brands,
    .fab {
        font-family: "Font Awesome 6 Brands" !important;
        font-weight: 400 !important;
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
        display: inline-block !important;
        font-style: normal !important;
        font-variant: normal !important;
        text-rendering: auto !important;
    }
    
    /* Make sure Font Awesome overrides any tabler-icon styles */
    nav i[class*="fa-"],
    nav i[class^="fa-"],
    body i[class*="fa-"],
    body i[class^="fa-"] {
        font-family: var(--fa-style-family, "Font Awesome 6 Free") !important;
    }
</style>

@yield('css')
@stack('styles')

<style>
/* Custom E-Kanban Styles */
.bg-ok {
    background-color: #d4edda !important;
}
.bg-not-ok {
    background-color: #dc3545 !important;
}
.bg-in-progress {
    background-color: #fff3cd !important;
}

/* DataTable Adjustments */
.dataTables_wrapper .dataTables_length select {
    min-width: 60px;
}

/* Card hover effect */
.card {
    transition: box-shadow 0.3s ease;
}

/* Breadcrumb styling */
.app-breadcrumb {
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    background-color: transparent;
}

/* Content area padding */
main {
    padding: 1.5rem;
}

/* Stats Card Styling */
.stats-card {
    border-radius: 12px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

/* Select2 size matching form-control-sm */
.select2-container--bootstrap-5 .select2-selection {
    min-height: calc(1.5em + 0.5rem + 2px) !important;
    padding: 0.25rem 0.5rem !important;
    font-size: 0.875rem !important;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    padding: 0 !important;
    line-height: 1.5 !important;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
    height: calc(1.5em + 0.5rem) !important;
}

.select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered {
    padding: 0 !important;
}

.select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
    padding: 0.15rem 0.4rem !important;
    font-size: 0.8rem !important;
}

.select2-container--bootstrap-5 .select2-dropdown .select2-results__option {
    padding: 0.35rem 0.75rem !important;
    font-size: 0.875rem !important;
}

.select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
    padding: 0.25rem 0.5rem !important;
    font-size: 0.875rem !important;
}

/* Button size matching form-control-sm */
.btn-sm, .btn-group-sm > .btn {
    padding: 0.25rem 0.5rem !important;
    font-size: 0.875rem !important;
    line-height: 1.5 !important;
}

/* Make all buttons in card-header and filters same height as form-control-sm */
.card-header .btn,
.filter-section .btn,
.input-group .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
}

/* DateRangePicker input matching form-control-sm */
.daterangepicker-input {
    height: calc(1.5em + 0.5rem + 2px) !important;
    padding: 0.25rem 0.5rem !important;
    font-size: 0.875rem !important;
}
</style>
