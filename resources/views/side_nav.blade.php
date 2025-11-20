@php
    $user = auth()->user() ?? null;
@endphp
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{url('dashboard')}}" class="brand-link">
      <span class="brand-text font-weight-light">E-Kanban</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="info">
          <a href="#" class="d-block">
            {{ $user->name ?? 'Guest User' }}
          </a>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            @foreach($userMenus as $menu)
            @php
                $hasActiveChild = false;
                $isParentActive = request()->is(ltrim($menu->url, '/'));
                
                // Check if any child menu is active
                if(count($menu->children) > 0) {
                    foreach($menu->children as $child) {
                        if(request()->is(ltrim($child->url, '/')) || request()->is(ltrim($child->url, '/').'/*')) {
                            $hasActiveChild = true;
                            break;
                        }
                    }
                }
                
                $menuOpen = $hasActiveChild ? 'menu-open' : '';
                $menuIsOpen = $hasActiveChild ? 'menu-is-opening menu-open' : '';
            @endphp
            <li class="nav-item {{ count($menu->children) > 0 ? 'has-treeview' : '' }} {{ $menuIsOpen }}">
                <a href="{{ $menu->url == '#' ? '#' : url($menu->url) }}" class="nav-link {{ $isParentActive || $hasActiveChild ? 'active' : '' }}">
                    <i class="nav-icon {{ $menu->icon }}"></i>
                    <p>
                        {{ $menu->name }}
                        @if(count($menu->children) > 0)
                        <i class="right fas fa-angle-left"></i>
                        @endif
                    </p>
                </a>
                @if(count($menu->children) > 0)
                <ul class="nav nav-treeview" style="{{ $hasActiveChild ? 'display: block;' : '' }}">
                    @foreach($menu->children as $subMenu)
                        @php
                            $isSubMenuActive = request()->is(ltrim($subMenu->url, '/')) || request()->is(ltrim($subMenu->url, '/').'/*');
                        @endphp
                        <li class="nav-item">
                            <a href="{{ $subMenu->url == '#' ? '#' : url($subMenu->url) }}" class="nav-link {{ $isSubMenuActive ? 'active' : '' }}">
                            <i class="{{ $subMenu->icon }} nav-icon"></i>
                            <p>{{ $subMenu->name }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
                @endif
            </li>
            @endforeach
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>
