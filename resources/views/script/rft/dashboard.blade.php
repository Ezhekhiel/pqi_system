<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- apexcharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
    integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8=" crossorigin="anonymous"></script>
<script>
    function toggleManual(header) {
        let content = header.nextElementSibling;
        content.classList.toggle("show");
    }
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
{{-- <script>
    document.addEventListener("DOMContentLoaded", function() {
        flatpickr("#tanggal", {
            dateFormat: "Y-m-d",
            defaultDate: @json($lastDate),
            minDate: @json($firstDate),
            maxDate: @json($lastDate),
        });
    });
</script> --}}
<script>
    $(document).ready(function() {
        let BoxCharts = {};
        let chartArea = null;
        let areaReject = {};
        let data_last = @json($get_last);
        let area = $('#area_id').val();
        let dataArea = data_last[area] ?? [];
        let arrayDeviceData = {
            output: {},
            reject: {}
        };
        let dataHeader = {};
        let arrayDevice = {};

        let latest = dataArea
            .filter(item => item.latest_data?.created_at)
            .sort((a, b) =>
                new Date(b.latest_data.created_at) -
                new Date(a.latest_data.created_at)
            )[0];

        let latestDate = latest?.latest_data?.created_at ?? null;
        let datelatest = latestDate?.substring(0, 10) ?? null;
        let chartOutputSummary = null;
        let chartRejectsSummary = null;
        let chartPassRateSummary = null;
        let chartRejectTrendSummary = null;

        $("#tanggal").val(datelatest);
        main();

        $('#tanggal').on('change', function() {
            main();
        });
        $('#area_id').on('change', function() {
            main();
        });
        $(document).on('click', '.jam-row', function() {

            let jam = $(this).data('jam');

            $('.detail-' + jam).toggle();

        });

        function main() {
            var tanggal = $('#tanggal').val();
            var area = $('#area_id').val();
            $.ajax({
                url: "{{ route('rft.main') }}",
                data: {
                    tanggal: tanggal,
                    area: area
                },
                method: "get",
                dataType: 'JSON',
                success: function(data) {
                    //reset Box Charts
                    Object.values(BoxCharts).forEach(chart => {
                        chart?.destroy();
                    });

                    BoxCharts = {};
                    //render visual
                    $('#visual').html(data.visual);

                    listenEvent(data.array_id_process);
                    // renderCharts(data.array_device);

                    renderChartsPareto(data.array_device);
                    renderChartByArea(data.arr_reject_by_device);

                    dataHeader = data.data_header;
                    arrayDevice = data.array_device;

                    buildArrayDevice();
                }
            });
        }
        // --- Langkah 1: Atur Standar Global (Hanya perlu sekali, misal di luar fungsi) ---
        // Ini akan memastikan semua bagan ApexCharts menggunakan font dan gaya dasar yang sama.
        Apex = {
            chart: {
                fontFamily: 'Inter, sans-serif',
                toolbar: {
                    show: false
                }
            },
            tooltip: {
                theme: 'dark' // Tooltip gelap terlihat lebih premium
            }
        };

        // --- Langkah 2: Fungsi yang Diperbarui ---
        // === chart dakan box process === //
        function renderChartsPareto(array_device) {
            Object.keys(array_device).forEach(function(processId) {
                let element = document.querySelector("#outputRejectChart_" + processId);
                if (!element) return;

                let rawData = array_device[processId] || [];

                // ambil jam dan reject
                let data = rawData.map(item => ({
                    jam: "Jam " + item.jam,
                    reject: Number(item.reject) || 0
                }));

                // filter reject > 0
                data = data.filter(d => d.reject > 0);

                // urutkan dari terbesar
                data.sort((a, b) => b.reject - a.reject);

                let categories = data.map(d => d.jam);
                let rejectData = data.map(d => d.reject);

                // hitung total reject
                let totalReject = rejectData.reduce((a, b) => a + b, 0);

                // cumulative %
                let cumulative = 0;
                let cumulativeData = rejectData.map(val => {
                    cumulative += val;
                    return totalReject > 0 ?
                        Number(((cumulative / totalReject) * 100).toFixed(1)) :
                        0;
                });

                // --- Konfigurasi Opsi yang Lebih Keren & Elegan ---
                let options = {
                    chart: {
                        height: 220,
                        type: 'line',
                        background: '#fff', // Latar belakang putih bersih
                    },
                    series: [{
                        name: 'Reject',
                        type: 'column',
                        data: rejectData
                    }, {
                        name: 'Cumulative %',
                        type: 'line',
                        data: cumulativeData
                    }],
                    // --- Pembaruan: Sudut Bar yang Membulat ---
                    plotOptions: {
                        bar: {
                            borderRadius: 6, // Radius pembulatan (px)
                            columnWidth: '65%', // Sedikit lebih lebar agar proporsional
                        }
                    },
                    xaxis: {
                        categories: categories,
                        title: {
                            text: 'Jam Produksi',
                            offsetY: -5,
                            style: {
                                color: '#6b7280', // Warna label abu-abu yang elegan
                                fontWeight: 600,
                                fontSize: '11px'
                            }
                        },
                        labels: {
                            style: {
                                colors: '#6b7280', // Warna label abu-abu
                                fontSize: '11px'
                            }
                        }
                    },
                    yaxis: [{
                        title: {
                            text: 'Reject Count',
                            style: {
                                color: '#6b7280', // Warna label abu-abu
                                fontWeight: 600,
                                fontSize: '11px'
                            }
                        },
                        labels: {
                            formatter: function(val) {
                                return Math.round(val);
                            },
                            style: {
                                colors: '#6b7280', // Warna label abu-abu
                                fontSize: '11px'
                            }
                        },
                        grid: {
                            show: false // Hapus grid di sumbu ini agar bersih
                        }
                    }, {
                        opposite: true,
                        min: 0,
                        max: 100,
                        tickAmount: 5,
                        title: {
                            text: 'Cumulative (%)',
                            style: {
                                color: '#6b7280', // Warna label abu-abu
                                fontWeight: 600,
                                fontSize: '11px'
                            }
                        },
                        labels: {
                            formatter: function(val) {
                                return Math.round(val) + "%";
                            },
                            style: {
                                colors: '#6b7280', // Warna label abu-abu
                                fontSize: '11px'
                            }
                        },
                        grid: {
                            show: false // Hapus grid di sumbu ini agar bersih
                        }
                    }],
                    // --- Pembaruan: Palet Warna Lembut dan Elegan ---
                    colors: ['#b91c1c', '#0e9f6e'], // Merah Tua & Hijau Tua
                    stroke: {
                        width: [0, 3], // 0 untuk bar, 3 untuk garis
                        curve: 'smooth' // Kurva halus ke garis kumulatif
                    },
                    dataLabels: {
                        enabled: false // Matikan default
                    },
                    // --- Pembaruan: Tambahkan Marker ke Garis Kumulatif ---
                    markers: {
                        size: 3, // Sedikit lebih kecil dari chart utama agar tidak penuh
                        colors: ['#0e9f6e'],
                        strokeColors: '#fff',
                        strokeWidth: 2,
                        hover: {
                            size: 5,
                        }
                    },
                    // --- Pembaruan: Gunakan Gradien Halus pada Bar ---
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shade: 'dark',
                            type: "vertical",
                            shadeIntensity: 0.1,
                            opacityFrom: 0.85,
                            opacityTo: 0.85,
                            stops: [0, 100],
                        }
                    },
                    grid: {
                        borderColor: '#e5e7eb', // Garis kisi abu-abu muda
                        strokeDashArray: 4, // Gunakan garis putus-putus
                        xaxis: {
                            lines: {
                                show: false // Hapus garis kisi vertikal
                            }
                        },
                        yaxis: {
                            lines: {
                                show: true // Tampilkan garis kisi horizontal
                            }
                        },
                    },
                    legend: {
                        show: true,
                        position: 'bottom',
                        horizontalAlign: 'center',
                        offsetY: 5,
                        labels: {
                            colors: '#6b7280', // Warna label abu-abu
                            useSeriesColors: false
                        },
                        markers: {
                            width: 10,
                            height: 10,
                            strokeWidth: 0,
                            radius: 10, // Legenda juga membulat
                        }
                    }
                };

                // Render bagan
                if (typeof ApexCharts !== 'undefined') {
                    BoxCharts[processId] = new ApexCharts(element, options);
                    BoxCharts[processId].render();
                } else {
                    console.error("ApexCharts is not defined. Please ensure the library is loaded.");
                }
            });
        }

        function renderChartByArea(data) {

            areaReject = data;
            updateChartByArea(data);

        }

        function chartHeader() {
            const total_prod = arrayDeviceData.output;
            const total_reject = arrayDeviceData.reject;
            const overall = dataHeader.overall_pass_rate;

            const total_reject_yesterday = dataHeader.total_reject_yesterday;

            const totalProduction = Object.values(total_prod)
                .reduce((sum, val) => sum + Number(val), 0);
            const totalReject = Object.values(total_reject)
                .reduce((sum, val) => sum + Number(val), 0);
            const totalRejectYesterday = Object.values(total_reject_yesterday)
                .reduce((sum, val) => sum + Number(val), 0);
            const overallPassRate = totalProduction === 0 ?
                0 :
                ((totalProduction - totalReject) / totalProduction) * 100;

            const rejectTrend = totalRejectYesterday > 0 ?
                ((totalReject - totalRejectYesterday) / totalRejectYesterday) * 100 :
                0;

            if (rejectTrend >= 0) {
                $("#reject_trend").html(
                    '<i class="bi bi-arrow-up-right text-danger"></i> +' +
                    rejectTrend.toFixed(1) + '%'
                );
            } else {
                $("#reject_trend").html(
                    '<i class="bi bi-arrow-down-right text-success"></i> ' +
                    rejectTrend.toFixed(1) + '%'
                );
            }


            // =============================
            // Prepare Data
            // =============================

            $("#total_production").text(totalProduction);
            $("#total_reject").text(totalReject);
            $("#overall_pass_rate").text(overallPassRate.toFixed(1) + "%");

            const category_data_prod = Object.keys(total_prod).map(h => h.padStart(2, '0') + ":00");
            const series_data_prod = Object.values(total_prod).map(Number);

            const category_data_reject = Object.keys(total_reject).map(h => h.padStart(2, '0') + ":00");
            const series_data_reject = Object.values(total_reject).map(Number);

            const category_data_overall = Object.keys(overall);
            const series_data_overall = Object.values(overall).map(Number);


            // =============================
            // Base Options
            // =============================

            const sparklineOptions = {
                chart: {
                    type: 'line',
                    height: 35,
                    sparkline: {
                        enabled: true
                    }
                },

                stroke: {
                    curve: 'smooth',
                    width: 2
                },

                dataLabels: {
                    enabled: false
                },

                tooltip: {
                    x: {
                        show: true
                    },
                    y: {
                        formatter: function(value) {
                            return Math.round(value);
                        }
                    }
                },

                yaxis: {
                    labels: {
                        formatter: function(value) {
                            return Math.round(value);
                        }
                    }
                },

                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: 'vertical',
                        opacityFrom: 0.85,
                        opacityTo: 0.25,
                        stops: [0, 100]
                    }
                },
            };

            chartOutputSummary = createOrUpdateSummaryChart(
                chartOutputSummary,
                "#chartOutputSummary",
                "Output",
                series_data_prod,
                category_data_prod,
                "#28a745",
                sparklineOptions
            );

            chartRejectsSummary = createOrUpdateSummaryChart(
                chartRejectsSummary,
                "#chartRejectsSummary",
                "Reject",
                series_data_reject,
                category_data_reject,
                "#dc3545",
                sparklineOptions
            );

            chartPassRateSummary = createOrUpdateSummaryChart(
                chartPassRateSummary,
                "#chartPassRateSummary",
                "Pass Rate",
                series_data_overall,
                category_data_overall,
                "#28a745",
                sparklineOptions
            );

            chartRejectTrendSummary = createOrUpdateSummaryChart(
                chartRejectTrendSummary,
                "#chartRejectTrendSummary",
                "Reject Trend",
                series_data_reject,
                category_data_reject,
                "#ffc107",
                sparklineOptions
            );

        }

        function createOrUpdateSummaryChart(
            chart,
            elementId,
            seriesName,
            seriesData,
            categories,
            color,
            sparklineOptions
        ) {

            const element = document.querySelector(elementId);

            if (!element) {
                console.warn(`Element ${elementId} tidak ditemukan.`);
                return chart;
            }

            if (!chart) {

                chart = new ApexCharts(element, {
                    ...sparklineOptions,
                    chart: {
                        type: 'area',
                        height: 45,
                        sparkline: {
                            enabled: true
                        }
                    },
                    series: [{
                        name: seriesName,
                        data: seriesData
                    }],
                    xaxis: {
                        categories: categories
                    },
                    colors: [color]
                });

                chart.render();

            } else {

                chart.updateOptions({
                    xaxis: {
                        categories: categories
                    }
                });

                chart.updateSeries([{
                    name: seriesName,
                    data: seriesData
                }]);
            }

            return chart;
        }

        function buildArrayDevice() {

            arrayDeviceData.output = {};
            arrayDeviceData.reject = {};

            Object.values(arrayDevice).forEach(area => {

                area.forEach(item => {

                    const jam = item.jam;

                    if (!arrayDeviceData.output[jam]) {
                        arrayDeviceData.output[jam] = 0;
                    }

                    if (!arrayDeviceData.reject[jam]) {
                        arrayDeviceData.reject[jam] = 0;
                    }

                    arrayDeviceData.output[jam] += Number(item.output) || 0;
                    arrayDeviceData.reject[jam] += Number(item.reject) || 0;

                });

            });

            chartHeader();
        }


        function listenEvent(arr_id_process) {
            arr_id_process.forEach(function(id_process) {

                Echo.channel('rft_' + id_process)
                    .listen('.rft.updated', function(e) {

                        console.log("Event diterima dari device " + id_process, e);

                        let data_rft = e.data.data_rft;
                        let rft_detail = e.data.rft;
                        const processId = Number(rft_detail.id_process);

                        // kalau card belum ada → buat
                        if ($('#card_' + processId).length === 0) {
                            main();
                        }

                        /* ===============================
                           HITUNG TOTAL HEADER
                        =============================== */

                        const total_output = Number(data_rft[processId]?.total_output) || 0;
                        const total_reject = Number(data_rft[processId]?.total_reject) || 0;

                        const rft = total_output > 0 ?
                            Math.floor(((total_output - total_reject) / total_output) * 100) :
                            0;

                        /* ===============================
                           UPDATE SENSOR HEADER
                        =============================== */

                        $('#total_output_' + processId).text(total_output);
                        $('#total_reject_' + processId).text(total_reject);
                        $('#total_rft_' + processId).text(rft + ' %');

                        // warna RFT text
                        let rftText = $('#total_rft_' + processId);
                        rftText.removeClass('text-success text-warning text-danger');

                        if (rft < 50) {
                            rftText.addClass('text-danger');
                        } else if (rft < 100) {
                            rftText.addClass('text-warning');
                        } else {
                            rftText.addClass('text-success');
                        }

                        // warna reject
                        let rejectText = $('#total_reject_' + processId);
                        rejectText.removeClass('text-success text-danger');
                        rejectText.addClass(total_reject > 0 ? 'text-danger' : 'text-success');


                        /* ===============================
                           UPDATE / INSERT TABLE ROW
                        =============================== */

                        let row = $('#tr_' + processId + '_' + rft_detail.jam);

                        let detail_output = parseInt(rft_detail.sum_total_output) || 0;
                        let detail_reject = parseInt(rft_detail.sum_total_reject) || 0;
                        let data_detail_output = '';
                        let data_reject_output = '';
                        let row_detail = '';
                        $.each(rft_detail.detail, function(index, value) {
                            row_detail = $('#tr_detail_' + processId + '_' + rft_detail
                                .jam + '_' + value.id_device);
                            data_detail_output = parseInt(value.total_output) || 0;
                            data_reject_output = parseInt(value.total_reject) || 0;

                            row_detail.find('td').eq(1).text(data_detail_output);
                            row_detail.find('td').eq(2).text(data_reject_output);
                        });

                        let rft_detail_value = 0;
                        if (detail_output > 0) {
                            rft_detail_value = ((detail_output - detail_reject) / detail_output) *
                                100;
                        }
                        rft_detail_value = Math.round(rft_detail_value);

                        let color = '';
                        if (rft_detail_value < 50) {
                            color = 'bg-danger';
                        } else if (rft_detail_value < 100) {
                            color = 'bg-warning text-dark';
                        } else {
                            color = 'bg-success';
                        }

                        if (row.length > 0) {

                            row.find('td').eq(1).text(detail_output);
                            row.find('td').eq(2).text(detail_reject);
                            row.find('td').eq(3).text(rft_detail_value + '%');


                            let progress = $('#progress_bar_' + rft_detail.id);

                            progress
                                .css('width', rft_detail_value + '%')
                                .attr('aria-valuenow', rft_detail_value)
                                .text(rft_detail_value + '%')
                                .removeClass('bg-success bg-warning bg-danger text-dark')
                                .addClass(color);

                        } else {

                            let jumlahRow = $('#tbody_' + processId + ' tr').length;

                            let newRow = `
                                    <tr id="tr_${processId}_${rft_detail.jam}" class="jam-row">
                                        <td>${rft_detail.jam}</td>
                                        <td>${detail_output}</td>
                                        <td>${detail_reject}</td>
                                        <td>${rft_detail_value}%</td>
                                        <td>
                                            <div class="progress rounded-pill" style="height: 20px;">
                                                <div class="progress-bar rounded-pill ${color}"
                                                    id="progress_bar_${rft_detail.id}"
                                                    role="progressbar"
                                                    style="width: ${rft_detail_value}%"
                                                    aria-valuenow="${rft_detail_value}"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    ${rft_detail_value}%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                `;

                            $('#tbody_' + processId).append(newRow);
                        }
                        /* ===============================
                        UPDATE CHART PARETO
                        ================================ */

                        let chartInstance = BoxCharts[processId];

                        if (chartInstance) {

                            let data = [];

                            $('#tbody_' + processId + ' tr.jam-row').each(function() {

                                let jam = $(this).find('td').eq(0).text();
                                let reject = parseInt($(this).find('td').eq(2).text()) || 0;

                                if (reject > 0) {
                                    data.push({
                                        jam: "Jam " + jam,
                                        reject: reject
                                    });
                                }

                            });

                            // urutkan dari terbesar
                            data.sort((a, b) => b.reject - a.reject);

                            let categories = data.map(d => d.jam);
                            let rejectData = data.map(d => d.reject);

                            // total reject
                            let totalReject = rejectData.reduce((a, b) => a + b, 0);

                            let cumulative = 0;
                            let cumulativeData = rejectData.map(val => {
                                cumulative += val;
                                return totalReject > 0 ? ((cumulative / totalReject) * 100)
                                    .toFixed(2) : 0;
                            });

                            chartInstance.updateOptions({
                                xaxis: {
                                    categories: categories
                                }
                            });

                            chartInstance.updateSeries([{
                                    name: 'Reject',
                                    type: 'column',
                                    data: rejectData
                                },
                                {
                                    name: 'Cumulative %',
                                    type: 'line',
                                    data: cumulativeData
                                }
                            ]);

                        }

                        /* ===============================
                        UPDATE CHART BY AREA
                        ================================ */

                        let proses = rft_detail.proses;

                        if (!areaReject[proses]) {
                            areaReject[proses] = 0;
                        }

                        areaReject[proses] = total_reject;

                        updateChartByArea(areaReject);

                        /* ===============================
                        UPDATE HEADER // GLOBAL SUMMARY
                        ================================ */
                        updateArrayDevice(processId, rft_detail.jam, rft_detail.sum_total_output,
                            rft_detail.sum_total_reject);
                    });

            });
        }

        function updateArrayDevice(processId, jam, output, reject) {

            output = Number(output) || 0;
            reject = Number(reject) || 0;

            if (!arrayDevice[processId]) {
                arrayDevice[processId] = [];
            }

            const data = arrayDevice[processId].find(
                item => item.jam == jam
            );

            if (data) {

                data.output = output;
                data.reject = reject;

            } else {

                arrayDevice[processId].push({
                    jam: jam,
                    output: output,
                    reject: reject
                });

            }

            buildArrayDevice();
        }

        function updateChartByArea(data) {
            let arr = Object.keys(data).map(key => ({
                name: key,
                value: Number(data[key]) || 0
            }));

            arr.sort((a, b) => b.value - a.value);

            let categories = arr.map(d => d.name);
            let values = arr.map(d => d.value);

            let total = values.reduce((a, b) => a + b, 0);

            let cumulative = 0;
            let cumulativeData = values.map(v => {
                cumulative += v;

                return total > 0 ?
                    Number(((cumulative / total) * 100).toFixed(1)) :
                    0;
            });

            if (!chartArea) {
                let options = {
                    chart: {
                        height: 300,
                        type: 'line',
                        toolbar: {
                            show: false
                        },
                        fontFamily: 'Inter, sans-serif',
                        background: '#fff',
                    },
                    series: [{
                        name: 'Reject',
                        type: 'column',
                        data: values
                    }, {
                        name: 'Cumulative',
                        type: 'line',
                        data: cumulativeData
                    }],
                    // --- Pembaruan Siku-siku yang Tajam menjadi Membulat ---
                    plotOptions: {
                        bar: {
                            borderRadius: 6, // Atur radius pembulatan di sini (px). Semakin besar, semakin bulat.
                            columnWidth: '60%', // Atur lebar bar agar lebih elegan (opsional)
                            // Anda juga bisa mengatur borderRadiusApplication ke 'end' (hanya atas) atau 'around' (atas-bawah)
                            // borderRadiusApplication: 'end'
                        }
                    },
                    xaxis: {
                        categories: categories,
                        labels: {
                            style: {
                                colors: '#6b7280',
                                fontSize: '12px'
                            }
                        }
                    },
                    yaxis: [{
                        title: {
                            text: 'Reject Count',
                            style: {
                                color: '#6b7280',
                                fontWeight: 600,
                                fontSize: '12px'
                            }
                        },
                        labels: {
                            style: {
                                colors: '#6b7280',
                                fontSize: '12px'
                            },
                            formatter: val => parseInt(val)
                        },
                        grid: {
                            show: false
                        }
                    }, {
                        opposite: true,
                        max: 100,
                        title: {
                            text: 'Cumulative (%)',
                            style: {
                                color: '#6b7280',
                                fontWeight: 600,
                                fontSize: '12px'
                            }
                        },
                        labels: {
                            style: {
                                colors: '#6b7280',
                                fontSize: '12px'
                            },
                            formatter: val => Math.round(val) + "%"
                        },
                        grid: {
                            show: false
                        }
                    }],
                    colors: ['#b91c1c', '#0e9f6e'],
                    stroke: {
                        width: [0, 3],
                        curve: 'smooth',
                    },
                    dataLabels: {
                        enabled: false,
                        enabledOnSeries: [0],
                        offsetY: -20,
                        style: {
                            fontSize: '12px',
                            fontWeight: 'bold',
                            colors: ['#b91c1c']
                        },
                        background: {
                            enabled: true,
                            foreColor: '#fff',
                            padding: 4,
                            borderRadius: 2,
                            borderWidth: 1,
                            borderColor: '#fff',
                            opacity: 0.9,
                            dropShadow: {
                                enabled: false,
                            }
                        }
                    },
                    markers: {
                        size: 4,
                        colors: ['#0e9f6e'],
                        strokeColors: '#fff',
                        strokeWidth: 2,
                        hover: {
                            size: 7,
                        }
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shade: 'dark',
                            type: "vertical",
                            shadeIntensity: 0.1,
                            opacityFrom: 0.85,
                            opacityTo: 0.85,
                            stops: [0, 100],
                        }
                    },
                    grid: {
                        borderColor: '#e5e7eb',
                        strokeDashArray: 4,
                        xaxis: {
                            lines: {
                                show: false
                            }
                        },
                        yaxis: {
                            lines: {
                                show: true
                            }
                        },
                    },
                    legend: {
                        show: true,
                        position: 'bottom',
                        horizontalAlign: 'center',
                        offsetY: 7,
                        labels: {
                            colors: '#6b7280',
                            useSeriesColors: false
                        },
                        markers: {
                            width: 12,
                            height: 12,
                            strokeWidth: 0,
                            radius: 12,
                            offsetX: 0,
                            offsetY: 0
                        }
                    },
                    tooltip: {
                        theme: 'dark',
                        style: {
                            fontSize: '12px',
                            fontFamily: 'Inter, sans-serif'
                        }
                    }
                };

                chartArea = new ApexCharts(
                    document.querySelector("#paretoByArea"),
                    options
                );

                chartArea.render();

            } else {
                // --- Terapkan perubahan borderRadius pada updateOptions ---
                chartArea.updateOptions({
                    xaxis: {
                        categories: categories
                    }
                });

                chartArea.updateSeries([{
                    name: 'Reject',
                    type: 'column',
                    data: values
                }, {
                    name: 'Cumulative',
                    type: 'line',
                    data: cumulativeData
                }]);
            }
        }
    });
</script>
