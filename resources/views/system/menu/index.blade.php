@extends('layout')

@section('title', 'Menu Management')

@section('content')
<div class="content-header" >
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Menu Management</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Menus</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Menu List</h3>
                <div class="card-tools">
                    @if(auth()->user()->hasMenuPermission('menus', 'can_create'))
                        <button type="button" class="btn btn-primary btn-sm" id="btn-add">
                            <i class="fas fa-plus"></i> Add Menu
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Drag and drop</strong> to reorder menus. Drop a menu onto another to make it a child. 
                    Changes are saved automatically.
                </div>
                
                <div id="menu-tree-container">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Loading menus...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@include('system.menu.form')
@endsection

@push('styles')
<!-- SortableJS Nested styles -->
<style>
.menu-tree {
    list-style: none;
    padding-left: 0;
    min-height: 50px;
}

.menu-tree .menu-tree {
    padding-left: 40px;
    margin-top: 5px;
}

.menu-item {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 5px;
    padding: 10px 15px;
    cursor: move;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.menu-item:hover {
    background: #f8f9fa;
    border-color: #007bff;
}

.menu-item.sortable-ghost {
    opacity: 0.4;
    background: #c8ebfb;
}

.menu-item.sortable-chosen {
    background: #e3f2fd;
}

.menu-item-content {
    display: flex;
    align-items: center;
    flex: 1;
}

.menu-item-handle {
    cursor: move;
    color: #999;
    margin-right: 15px;
}

.menu-item-icon {
    width: 30px;
    text-align: center;
    margin-right: 10px;
    color: #666;
}

.menu-item-info {
    flex: 1;
}

.menu-item-name {
    font-weight: 600;
    color: #333;
}

.menu-item-code {
    font-size: 12px;
    color: #666;
    margin-left: 10px;
}

.menu-item-url {
    font-size: 12px;
    color: #888;
}

.menu-item-actions {
    display: flex;
    gap: 5px;
}

.menu-item-badge {
    margin-left: 10px;
}

.menu-children {
    min-height: 10px;
}

.menu-children.empty-placeholder {
    min-height: 30px;
    border: 2px dashed #ddd;
    border-radius: 4px;
    margin: 5px 0 5px 40px;
    background: #f9f9f9;
}

.nested-sortable-placeholder {
    background: #e8f4fc;
    border: 2px dashed #007bff;
    border-radius: 4px;
    min-height: 50px;
    margin-bottom: 5px;
}

.saving-indicator {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #17a2b8;
    color: white;
    padding: 10px 20px;
    border-radius: 4px;
    display: none;
    z-index: 1050;
}

.saving-indicator.show {
    display: block;
}

/* Select2 Icon Picker Styles */
.select2-icon-option {
    display: flex;
    align-items: center;
    gap: 10px;
}

.select2-icon-option i {
    width: 20px;
    text-align: center;
    font-size: 16px;
}

.select2-container--default .select2-selection--single {
    height: 38px;
    padding: 4px 0;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 28px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}
</style>
@endpush

@push('scripts')
<!-- SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<!-- Select2 -->
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<!-- SweetAlert2 -->
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
// FontAwesome Icons List
const fontAwesomeIcons = [
    // Dashboard & Navigation
    { id: 'fas fa-tachometer-alt', text: 'Tachometer (Dashboard)' },
    { id: 'fas fa-home', text: 'Home' },
    { id: 'fas fa-bars', text: 'Bars (Menu)' },
    { id: 'fas fa-th', text: 'Grid' },
    { id: 'fas fa-th-large', text: 'Grid Large' },
    { id: 'fas fa-th-list', text: 'List' },
    { id: 'fas fa-list', text: 'List Alt' },
    { id: 'fas fa-list-ul', text: 'List Unordered' },
    { id: 'fas fa-list-ol', text: 'List Ordered' },
    
    // Users & People
    { id: 'fas fa-user', text: 'User' },
    { id: 'fas fa-users', text: 'Users' },
    { id: 'fas fa-user-plus', text: 'User Plus' },
    { id: 'fas fa-user-minus', text: 'User Minus' },
    { id: 'fas fa-user-edit', text: 'User Edit' },
    { id: 'fas fa-user-cog', text: 'User Cog' },
    { id: 'fas fa-user-shield', text: 'User Shield' },
    { id: 'fas fa-user-tie', text: 'User Tie' },
    { id: 'fas fa-user-circle', text: 'User Circle' },
    { id: 'fas fa-users-cog', text: 'Users Cog' },
    { id: 'fas fa-id-card', text: 'ID Card' },
    { id: 'fas fa-id-badge', text: 'ID Badge' },
    { id: 'fas fa-address-book', text: 'Address Book' },
    { id: 'fas fa-address-card', text: 'Address Card' },
    
    // Settings & Configuration
    { id: 'fas fa-cog', text: 'Cog (Settings)' },
    { id: 'fas fa-cogs', text: 'Cogs (Settings Multiple)' },
    { id: 'fas fa-sliders-h', text: 'Sliders' },
    { id: 'fas fa-wrench', text: 'Wrench' },
    { id: 'fas fa-tools', text: 'Tools' },
    { id: 'fas fa-toolbox', text: 'Toolbox' },
    { id: 'fas fa-screwdriver', text: 'Screwdriver' },
    { id: 'fas fa-hammer', text: 'Hammer' },
    
    // Data & Database
    { id: 'fas fa-database', text: 'Database' },
    { id: 'fas fa-server', text: 'Server' },
    { id: 'fas fa-hdd', text: 'Hard Drive' },
    { id: 'fas fa-table', text: 'Table' },
    { id: 'fas fa-columns', text: 'Columns' },
    { id: 'fas fa-stream', text: 'Stream' },
    
    // Files & Documents
    { id: 'fas fa-file', text: 'File' },
    { id: 'fas fa-file-alt', text: 'File Alt' },
    { id: 'fas fa-file-pdf', text: 'File PDF' },
    { id: 'fas fa-file-excel', text: 'File Excel' },
    { id: 'fas fa-file-word', text: 'File Word' },
    { id: 'fas fa-file-image', text: 'File Image' },
    { id: 'fas fa-file-archive', text: 'File Archive' },
    { id: 'fas fa-file-code', text: 'File Code' },
    { id: 'fas fa-file-csv', text: 'File CSV' },
    { id: 'fas fa-file-download', text: 'File Download' },
    { id: 'fas fa-file-upload', text: 'File Upload' },
    { id: 'fas fa-file-import', text: 'File Import' },
    { id: 'fas fa-file-export', text: 'File Export' },
    { id: 'fas fa-folder', text: 'Folder' },
    { id: 'fas fa-folder-open', text: 'Folder Open' },
    { id: 'fas fa-folder-plus', text: 'Folder Plus' },
    { id: 'fas fa-folder-minus', text: 'Folder Minus' },
    { id: 'fas fa-copy', text: 'Copy' },
    { id: 'fas fa-paste', text: 'Paste' },
    { id: 'fas fa-clipboard', text: 'Clipboard' },
    { id: 'fas fa-clipboard-list', text: 'Clipboard List' },
    { id: 'fas fa-clipboard-check', text: 'Clipboard Check' },
    
    // Charts & Reports
    { id: 'fas fa-chart-bar', text: 'Chart Bar' },
    { id: 'fas fa-chart-line', text: 'Chart Line' },
    { id: 'fas fa-chart-pie', text: 'Chart Pie' },
    { id: 'fas fa-chart-area', text: 'Chart Area' },
    { id: 'fas fa-poll', text: 'Poll' },
    { id: 'fas fa-poll-h', text: 'Poll Horizontal' },
    { id: 'fas fa-percentage', text: 'Percentage' },
    { id: 'fas fa-analytics', text: 'Analytics' },
    
    // Commerce & Business
    { id: 'fas fa-shopping-cart', text: 'Shopping Cart' },
    { id: 'fas fa-shopping-bag', text: 'Shopping Bag' },
    { id: 'fas fa-shopping-basket', text: 'Shopping Basket' },
    { id: 'fas fa-store', text: 'Store' },
    { id: 'fas fa-store-alt', text: 'Store Alt' },
    { id: 'fas fa-cash-register', text: 'Cash Register' },
    { id: 'fas fa-receipt', text: 'Receipt' },
    { id: 'fas fa-money-bill', text: 'Money Bill' },
    { id: 'fas fa-money-bill-wave', text: 'Money Bill Wave' },
    { id: 'fas fa-money-check', text: 'Money Check' },
    { id: 'fas fa-money-check-alt', text: 'Money Check Alt' },
    { id: 'fas fa-credit-card', text: 'Credit Card' },
    { id: 'fas fa-wallet', text: 'Wallet' },
    { id: 'fas fa-coins', text: 'Coins' },
    { id: 'fas fa-piggy-bank', text: 'Piggy Bank' },
    { id: 'fas fa-dollar-sign', text: 'Dollar Sign' },
    { id: 'fas fa-euro-sign', text: 'Euro Sign' },
    { id: 'fas fa-pound-sign', text: 'Pound Sign' },
    { id: 'fas fa-yen-sign', text: 'Yen Sign' },
    { id: 'fas fa-tags', text: 'Tags' },
    { id: 'fas fa-tag', text: 'Tag' },
    { id: 'fas fa-barcode', text: 'Barcode' },
    { id: 'fas fa-qrcode', text: 'QR Code' },
    
    // Industry & Manufacturing
    { id: 'fas fa-industry', text: 'Industry' },
    { id: 'fas fa-warehouse', text: 'Warehouse' },
    { id: 'fas fa-boxes', text: 'Boxes' },
    { id: 'fas fa-box', text: 'Box' },
    { id: 'fas fa-box-open', text: 'Box Open' },
    { id: 'fas fa-pallet', text: 'Pallet' },
    { id: 'fas fa-dolly', text: 'Dolly' },
    { id: 'fas fa-dolly-flatbed', text: 'Dolly Flatbed' },
    { id: 'fas fa-truck', text: 'Truck' },
    { id: 'fas fa-truck-loading', text: 'Truck Loading' },
    { id: 'fas fa-shipping-fast', text: 'Shipping Fast' },
    { id: 'fas fa-conveyor-belt', text: 'Conveyor Belt' },
    
    // Location & Map
    { id: 'fas fa-map', text: 'Map' },
    { id: 'fas fa-map-marked', text: 'Map Marked' },
    { id: 'fas fa-map-marked-alt', text: 'Map Marked Alt' },
    { id: 'fas fa-map-marker', text: 'Map Marker' },
    { id: 'fas fa-map-marker-alt', text: 'Map Marker Alt' },
    { id: 'fas fa-map-pin', text: 'Map Pin' },
    { id: 'fas fa-location-arrow', text: 'Location Arrow' },
    { id: 'fas fa-compass', text: 'Compass' },
    { id: 'fas fa-globe', text: 'Globe' },
    { id: 'fas fa-globe-asia', text: 'Globe Asia' },
    { id: 'fas fa-globe-americas', text: 'Globe Americas' },
    { id: 'fas fa-globe-europe', text: 'Globe Europe' },
    { id: 'fas fa-building', text: 'Building' },
    { id: 'fas fa-city', text: 'City' },
    { id: 'fas fa-landmark', text: 'Landmark' },
    
    // Communication
    { id: 'fas fa-envelope', text: 'Envelope' },
    { id: 'fas fa-envelope-open', text: 'Envelope Open' },
    { id: 'fas fa-inbox', text: 'Inbox' },
    { id: 'fas fa-paper-plane', text: 'Paper Plane' },
    { id: 'fas fa-comment', text: 'Comment' },
    { id: 'fas fa-comments', text: 'Comments' },
    { id: 'fas fa-comment-alt', text: 'Comment Alt' },
    { id: 'fas fa-comment-dots', text: 'Comment Dots' },
    { id: 'fas fa-phone', text: 'Phone' },
    { id: 'fas fa-phone-alt', text: 'Phone Alt' },
    { id: 'fas fa-mobile', text: 'Mobile' },
    { id: 'fas fa-mobile-alt', text: 'Mobile Alt' },
    { id: 'fas fa-fax', text: 'Fax' },
    { id: 'fas fa-bell', text: 'Bell' },
    { id: 'fas fa-bell-slash', text: 'Bell Slash' },
    { id: 'fas fa-bullhorn', text: 'Bullhorn' },
    { id: 'fas fa-broadcast-tower', text: 'Broadcast Tower' },
    
    // Time & Calendar
    { id: 'fas fa-calendar', text: 'Calendar' },
    { id: 'fas fa-calendar-alt', text: 'Calendar Alt' },
    { id: 'fas fa-calendar-check', text: 'Calendar Check' },
    { id: 'fas fa-calendar-plus', text: 'Calendar Plus' },
    { id: 'fas fa-calendar-minus', text: 'Calendar Minus' },
    { id: 'fas fa-calendar-times', text: 'Calendar Times' },
    { id: 'fas fa-calendar-day', text: 'Calendar Day' },
    { id: 'fas fa-calendar-week', text: 'Calendar Week' },
    { id: 'fas fa-clock', text: 'Clock' },
    { id: 'fas fa-hourglass', text: 'Hourglass' },
    { id: 'fas fa-hourglass-half', text: 'Hourglass Half' },
    { id: 'fas fa-hourglass-start', text: 'Hourglass Start' },
    { id: 'fas fa-hourglass-end', text: 'Hourglass End' },
    { id: 'fas fa-stopwatch', text: 'Stopwatch' },
    { id: 'fas fa-history', text: 'History' },
    
    // Security & Access
    { id: 'fas fa-lock', text: 'Lock' },
    { id: 'fas fa-lock-open', text: 'Lock Open' },
    { id: 'fas fa-unlock', text: 'Unlock' },
    { id: 'fas fa-unlock-alt', text: 'Unlock Alt' },
    { id: 'fas fa-key', text: 'Key' },
    { id: 'fas fa-shield-alt', text: 'Shield' },
    { id: 'fas fa-fingerprint', text: 'Fingerprint' },
    { id: 'fas fa-eye', text: 'Eye' },
    { id: 'fas fa-eye-slash', text: 'Eye Slash' },
    { id: 'fas fa-ban', text: 'Ban' },
    { id: 'fas fa-user-lock', text: 'User Lock' },
    { id: 'fas fa-user-secret', text: 'User Secret' },
    
    // Actions & Controls
    { id: 'fas fa-plus', text: 'Plus' },
    { id: 'fas fa-plus-circle', text: 'Plus Circle' },
    { id: 'fas fa-plus-square', text: 'Plus Square' },
    { id: 'fas fa-minus', text: 'Minus' },
    { id: 'fas fa-minus-circle', text: 'Minus Circle' },
    { id: 'fas fa-minus-square', text: 'Minus Square' },
    { id: 'fas fa-times', text: 'Times (Close)' },
    { id: 'fas fa-times-circle', text: 'Times Circle' },
    { id: 'fas fa-check', text: 'Check' },
    { id: 'fas fa-check-circle', text: 'Check Circle' },
    { id: 'fas fa-check-square', text: 'Check Square' },
    { id: 'fas fa-edit', text: 'Edit' },
    { id: 'fas fa-pen', text: 'Pen' },
    { id: 'fas fa-pencil-alt', text: 'Pencil' },
    { id: 'fas fa-trash', text: 'Trash' },
    { id: 'fas fa-trash-alt', text: 'Trash Alt' },
    { id: 'fas fa-eraser', text: 'Eraser' },
    { id: 'fas fa-save', text: 'Save' },
    { id: 'fas fa-download', text: 'Download' },
    { id: 'fas fa-upload', text: 'Upload' },
    { id: 'fas fa-sync', text: 'Sync' },
    { id: 'fas fa-sync-alt', text: 'Sync Alt' },
    { id: 'fas fa-redo', text: 'Redo' },
    { id: 'fas fa-undo', text: 'Undo' },
    { id: 'fas fa-refresh', text: 'Refresh' },
    { id: 'fas fa-search', text: 'Search' },
    { id: 'fas fa-search-plus', text: 'Search Plus' },
    { id: 'fas fa-search-minus', text: 'Search Minus' },
    { id: 'fas fa-filter', text: 'Filter' },
    { id: 'fas fa-sort', text: 'Sort' },
    { id: 'fas fa-sort-up', text: 'Sort Up' },
    { id: 'fas fa-sort-down', text: 'Sort Down' },
    { id: 'fas fa-expand', text: 'Expand' },
    { id: 'fas fa-compress', text: 'Compress' },
    { id: 'fas fa-arrows-alt', text: 'Arrows Alt' },
    { id: 'fas fa-external-link-alt', text: 'External Link' },
    { id: 'fas fa-link', text: 'Link' },
    { id: 'fas fa-unlink', text: 'Unlink' },
    { id: 'fas fa-share', text: 'Share' },
    { id: 'fas fa-share-alt', text: 'Share Alt' },
    { id: 'fas fa-reply', text: 'Reply' },
    { id: 'fas fa-reply-all', text: 'Reply All' },
    { id: 'fas fa-forward', text: 'Forward' },
    
    // Status & Indicators
    { id: 'fas fa-info', text: 'Info' },
    { id: 'fas fa-info-circle', text: 'Info Circle' },
    { id: 'fas fa-question', text: 'Question' },
    { id: 'fas fa-question-circle', text: 'Question Circle' },
    { id: 'fas fa-exclamation', text: 'Exclamation' },
    { id: 'fas fa-exclamation-circle', text: 'Exclamation Circle' },
    { id: 'fas fa-exclamation-triangle', text: 'Exclamation Triangle' },
    { id: 'fas fa-flag', text: 'Flag' },
    { id: 'fas fa-flag-checkered', text: 'Flag Checkered' },
    { id: 'fas fa-star', text: 'Star' },
    { id: 'fas fa-star-half-alt', text: 'Star Half' },
    { id: 'fas fa-heart', text: 'Heart' },
    { id: 'fas fa-thumbs-up', text: 'Thumbs Up' },
    { id: 'fas fa-thumbs-down', text: 'Thumbs Down' },
    { id: 'fas fa-bookmark', text: 'Bookmark' },
    { id: 'fas fa-trophy', text: 'Trophy' },
    { id: 'fas fa-award', text: 'Award' },
    { id: 'fas fa-medal', text: 'Medal' },
    { id: 'fas fa-certificate', text: 'Certificate' },
    
    // Arrows & Direction
    { id: 'fas fa-arrow-up', text: 'Arrow Up' },
    { id: 'fas fa-arrow-down', text: 'Arrow Down' },
    { id: 'fas fa-arrow-left', text: 'Arrow Left' },
    { id: 'fas fa-arrow-right', text: 'Arrow Right' },
    { id: 'fas fa-arrow-circle-up', text: 'Arrow Circle Up' },
    { id: 'fas fa-arrow-circle-down', text: 'Arrow Circle Down' },
    { id: 'fas fa-arrow-circle-left', text: 'Arrow Circle Left' },
    { id: 'fas fa-arrow-circle-right', text: 'Arrow Circle Right' },
    { id: 'fas fa-chevron-up', text: 'Chevron Up' },
    { id: 'fas fa-chevron-down', text: 'Chevron Down' },
    { id: 'fas fa-chevron-left', text: 'Chevron Left' },
    { id: 'fas fa-chevron-right', text: 'Chevron Right' },
    { id: 'fas fa-angle-up', text: 'Angle Up' },
    { id: 'fas fa-angle-down', text: 'Angle Down' },
    { id: 'fas fa-angle-left', text: 'Angle Left' },
    { id: 'fas fa-angle-right', text: 'Angle Right' },
    { id: 'fas fa-angle-double-up', text: 'Angle Double Up' },
    { id: 'fas fa-angle-double-down', text: 'Angle Double Down' },
    { id: 'fas fa-angle-double-left', text: 'Angle Double Left' },
    { id: 'fas fa-angle-double-right', text: 'Angle Double Right' },
    
    // Tasks & Projects
    { id: 'fas fa-tasks', text: 'Tasks' },
    { id: 'fas fa-project-diagram', text: 'Project Diagram' },
    { id: 'fas fa-sitemap', text: 'Sitemap' },
    { id: 'fas fa-network-wired', text: 'Network' },
    { id: 'fas fa-code-branch', text: 'Code Branch' },
    { id: 'fas fa-code', text: 'Code' },
    { id: 'fas fa-terminal', text: 'Terminal' },
    { id: 'fas fa-bug', text: 'Bug' },
    { id: 'fas fa-lightbulb', text: 'Lightbulb' },
    { id: 'fas fa-magic', text: 'Magic' },
    { id: 'fas fa-rocket', text: 'Rocket' },
    { id: 'fas fa-bolt', text: 'Bolt' },
    { id: 'fas fa-fire', text: 'Fire' },
    { id: 'fas fa-fire-alt', text: 'Fire Alt' },
    
    // Media & Content
    { id: 'fas fa-image', text: 'Image' },
    { id: 'fas fa-images', text: 'Images' },
    { id: 'fas fa-camera', text: 'Camera' },
    { id: 'fas fa-video', text: 'Video' },
    { id: 'fas fa-film', text: 'Film' },
    { id: 'fas fa-music', text: 'Music' },
    { id: 'fas fa-headphones', text: 'Headphones' },
    { id: 'fas fa-microphone', text: 'Microphone' },
    { id: 'fas fa-volume-up', text: 'Volume Up' },
    { id: 'fas fa-volume-down', text: 'Volume Down' },
    { id: 'fas fa-volume-mute', text: 'Volume Mute' },
    { id: 'fas fa-play', text: 'Play' },
    { id: 'fas fa-pause', text: 'Pause' },
    { id: 'fas fa-stop', text: 'Stop' },
    { id: 'fas fa-step-forward', text: 'Step Forward' },
    { id: 'fas fa-step-backward', text: 'Step Backward' },
    
    // Misc
    { id: 'fas fa-circle', text: 'Circle' },
    { id: 'fas fa-square', text: 'Square' },
    { id: 'fas fa-cube', text: 'Cube' },
    { id: 'fas fa-cubes', text: 'Cubes' },
    { id: 'fas fa-puzzle-piece', text: 'Puzzle Piece' },
    { id: 'fas fa-gem', text: 'Gem' },
    { id: 'fas fa-gift', text: 'Gift' },
    { id: 'fas fa-crown', text: 'Crown' },
    { id: 'fas fa-sun', text: 'Sun' },
    { id: 'fas fa-moon', text: 'Moon' },
    { id: 'fas fa-cloud', text: 'Cloud' },
    { id: 'fas fa-cloud-upload-alt', text: 'Cloud Upload' },
    { id: 'fas fa-cloud-download-alt', text: 'Cloud Download' },
    { id: 'fas fa-print', text: 'Print' },
    { id: 'fas fa-desktop', text: 'Desktop' },
    { id: 'fas fa-laptop', text: 'Laptop' },
    { id: 'fas fa-tablet-alt', text: 'Tablet' },
    { id: 'fas fa-keyboard', text: 'Keyboard' },
    { id: 'fas fa-mouse', text: 'Mouse' },
    { id: 'fas fa-power-off', text: 'Power Off' },
    { id: 'fas fa-plug', text: 'Plug' },
    { id: 'fas fa-battery-full', text: 'Battery Full' },
    { id: 'fas fa-wifi', text: 'WiFi' },
    { id: 'fas fa-signal', text: 'Signal' },
    { id: 'fas fa-bluetooth', text: 'Bluetooth' },
    { id: 'fas fa-rss', text: 'RSS' },
    { id: 'fas fa-at', text: 'At' },
    { id: 'fas fa-hashtag', text: 'Hashtag' },
    { id: 'fas fa-quote-left', text: 'Quote Left' },
    { id: 'fas fa-quote-right', text: 'Quote Right' },
    { id: 'fas fa-paragraph', text: 'Paragraph' },
    { id: 'fas fa-align-left', text: 'Align Left' },
    { id: 'fas fa-align-center', text: 'Align Center' },
    { id: 'fas fa-align-right', text: 'Align Right' },
    { id: 'fas fa-align-justify', text: 'Align Justify' },
    { id: 'fas fa-bold', text: 'Bold' },
    { id: 'fas fa-italic', text: 'Italic' },
    { id: 'fas fa-underline', text: 'Underline' },
    { id: 'fas fa-strikethrough', text: 'Strikethrough' },
    { id: 'fas fa-text-height', text: 'Text Height' },
    { id: 'fas fa-text-width', text: 'Text Width' },
    { id: 'fas fa-font', text: 'Font' },
    { id: 'fas fa-heading', text: 'Heading' },
    { id: 'fas fa-spell-check', text: 'Spell Check' },
    { id: 'fas fa-language', text: 'Language' },
    { id: 'fas fa-palette', text: 'Palette' },
    { id: 'fas fa-paint-brush', text: 'Paint Brush' },
    { id: 'fas fa-fill-drip', text: 'Fill Drip' },
    { id: 'fas fa-tint', text: 'Tint' },
    { id: 'fas fa-adjust', text: 'Adjust' },
    { id: 'fas fa-crop', text: 'Crop' },
    { id: 'fas fa-ruler', text: 'Ruler' },
    { id: 'fas fa-ruler-combined', text: 'Ruler Combined' },
    { id: 'fas fa-drafting-compass', text: 'Drafting Compass' },
    { id: 'fas fa-object-group', text: 'Object Group' },
    { id: 'fas fa-object-ungroup', text: 'Object Ungroup' },
    { id: 'fas fa-layer-group', text: 'Layer Group' },
    { id: 'fas fa-grip-vertical', text: 'Grip Vertical' },
    { id: 'fas fa-grip-horizontal', text: 'Grip Horizontal' },
    { id: 'fas fa-ellipsis-h', text: 'Ellipsis Horizontal' },
    { id: 'fas fa-ellipsis-v', text: 'Ellipsis Vertical' },
];

// Format icon option for Select2
function formatIconOption(icon) {
    if (!icon.id) return icon.text;
    return $('<span class="select2-icon-option"><i class="' + icon.id + '"></i> ' + icon.text + '</span>');
}

$(function() {
    let menuTree = [];
    let sortableInstances = [];
    const canUpdate = {{ auth()->user()->hasMenuPermission('menus', 'can_update') ? 'true' : 'false' }};
    const canDelete = {{ auth()->user()->hasMenuPermission('menus', 'can_delete') ? 'true' : 'false' }};

    // Load menu tree
    loadMenuTree();

    function loadMenuTree() {
        $.ajax({
            url: "{{ route('system.menus.tree') }}",
            type: 'GET',
            success: function(response) {
                menuTree = response.data || response;
                renderMenuTree(menuTree);
                initSortable();
            },
            error: function(xhr) {
                $('#menu-tree-container').html(
                    '<div class="alert alert-danger">Failed to load menus. Please refresh the page.</div>'
                );
            }
        });
    }

    function renderMenuTree(items, isChild = false) {
        let html = '<ul class="menu-tree' + (isChild ? ' menu-children' : '') + '" data-parent="' + (isChild ? 'child' : 'root') + '">';
        
        items.forEach(function(item) {
            html += renderMenuItem(item);
        });
        
        html += '</ul>';
        
        if (!isChild) {
            $('#menu-tree-container').html(html);
        }
        
        return html;
    }

    function renderMenuItem(item) {
        let statusBadge = item.is_active 
            ? '<span class="badge bg-success menu-item-badge">Active</span>' 
            : '<span class="badge bg-danger menu-item-badge">Inactive</span>';
        
        let actions = '';
        if (canUpdate) {
            actions += '<button type="button" class="btn btn-sm btn-info btn-edit" data-id="' + item.id + '" title="Edit"><i class="fas fa-edit"></i></button>';
        }
        if (canDelete) {
            actions += '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' + item.id + '" title="Delete"><i class="fas fa-trash"></i></button>';
        }

        let html = '<li class="menu-item-wrapper" data-id="' + item.id + '">';
        html += '<div class="menu-item">';
        html += '<div class="menu-item-content">';
        html += '<span class="menu-item-handle"><i class="fas fa-grip-vertical"></i></span>';
        html += '<span class="menu-item-icon"><i class="' + (item.icon || 'fas fa-circle') + '"></i></span>';
        html += '<div class="menu-item-info">';
        html += '<span class="menu-item-name">' + item.name + '</span>';
        html += '<span class="menu-item-code">(' + item.code + ')</span>';
        html += statusBadge;
        html += '<div class="menu-item-url">' + item.url + '</div>';
        html += '</div>';
        html += '</div>';
        html += '<div class="menu-item-actions">' + actions + '</div>';
        html += '</div>';
        
        // Children container (always present for drop target)
        html += '<ul class="menu-tree menu-children" data-parent="' + item.id + '">';
        if (item.children && item.children.length > 0) {
            item.children.forEach(function(child) {
                html += renderMenuItem(child);
            });
        }
        html += '</ul>';
        
        html += '</li>';
        return html;
    }

    function initSortable() {
        // Destroy existing instances
        sortableInstances.forEach(function(instance) {
            instance.destroy();
        });
        sortableInstances = [];

        // Initialize sortable on all menu-tree elements
        document.querySelectorAll('.menu-tree').forEach(function(el) {
            let sortable = new Sortable(el, {
                group: 'nested',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                handle: '.menu-item-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: function(evt) {
                    saveMenuOrder();
                }
            });
            sortableInstances.push(sortable);
        });
    }

    function saveMenuOrder() {
        showSavingIndicator();
        
        let items = getNestedItems(document.querySelector('.menu-tree[data-parent="root"]'));
        
        $.ajax({
            url: "{{ route('system.menus.reorder') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                items: items
            },
            success: function(response) {
                hideSavingIndicator();
                // Reinitialize sortable for new children containers
                initSortable();
            },
            error: function(xhr) {
                hideSavingIndicator();
                Swal.fire('Error!', 'Failed to save menu order', 'error');
                loadMenuTree(); // Reload on error
            }
        });
    }

    function getNestedItems(container) {
        let items = [];
        if (!container) return items;
        
        container.querySelectorAll(':scope > .menu-item-wrapper').forEach(function(el) {
            let item = {
                id: el.dataset.id,
                children: []
            };
            
            let childContainer = el.querySelector(':scope > .menu-children');
            if (childContainer) {
                item.children = getNestedItems(childContainer);
            }
            
            items.push(item);
        });
        
        return items;
    }

    function showSavingIndicator() {
        if (!$('.saving-indicator').length) {
            $('body').append('<div class="saving-indicator"><i class="fas fa-spinner fa-spin"></i> Saving...</div>');
        }
        $('.saving-indicator').addClass('show');
    }

    function hideSavingIndicator() {
        $('.saving-indicator').removeClass('show');
    }

    // Initialize Icon Select2
    function initIconSelect2(selectedValue = '') {
        // Destroy existing instance if any
        if ($('#icon').hasClass('select2-hidden-accessible')) {
            $('#icon').select2('destroy');
        }
        
        // Clear and populate options
        $('#icon').html('<option value="">-- Select Icon --</option>');
        fontAwesomeIcons.forEach(function(icon) {
            $('#icon').append('<option value="' + icon.id + '">' + icon.text + '</option>');
        });
        
        // Initialize Select2
        $('#icon').select2({
            dropdownParent: $('#menuModal'),
            placeholder: '-- Select Icon --',
            allowClear: true,
            templateResult: formatIconOption,
            templateSelection: formatIconOption,
            matcher: function(params, data) {
                // Custom search: search in both id (icon class) and text
                if ($.trim(params.term) === '') {
                    return data;
                }
                
                var term = params.term.toLowerCase();
                var text = data.text.toLowerCase();
                var id = data.id ? data.id.toLowerCase() : '';
                
                if (text.indexOf(term) > -1 || id.indexOf(term) > -1) {
                    return data;
                }
                
                return null;
            }
        });
        
        // Set value if provided
        if (selectedValue) {
            // Add option if not exists
            if ($('#icon option[value="' + selectedValue + '"]').length === 0) {
                var iconText = selectedValue.replace('fas fa-', '').replace(/-/g, ' ');
                iconText = iconText.charAt(0).toUpperCase() + iconText.slice(1);
                $('#icon').append('<option value="' + selectedValue + '">' + iconText + ' (Custom)</option>');
            }
            $('#icon').val(selectedValue).trigger('change');
        }
    }

    // Add Menu Button
    $('#btn-add').click(function() {
        $('#menuForm')[0].reset();
        $('#menu_id').val('');
        $('#menuModalLabel').text('Add Menu');
        $('.error-text').text('');
        refreshParentDropdown();
        initIconSelect2('');
        $('#menuModal').modal('show');
    });

    // Refresh parent dropdown with current menu tree
    function refreshParentDropdown() {
        let $select = $('#parent_id');
        let currentId = $('#menu_id').val();
        
        $select.html('<option value="">None (Top Level)</option>');
        
        function addOptions(items, prefix = '') {
            items.forEach(function(item) {
                // Don't show current item or its children as parent options
                if (item.id != currentId) {
                    $select.append('<option value="' + item.id + '">' + prefix + item.name + '</option>');
                    if (item.children && item.children.length > 0) {
                        addOptions(item.children, prefix + '— ');
                    }
                }
            });
        }
        
        addOptions(menuTree);
    }

    // Edit Menu
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $.ajax({
            url: "{{ route('system.menus.index') }}/" + id + "/edit",
            type: 'GET',
            success: function(response) {
                const menu = response.data || response;

                $('#menu_id').val(menu.id);
                $('#code').val(menu.code);
                $('#name').val(menu.name);
                $('#url').val(menu.url);
                $('#order').val(menu.order);

                if(menu.is_active == 1) {
                    $('#is_active_yes').prop('checked', true);
                } else {
                    $('#is_active_no').prop('checked', true);
                }
                
                refreshParentDropdown();
                $('#parent_id').val(menu.parent_id);
                
                // Initialize icon select2 with current value
                initIconSelect2(menu.icon || '');
                
                $('#menuModalLabel').text('Edit Menu');
                $('.error-text').text('');
                $('#menuModal').modal('show');
            },
            error: function(xhr) {
                Swal.fire('Error!', 'Failed to load menu data', 'error');
            }
        });
    });

    // Save Menu
    $('#menuForm').submit(function(e) {
        e.preventDefault();
        $('.error-text').text('');
        
        var formData = $(this).serialize();
        var menuId = $('#menu_id').val();
        var url = menuId ? "{{ route('system.menus.index') }}/" + menuId : "{{ route('system.menus.store') }}";
        var method = menuId ? 'PUT' : 'POST';
        
        if(menuId) {
            formData += '&_method=PUT';
        }
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function(response) {
                $('#menuModal').modal('hide');
                loadMenuTree(); // Reload tree instead of datatable
                Swal.fire('Success!', response.message, 'success');
            },
            error: function(xhr) {
                if(xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        $('.' + key + '_error').text(value[0]);
                    });
                } else {
                    Swal.fire('Error!', xhr.responseJSON.message || 'Something went wrong', 'error');
                }
            }
        });
    });

    // Delete Menu
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('system.menus.index') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        loadMenuTree(); // Reload tree instead of datatable
                        Swal.fire('Deleted!', response.message, 'success');
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON.message || 'Failed to delete menu', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
