# Template & Views Documentation

## AdminLTE Integration

The JAI E-Kanban application uses **AdminLTE 3** for its admin interface, matching the styling and structure from jai-sampling-qa-apps.

## Main Layouts

### 1. Main Layout (`layout.blade.php`)
Used for authenticated pages with full admin interface including sidebar navigation.

**Usage:**
```blade
@extends('layout')

@section('pageTitle', 'Page Title')

@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="#">Home</a></li>
    <li class="breadcrumb-item active">Page Title</li>
</ol>
@endsection

@section('content')
    <!-- Your content here -->
@endsection

@section('css')
    <!-- Additional CSS -->
@endsection

@section('script')
    <!-- Additional JavaScript -->
@endsection
```

### 2. Extra Layout (`extra_layout.blade.php`)
Used for login and other pages without sidebar navigation.

**Usage:**
```blade
@extends('extra_layout')

@section('content')
    <!-- Your login/register content -->
@endsection

@section('script')
    <!-- Additional JavaScript -->
@endsection
```

## Sidebar Navigation (`side_nav.blade.php`)

The sidebar menu is configured via `config/menu.php`. The menu structure supports:
- Single level menus
- Multi-level nested menus (up to 3 levels)
- Icons using Font Awesome

**Menu Configuration Example:**
```php
'main_menu' => [
    [
        'title' => 'Dashboard',
        'url' => 'dashboard',
        'icon' => 'tachometer-alt',  // Font Awesome icon name
        'sub_menu' => '#'  // No submenu
    ],
    [
        'title' => 'Management',
        'url' => '#',
        'icon' => 'clipboard-list',
        'sub_menu' => [
            [
                'title' => 'Items',
                'url' => 'management/items',
                'icon' => 'circle',
                'sub_menu' => '#'
            ],
        ]
    ],
]
```

## Available Views

### Dashboard
- **Path:** `resources/views/dashboard/index.blade.php`
- **Route:** `/dashboard`
- **Features:** Info boxes, welcome section

### Login
- **Path:** `resources/views/login/index.blade.php`
- **Route:** `/login` (needs to be configured with auth)
- **Features:** Login form with validation

## AdminLTE Components

### Info Boxes
```blade
<div class="info-box">
    <span class="info-box-icon bg-info elevation-1">
        <i class="fas fa-credit-card"></i>
    </span>
    <div class="info-box-content">
        <span class="info-box-text">Title</span>
        <span class="info-box-number">123</span>
    </div>
</div>
```

### Cards
```blade
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Card Title</h3>
    </div>
    <div class="card-body">
        <!-- Content -->
    </div>
    <div class="card-footer">
        <!-- Footer -->
    </div>
</div>
```

### Data Tables
```blade
<table id="myTable" class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Column 1</th>
            <th>Column 2</th>
        </tr>
    </thead>
    <tbody>
        <!-- Data rows -->
    </tbody>
</table>

@section('script')
<script>
$(document).ready(function() {
    $('#myTable').DataTable({
        responsive: true,
        autoWidth: false,
    });
});
</script>
@endsection
```

### Buttons
```blade
<button class="btn btn-primary">Primary</button>
<button class="btn btn-success">Success</button>
<button class="btn btn-warning">Warning</button>
<button class="btn btn-danger">Danger</button>
<button class="btn btn-info">Info</button>
```

## Included Plugins

- **DataTables** - For advanced table features
- **Select2** - Enhanced select boxes
- **SweetAlert2** - Beautiful alerts
- **DateRangePicker** - Date range selection
- **TempusDominus** - DateTime picker
- **Font Awesome** - Icons
- **jQuery Validate** - Form validation
- **Moment.js** - Date manipulation

## Custom CSS Classes

```css
.bg-ok          /* Green background for OK status */
.bg-not-ok      /* Red background for NOT OK status */
.bg-in-progress /* Yellow background for IN PROGRESS status */
```

## Asset Structure

```
public/
├── css/
│   └── adminlte.min.css
├── js/
│   └── adminlte.min.js
└── plugins/
    ├── fontawesome-free/
    ├── datatables/
    ├── select2/
    ├── sweetalert2/
    ├── daterangepicker/
    ├── tempusdominus-bootstrap-4/
    ├── jquery/
    ├── bootstrap/
    └── overlayScrollbars/
```

## Creating New Views

1. Create view file in `resources/views/your_module/`
2. Extend the appropriate layout
3. Define sections (pageTitle, breadcrumb, content, script, css)
4. Create controller and route
5. Add menu item to `config/menu.php` if needed

## Example: Complete CRUD View

```blade
@extends('layout')

@section('pageTitle', 'Items List')

@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ url('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Items</li>
</ol>
@endsection

@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Items List</h3>
                <div class="card-tools">
                    <a href="{{ route('items.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Add New
                    </a>
                </div>
            </div>
            <div class="card-body">
                <table id="itemsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data populated by DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@section('script')
<script>
$(document).ready(function() {
    $('#itemsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("items.data") }}',
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'action', name: 'action', orderable: false }
        ]
    });
});
</script>
@endsection
```

## Browser Access

Open your browser and navigate to:
- **Dashboard:** http://localhost:8001/dashboard
- **Login:** http://localhost:8001/login

## Notes

- All AdminLTE assets are copied from jai-sampling-qa-apps
- Styling and structure match the original application
- Menu system supports unlimited nesting levels
- All plugins are pre-configured and ready to use
