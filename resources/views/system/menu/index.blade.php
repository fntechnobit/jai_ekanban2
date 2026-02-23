@extends('layouts.master')

@section('title', 'Menu Management')

@section('breadcrumb')
    <x-page-header menu-code="menus" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Menu List</h5>
                <div class="card-tools float-end">
                    @if(auth()->user()->hasMenuPermission('menus', 'can_create'))
                        <button type="button" class="btn btn-primary btn-sm" id="btn-add">
                            <i class="fa-solid fa-plus"></i> Add Menu
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fa-solid fa-circle-info"></i> 
                    <strong>Drag and drop</strong> to reorder menus. Drop a menu onto another to make it a child. 
                    Changes are saved automatically.
                </div>
                
                <div id="menu-tree-container"
                        data-can-update="{{ auth()->user()->hasMenuPermission('menus', 'can_update') ? '1' : '0' }}"
                        data-can-delete="{{ auth()->user()->hasMenuPermission('menus', 'can_delete') ? '1' : '0' }}">
                    <div class="text-center py-4">
                        <i class="fa-solid fa-spinner ti-spin" style="font-size: 2rem;"></i>
                        <p class="mt-2">Loading menus...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
<script src="{{ asset('assets/vendor/sortablejs/Sortable.min.js') }}"></script>
<!-- Select2 -->
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<!-- SweetAlert2 -->
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
// Tabler Icons List
const tablerIcons = [
    // Dashboard & Navigation
    { id: 'fa-solid fa-gauge-high', text: 'Dashboard' },
    { id: 'fa-solid fa-home', text: 'Home' },
    { id: 'fa-solid fa-bars', text: 'Menu' },
    { id: 'fa-solid fa-table-cells', text: 'Grid' },
    { id: 'fa-solid fa-table-cells-large', text: 'Grid Large' },
    { id: 'fa-solid fa-list', text: 'List' },
    { id: 'fa-solid fa-rectangle-list', text: 'List Details' },
    { id: 'fa-solid fa-list-numbers', text: 'List Numbers' },
    
    // Users & People
    { id: 'fa-solid fa-user', text: 'User' },
    { id: 'fa-solid fa-users', text: 'Users' },
    { id: 'fa-solid fa-user-plus', text: 'User Plus' },
    { id: 'fa-solid fa-user-minus', text: 'User Minus' },
    { id: 'fa-solid fa-user-pen', text: 'User Edit' },
    { id: 'fa-solid fa-user-gear', text: 'User Cog' },
    { id: 'fa-solid fa-user-shield', text: 'User Shield' },
    { id: 'fa-solid fa-users-group', text: 'Users Group' },
    { id: 'fa-solid fa-id-card', text: 'ID Card' },
    { id: 'fa-solid fa-id-badge', text: 'ID Badge' },
    { id: 'fa-solid fa-address-book', text: 'Address Book' },
    
    // Settings & Configuration
    { id: 'fa-solid fa-gear', text: 'Settings' },
    { id: 'fa-solid fa-gears', text: 'Settings Alt' },
    { id: 'fa-solid fa-sliders', text: 'Adjustments' },
    { id: 'fa-solid fa-wrench', text: 'Tool' },
    { id: 'fa-solid fa-tools', text: 'Tools' },
    { id: 'fa-solid fa-hammer', text: 'Hammer' },
    
    // Data & Database
    { id: 'fa-solid fa-database', text: 'Database' },
    { id: 'fa-solid fa-server', text: 'Server' },
    { id: 'fa-solid fa-table', text: 'Table' },
    { id: 'fa-solid fa-columns', text: 'Columns' },
    
    // Files & Documents
    { id: 'fa-solid fa-file', text: 'File' },
    { id: 'fa-solid fa-file-text', text: 'File Text' },
    { id: 'fa-solid fa-file-type-pdf', text: 'File PDF' },
    { id: 'fa-solid fa-file-spreadsheet', text: 'File Excel' },
    { id: 'fa-solid fa-file-type-doc', text: 'File Word' },
    { id: 'fa-solid fa-image', text: 'File Image' },
    { id: 'fa-solid fa-file-zipper', text: 'File Archive' },
    { id: 'fa-solid fa-file-code', text: 'File Code' },
    { id: 'fa-solid fa-file-arrow-down', text: 'File Download' },
    { id: 'fa-solid fa-file-arrow-up', text: 'File Upload' },
    { id: 'fa-solid fa-file-import', text: 'File Import' },
    { id: 'fa-solid fa-file-export', text: 'File Export' },
    { id: 'fa-solid fa-folder', text: 'Folder' },
    { id: 'fa-solid fa-folder-open', text: 'Folder Open' },
    { id: 'fa-solid fa-folder-plus', text: 'Folder Plus' },
    { id: 'fa-solid fa-copy', text: 'Copy' },
    { id: 'fa-solid fa-clipboard', text: 'Clipboard' },
    { id: 'fa-solid fa-clipboard-list', text: 'Clipboard List' },
    { id: 'fa-solid fa-clipboard-check', text: 'Clipboard Check' },
    
    // Charts & Reports
    { id: 'fa-solid fa-chart-bar', text: 'Chart Bar' },
    { id: 'fa-solid fa-chart-line', text: 'Chart Line' },
    { id: 'fa-solid fa-chart-pie', text: 'Chart Pie' },
    { id: 'fa-solid fa-chart-area', text: 'Chart Area' },
    { id: 'fa-solid fa-chart-line', text: 'Analytics' },
    
    // Commerce & Business
    { id: 'fa-solid fa-shopping-cart', text: 'Shopping Cart' },
    { id: 'fa-solid fa-shopping-bag', text: 'Shopping Bag' },
    { id: 'fa-solid fa-store', text: 'Store' },
    { id: 'fa-solid fa-money-bill', text: 'Cash' },
    { id: 'fa-solid fa-receipt', text: 'Receipt' },
    { id: 'fa-solid fa-credit-card', text: 'Credit Card' },
    { id: 'fa-solid fa-wallet', text: 'Wallet' },
    { id: 'fa-solid fa-coins', text: 'Coins' },
    { id: 'fa-solid fa-dollar-sign', text: 'Dollar' },
    { id: 'fa-solid fa-tag', text: 'Tag' },
    { id: 'fa-solid fa-tags', text: 'Tags' },
    { id: 'fa-solid fa-barcode', text: 'Barcode' },
    { id: 'fa-solid fa-qrcode', text: 'QR Code' },
    
    // Industry & Manufacturing
    { id: 'fa-solid fa-industry', text: 'Factory' },
    { id: 'fa-solid fa-building-warehouse', text: 'Warehouse' },
    { id: 'fa-solid fa-box', text: 'Package' },
    { id: 'fa-solid fa-boxes', text: 'Packages' },
    { id: 'fa-solid fa-truck', text: 'Truck' },
    { id: 'fa-solid fa-truck-loading', text: 'Truck Delivery' },
    { id: 'fa-solid fa-truck', text: 'Forklift' },
    
    // Location & Map
    { id: 'fa-solid fa-map', text: 'Map' },
    { id: 'fa-solid fa-map-pin', text: 'Map Pin' },
    { id: 'fa-solid fa-map-2', text: 'Map Alt' },
    { id: 'fa-solid fa-map-marker-alt', text: 'Location' },
    { id: 'fa-solid fa-compass', text: 'Compass' },
    { id: 'fa-solid fa-globe', text: 'World' },
    { id: 'fa-solid fa-building', text: 'Building' },
    { id: 'fa-solid fa-building-skyscraper', text: 'Skyscraper' },
    
    // Communication
    { id: 'fa-solid fa-envelope', text: 'Mail' },
    { id: 'fa-solid fa-envelope-opened', text: 'Mail Opened' },
    { id: 'fa-solid fa-inbox', text: 'Inbox' },
    { id: 'fa-solid fa-paper-plane', text: 'Send' },
    { id: 'fa-solid fa-comment', text: 'Message' },
    { id: 'fa-solid fa-comments', text: 'Messages' },
    { id: 'fa-solid fa-phone', text: 'Phone' },
    { id: 'fa-solid fa-mobile-screen-button', text: 'Mobile' },
    { id: 'fa-solid fa-bell', text: 'Bell' },
    { id: 'fa-solid fa-bell-off', text: 'Bell Off' },
    { id: 'fa-solid fa-bullhorn', text: 'Speakerphone' },
    
    // Time & Calendar
    { id: 'fa-solid fa-calendar', text: 'Calendar' },
    { id: 'fa-solid fa-calendar-event', text: 'Calendar Event' },
    { id: 'fa-solid fa-calendar-plus', text: 'Calendar Plus' },
    { id: 'fa-solid fa-calendar-time', text: 'Calendar Time' },
    { id: 'fa-solid fa-clock', text: 'Clock' },
    { id: 'fa-solid fa-hourglass', text: 'Hourglass' },
    { id: 'fa-solid fa-alarm-clock', text: 'Alarm' },
    { id: 'fa-solid fa-history', text: 'History' },
    
    // Security & Access
    { id: 'fa-solid fa-lock', text: 'Lock' },
    { id: 'fa-solid fa-lock-open', text: 'Lock Open' },
    { id: 'fa-solid fa-key', text: 'Key' },
    { id: 'fa-solid fa-shield-alt', text: 'Shield' },
    { id: 'fa-solid fa-shield-alt', text: 'Shield Check' },
    { id: 'fa-solid fa-fingerprint', text: 'Fingerprint' },
    { id: 'fa-solid fa-eye', text: 'Eye' },
    { id: 'fa-solid fa-eye-off', text: 'Eye Off' },
    { id: 'fa-solid fa-ban', text: 'Ban' },
    
    // Actions & Controls
    { id: 'fa-solid fa-plus', text: 'Plus' },
    { id: 'fa-solid fa-circle-plus', text: 'Plus Circle' },
    { id: 'fa-solid fa-minus', text: 'Minus' },
    { id: 'fa-solid fa-circle-minus', text: 'Minus Circle' },
    { id: 'fa-solid fa-xmark', text: 'Close' },
    { id: 'fa-solid fa-xmark-circle', text: 'Close Circle' },
    { id: 'fa-solid fa-check', text: 'Check' },
    { id: 'fa-solid fa-circle-check', text: 'Check Circle' },
    { id: 'fa-solid fa-pen-to-square', text: 'Edit' },
    { id: 'fa-solid fa-pencil-alt', text: 'Pencil' },
    { id: 'fa-solid fa-trash', text: 'Trash' },
    { id: 'fa-solid fa-eraser', text: 'Eraser' },
    { id: 'fa-solid fa-floppy-disk', text: 'Save' },
    { id: 'fa-solid fa-download', text: 'Download' },
    { id: 'fa-solid fa-upload', text: 'Upload' },
    { id: 'fa-solid fa-arrows-rotate', text: 'Refresh' },
    { id: 'fa-solid fa-arrow-rotate-right', text: 'Reload' },
    { id: 'fa-solid fa-arrows-rotate', text: 'Rotate' },
    { id: 'fa-solid fa-magnifying-glass', text: 'Search' },
    { id: 'fa-solid fa-magnifying-glass-plus', text: 'Zoom In' },
    { id: 'fa-solid fa-magnifying-glass-minus', text: 'Zoom Out' },
    { id: 'fa-solid fa-filter', text: 'Filter' },
    { id: 'fa-solid fa-sort-amount-asc', text: 'Sort Ascending' },
    { id: 'fa-solid fa-sort-amount-desc', text: 'Sort Descending' },
    { id: 'fa-solid fa-expand', text: 'Maximize' },
    { id: 'fa-solid fa-compress', text: 'Minimize' },
    { id: 'fa-solid fa-arrow-up-right-from-square', text: 'External Link' },
    { id: 'fa-solid fa-link', text: 'Link' },
    { id: 'fa-solid fa-share-alt', text: 'Share' },
    
    // Status & Indicators
    { id: 'fa-solid fa-circle-info', text: 'Info' },
    { id: 'fa-solid fa-circle-question', text: 'Help' },
    { id: 'fa-solid fa-circle-exclamation', text: 'Alert' },
    { id: 'fa-solid fa-exclamation-triangle', text: 'Warning' },
    { id: 'fa-solid fa-flag', text: 'Flag' },
    { id: 'fa-solid fa-star', text: 'Star' },
    { id: 'fa-solid fa-heart', text: 'Heart' },
    { id: 'fa-solid fa-table-cellsumbs-up', text: 'Thumbs Up' },
    { id: 'fa-solid fa-table-cellsumbs-down', text: 'Thumbs Down' },
    { id: 'fa-solid fa-bookmark', text: 'Bookmark' },
    { id: 'fa-solid fa-trophy', text: 'Trophy' },
    { id: 'fa-solid fa-award', text: 'Award' },
    { id: 'fa-solid fa-medal', text: 'Medal' },
    { id: 'fa-solid fa-certificate', text: 'Certificate' },
    
    // Arrows & Direction
    { id: 'fa-solid fa-arrow-up', text: 'Arrow Up' },
    { id: 'fa-solid fa-arrow-down', text: 'Arrow Down' },
    { id: 'fa-solid fa-arrow-left', text: 'Arrow Left' },
    { id: 'fa-solid fa-arrow-right', text: 'Arrow Right' },
    { id: 'fa-solid fa-chevron-up', text: 'Chevron Up' },
    { id: 'fa-solid fa-chevron-down', text: 'Chevron Down' },
    { id: 'fa-solid fa-chevron-left', text: 'Chevron Left' },
    { id: 'fa-solid fa-chevron-right', text: 'Chevron Right' },
    { id: 'fa-solid fa-angles-up', text: 'Chevrons Up' },
    { id: 'fa-solid fa-angles-down', text: 'Chevrons Down' },
    { id: 'fa-solid fa-angles-left', text: 'Chevrons Left' },
    { id: 'fa-solid fa-angles-right', text: 'Chevrons Right' },
    
    // Tasks & Projects
    { id: 'fa-solid fa-checklist', text: 'Checklist' },
    { id: 'fa-solid fa-list-check', text: 'Subtask' },
    { id: 'fa-solid fa-sitemap', text: 'Sitemap' },
    { id: 'fa-solid fa-project-diagram', text: 'Network' },
    { id: 'fa-solid fa-code-branch', text: 'Git Branch' },
    { id: 'fa-solid fa-code', text: 'Code' },
    { id: 'fa-solid fa-terminal', text: 'Terminal' },
    { id: 'fa-solid fa-bug', text: 'Bug' },
    { id: 'fa-solid fa-lightbulb', text: 'Lightbulb' },
    { id: 'fa-solid fa-magic', text: 'Magic' },
    { id: 'fa-solid fa-rocket', text: 'Rocket' },
    { id: 'fa-solid fa-bolt', text: 'Bolt' },
    { id: 'fa-solid fa-fire', text: 'Flame' },
    
    // Media & Content
    { id: 'fa-solid fa-image', text: 'Photo' },
    { id: 'fa-solid fa-camera', text: 'Camera' },
    { id: 'fa-solid fa-video', text: 'Video' },
    { id: 'fa-solid fa-film', text: 'Movie' },
    { id: 'fa-solid fa-music', text: 'Music' },
    { id: 'fa-solid fa-headphones', text: 'Headphones' },
    { id: 'fa-solid fa-microphone', text: 'Microphone' },
    { id: 'fa-solid fa-volume-up', text: 'Volume' },
    { id: 'fa-solid fa-volume-mute', text: 'Volume Off' },
    { id: 'fa-solid fa-play', text: 'Play' },
    { id: 'fa-solid fa-pause', text: 'Pause' },
    { id: 'fa-solid fa-stop', text: 'Stop' },
    
    // Misc
    { id: 'fa-solid fa-circle', text: 'Circle' },
    { id: 'fa-solid fa-square', text: 'Square' },
    { id: 'fa-solid fa-cube', text: 'Cube' },
    { id: 'fa-solid fa-puzzle-piece', text: 'Puzzle' },
    { id: 'fa-solid fa-gem', text: 'Diamond' },
    { id: 'fa-solid fa-gift', text: 'Gift' },
    { id: 'fa-solid fa-crown', text: 'Crown' },
    { id: 'fa-solid fa-sun', text: 'Sun' },
    { id: 'fa-solid fa-moon', text: 'Moon' },
    { id: 'fa-solid fa-cloud', text: 'Cloud' },
    { id: 'fa-solid fa-cloud-arrow-up', text: 'Cloud Upload' },
    { id: 'fa-solid fa-cloud-arrow-down', text: 'Cloud Download' },
    { id: 'fa-solid fa-print', text: 'Printer' },
    { id: 'fa-solid fa-desktop', text: 'Desktop' },
    { id: 'fa-solid fa-laptop', text: 'Laptop' },
    { id: 'fa-solid fa-tablet-screen-button', text: 'Tablet' },
    { id: 'fa-solid fa-keyboard', text: 'Keyboard' },
    { id: 'fa-solid fa-mouse', text: 'Mouse' },
    { id: 'fa-solid fa-power-off', text: 'Power' },
    { id: 'fa-solid fa-plug', text: 'Plug' },
    { id: 'fa-solid fa-battery-full', text: 'Battery' },
    { id: 'fa-solid fa-wifi', text: 'WiFi' },
    { id: 'fa-solid fa-signal', text: 'Signal' },
    { id: 'fa-brands fa-bluetooth', text: 'Bluetooth' },
    { id: 'fa-solid fa-rss', text: 'RSS' },
    { id: 'fa-solid fa-at', text: 'At' },
    { id: 'fa-solid fa-hashtag', text: 'Hash' },
    { id: 'fa-solid fa-quote-right', text: 'Quote' },
    { id: 'fa-solid fa-align-left', text: 'Align Left' },
    { id: 'fa-solid fa-align-center', text: 'Align Center' },
    { id: 'fa-solid fa-align-right', text: 'Align Right' },
    { id: 'fa-solid fa-align-justify', text: 'Align Justify' },
    { id: 'fa-solid fa-bold', text: 'Bold' },
    { id: 'fa-solid fa-italic', text: 'Italic' },
    { id: 'fa-solid fa-underline', text: 'Underline' },
    { id: 'fa-solid fa-strikethrough', text: 'Strikethrough' },
    { id: 'fa-solid fa-palette', text: 'Palette' },
    { id: 'fa-solid fa-brush', text: 'Brush' },
    { id: 'fa-solid fa-paint-brush', text: 'Paint' },
    { id: 'fa-solid fa-crop', text: 'Crop' },
    { id: 'fa-solid fa-ruler', text: 'Ruler' },
    { id: 'fa-solid fa-ellipsis', text: 'Dots Horizontal' },
    { id: 'fa-solid fa-ellipsis-verticalertical', text: 'Dots Vertical' },
];

// Format icon option for Select2
function formatIconOption(icon) {
    if (!icon.id) return icon.text;
    return $('<span class="select2-icon-option"><i class="' + icon.id + '"></i> ' + icon.text + '</span>');
}

$(function() {
    let menuTree = [];
    let sortableInstances = [];
    const $container = $('#menu-tree-container');
    const canUpdate = parseInt($container.attr('data-can-update')) === 1;
    const canDelete = parseInt($container.attr('data-can-delete')) === 1;

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
            actions += '<button type="button" class="btn btn-sm btn-info btn-edit" data-id="' + item.id + '" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>';
        }
        if (canDelete) {
            actions += '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' + item.id + '" title="Delete"><i class="fa-solid fa-trash"></i></button>';
        }

        let html = '<li class="menu-item-wrapper" data-id="' + item.id + '">';
        html += '<div class="menu-item">';
        html += '<div class="menu-item-content">';
        html += '<span class="menu-item-handle"><i class="fa-solid fa-grip-vertical"></i></span>';
        html += '<span class="menu-item-icon"><i class="' + (item.icon || 'fa-solid fa-circle') + '"></i></span>';
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
            $('body').append('<div class="saving-indicator"><i class="fa-solid fa-spinner ti-spin"></i> Saving...</div>');
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
        tablerIcons.forEach(function(icon) {
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
                var iconText = selectedValue.replace('fa-solid fa-', '').replace(/-/g, ' ');
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
