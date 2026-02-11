<!-- Menu Navigation starts -->
<nav class="dark-sidebar">
    <div class="app-logo">
        <a class="logo d-inline-block" href="{{ url('dashboard') }}">
            <img src="{{ asset('assets/images/logo/dark.png') }}" alt="E-Kanban" class="dark-logo">
            <img src="{{ asset('assets/images/logo/1.png') }}" alt="E-Kanban" class="light-logo">
        </a>

        <span class="bg-light-light toggle-semi-nav">
            <i class="fa-solid fa-angles-right f-s-20"></i>
        </span>
    </div>
    <div class="app-nav" id="app-simple-bar">
        <ul class="main-nav p-0 mt-2">
            @foreach($userMenus as $menu)
            @php
                $hasActiveChild = false;
                $isParentActive = request()->is(ltrim($menu->url, '/')) || request()->is(ltrim($menu->url, '/').'/*');
                
                // Check if any child menu is active
                if(count($menu->children) > 0) {
                    foreach($menu->children as $child) {
                        if(request()->is(ltrim($child->url, '/')) || request()->is(ltrim($child->url, '/').'/*')) {
                            $hasActiveChild = true;
                            break;
                        }
                    }
                }
                
                $menuId = 'menu-' . Str::slug($menu->name);
                $isOpen = $hasActiveChild ? 'show' : '';
                $isExpanded = $hasActiveChild ? 'true' : 'false';
            @endphp
            
            @if(count($menu->children) > 0)
                {{-- Menu with children --}}
                <li class="menu-title">
                    <span>{{ $menu->name }}</span>
                </li>
                @foreach($menu->children as $subMenu)
                    @php
                        $hasSubChildren = isset($subMenu->children) && count($subMenu->children) > 0;
                        $isSubMenuActive = request()->is(ltrim($subMenu->url, '/')) || request()->is(ltrim($subMenu->url, '/').'/*');
                        $subMenuId = 'submenu-' . Str::slug($subMenu->name);
                        
                        // Check for active grandchildren
                        $hasActiveGrandChild = false;
                        if($hasSubChildren) {
                            foreach($subMenu->children as $grandChild) {
                                if(request()->is(ltrim($grandChild->url, '/')) || request()->is(ltrim($grandChild->url, '/').'/*')) {
                                    $hasActiveGrandChild = true;
                                    break;
                                }
                            }
                        }
                    @endphp
                    
                    @if($hasSubChildren)
                        <li class="{{ $isSubMenuActive || $hasActiveGrandChild ? 'active' : '' }}">
                            <a class="{{ $isSubMenuActive || $hasActiveGrandChild ? 'active' : '' }}" 
                               data-bs-toggle="collapse" 
                               href="#{{ $subMenuId }}" 
                               aria-expanded="{{ $hasActiveGrandChild ? 'true' : 'false' }}">
                                <i class="{{ $subMenu->icon ?? 'fa-solid fa-circle' }}"></i>
                                {{ $subMenu->name }}
                            </a>
                            <ul class="collapse {{ $hasActiveGrandChild ? 'show' : '' }}" id="{{ $subMenuId }}">
                                @foreach($subMenu->children as $grandChild)
                                    @php
                                        $isGrandChildActive = request()->is(ltrim($grandChild->url, '/')) || request()->is(ltrim($grandChild->url, '/').'/*');
                                    @endphp
                                    <li class="{{ $isGrandChildActive ? 'active' : '' }}">
                                        <a href="{{ url($grandChild->url) }}" class="{{ $isGrandChildActive ? 'active' : '' }}">
                                            {{ $grandChild->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="{{ $isSubMenuActive ? 'active' : '' }} {{ $subMenu->url == '#' ? '' : 'no-sub' }}">
                            <a class="{{ $isSubMenuActive ? 'active' : '' }}" href="{{ $subMenu->url == '#' ? '#' : url($subMenu->url) }}">
                                <i class="{{ $subMenu->icon ?? 'fa-solid fa-circle' }}"></i>
                                {{ $subMenu->name }}
                            </a>
                        </li>
                    @endif
                @endforeach
            @else
                {{-- Menu without children --}}
                <li class="{{ $isParentActive ? 'active' : '' }} no-sub">
                    <a class="{{ $isParentActive ? 'active' : '' }}" href="{{ $menu->url == '#' ? '#' : url($menu->url) }}">
                        <i class="{{ $menu->icon ?? 'fa-solid fa-home' }}"></i>
                        {{ $menu->name }}
                    </a>
                </li>
            @endif
            @endforeach
        </ul>
    </div>

    <div class="menu-navs">
        <span class="menu-previous"><i class="fa-solid fa-chevron-left"></i></span>
        <span class="menu-next"><i class="fa-solid fa-chevron-right"></i></span>
    </div>
</nav>
<!-- Menu Navigation ends -->
