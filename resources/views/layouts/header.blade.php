<!-- Header Section starts -->
<header class="header-main">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 d-flex align-items-center header-left">
                                <span class="header-toggle me-3">
                                    <i class="fa-solid fa-table-cells-large"></i>
                                </span>

                                {{-- SIREP sync/generate status — shared across Dashboard, Assy Scheduler
                                     and Schedule Verification, populated by assy-generate-shared.js --}}
                                <div class="d-flex align-items-center gap-2 flex-wrap d-none d-md-flex" id="sync-status-badges">
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" id="badge-last-sync" title="Terakhir sinkronisasi data dari SIREP">
                                        <i class="fa-solid fa-rotate me-1"></i> Sinkron: <span id="last-sync-time">memuat...</span>
                                    </span>
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" id="badge-last-generate" title="Terakhir generate jadwal assy">
                                        <i class="fa-solid fa-calendar-check me-1"></i> Generate: <span id="last-generate-time">memuat...</span>
                                    </span>
                                </div>
                            </div>

                            <div class="col-6 d-flex align-items-center justify-content-end header-right">
                                <ul class="d-flex align-items-center">
                                    <li class="header-search d-md-none">
                                        <a href="#" class="d-block head-icon" role="button" data-bs-toggle="offcanvas"
                                            data-bs-target="#offcanvasTop" aria-controls="offcanvasTop">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </a>

                                        <div class="offcanvas offcanvas-top search-canvas" tabindex="-1" id="offcanvasTop">
                                            <div class="offcanvas-body">
                                                <div class="d-flex align-items-center">
                                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                                                </div>
                                            </div>
                                        </div>
                                    </li>

                                    <li class="header-dark head-icon">
                                        <div class="sun-logo">
                                            <i class="fa-solid fa-sun"></i>
                                        </div>
                                        <div class="moon-logo">
                                            <i class="fa-solid fa-moon-filled"></i>
                                        </div>
                                    </li>

                                    <li class="header-notification">
                                        <div class="flex-shrink-0 app-dropdown">
                                            <a href="#" class="d-block head-icon position-relative" id="notificationBell" data-bs-toggle="dropdown"
                                                data-bs-auto-close="outside" aria-expanded="false">
                                                <i class="fa-solid fa-bell"></i>
                                                <span id="notificationBadge" class="position-absolute translate-middle p-1 bg-success border border-light rounded-circle animate__animated animate__fadeIn animate__infinite animate__slower" style="display: none;"></span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end bg-transparent border-0">
                                                <div class="card">
                                                    <div class="card-header bg-primary">
                                                        <h5 class="text-white">Notification 
                                                            <span class="float-end"><i class="fa-solid fa-bell text-white"></i></span>
                                                        </h5>
                                                    </div>
                                                    <div class="card-body p-0">
                                                        <div class="head-container app-scroll">
                                                            <div id="notificationContent" class="hidden-massage py-4 px-3 text-center">
                                                                <img src="{{ asset('assets/images/icons/bell.png') }}" class="w-50 h-50 mb-3 mt-2" alt="">
                                                                <div>
                                                                    <h6 class="mb-0">No Notifications</h6>
                                                                    <p class="text-secondary">You're all caught up!</p>
                                                                </div>
                                                            </div>
                                                            <div id="notificationList" style="display: none;">
                                                                <!-- Notifications will be dynamically added here -->
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>

                                    <li class="header-profile">
                                        <div class="flex-shrink-0 dropdown">
                                            <a href="#" class="d-block head-icon pe-0" data-bs-toggle="dropdown" aria-expanded="false">
                                                <div class="d-flex align-items-center">
                                                    <span class="h-35 w-35 d-flex-center b-r-50 bg-primary text-white">
                                                        <i class="fa-solid fa-user f-s-18"></i>
                                                    </span>
                                                </div>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end header-card border-0 px-2">
                                                <li class="dropdown-item d-flex align-items-center p-2">
                                                    <span class="h-35 w-35 d-flex-center b-r-50 bg-primary text-white position-relative">
                                                        <i class="fa-solid fa-user f-s-18"></i>
                                                        <span class="position-absolute top-0 end-0 p-1 bg-success border border-light rounded-circle"></span>
                                                    </span>
                                                    <div class="flex-grow-1 ps-2">
                                                        <h6 class="mb-0">{{ Auth::user()->name ?? 'User' }}</h6>
                                                        <p class="f-s-12 mb-0 text-secondary">{{ Auth::user()->userGroup->name ?? 'Guest' }}</p>
                                                    </div>
                                                </li>

                                                <li class="app-divider-v dotted py-1"></li>
                                                <li>
                                                    <a class="dropdown-item" href="#">
                                                        <i class="fa-solid fa-user-circle pe-1 f-s-18"></i> Profile
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#">
                                                        <i class="fa-solid fa-gear pe-1 f-s-18"></i> Settings
                                                    </a>
                                                </li>

                                                <li class="app-divider-v dotted py-1"></li>
                                                <li class="btn-light-danger b-r-5">
                                                    <form action="{{ route('logout') }}" method="POST" class="d-inline w-100">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item mb-0 text-danger border-0 bg-transparent w-100 text-start">
                                                            <i class="fa-solid fa-right-from-bracket pe-1 f-s-18 text-danger"></i> Log Out
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- Header Section ends -->
