@php
    $menu = config('menu.main_menu', []);
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
            @foreach($menu as $row_menu)
            <li class="nav-item">
                <a href="{{ url($row_menu['url']) }}" class="nav-link">
                    <i class="nav-icon fas fa-{{$row_menu['icon']}}"></i>
                    <p>
                        {{ $row_menu['title'] }}
                        @if(isset($row_menu['sub_menu']) && $row_menu['sub_menu'] != '#')
                        <i class="right fas fa-angle-left"></i>
                        @endif
                    </p>
                </a>
                @if(isset($row_menu['sub_menu']) && $row_menu['sub_menu'] != '#')
                <ul class="nav nav-treeview">
                    @foreach($row_menu['sub_menu'] as $row_sub_menu)
                        <li class="nav-item">
                            <a href="{{url('/'.$row_sub_menu['url'])}}" class="nav-link">
                            <i class="far fa-{{ $row_sub_menu['icon'] }} nav-icon"></i>
                            <p>{{$row_sub_menu['title']}}
                                @if(isset($row_sub_menu['sub_menu']) && $row_sub_menu['sub_menu'] != '#')
                                <i class="right fas fa-angle-left"></i>
                                @endif
                            </p>
                            </a>
                            @if(isset($row_sub_menu['sub_menu']) && $row_sub_menu['sub_menu'] != '#')
                            <ul class="nav nav-treeview">
                                @foreach($row_sub_menu['sub_menu'] as $row_sub_sub_menu)
                                    <li class="nav-item">
                                        <a href="{{url('/'.$row_sub_sub_menu['url'])}}" class="nav-link">
                                        <i class="far fa-{{ $row_sub_sub_menu['icon'] }} nav-icon"></i>
                                        <p>
                                          {{$row_sub_sub_menu['title']}}
                                        </p>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            @endif
                        </li>
                    @endforeach>
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
