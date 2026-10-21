<!-- apexcharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
    integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8=" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Moment.js (WAJIB ADA) -->
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script>
    $(document).ready(function() {
        $(document).on('click', '.toggleTableBtn', function() {
            const id = $(this).attr('id');
            let split_id = id.split("-");
            let id_ = split_id[1];
            const $tableWrapper = $("#tableWrapper-" + id_);
            const $icon = $("#toggleIcon-" + id_);

            const isHidden = $tableWrapper.css("display") === "none";
            if (isHidden) {
                $tableWrapper.css("display", "block");
                $icon.removeClass("bi-chevron-down").addClass("bi-chevron-up");
                $(this).html(`<i class="bi bi-chevron-up"></i> Hide Table`);
            } else {
                $tableWrapper.css("display", "none");
                $icon.removeClass("bi-chevron-up").addClass("bi-chevron-down");
                $(this).html(`<i class="bi bi-chevron-down"></i> Show Table`);
            }
        });

    });
</script>
<script>
    let chartInstances = {};
    let chartInstancesTimer = {};
    const MAX_DATA_POINTS = 30;
    var arr_limit = {};
    window.standar_min = {};
    window.standar_max = {};
    window.radialCharts = {};
    window.activeChannels = {};
    let chartMap = null;
    $(document).ready(function() {
        $('#select_area').on('change', function() {
            resetListener();
            $.ajax({
                url: "{{ route('IOT.change_area') }}",
                data: {
                    area: $(this).val()
                },
                dataType: 'JSON',
                method: "GET",
                success: function(data) {
                    $('#select_model').html(data.select_model);
                },
                error: function(xhr) {
                    console.error("Gagal memuat data chart awal", xhr);
                }
            });
        })
        $('#select_model').on('change', function() {
            main();
        })


        function loadChartAjax(area, title) {
            $.ajax({
                url: "{{ route('IOT.chart_mapping') }}",
                data: {
                    area: area,
                    title: title
                },
                method: "GET",
                success: function(data) {
                    mapChart(data.series, data.categories);
                },
                error: function(xhr) {
                    console.error("Gagal memuat data chart awal", xhr);
                }
            });
        }

        function mapChart(series, categories) {
            if (chartMap === null) {

                const options = {
                    chart: {
                        type: 'line',
                        height: 350,
                        toolbar: {
                            show: true
                        }
                    },
                    series: series,
                    xaxis: {
                        categories: categories
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 3
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val != null ? val : '-';
                            }
                        }
                    }
                };

                chartMap = new ApexCharts(
                    document.querySelector('#lineChartMultiple'),
                    options
                );

                chartMap.render();
            }
            // JIKA SUDAH ADA → UPDATE
            else {
                chartMap.updateOptions({
                    xaxis: {
                        categories
                    },
                    series: series
                });
            }
        }


        function resetListener() {
            Object.keys(window.activeChannels).forEach(channelName => {
                Echo.leave(channelName);
            });

            window.activeChannels = {}; // reset
            $('.content-tab').html("");
            console.log("Data Listener di stop!");
        }
        $(document).on('click', 'button[data-bs-toggle="tab"], a[data-bs-toggle="tab"]', function(e) {
            // 1. Cegah aksi default halaman (reload/submit/scroll ke atas)
            e.preventDefault();

            const id = $(this).attr('id');

            if (id !== "nav-real-time-tab") {
                resetListener();
            }

            // 2. Reset dropdown tab jika ada
            $('.select-tab').prop('selectedIndex', 0);
        });
    });

    function main() {
        var area = $('#select_area').val();
        var model = $('#select_model').val();
        Object.keys(window.activeChannels).forEach(channelName => {
            Echo.leave(channelName);
        });
        $.ajax({
            url: "{{ route('IOT.chart_data') }}",
            data: {
                area: area,
                model: model
            },
            method: "GET",
            success: function(data) {
                renderData(data.cart_data);
            },
            error: function(xhr) {
                console.error("Gagal memuat data chart awal", xhr);
            }
        });
    }

    function renderData(cart_data) {
        window.chart_data = cart_data;
        let main_data_card = document.getElementById('main_data_card');
        let card_container = document.getElementById('card_container');
        let mainDataCardHTML = cardContainerHTML = '';

        // =========================
        // ✅ STEP 1: BUILD HTML
        // =========================
        let result = {};
        var standar_min = 0;
        var standar_max = 0;

        Object.entries(cart_data || {}).forEach(([process, devices]) => {
            var stringCard = '';
            var id_device = '';

            // Sorting devices berdasarkan remark
            const sortedData = Object.fromEntries(
                Object.entries(devices || {})
                .filter(([_, value]) => value?.remark !== undefined)
                .sort(([_, a], [__, b]) => {
                    return a.remark.localeCompare(b.remark, undefined, {
                        numeric: true
                    });
                })
            );
            console.log(sortedData);

            Object.entries(sortedData).forEach(([deviceId, item]) => {
                if (deviceId === 'standar') return;
                if (!item.avg || Object.keys(item.avg).length === 0) return;

                // ambil last value
                const values = Object.values(item.avg);
                const lastValue = values[values.length - 1];

                result[deviceId] = parseFloat(lastValue);
                var status =
                    `<span class="badge iot-badge-status bg-danger fw-bold" id="view-status-${deviceId}">↓ UBNORMAL</span>`;

                // Pengecekan standar aman (menggunakan devices.standar)
                const std = devices?.standar;
                if (item.remark?.includes('HOT') && std?.HOT) {
                    standar_min = std.HOT.min;
                    standar_max = std.HOT.max;
                } else if (item.remark?.includes('COLD') && std?.COLD) {
                    standar_min = std.COLD.min;
                    standar_max = std.COLD.max;
                } else if (std?.standar) {
                    standar_min = std.standar.min;
                    standar_max = std.standar.max;
                }

                window.standar_max[deviceId] = standar_min;
                window.standar_min[deviceId] = standar_max;

                if (parseFloat(lastValue) >= standar_min && parseFloat(lastValue) <= standar_max) {
                    status =
                        `<span class="badge iot-badge-status bg-success fw-bold" id="view-status-${deviceId}">↑ NORMAL</span>`;
                }
                var unit = item.unit;

                var color_progress = "";
                if (parseFloat(item.data_progress_bar.percentage) >= 90) {
                    color_progress = "bg-success";
                } else if (parseFloat(item.data_progress_bar.percentage) >= 50) {
                    color_progress = "bg-warning";
                } else {
                    color_progress = "bg-danger";
                }

                stringCard += `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="fw-bold fs-5 text-white" id="last-value-${deviceId}">${lastValue} ${unit}</div>
                    <div class="text-secondary small fw-medium font-monospace">${item.remark}</div>
                    ${status}
                </div>
                <div class="border-0 bg-transparent">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-secondary" style="font-size: 0.75rem;">Machine Std / Day</span>
                        <span class="fw-semibold text-light" style="font-size: 0.75rem;" id="percentage_${deviceId}">${item.data_progress_bar.percentage}%</span>
                    </div>
                    <div class="progress iot-progress-bg" style="height: 6px;">
                        <div id="prograss_bar_${deviceId}" class="progress-bar ${color_progress}" style="width:${item.data_progress_bar.percentage}%"></div>
                    </div>
                    <div class="d-flex justify-content-end small mt-1" style="font-size: 0.75rem;">
                        <span class="text-secondary" id="total_process_${deviceId}">Total Process: <strong class="text-light">${item.data_progress_bar.total_data}</strong></span>
                    </div>
                    <div style="display:none" id="total_bagus_${deviceId}">${item.data_progress_bar.jumlah_nilai_bagus}</div>
                </div>
            `;
                id_device = deviceId;
            });

            // FIX: Ambil standar langsung dari devices.standar (bukan sortedData.standar)
            // Tambahkan fallback Object.entries(devices?.standar || {}) agar tidak throw error
            let standar_badge = '';
            Object.entries(devices?.standar || {}).forEach(([index, item]) => {
                standar_badge +=
                    `<span class="badge iot-badge-limit rounded-2 px-2 py-1">${index} - Min=${item.min} Max=${item.max}</span>`;
            });

            const safeProcess = process.replace(/\s+/g, '-');
            mainDataCardHTML += `
            <div class="card card-chart" data-process="${process}">
                <div class="card-header">
                    <h3 class="card-title">${process}</h3>
                </div>
                <div class="card-body">
                    <div id="chart-${safeProcess}" style="min-height:180px;"></div>
                </div>
            </div>
        `;

            cardContainerHTML += `
            <div class="card iot-card text-white rounded-4 p-2" style="max-width:400px;">
                <div class="card-body p-3">
                    <!-- HEADER -->
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                        <h6 class="mb-0 fw-bold text-uppercase tracking-wide text-light">${process}</h6>

                        <!-- Pembungkus badge agar bertumpuk vertikal -->
                        <div class="d-flex flex-column align-items-end gap-1">
                            ${standar_badge}
                        </div>
                    </div>

                    <!-- AREA SCROLLABLE (Scrollbar tersembunyi) -->
                    <div class="custom-scroll" style="max-height: 380px; overflow-y: auto;">
                        ${stringCard}
                    </div>
                </div>
            </div>
        `;
        });

        // inject sekali aja
        if (main_data_card) main_data_card.innerHTML = mainDataCardHTML;
        if (card_container) card_container.innerHTML = cardContainerHTML;

        // =========================
        // ✅ STEP 2: RENDER CHART
        // =========================
        inisiasiChartValue(cart_data);
    }

    // 1. Fungsi Khusus Pengolahan Data
    function prepareChartData(processData, typeData = 'avg') {
        // 1. Ambil objek standar (jika tidak ada, beri fallback objek kosong)
        const standar = processData.standar || {};

        // 2. Filter entri agar HANYA memproses sensor (abaikan 'min', 'max', dan 'standar')
        const entries = Object.entries(processData).filter(
            ([id]) => id !== "min" && id !== "max" && id !== "standar"
        );

        // Ambil semua jam unik berdasarkan typeData yang dipilih
        let allTimes = new Set();
        entries.forEach(([id, item]) => {
            if (typeof LineListenForRealTimeUpdates === 'function') {
                LineListenForRealTimeUpdates(id);
            }

            const targetData = item[typeData];
            if (targetData && typeof targetData === 'object' && !Array.isArray(targetData)) {
                Object.keys(targetData).forEach(t => allTimes.add(t));
            }
        });

        // Sort jam (x-axis categories)
        const categories = Array.from(allTimes).sort((a, b) => a.localeCompare(b));

        // Grouping data series
        let grouped = {};
        entries.forEach(([_, item]) => {
            // Pastikan item memiliki remark agar tidak menjadi key "undefined"
            const seriesKey = item.remark || 'Unknown';

            if (!grouped[seriesKey]) {
                grouped[seriesKey] = new Array(categories.length).fill(null);
            }

            const targetData = item[typeData];
            let lastVal = null;

            categories.forEach((time, index) => {
                if (targetData && targetData[time] !== undefined) {
                    lastVal = parseFloat(targetData[time]);
                    grouped[seriesKey][index] = lastVal;
                } else if (lastVal !== null) {
                    grouped[seriesKey][index] = lastVal;
                }
            });
        });

        // Format ke struktur ApexCharts series
        const series = Object.entries(grouped).map(([remark, values]) => ({
            name: remark,
            data: values
        }));

        return {
            standar, // Mengembalikan objek standar dinamis
            categories,
            grouped,
            series
        };
    }

    // 2. Fungsi Khusus Rendering Chart & Event Listener
    function renderChartValue(process, processData) {

        const safeProcess = process.replace(/\s+/g, '-');
        const el = document.querySelector(`#chart-${safeProcess}`);
        if (!el) return;

        // Clean up chart lama
        if (chartInstances[process] && chartInstances[process].chart) {
            chartInstances[process].chart.destroy();
        }

        const {
            categories,
            grouped,
            series
        } = prepareChartData(processData, 'avg');

        if (series.length === 0) {
            el.innerHTML = `<div style="text-align:center">No Data</div>`;
            return;
        }

        // 1. Ekstrak nilai min & max dari objek standar secara dinamis
        const standards = processData.standar || {};
        const standardLimits = [];
        const annotationsYaxis = [];

        // Daftar warna acak/tetap untuk membedakan garis standar jika lebih dari satu
        const colorPairs = [{
                min: '#0000FF',
                max: '#FF0000'
            }, // Standard 1 (Blue / Red)
            {
                min: '#008000',
                max: '#FF8C00'
            }, // Standard 2 (Green / DarkOrange)
            {
                min: '#8A2BE2',
                max: '#C71585'
            } // Standard 3 (Purple / Magenta)
        ];

        let colorIdx = 0;
        for (const [key, bounds] of Object.entries(standards)) {
            const minVal = parseFloat(bounds.min);
            const maxVal = parseFloat(bounds.max);

            if (!isNaN(minVal)) standardLimits.push(minVal);
            if (!isNaN(maxVal)) standardLimits.push(maxVal);

            const colors = colorPairs[colorIdx % colorPairs.length];

            // Buat anotasi garis MAX jika nilainya ada
            if (!isNaN(maxVal)) {
                annotationsYaxis.push({
                    y: maxVal,
                    borderColor: colors.max,
                    label: {
                        text: `${key} MAX (${maxVal})`,
                        style: {
                            color: '#fff',
                            background: colors.max
                        }
                    }
                });
            }

            // Buat anotasi garis MIN jika nilainya ada
            if (!isNaN(minVal)) {
                annotationsYaxis.push({
                    y: minVal,
                    borderColor: colors.min,
                    label: {
                        text: `${key} MIN (${minVal})`,
                        style: {
                            color: '#fff',
                            background: colors.min
                        }
                    }
                });
            }

            colorIdx++;
        }

        // 2. Hitung yMin dan yMax dari gabungan data series + semua nilai standar
        const allValues = series.flatMap(s => s.data).filter(v => v !== null && !isNaN(v));
        const combinedValues = [...allValues, ...standardLimits];

        const yMin = combinedValues.length > 0 ? Math.min(...combinedValues) : 0;
        const yMax = combinedValues.length > 0 ? Math.max(...combinedValues) : 100;
        const padding = (yMax - yMin) * 0.1 || 1;

        const options = {
            chart: {
                id: `chart-${safeProcess}`,
                type: 'line',
                height: 300,
                animations: {
                    enabled: false,
                    dynamicAnimation: {
                        enabled: false
                    }
                },
                zoom: {
                    enabled: true,
                    type: 'x',
                    autoScaleYaxis: false
                },
                toolbar: {
                    show: true
                }
            },
            series,
            xaxis: {
                categories
            },
            yaxis: {
                min: yMin - padding,
                max: yMax + padding
            },
            stroke: {
                width: 2,
                curve: 'smooth'
            },
            markers: {
                size: 1,
                hover: {
                    size: 5
                }
            },
            tooltip: {
                shared: true,
                intersect: false
            },
            // 3. Masukkan annotations yaxis dinamis yang sudah di-generate
            annotations: {
                yaxis: annotationsYaxis
            }
        };

        const chart = new ApexCharts(el, options);

        chart.render().then(() => {
            if (!el.dataset.hasWheelEvent) {
                el.addEventListener('wheel', function(event) {
                    event.preventDefault();

                    const currentMin = chart.w.globals.minX;
                    const currentMax = chart.w.globals.maxX;
                    const range = currentMax - currentMin;
                    const zoomFactor = event.deltaY < 0 ? 0.8 : 1.25;
                    const newRange = range * zoomFactor;

                    const center = (currentMin + currentMax) / 2;
                    let newMin = center - newRange / 2;
                    let newMax = center + newRange / 2;

                    newMin = Math.max(0, newMin);
                    newMax = Math.min(categories.length - 1, newMax);

                    chart.zoomX(newMin, newMax);
                }, {
                    passive: false
                });

                el.dataset.hasWheelEvent = 'true';
            }
        });

        chartInstances[process] = {
            chart,
            grouped,
            categories
        };
    }

    // 3. Main Loop Executer
    function inisiasiChartValue(cart_data) {
        Object.entries(cart_data).forEach(([process, data]) => {
            renderChartValue(process, data);
        });
    }

    function nyalainRadar(coordinate, no) {

        const [cx, cy, r] = coordinate.split(',').map(Number);

        const radar = $(`#radar-${no}`);

        radar.find('.area-fill')
            .attr({
                cx,
                cy,
                r
            });

        radar.find('.center-dot, .wave')
            .attr({
                cx,
                cy
            });

    }

    const tidakAdaTimer = `
            <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100%; min-height: 400px; color: #6c757d;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 12px; opacity: 0.6;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span style="font-size: 1.25rem; font-weight: 600; letter-spacing: 0.5px;">
                        TIDAK ADA DATA TIMER
                    </span>
                    <span style="font-size: 0.875rem; color: #a0aec0; margin-top: 4px;">
                        Data timer tidak tersedia
                    </span>
            </div>`;

    // 1. Variabel Global Tunggal untuk Menyimpan Instance Chart Modal
    let modalChartInstance = null;
    let timerChartInstance = null;

    // 2. Fungsi Reset yang Benar-Benar Membersihkan Memory & DOM
    function resetModalChart() {
        // Hancurkan Objek ApexCharts dari Memory
        if (modalChartInstance) {
            modalChartInstance.destroy();
            modalChartInstance = null;
        }
        if (timerChartInstance) {
            timerChartInstance.destroy();
            timerChartInstance = null;
        }

        // Bersihkan Isi Elemen HTML
        $('#body-value-actual').empty();
        $('#body-timer-actual').empty();
        $('#modal-chart').empty();
        $('#chartTimer').empty();
        $('#radar-container').empty();
    }

    // 3. Event Handler Click Card
    $(document).on('click', '.card-chart', function() {
        const process = $(this).data('process');
        console.log('Click process:', process);

        const chartData = chartInstances[process];
        if (!chartData) {
            console.log('Chart tidak ditemukan:', process);
            return;
        }

        $('#chartModalTitle').text(process);

        const modalEl = document.getElementById('chartModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        // Fungsi Utama Render Data
        const executeRender = async () => {
            // SELALU RESET SEBELUM MENGGAMBAR BARU
            resetModalChart();

            renderColumnChartModal(window.chart_data[process], process);
            await renderChartTimer(process, window.chart_data[process]);
            await renderModalChart(process, chartData);
        };

        if ($(modalEl).hasClass('show')) {
            executeRender();
        } else {
            $('#chartModal').one('shown.bs.modal', function() {
                executeRender();
            });
            modal.show();
        }
    });

    // 4. Bersihkan juga saat Modal Ditutup oleh User
    $('#chartModal').on('hidden.bs.modal', function() {
        resetModalChart();
    });


    // ==================== FUNGSI RENDER ====================

    function renderColumnChartModal(data, process) {
        if (!data) return;

        const jumlahIndex = Object.keys(data).filter(key => key !== 'min' && key !== 'max').length;
        let jumlah_col = (jumlahIndex <= 2) ? 12 : 6;

        let g_radar = '';
        let html_value_act = '';
        let html_timer_act = '';

        Object.entries(data).forEach(([key, value]) => {
            if (!value || value.coordinate == null) return;

            // Radar
            if (typeof nyalainRadar === 'function') {
                nyalainRadar(value.coordinate, key);
            }
            const [cx, cy, r] = value.coordinate.split(',').map(Number);
            g_radar += `
            <g id="radar-${key}" class="radar" style="--color:#f01818;">
                <g>
                    <circle cx="${cx}" cy="${cy}" r="${r}" class="area-fill" />
                    <circle cx="${cx}" cy="${cy}" r="${r-10}" class="center-dot" />
                    <circle cx="${cx}" cy="${cy}" r="${r-10}" class="wave wave1" />
                </g>
            </g>`;

            // Ambil Nilai Terakhir Value
            const sortedKeys = Object.keys(value.avg || {}).sort();
            const lastKey = sortedKeys[sortedKeys.length - 1];
            const lastValue = lastKey ? parseFloat(value.avg[lastKey]) : NaN;

            // Ambil Nilai Terakhir Timer (DIFIX: Menggunakan sortedKeysTimer)
            const sortedKeysTimer = Object.keys(value.timer || {}).sort();
            const lastKeyTimer = sortedKeysTimer[sortedKeysTimer.length - 1];
            const lastTimer = lastKeyTimer ? Number((parseFloat(value.timer[lastKeyTimer])).toFixed(2)) :
                NaN;

            // Status
            let status = "Normal";
            let status_color = "#10b981";
            let border_color = "rgba(16, 185, 129, 0.25)";
            let text_color = "#000000";

            if (lastValue < data.min || lastValue > data.max) {
                status = "Ubnormal";
                status_color = "#f01f53";
                border_color = "rgba(255, 163, 88, 0.25)";
                text_color = "#ffffff";
            }

            // Card Body Value
            html_value_act += `
        <div class="col-12 col-md-${jumlah_col}">
            <div class="border rounded-4 p-3 bg-white h-100">
                <small class="fw-bold text-secondary text-uppercase d-block mb-3" style="font-size: 0.75rem;">
                    ${process} - ${value.remark}
                </small>
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-3 bg-light d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 60px; height: 80px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="#f97316" viewBox="0 0 16 16">
                            <path d="M8 14a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" />
                            <path d="M8 0a2.5 2.5 0 0 0-2.5 2.5v7.55a3.5 3.5 0 1 0 5 0V2.5A2.5 2.5 0 0 0 8 0zM7.5 2.5a.5.5 0 0 1 1 0v7.1a.5.5 0 0 0 .25.433A2.5 2.5 0 1 1 6.5 10a.5.5 0 0 0 .25-.433V2.5z" />
                        </svg>
                    </div>
                    <div>
                        <span class="d-block text-secondary fw-semibold mb-1" style="font-size: 0.7rem;">Last Value (AKTUAL)</span>
                        <div class="fw-bold lh-1 text-warning mb-1" style="font-size: 2rem; color: #f97316 !important;">
                            ${isNaN(lastValue) ? 'NaN' : lastValue} <span class="fs-6 text-warning" style="color: #f97316 !important;">${value.unit || ''}</span>
                        </div>
                        <span class="d-block text-secondary fw-semibold mb-1" style="font-size: 0.68rem;">SV (SET RANGE)</span>
                        <span class="fw-bold text-success" style="font-size: 0.85rem;">${data.min} - ${data.max} ${value.unit || ''}</span>
                    </div>
                </div>
                <div class="rounded-pill text-center py-1 fw-bold" style="color: ${text_color}; background: ${status_color}; border: 1px solid ${border_color};">
                    ${status}
                </div>
            </div>
        </div>`;

            // Card Body Timer

            html_timer_act += `
        <div class="col-12 col-md-${jumlah_col}">
            <div class="border rounded-4 p-3 bg-white h-100">
                <small class="fw-bold text-secondary text-uppercase d-block mb-3" style="font-size: 0.75rem;">
                    ${process} - ${value.remark}
                </small>
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-3 bg-light d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 60px; height: 80px;">
                       <i class="bi bi-stopwatch"></i>
                    </div>
                    <div>
                        <span class="d-block text-secondary fw-semibold mb-1" style="font-size: 0.7rem;">Last Timer (AKTUAL)</span>
                        <div class="fw-bold lh-1 text-warning mb-1" style="font-size: 2rem; color: #f97316 !important;">
                            ${isNaN(lastTimer) ? 'NaN' : lastTimer} <span class="fs-6 text-warning" style="color: #f97316 !important;">s</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
        });
        if (process === "GAUGE MARKING" || process === "HEATING CONVEYOR" || process === "CHILLER") {
            html_timer_act = `
                <div class="card h-100 min-vh-25 border-0 shadow-sm rounded-4 bg-light bg-gradient d-flex align-items-center justify-content-center p-4">
                <div class="text-center px-3 py-2 rounded-3 bg-white bg-opacity-75 shadow-sm border border-white">
                    <span class="text-dark fw-bold text-uppercase fs-6 tracking-wider d-flex align-items-center gap-2">
                    <span class="p-1 bg-warning bg-opacity-20 text-warning rounded-circle d-inline-flex">
                        &#9888; <!-- Ikon Peringatan/Info -->
                    </span>
                    Tidak Ada Data Timer
                    </span>
                </div>
                </div>
                `;
        }

        $('#mappingImage').attr('src', `/images/iot/mesin/${process}.png`);
        $('#radar-container').html(g_radar);
        $("#body-value-actual").html(html_value_act);
        $("#body-timer-actual").html(html_timer_act);
    }

    function renderChartTimer(process, processData) {
        const container = document.querySelector('#chartTimer');
        if (!container) return;

        if (process === "GAUGE MARKING" || process === "HEATING CONVEYOR" || process === "CHILLER") {
            container.innerHTML = `
                <div class="card h-100 min-vh-25 border-0 shadow-sm rounded-4 bg-light bg-gradient d-flex align-items-center justify-content-center p-4">
                <div class="text-center px-3 py-2 rounded-3 bg-white bg-opacity-75 shadow-sm border border-white">
                    <span class="text-dark fw-bold text-uppercase fs-6 tracking-wider d-flex align-items-center gap-2">
                    <span class="p-1 bg-warning bg-opacity-20 text-warning rounded-circle d-inline-flex">
                        &#9888; <!-- Ikon Peringatan/Info -->
                    </span>
                    Tidak Ada Data Timer
                    </span>
                </div>
                </div>
                `;
            return;
        }

        const {
            categories,
            series
        } = prepareChartData(processData, 'timer');

        if (!series || series.length === 0) {
            container.innerHTML = `<div class="text-center p-4">No Data</div>`;
            return;
        }

        const options = {
            chart: {
                type: 'line',
                height: 450,
                zoom: {
                    enabled: true,
                    type: 'x',
                    autoScaleYaxis: false
                },
                toolbar: {
                    show: true
                }
            },
            series: series,
            xaxis: {
                categories: categories
            },
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            markers: {
                size: 5
            }
        };

        timerChartInstance = new ApexCharts(container, options);
        return timerChartInstance.render();
    }

    function renderModalChart(process, chartData) {
        const container = document.querySelector('#modal-chart');
        if (!container || !chartData || !chartData.chart) return;

        // 1. Ambil opsi yang sudah matang dari chart dashboard utama
        const sourceOptions = chartData.chart.opts;

        // 2. Clone opsi tersebut dan sesuaikan hanya properti yang butuh beda (seperti height)
        const modalOptions = JSON.parse(JSON.stringify(sourceOptions));

        modalOptions.chart.id = `modal-chart-${process.replace(/\s+/g, '-')}`;
        modalOptions.chart.height = 450; // Lebih tinggi untuk tampilan modal
        modalOptions.stroke.curve = 'smooth'; // Opsional jika ingin garis modal lebih halus

        // 3. Render chart di modal menggunakan opsi yang disalin
        modalChartInstance = new ApexCharts(container, modalOptions);

        return modalChartInstance.render().then(() => {
            // Event wheel zoom jika diperlukan
            if (!container.dataset.hasWheelEvent) {
                container.addEventListener('wheel', function(event) {
                    event.preventDefault();
                    const currentMin = modalChartInstance.w.globals.minX;
                    const currentMax = modalChartInstance.w.globals.maxX;
                    const range = currentMax - currentMin;
                    const zoomFactor = event.deltaY < 0 ? 0.8 : 1.25;
                    const newRange = range * zoomFactor;

                    const center = (currentMin + currentMax) / 2;
                    let newMin = Math.max(0, center - newRange / 2);
                    let newMax = Math.min(chartData.categories.length - 1, center + newRange / 2);

                    modalChartInstance.zoomX(newMin, newMax);
                }, {
                    passive: false
                });

                container.dataset.hasWheelEvent = 'true';
            }
        });
    }

    function LineListenForRealTimeUpdates(deviceId) {
        if (window.activeChannels[deviceId]) return;

        const channelName = `pressure-device.${deviceId}`;

        Echo.channel(channelName)
            .listen('.pressure.updated', (data) => {
                // console.log("reload - data");

                // console.log(data);

                handleRealtimeUpdate(deviceId, data);
                handleRealtimeCardUpdate(deviceId, data);
            });

        window.activeChannels[deviceId] = channelName;
    }

    function handleRealtimeCardUpdate(deviceId, payload) {
        // console.log("Payload");
        // console.log(payload);
        // console.log(" End Payload");
        // console.log(deviceId);


        const tekanan = payload.tekanan;
        const valEl = $('#last-value-' + deviceId);
        const statusEl = $('#view-status-' + deviceId);
        let progress_bar = $('#prograss_bar_' + deviceId);
        let total_process = $('#total_process_' + deviceId);
        let total_bagus = $('#total_bagus_' + deviceId);
        let percentage = $('#percentage_' + deviceId);
        let lastPercent = parseInt(percentage.html().match(/\d+/)[0]);
        let lastTotal = parseInt(total_process.html().match(/\d+/)[0]) + 1;
        let lastTotalBagus = parseInt(total_bagus.html().match(/\d+/)[0]);

        // =========================
        // ✅ UPDATE VALUE
        // =========================
        var unit = " Kg/Cm2"
        if (deviceId.startsWith("TEMP")) {
            unit = " °C";
        }
        valEl.html(tekanan + unit);

        // animasi value
        valEl.addClass('flash-update');
        setTimeout(() => valEl.removeClass('flash-update'), 600);

        // =========================
        // ✅ UPDATE STATUS
        // =========================
        statusEl.removeClass();
        let status = ``;
        // console.log("tekanan" + tekanan);
        // console.log("standar" + window.standar_max[deviceId]);

        if (parseFloat(tekanan) >= window.standar_min[deviceId] &&
            parseFloat(tekanan) <= window.standar_max[deviceId]) {

            statusEl.addClass('badge iot-badge-status bg-success fw-bold');
            status = `↑ NORMAL`;
        } else {
            statusEl.addClass('badge iot-badge-status bg-danger fw-bold');
            status = `↓ ABNORMAL`;
        }

        statusEl.html(status);

        // animasi badge
        statusEl.find('.badge').addClass('flash-badge');
        setTimeout(() => {
            statusEl.find('.badge').removeClass('flash-badge');
        }, 800);
        // =========================
        // ✅ UPDATE PROGRESSBAR DATA
        // =========================
        total_process.html("Total Process: " + lastTotal);
        // console.log("Update Total Process : " + lastTotal);

        if (parseFloat(tekanan) >= window.standar_min[deviceId] &&
            parseFloat(tekanan) <= window.standar_max[deviceId]) {
            lastTotalBagus = lastTotalBagus + 1;
            total_bagus.html(lastTotalBagus);
            lastPercent = (lastTotalBagus / lastTotal) * 100;
            lastPercent = lastPercent.toFixed(2);
            percentage.html(lastPercent + " %");
            // console.log("Update Nilai Bagus");

            progress_bar.css('width', lastPercent + '%');
            if (parseFloat(lastPercent) >= 90) {
                color_progress = "bg-success";
            } else if (parseFloat(lastPercent) >= 50) {
                color_progress = "bg-warning";
            } else {
                color_progress = "bg-danger";
            }

            progress_bar.removeClass(function(index, className) {
                    return (className.match(/bg-\S+/g) || []).join(' ');
                })
                .addClass(color_progress);

        }

    }

    function handleRealtimeUpdate(deviceId, payload) {
        const {
            tekanan,
            time
        } = payload;

        const value = parseFloat(tekanan);

        // cari process mana yang punya device ini
        Object.entries(chart_data).forEach(([process, data]) => {

            if (!data[deviceId]) return; // skip kalau bukan miliknya

            const chartObj = chartInstances[process];
            if (!chartObj) return;

            const {
                chart,
                grouped,
                categories
            } = chartObj;
            const remark = data[deviceId].remark;

            // =========================
            // ✅ UPDATE CATEGORY (TIME)
            // =========================
            if (!categories.includes(time)) {
                categories.push(time);
                categories.sort((a, b) => a.localeCompare(b));
            }

            // =========================
            // ✅ UPDATE DATA SERIES
            // =========================
            if (!grouped[remark]) {
                grouped[remark] = new Array(categories.length).fill(null);
            }

            const index = categories.indexOf(time);

            // 🔥 pastikan semua series panjangnya sama dulu
            Object.keys(grouped).forEach(key => {
                while (grouped[key].length < categories.length) {
                    grouped[key].push(null);
                }
            });

            // ambil last value sebelumnya
            let lastVal = null;

            for (let i = index - 1; i >= 0; i--) {
                if (grouped[remark][i] !== null && grouped[remark][i] !== undefined) {
                    lastVal = grouped[remark][i];
                    break;
                }
            }

            // isi value sekarang
            grouped[remark][index] = value !== undefined ? value : lastVal;

            // sync semua series
            Object.keys(grouped).forEach(key => {
                let lastVal = null;

                grouped[key] = grouped[key].map(v => {
                    if (v !== null && v !== undefined) {
                        lastVal = v;
                        return v;
                    }
                    return lastVal;
                });
            });
            if (categories.length > MAX_DATA_POINTS) {
                categories.shift();

                Object.keys(grouped).forEach(key => {
                    grouped[key].shift();
                });
            }
            Object.keys(grouped).forEach(key => {
                grouped[key] = grouped[key].map(v =>
                    (v === undefined ? null : v)
                );
            });
            // =========================
            // ✅ BUILD ULANG SERIES
            // =========================
            const newSeries = Object.entries(grouped).map(([r, values]) => ({
                name: r,
                data: values
            }));

            // =========================
            // ✅ UPDATE CHART TANPA RE-RENDER
            // =========================
            chart.updateOptions({
                xaxis: {
                    categories
                },
                series: newSeries
            });

        });
    }

    function getLastNumber(deviceId) {
        const tbody = document.querySelector(".tbody-" + deviceId);
        const rows = tbody.querySelectorAll("tr");

        if (rows.length === 0) return 0; // jika kosong

        const lastRow = rows[rows.length - 1];
        const lastNumber = parseInt(lastRow.children[0].textContent);

        return lastNumber;
    }
    $(document).on('click', '.card-title', function() {
        $('.chart-flip').toggleClass('active');
    });
</script>
