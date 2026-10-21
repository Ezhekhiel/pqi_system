<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<!-- Daterangepicker -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/image-map-resizer/1.0.10/js/imageMapResizer.min.js"></script>
<script>
    let mainData = {};
    const usl = 60;
    const ucl = 55;
    const lcl = 42;
    let device_id = [];
    let donutChart = null;
    let data_array = {},
        active_charts = {},
        val_radar = {};
    let html = '',
        html_option = '',
        html_alarm_history = '';
    let val_success = 0,
        val_warning = 0,
        val_danger = 0,
        avg_temperature = 0,
        avg_humidity = 0,
        total_device = 0;
    document.addEventListener("DOMContentLoaded", function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        main();
        getData();

        function main() {
            const today = new Date().toISOString().split('T')[0];
            // Ambil jam sekarang format "HH:00"
            const fromHour = new Date().getHours();
            const toHour = new Date().getHours() + 1;
            const fromFormattedHour = (fromHour < 10 ? '0' + fromHour : fromHour) + ':00';
            const toFormattedHour = (toHour < 10 ? '0' + toHour : toHour) + ':00';

            // Buat array isi jam 01:00 sampai 24:00 secara otomatis
            let fromOptions = '<option value="">From</option>';
            let toOptions = '<option value="">to</option>';
            for (let i = 1; i <= 24; i++) {
                let hh = i < 10 ? '0' + i : i;
                let timeVal = `${hh}:00`;
                if (i == 24) {
                    timeVal = `00:00`;
                }
                // Jika timeVal sama dengan jam sekarang, beri attribute 'selected'
                let isSelectedFrom = (timeVal === fromFormattedHour) ? 'selected' : '';
                let isSelectedTo = (timeVal === toFormattedHour) ? 'selected' : '';


                fromOptions += `<option value="${timeVal}" ${isSelectedFrom}>${timeVal}</option>`;
                toOptions += `<option value="${timeVal}" ${isSelectedTo}>${timeVal}</option>`;
            }
            const buildingData = @json($get_building);
            const areaData = @json($get_area);


            const buildingOptions = buildingData.map(building => {
                return `<option value="${building.id}">${building.building}</option>`;
            }).join('');
            const areaOption = areaData.map(a => {
                return `<option value="${a.id}">${a.area}</option>`;
            }).join('');
            $('#header-template').html(`
                <div class="d-flex gap-2 w-100 align-items-center">
                            <select id="select_building_data" class="form-control select-tab" onchange="changeBuilding(this.value)">
                                ${buildingOptions}
                            </select>
                            <select id="select_area_data" class="form-control select-tab search_tab_data" onchange="getData()">
                                ${areaOption}
                            </select>
                            <input type="date" id="dateBiasa" value="${today}" class="form-control" onchange="getData()">
                            <select id="dataHourFrom" class="form-control" onchange="getData()">
                                ${fromOptions}
                                </select>
                            <select id="dataHourTo" class="form-control" onchange="getData()">
                               ${toOptions}
                                </select>
                </div>
            `);
        }
        $('#mappingImage').on('load', function() {

            let width = this.naturalWidth;
            let height = this.naturalHeight;

            $('#mappingSvg')
                .attr('viewBox', `0 0 ${width} ${height}`);

        });
    });

    function changeBuilding(building) {
        $.ajax({
            url: "{{ route('TH.Dashboard.change_building') }}", // atau "/your-url"
            type: "GET",
            data: {
                building: building
            },
            dataType: 'JSON',
            beforeSend: function() {
                console.log('Loading change building');
            },
            success: function(response) {
                $('#select_area_data').html(response.list_area);

            },
            error: function(xhr) {
                console.log(xhr.responseText);
            }
        });
    }

    function getData() {
        let building = $('#select_building_data').val();
        let area = $('#select_area_data').val();
        let date = $('#dateBiasa').val();
        let from = $('#dataHourFrom').val();
        let to = $('#dataHourTo').val();
        var hourRange = "";
        if (from != "" && to != "") {
            hourRange = from + " - " + to;
        }
        $.ajax({
            url: "{{ route('TH.Dashboard.getData') }}", // atau "/your-url"
            type: "POST",
            data: {
                building: building,
                area: area,
                date: date,
                hourRange: hourRange
            },
            dataType: 'JSON',

            beforeSend: function() {
                console.log('Loading get data');
            },
            success: function(response) {
                mainData = response;

                renderData(response);
            },
            error: function(xhr) {
                console.log(xhr.responseText);
            }
        });
    }

    function renderData(data) {
        html = '';
        let html_option = '';
        let html_alarm_history = '';
        let total_device = Object.keys(data.result_data).length;
        let total_device_hidup = 0,
            count_danger = 0,
            count_warning = 0,
            count_normal = 0;
        var data_area = {};

        Object.entries(data.result_data)
            .sort((a, b) => {
                const getAreaNumber = (key) => {
                    const match = key.match(/area\s*(\d+)/i);
                    return match ? parseInt(match[1], 10) : Number.MAX_SAFE_INTEGER;
                };

                return getAreaNumber(a[0]) - getAreaNumber(b[0]);
            })
            .forEach(([key, value]) => {
                const humidityList = value.humidity || [];

                if (humidityList.length > 0) {
                    total_device_hidup++;
                }

                // Destructuring array hasil split agar langsung jadi variabel area & id
                const [area, id] = key.split(" | ");
                device_id.push(id);

                // Render status area & option
                data_area = renderStatusArea(value, id, area);
                html_option += `<option value="${key}">${area}</option>`;

                // Alarm history menggunakan forEach bawaan JS
                humidityList.forEach((value_humidity, index) => {
                    let humi = '';
                    let color_humi = '';
                    let desc = '';

                    // Menyederhanakan struktur IF-ELSE agar tidak redundan
                    if (value_humidity < lcl) {
                        humi = 'LOW';
                        color_humi = 'danger';
                        desc =
                            `Sangat kering di bawah ${lcl}%`; // Menggunakan variabel lcl, bukan hardcoded 50%
                        count_danger++;
                    } else if (value_humidity > usl) {
                        humi = 'VERY HIGH';
                        color_humi = 'danger';
                        desc = `PERHATIAN! Area basah di atas ${usl}%`;
                        count_danger++;
                    } else if (value_humidity > ucl) {
                        humi = 'HIGH';
                        color_humi = 'warning';
                        desc = `Basah di atas ${ucl}% tapi di bawah ${usl}%`;
                        count_warning++;
                    } else {
                        count_normal++;
                        return; // Jika normal, langsung skip sintaks HTML di bawah
                    }

                    // Template string HTML (Hanya dieksekusi jika statusnya LOW/HIGH/VERY HIGH)
                    html_alarm_history += `
                        <tr>
                            <td class="ps-3 text-muted">${value.created_at[index]}</td>
                            <td class="fw-semibold">${area}</td>
                            <td>Humidity</td>
                            <td>
                                <span class="badge bg-${color_humi}-subtle text-${color_humi} border border-${color_humi}-subtle px-2 py-1">
                                    <i class="bi bi-thermometer-high"></i> ${humi}
                                </span>
                            </td>
                            <td class="fw-semibold">${value_humidity}%</td>
                            <td><span class="text-${color_humi}">●</span> ${desc}</td>
                        </tr>
                    `;
                });
            });
        if (html_alarm_history != '') {
            $('#tbody_alarm_history').html(html_alarm_history);
        } else {
            $('#tbody_alarm_history').html(`
            <tr>
                                        <td colspan="6" class="text-center">Tidak ada keadaan darurat</td>
                                    </tr>
            `);
        }

        renderHeaderCard(data, data_area, total_device_hidup);

        //card status area
        $('#card-status-area').html(data_area.html);

        // 2 chart detail
        //----//option area
        $('.area_option').html(html_option);
        $('#option_area_2 option:eq(1)').prop('selected', true).trigger('change');

        var option_1 = $('#option_area_1').val();
        renderChartDetail(option_1, "1");
        //==option 2 udah otomatis berubah karna sudah di setting $('#option_area_2 option:eq(1)').prop('selected', true).trigger('change');

        //-----nyalakan donut chart
        createDonutChart(count_danger, count_warning, count_normal);

    }

    function renderStatusArea(value, id, area) {
        var lastTemperature = value.temperature.at(-1);
        var lastHumidity = value.humidity.at(-1);
        var color = '';
        var text_alert = '';
        if (lastHumidity > usl || lastHumidity < lcl) {
            color = 'danger';
            text_alert = 'Critical';
            val_danger = val_danger + 1;
        } else if (lastHumidity < usl && lastHumidity > ucl) {
            color = 'warning';
            text_alert = 'Warning';
            val_warning = val_warning + 1;
        } else {
            color = 'success';
            text_alert = 'Normal';
            val_success = val_success + 1;
        }
        html += `
                    <div id=${id}
                        class="d-flex align-items-center justify-content-between p-2 border-start border-${color} border-4 bg-${color} bg-opacity-10 rounded-end mb-2 small">
                        <span class="fw-bold">${area}</span>
                        <div class="d-flex align-items-center gap-3">
                            <span>${lastTemperature}</span>
                            <span>${lastHumidity}</span>
                            <span
                                class="badge bg-${color}-subtle text-${color} border border-${color}-subtle text-uppercase px-2 py-1">${text_alert}</span>
                        </div>
                    </div>
                `;
        var data = {
            html: html,
            val_danger: val_danger,
            val_warning: val_warning,
            val_success: val_success
        };
        return data;
    }

    function renderHeaderCard(data, data_area, total_device_hidup) {
        //card atas baris pertama
        var percent_danger = (data_area.val_danger / total_device) * 100;
        $('#val_danger').html(data_area.val_danger);
        $('#percent_danger').html(percent_danger.toFixed(2) + "% dari total area");

        var percent_warning = (data_area.val_warning / total_device) * 100;
        $('#val_warning').html(data_area.val_warning);
        $('#percent_warning').html(percent_warning.toFixed(2) + "% dari total area");

        var percent_success = (data_area.val_success / total_device) * 100;
        $('#val_success').html(data_area.val_success);
        $('#percent_success').html(percent_success.toFixed(2) + "% dari total area");

        $('#val_avg_temperature').html(data.temperature.value);
        $('#status_avg_temperature').html(data.temperature.status);

        $('#val_avg_humidty').html(data.humidity.value);
        $('#status_avg_humidty').html(data.humidity.status);

        $('#val_total_device').html(total_device);
        let status_total_device = '';
        if (total_device == total_device_hidup) {
            status_total_device = 'Semua Aktif';
        } else {
            var total_mati = total_device - total_device_hidup;
            status_total_device = total_mati + ' Device Tidak Aktif';
        }
        $('#status_total_device').html(status_total_device);
    }

    function renderChartDetail(key, id) {
        $("#data_" + id + "_header").html(key);

        var data = mainData.result_data[key];

        // console.log("RESULT DATA:", mainData);

        createTrendChart("chartdiv_data_" + id, data);

        nyalainRadar(data, id);
    }

    function nyalainRadar(data, no) {
        console.log(data);

        var building = $('#select_building_data option:selected').text();
        var area = $('#select_area_data option:selected').text();

        $('#mappingImage').attr(
            'src',
            `{{ asset('images/mapping_v2/${building}/${area}.png') }}`
        );

        const [cx, cy, r] = data.coordinates.split(',').map(Number);

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

    //==============chart==================//
    // ==========================================
    // 1. HELPER & CONFIG UNTUK TREND CHART (LINE)
    // ==========================================
    function ubahCardChart(divId, data_humidity, data_time) {
        arr_id = divId.split("_");
        id = arr_id[2];

        // 2. Mencari Nilai Maksimum & Index-nya
        const maxVal = Math.max(...data_humidity);
        const maxIndex = data_humidity.indexOf(maxVal); // Ambil index max
        const maxTime = data_time[maxIndex]; // Ambil waktu berdasarkan index max
        $('#max_data_' + id).html(maxVal + "%");
        $('#max_time_' + id).html(maxTime);

        // 3. Mencari Nilai Minimum & Index-nya
        const minVal = Math.min(...data_humidity);
        const minIndex = data_humidity.indexOf(minVal); // Ambil index min
        const minTime = data_time[minIndex]; // Ambil waktu berdasarkan index min
        $('#min_data_' + id).html(minVal + "%");
        $('#min_time_' + id).html(minTime);

        // 4. Mencari Nilai Rata-rata (Average)
        const total = data_humidity.reduce((sum, value) => sum + value, 0);
        const avgVal = total / data_humidity.length;
        $('#avg_data_' + id).html(avgVal.toFixed(2) + "%");

        // 5. Mencari Data Terakhir (Latest) & Index-nya
        const latestIndex = data_humidity.length - 1; // Index terakhir pastilah panjang data - 1
        const latestVal = data_humidity[latestIndex];
        const latestTime = data_time[latestIndex]; // Ambil waktu terakhir
        $('#latest_data_' + id).html(latestVal + "%");
        $('#latest_time_' + id).html(latestTime);
    }

    function createTrendChart(divId, dataValues) {

        // Ambil data untuk x-axis (waktu) dan y-axis (nilai)
        var categories = dataValues.created_at;
        var humidityData = dataValues.humidity;
        ubahCardChart(divId, humidityData, categories);

        var temperatureData = dataValues.temperature;

        var options = {
            chart: {
                type: 'line',
                height: 280,
                sparkline: {
                    enabled: false
                },
                toolbar: {
                    show: false
                },
                animations: {
                    enabled: true
                }
            },
            colors: ['#000000', '#198754'],
            series: [{
                name: 'Humidity',
                data: humidityData
            }, {
                name: 'Temperature',
                data: temperatureData
            }],
            stroke: {
                curve: 'smooth',
                width: 2
            },
            markers: {
                size: 4,
                colors: ['#000000', '#198754'],
                strokeWidth: 0
            },
            grid: {
                borderColor: '#f1f1f1',
                padding: {
                    right: 20,
                    left: 10
                }
            },
            xaxis: {
                categories: categories,
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                labels: {
                    style: {
                        colors: '#6c757d',
                        fontSize: '11px'
                    }
                }
            },
            yaxis: {
                min: 20,
                max: 85,
                tickAmount: 5,
                labels: {
                    style: {
                        colors: '#6c757d',
                        fontSize: '11px'
                    }
                }
            },
            annotations: {
                position: 'back',
                yaxis: [{
                        y: usl,
                        borderColor: '#dc3545',
                        strokeDashArray: 0,
                        borderWidth: 1.5,
                        label: {
                            show: false
                        }
                    },
                    {
                        y: ucl,
                        borderColor: '#ffc107',
                        strokeDashArray: 0,
                        borderWidth: 1.5,
                        label: {
                            show: false
                        }
                    },
                    {
                        y: lcl,
                        borderColor: '#ffc107',
                        strokeDashArray: 0,
                        borderWidth: 1.5,
                        label: {
                            show: false
                        }
                    }
                ]
            },
            // SETTING TOOLTIP KUSTOM DI SINI
            tooltip: {
                shared: true,
                intersect: false,
                custom: function({
                    series,
                    seriesIndex,
                    dataPointIndex,
                    w
                }) {

                    // MENGAMBIL DATA LANGSUNG DARI VARIABEL UTAMA
                    var datetime = (categories && categories[dataPointIndex]) ? categories[
                        dataPointIndex] : '-';

                    var humidity = '-';
                    var temperature = '-';

                    if (series && series[0] && series[0][dataPointIndex] !== undefined) {
                        humidity = series[0][dataPointIndex];
                    }
                    if (series && series[1] && series[1][dataPointIndex] !== undefined) {
                        temperature = series[1][dataPointIndex];
                    }

                    // Render HTML
                    return '<div style="padding: 10px; font-size: 11px; line-height: 1.5; background: #fff; border: 1px solid #e0e0e0; border-radius: 5px; box-shadow: 2px 2px 6px rgba(0,0,0,0.1);">' +
                        '<div style="font-weight: bold; margin-bottom: 5px; color: #333;">' +
                        datetime + '</div>' +
                        '<div><span style="color: #000000; font-size: 14px; vertical-align: middle;">●</span> Humidity: <b>' +
                        humidity + '%</b></div>' +
                        '<div style="margin-bottom: 4px;"><span style="color: #198754; font-size: 14px; vertical-align: middle;">●</span> Temperature: <b>' +
                        temperature + '°C</b></div>' +
                        '<div style="border-top: 1px dashed #ddd; margin: 4px 0;"></div>' +
                        '<div style="color: #dc3545;">USL: <b>' + (typeof usl !== 'undefined' ?
                            usl : '-') + '%</b></div>' +
                        '<div style="color: #bfa100;">UCL: <b>' + (typeof ucl !== 'undefined' ?
                            ucl : '-') + '%</b></div>' +
                        '<div style="color: #bfa100;">LCL: <b>' + (typeof lcl !== 'undefined' ?
                            lcl : '-') + '%</b></div>' +
                        '</div>';
                }
            }
        };

        // ==================== LOGIKA REFRESH/RESET CHART DI SINI ====================

        // 1. Cek apakah divId ini sudah memiliki chart yang aktif sebelumnya
        if (divId in active_charts) {
            // Hancurkan instance ApexCharts yang lama agar tidak menumpuk
            active_charts[divId].destroy();
        }

        // 2. Buat instance chart baru
        var chart = new ApexCharts(document.querySelector("#" + divId), options);
        chart.render();

        // 3. Simpan instance chart yang baru ke dalam objek global active_charts
        active_charts[divId] = chart;

        // ============================================================================
    }

    // ==========================================
    // 3. CONFIG DONUT CHART (DISTRIBUSI STATUS)
    // ==========================================
    function createDonutChart(danger, warning, normal) {

        // hapus chart sebelumnya
        if (donutChart) {
            donutChart.destroy();
        }

        var donutOptions = {
            chart: {
                type: 'donut',
                height: 190
            },
            series: [normal, warning, danger],
            labels: ['Normal', 'Warning', 'Critical'],
            colors: ['#198754', '#ffc107', '#dc3545'],
            stroke: {
                width: 2
            },
            dataLabels: {
                enabled: false
            },
            legend: {
                show: true,
                position: 'right',
                verticalAlign: 'middle',
                fontSize: '11px',
                fontFamily: 'inherit',
                formatter: function(seriesName, opts) {

                    var val = opts.w.globals.series[opts.seriesIndex];
                    var total = opts.w.globals.series.reduce((a, b) => a + b, 0);

                    var percent = total > 0 ?
                        ((val / total) * 100).toFixed(1) :
                        0;

                    return `${seriesName}: <b>${val} (${percent}%)</b>`;
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '11px',
                                color: '#777777',
                                offsetY: 15
                            },
                            value: {
                                show: true,
                                fontSize: '18px',
                                fontWeight: 'bold',
                                color: '#333333',
                                offsetY: -20,
                                formatter: function(val) {
                                    return val;
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total Record',
                                color: '#777777',
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            }
        };


        donutChart = new ApexCharts(
            document.querySelector("#chartdiv_donut"),
            donutOptions
        );

        donutChart.render();
    }
</script>
