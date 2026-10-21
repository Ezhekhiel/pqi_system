<!--begin::Sidebar-->
<aside class="app-sidebar bg-body-secondary shadow" id="display_sidebar" data-bs-theme="dark">
    <!--begin::Sidebar Brand-->
    <div class="sidebar-brand">
        <!--begin::Brand Link-->
        <a href="./index.html" class="brand-link">
            <!--begin::Brand Image-->
            <img src="{{ asset('/assets/img/AdminLTELogo.png') }}" alt="AdminLTE Logo"
                class="brand-image opacity-75 shadow" />
            <!--end::Brand Image-->
            <!--begin::Brand Text-->
            <span class="brand-text fw-light">PT. PWI 2</span>
            <!--end::Brand Text-->
        </a>
        <!--end::Brand Link-->
    </div>
    <!--end::Sidebar Brand-->
    <!--begin::Sidebar Wrapper-->
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <!--begin::Sidebar Menu-->
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation"
                aria-label="Main navigation" data-accordion="false" id="navigation">
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-patch-check"></i>
                        <p>
                            PQI System
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('pqi') }}" class="nav-link">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>DASHBOARD</p>
                            </a>
                        </li>
                    </ul>
                </li>
                {{-- manage STOP LINE --}}
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-box-seam-fill"></i>
                        <p>
                            MANAGE STOP LINE
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('Manage/Location') }}" class="nav-link">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>MANAGE LOCATION</p>
                            </a>
                        </li>
                    </ul>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('PQI/Manage Device') }}" class="nav-link">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>MANAGE DEVICE PQI</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <!--end::Sidebar Menu-->
                {{-- manage STOP LINE --}}
                {{-- <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-box-seam-fill"></i>
                        <p>
                            MANAGE CELL INFORMATION
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('cell') }}" class="nav-link">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>CELL TARGET</p>
                            </a>
                        </li>
                    </ul>
                </li> --}}

        </nav>
    </div>
    <!--end::Sidebar Wrapper-->
</aside>
<!--end::Sidebar-->
