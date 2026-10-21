@extends('layouts.main')
@section('content')
    <div class="app-content-header py-3">
        <div class="container-fluid">
            <!-- Warna dipaskan dengan tone sidebar (#343a40 ke #212529) -->
            <div class="card border-0 shadow-sm text-white"
                style="background: linear-gradient(135deg, #343a40 0%, #212529 100%);">
                <div class="card-body text-center py-5 position-relative overflow-hidden">

                    <!-- Efek Cahaya Dekorasi -->
                    <div class="position-absolute start-0 top-0 translate-middle-y bg-primary opacity-10 rounded-circle"
                        style="width: 250px; height: 250px; filter: blur(40px);"></div>
                    <div class="position-absolute end-0 bottom-0 translate-middle-y bg-info opacity-10 rounded-circle"
                        style="width: 250px; height: 250px; filter: blur(40px);"></div>

                    <!-- Konten -->
                    <div class="position-relative">
                        <span class="badge bg-primary bg-opacity-25 text-info px-3 py-2 rounded-pill mb-3 fw-semibold">
                            <i class="bi bi-shield-check me-1"></i> PT. Parkland World Indonesia 2
                        </span>
                        <h2 class="fw-bold display-6 mb-2 text-white">WELCOME TO IOT CENTER SYSTEM</h2>
                        <p class="text-light opacity-75 mb-3 fs-6">
                            Pusat monitoring dan kontrol sistem produksi terintegrasi. Halo, <span
                                class="text-white fw-semibold">{{ Auth::user()->name ?? 'Administrator' }}</span>!
                        </p>
                        <div class="text-muted small">
                            <i class="bi bi-calendar-event me-1"></i> {{ date('l, d F Y') }}
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
