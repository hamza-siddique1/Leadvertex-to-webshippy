@php
$role = Auth()->user()->role;
@endphp
<nav id="sidebar" class="sidebar">
    <div class="sidebar-content js-simplebar">
        <a class="sidebar-brand" href="{{ route('dashboard.index') }}">
            <span class="align-middle me-3">
                <img src="{{ asset('assets/img/crm-logo.png') }}" alt="logo" width="130" />
            </span>
        </a>

        <ul class="sidebar-nav">
            <li class="sidebar-header">
                General
            </li>
            <li class="sidebar-item {{ request()->is('dashboard') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('dashboard.index') }}">
                    <i class="align-middle" data-feather="sliders"></i>
                    <span class="align-middle">Dashboard</span>
                </a>
            </li>
            @if ($role == 'admin')
            <li class="sidebar-header">
                Manage
            </li>
            <li class="sidebar-item {{ request()->is('users*') ? 'active' : '' }} ">
                <a data-target="#users" data-toggle="collapse" class="sidebar-link collapsed">
                    <i class="align-middle" data-feather="users"></i>
                    <span class="align-middle">Users</span>
                </a>
                <ul id="users"
                    class="sidebar-dropdown list-unstyled collapse {{ request()->is('users*') ? 'show' : '' }}"
                    data-parent="#sidebar">

                    <li class="sidebar-item {{ request()->is('users') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route('users.index') }}">
                            <i class="align-middle" data-feather="users"></i>
                            <span class="align-middle">All Users</span>
                        </a>
                    </li>
                    <li class="sidebar-item {{ request()->is('users/create') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route('users.create') }}">
                            <i class="align-middle" data-feather="user-plus"></i>
                            <span class="align-middle">Add New User</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif
            <!-- Task Management Sidebar Section -->
            <li class="sidebar-item {{ request()->is('tasks*') ? 'active' : '' }}">
                <a data-target="#tasks" data-toggle="collapse" class="sidebar-link collapsed">
                    <i class="align-middle" data-feather="check-square"></i>
                    <span class="align-middle">Tasks</span>
                </a>
                <ul id="tasks"
                    class="sidebar-dropdown list-unstyled collapse {{ request()->is('tasks*') ? 'show' : '' }}"
                    data-parent="#sidebar">
                    <li class="sidebar-item {{ request()->is('tasks') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route('tasks.index') }}">
                            <i class="align-middle" data-feather="list"></i>
                            <span class="align-middle">All Tasks</span>
                        </a>
                    </li>
                    @if ($role == 'admin')
                    <li class="sidebar-item {{ request()->is('tasks/create') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route('tasks.create') }}">
                            <i class="align-middle" data-feather="plus-square"></i>
                            <span class="align-middle">Add New Task</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </li>

            <!-- Doctor Visits Sidebar Section -->
            <li class="sidebar-item {{ request()->is('doctor-visits*') ? 'active' : '' }}">
                <a data-target="#doctor-visits" data-toggle="collapse" class="sidebar-link collapsed">
                    <i class="align-middle" data-feather="user"></i>
                    <span class="align-middle">Doctor Visits</span>
                </a>
                <ul id="doctor-visits"
                    class="sidebar-dropdown list-unstyled collapse {{ request()->is('doctor-visits*') ? 'show' : '' }}"
                    data-parent="#sidebar">

                    <li class="sidebar-item {{ request()->is('doctor-visits') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route('doctor-visits.index') }}">
                            <i class="align-middle" data-feather="list"></i>
                            <span class="align-middle">All Visits</span>
                        </a>
                    </li>
                    <li class="sidebar-item {{ request()->is('doctor-visits/create') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route('doctor-visits.create') }}">
                            <i class="align-middle" data-feather="plus-square"></i>
                            <span class="align-middle">Add New Visit</span>
                        </a>
                    </li>

                </ul>
            </li>

            <li class="sidebar-item {{ request()->is('invoices*') ? 'active' : '' }}">
                <a data-target="#invoices" data-toggle="collapse" class="sidebar-link collapsed">
                    <i class="align-middle" data-feather="file-text"></i>
                    <span class="align-middle">Invoices</span>
                </a>
                <ul id="invoices"
                    class="sidebar-dropdown list-unstyled collapse {{ request()->is('invoices*') ? 'show' : '' }}"
                    data-parent="#sidebar">

                    <li class="sidebar-item {{ request()->is('invoices') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ route('files.index') }}">
                            <i class="align-middle" data-feather="file-text"></i>
                            <span class="align-middle">All Invoices</span>
                        </a>
                    </li>

                </ul>
            </li>

        </ul>
    </div>
</nav>
