<style>
        /* === Sticky Footer System === */
        html, body {
            height: 100%;
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .content-wrapper {
            flex: 1;
        }

        /* ANDON title animation */
        .blink {
            animation: blinker 4s linear infinite;
        }
        @keyframes blinker {
            50% { opacity: 0; }
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            h3 { font-size: 95%; padding-top: 10px; }
            .dropdown-toggle, .card-title { font-size: 80%; }
        }
        .footer-stylish {
            background: linear-gradient(90deg, #1f1f1f, #2c2c2c, #1f1f1f);
            color: #ffffff;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;

            /* garis glowing atas */
            border-top: 3px solid #00d4ff;
            box-shadow: 0 -2px 10px rgba(0, 212, 255, 0.5);

            /* animasi halus */
            transition: 300ms ease-in-out;
        }

        /* Efek hover: glow biru lebih kuat */
        .footer-stylish:hover {
            border-top-color: #00f0ff;
            box-shadow: 0 -3px 20px rgba(0, 240, 255, 0.7);
        }

        /* Responsif */
        .footer-title {
            font-size: 2vw;
        }
        @media (max-width: 600px) {
            .footer-title {
                font-size: 20px;
            }
        }
        /* === Stylish Table Header === */
        .table-header-stylish {
            position: sticky;
            top: 0;
            background: linear-gradient(90deg, #191919, #222, #191919);
            color: #00eaff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 25px;
            letter-spacing: 1px;
            border-bottom: 3px solid #00eaff;
            box-shadow: 0 2px 15px rgba(0, 234, 255, 0.25);
            z-index: 10;           /* penting! biar berada di atas */
        }

        /* Tambahan garis pemisah kolom */
        .table-header-stylish th {
            border-right: 1px solid rgba(0, 234, 255, 0.2);
        }

        .table-header-stylish th:last-child {
            border-right: none;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .table-header-stylish {
                font-size: 14px;
                letter-spacing: 0.5px;
            }
        }
        /* === Make tbody scroll without affecting footer === */
        .table-scroll-wrapper {
            max-height: calc(90vh - 200px); /* tinggi otomatis sisa layar */
            overflow-y: auto;
            display: block;
        }

        /* Agar header tetap rapi dalam mode block */
        .table-scroll-wrapper table {
            border-collapse: collapse;
            width: 100%;
        }

        /* Biar thead tidak ikut scroll */
        .table-scroll-wrapper thead th {
            position: sticky;
            top: 0;
            z-index: 5;
        }

        tbody td {
            font-size: 4vh;
            padding: 6px 10px;
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
            padding: 0 !important;
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
            padding:20px;
        }
        /* BAGIAN BAWAH */
        .left-bottom {
            flex: 1;
            font-size: 7vh;
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
