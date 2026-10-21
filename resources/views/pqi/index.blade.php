@extends('layouts.main')
@section('content')
    <!-- ApexCharts JS - Pastikan ini sudah ada di layouts.main Anda, jika belum, tambahkan di sini -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        /* Class khusus untuk mengatur tinggi dan scroll */
        .table-scrollable {
            max-height: 300px;
            /* Tentukan tinggi maksimal tabel di sini */
            overflow-y: auto;
            /* Memunculkan scrollbar vertikal jika konten melebihi max-height */
        }

        /* Menjaga agar Header Tabel (thead) tetap diam saat di-scroll */
        .table-scrollable thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background-color: #212529;
            /* Sesuaikan warna latar header jika perlu */
            color: #ffffff;
        }

        .dashboard-header {
            background-color: #f3f1e7;
            border-bottom: 1px solid #e2ded0;
            padding: 12px 24px;
        }

        .dashboard-title {
            font-weight: 700;
            letter-spacing: 1px;
            font-size: 1.25rem;
            color: #111827;
        }

        .header-controls .form-select-sm,
        .form-control-sm .header-controls .btn-sm {
            background-color: transparent;
            border: 1px solid #d1cbbe;
            color: #4b5563;
            font-size: 0.825rem;
            font-weight: 600;
        }

        .header-controls .form-select-sm:focus {
            border-color: #0284c7;
            box-shadow: none;
        }

        .highlight-val {
            color: #0284c7;
        }

        /* Container pembungkus harus relative agar overlay pas di dalam elemen */
        #top3ChartDiv,
        .table-responsive {
            position: relative;
        }

        /* Overlay Transparan */
        .chart-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(2px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
            border-radius: 8px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease-in-out;
        }

        /* Munculkan Overlay saat class 'active' ditambah */
        .chart-loading-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        /* Spinner Animasi Putar */
        .loading-spinner {
            width: 35px;
            height: 35px;
            border: 4px solid #e0e0e0;
            border-top: 4px solid #3b82f6;
            /* Warna biru */
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
    <!-- Header / Navbar -->
    <header class="dashboard-header d-flex justify-content-between align-items-center">
        <!-- Title -->
        <div class="dashboard-title text-uppercase">
            Real-Time Industrial Quality Intelligence Dashboard
        </div>

        <!-- Controls / Actions -->
        <div class="header-controls d-flex align-items-center gap-2">
            <!-- Refresh Rate Dropdown -->
            <div class="input-group input-group-sm">
                <label class="input-group-text bg-transparent border-0 px-1 text-muted fw-semibold"
                    style="font-size: 0.75rem;">REFRESH RATE:</label>
                <select class="form-select form-select-sm rounded pe-4" id="relaoadTime">
                    <option value="300000" selected>5 Menit</option>
                    <option value="180000">3 Menit</option>
                    <option value="60000">1 Menit</option>
                </select>
            </div>

            <!-- Filter Dropdown (Date) -->
            <div class="input-group input-group-sm">
                <label class="input-group-text bg-transparent border-0 px-1 text-muted fw-semibold"
                    style="font-size: 0.75rem;">Date:</label>
                <input type="date" class="form-select form-select-sm rounded pe-4" name="search_date"
                    value="{{ date('Y-m-d') }}" id="search_date">
            </div>

            <!-- Export Button -->
            <div class="input-group input-group-sm">
                <a href="{{ route('pqi.export') }}"
                    class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 px-2 rounded text-decoration-none"
                    id="btnExport" style="font-size: 0.75rem; height: 31px;">
                    <i class="bi bi-file-earmark-excel text-success"></i>
                    <span>Export</span>
                </a>
            </div>
        </div>
    </header>

    <body>
        <!-- Content Body -->
        <div class="container-fluid px-4 py-2">
            <div class="row g-3">
                {{-- /////////////==============BAGIAN 1============////////////// --}}
                <div class="col-4" style="font-size:80%">
                    <div class="container bg-white p-4 rounded shadow-sm h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Live PQI Record</h5>

                            <!-- Input Search Engine -->
                            <div class="w-25">
                                <input type="text" id="search-input" class="form-control" style="font-size: 80%"
                                    placeholder="Cari data (Cell, PO, Article)...">
                            </div>
                        </div>

                        <!-- Wrapper dengan class table-scrollable -->
                        <div class="table-scrollable border rounded">
                            <div class="chart-loading-overlay chartLoader" id="tableLoader">
                                <div class="loading-spinner"></div>
                            </div>
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Cell</th>
                                        <th scope="col">Article</th>
                                        <th scope="col">PO</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Defect</th>
                                        <th scope="col">Time</th>
                                    </tr>
                                </thead>
                                <tbody id="live_table">
                                    <tr>
                                        <td colspan="7" class="text-center py-3 text-muted">

                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="container bg-white p-4 rounded shadow-sm h-100">
                        <h5 class="mb-0">Top 3 Defect Name</h5>
                        <div class="card-body">
                            <div id="top3ChartByName" style="width: 100%; height: 350px;">
                                <div class="chart-loading-overlay chartLoader" id="chartLoaderByName">
                                    <div class="loading-spinner"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-4">
                    <div class="container bg-white p-4 rounded shadow-sm h-100">
                        <h5 class="mb-0">Top 3 Defect by Line</h5>
                        <div class="card-body">
                            <div id="top3ChartByLine" style="width: 100%; height: 350px;">
                                <div class="chart-loading-overlay chartLoader" id="chartLoaderByLine">
                                    <div class="loading-spinner"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6">
                    <div class="container bg-white p-4 rounded shadow-sm h-100">
                        <h5 class="mb-0">Pareto Analysis of Daily Defects by Deffect Name</h5>
                        <div class="card-body">
                            <div id="paretoChart" style="width: 100%; height: 350px;">
                                <div class="chart-loading-overlay chartLoader" id="chartLoaderTotalStopLine">
                                    <div class="loading-spinner"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-2">
                    <!-- Mengganti 'container' dengan struktur 'card' agar lebih bersih -->
                    <div class="card bg-white p-3 rounded shadow-sm h-100 border-0">

                        <!-- Header Card -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <!-- Judul dibuat sedikit lebih kecil dan abu-abu agar fokus utama tetap pada angka -->
                            <h6 class="mb-0 text-secondary fw-bold">TOTAL STOP LINE</h6>

                            <!-- Hapus w-5 karena tidak ada di Bootstrap bawaan dan tidak diperlukan untuk icon -->
                            <!-- Tambahkan cursor-pointer dan title untuk efek tooltip sederhana -->
                            <i id="defectRulesInfo" class="bi bi-info-circle-fill text-info fs-5"
                                style="cursor: pointer;"></i>
                        </div>

                        <!-- Body / Angka -->
                        <!-- flex-grow-1 menggantikan height: 350px agar ukurannya otomatis mengikuti card yang h-100 -->
                        <div class="chart-loading-overlay" id="tableStopLine">
                            <div class="loading-spinner"></div>
                        </div>
                        <div
                            class="d-flex justify-content-center align-items-center flex-grow-1 position-relative overflow-hidden py-4">
                            <!-- Icon Watermark Transparan -->
                            <i class="bi bi-slash-circle-fill text-danger position-absolute opacity-10"
                                style="font-size: 11rem; right: -20px; bottom: -20px;"></i>

                            <!-- Teks Utama & Sub-label -->
                            <div class="text-center position-relative z-1">
                                <div class="text-danger fw-bolder display-2 mb-0" id="total_stop_line"
                                    style="font-weight: 900; line-height: 1;">
                                    29</div>
                                <div class="text-secondary small fw-bold text-uppercase mt-2 tracking-wide">
                                    <span class="badge bg-danger-subtle text-danger">Incidents</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-4" style="font-size:80%">
                    <div class="container bg-white p-4 rounded shadow-sm h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Data Stop Line</h5>
                        </div>

                        <!-- Wrapper dengan class table-scrollable -->
                        <div class="table-scrollable border rounded">
                            <div class="chart-loading-overlay" id="tableStopLine">
                                <div class="loading-spinner"></div>
                            </div>
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Cell</th>
                                        <th scope="col">Defect Name</th>
                                        <th scope="col">Time</th>
                                        <th scope="col">Alert Type</th>
                                    </tr>
                                </thead>
                                <tbody id="live_stop_line_table">
                                    <tr>
                                        <td colspan="7" class="text-center py-3 text-muted">

                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </body>


    @include('script.pqi.script')

    {{-- Koding JavaScript untuk Sparkline Global Summary --}}
@endsection
