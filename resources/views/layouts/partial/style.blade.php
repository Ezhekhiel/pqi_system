<style>
    .smooth-hide {
        transition: all 0.3s ease;
        opacity: 0;
        transform: translateY(-10px);
    }

    .smooth-show {
        transition: all 0.3s ease;
        opacity: 1;
        transform: translateY(0);
    }

    /* Kembalikan native appearance untuk date */
    input[type="date"].form-control {
        appearance: auto !important;
        -webkit-appearance: auto !important;
        -moz-appearance: auto !important;
    }

    /* Pastikan ikon kalender tidak disembunyikan */
    input[type="date"]::-webkit-calendar-picker-indicator {
        display: block !important;
        opacity: 1 !important;
    }

    grid: {
        borderColor: '#ddd',
            strokeDashArray: 3
    }

    @layer base {

        table th,
        table td {
            white-space: nowrap;
        }
    }

    /* Override untuk td yang spesifik di Matriks X */
    .x-matrix-container td {
        white-space: normal !important;
        /* Biarkan teks normal di dalam X-Matrix */
    }

    /* GRID FLUID + MAX 6 KOLOM */
    .dynamic-grid-humidity {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
    }

    /* BATAS MAKS 6 KOLOM SAAT LAYAR BESAR */
    @media (min-width: 1200px) {
        .dynamic-grid-humidity {
            grid-template-columns: repeat(5, 1fr);
        }
    }

    /* GRID FLUID + MAX 6 KOLOM */
    .dynamic-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
    }

    /* BATAS MAKS 6 KOLOM SAAT LAYAR BESAR */
    @media (min-width: 1200px) {
        .dynamic-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    /* ---------- FLIP CARD ---------- */
    .flip-container {
        perspective: 1000px;
        width: 100%;
        height: 300px;
        /* Sesuaikan tinggi */
        cursor: pointer;
    }

    .flip-card {
        overflow: visible !important;
        width: 100%;
        height: 100%;
        position: relative;
        transform-style: preserve-3d;
        transition: transform 0.6s ease;
    }

    .flip-card,
    .flip-front,
    .flip-back,
    .flip-front .card,
    .flip-back .card,
    .flip-front .card-body,
    .flip-back .card-body {
        overflow: visible !important;
        backface-visibility: hidden !important;
    }

    .flip-container.active .flip-card {
        transform: rotateY(180deg);
    }

    .flip-front,
    .flip-back {
        overflow: visible !important;
        position: absolute;
        width: 100%;
        height: 100%;
        backface-visibility: hidden;
        border-radius: 10px;
        padding: 15px;
        display: block;
        /* dari flex -> block */
        justify-content: center;
        align-items: center;
        font-size: 20px;
        color: #fff;
    }

    .flip-front {}

    .flip-back {
        transform: rotateY(180deg);
    }

    .alert {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }

    /* Dropdown export menu jadi dark */
    .apexcharts-menu-item {
        background: #1f1f1f !important;
        /* Dark */
        border: 1px solid #333 !important;
        color: #fff !important;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4) !important;
    }

    /* Hover pilihan menu */
    .apexcharts-menu-item:hover {
        background: #333 !important;
        color: #fff !important;
    }

    /* Teks menu warna putih */
    .apexcharts-menu-item {
        color: #fff !important;
    }

    .chart-container {
        width: 100%;
        max-width: 100%;
        height: 350px;
        /* atur sesuai kebutuhan */
        overflow: hidden;
        /* cegah keluar frame */
        position: relative;
    }

    /* SIZE MODAL */
    .modal-custom-size {
        width: 90vw;
        max-width: 90vw;
    }

    .custom-modal {
        height: 80vh;
        border-radius: 22px;
        overflow: hidden;
        border: 2px solid #1f5c93;
    }

    /* PANEL KIRI */
    .modal-panel {
        background: white;
        display: flex;
        flex-direction: column;
    }

    /* BAGIAN ATAS */
    .left-top {
        flex: 1;
        font-size: 30vh;
        font-weight: 700;
        border-bottom: 2px solid #1f5c93;
        background-color: #1f5c93;
    }

    .auto-fit-text {
        font-weight: 700;
        color: white;
        white-space: nowrap;
        line-height: 1;
    }

    /* BAGIAN BAWAH */
    .left-bottom {
        flex: 1;
        font-size: 13vh;
        font-weight: 700;
        max-height: 15vh;
        background-color: #000000;
        color: #ffffff;
    }

    /* BAGIAN ATAS */
    .right-top {
        flex: 1;
        font-size: 20vh;
        font-weight: 700;
        border-bottom: 2px solid #1f5c93;
    }

    /* BAGIAN BAWAH */
    .right-bottom {
        flex: 1;
        font-size: 13vh;
        font-weight: 700;
    }

    /* TEXT */
    .area-text {
        user-select: none;
    }
</style>
