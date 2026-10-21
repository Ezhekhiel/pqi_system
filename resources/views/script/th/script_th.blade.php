<!-- apexcharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
    integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8=" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Moment.js (WAJIB ADA) -->
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

<!-- Daterangepicker -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/image-map-resizer/1.0.10/js/imageMapResizer.min.js"></script>
<script>
    function exportExcelData() {
        var building = $('#select_building_data').val();
        var area = $('#select_area_data').val();
        var dateRange = $('#daterange').val();
        let url = `/TH/data_iot/download?area=${area}&range_date=${dateRange}&building=${building}`;
        window.open(url, '_blank'); // buka tab baru
    }
</script>
<script>
    let charts = {};
    let dataCharts = {}; // Data IoT
    let chartOptions = {};
    let zoomChart = null;
    let activeZoomDevice = null;
    let subscribedChannels = {};
    $(document).ready(function() {
        var building = '';
        $('#select_building').on('change', function(event) {
            $.ajax({
                url: "{{ route('TH.Dashboard.change_building') }}",
                method: "get",
                data: {
                    building: $(this).val()
                },
                dataType: 'JSON',
                success: function(data) {
                    $('#select_area').html(data.list_area)
                }
            });
        });
        $('#select_area').on('change', function(event) {
            var building = $('#select_building').val();
            var area_value = $(this).val();
            $.ajax({
                url: "{{ route('TH.Dashboard.change_area') }}",
                method: "get",
                data: {
                    building: building,
                    area: $(this).val()
                },
                dataType: 'JSON',
                success: function(data) {

                    let container = $('#main_data_card');

                    Object.keys(charts).forEach(id => {
                        charts[id].destroy();
                    });

                    charts = {};
                    container.empty();
                    let totalData = Object.keys(data).length;
                    ubahStyleGridDynamic(totalData);

                    Object.keys(data)
                        .sort(function(a, b) {

                            let areaA = parseInt(a.match(/\d+/)[0]);
                            let areaB = parseInt(b.match(/\d+/)[0]);

                            return areaA - areaB;

                        })
                        .forEach(function(index) {

                            let parts = index.split(" | ");
                            let area = parts[0].trim();
                            let id_device = parts[1].trim();

                            let chartId = `chart-${id_device}`;

                            container.append(`
                                        <div class="card shadow-sm chart-card"
                                            data-device="${id_device}"
                                            style="cursor:pointer;">
                                            <div class="card-header fw-bold">
                                                Device ${area}
                                            </div>
                                            <div class="card-body">
                                                <div id="${chartId}" style="min-height: 300px;"></div>
                                            </div>
                                        </div>
                                    `);

                            renderChart(chartId, data[index], area_value);
                            listenDevice(id_device, area_value);

                        });
                }
            });
        });

        //tab data
        $('#select_building_data').on('change', function(event) {
            $.ajax({
                url: "{{ route('TH.Dashboard.change_building') }}",
                method: "get",
                data: {
                    building: $(this).val()
                },
                dataType: 'JSON',
                success: function(data) {
                    $('#select_area_data').html(data.list_area)
                }
            });
        });
        $('#select_area_data').on('change', function(event) {
            if ($('#dataHourFrom').val() != "" && $("#dataHourTo").val() != "") {
                getDataIoT();
            }
        });
        $('#dataHourFrom').on('change', function(event) {
            $('#dataHourTo').val("");
            if ($('#dataHourTo').val() != "" && $("#select_area_data").val() != "") {
                getDataIoT();
            }
        });
        $('#dataHourTo').on('change', function(event) {
            if ($('#dataHourFrom').val() != "" && $("#select_area_data").val() != "") {
                getDataIoT();
            }
        });
        $(document).on('click', '.chart-card', function() {

            let device = $(this).data('device');
            let title = $(this).find('.card-header').text().trim();

            openZoomChart(device, title);

        });


        $(document).on('click', '.chart-card-detail', function() {

            let device = "data-" + $(this).data('device');
            let title = $(this).find('.card-header').text().trim();

            openZoomChart(device, title);

        });
        $('#modalChart').on('hidden.bs.modal', function() {

            clearTimeout(zoomTimeout);

            activeZoomDevice = null;

            if (zoomChart) {
                zoomChart.destroy();
                zoomChart = null;
            }

            $("#chartZoom").empty();

        });
        $('.preview-image').on('click', function() {
            var building = $('#select_building').val();
            var area = $('#select_area').val();

            if (!building || !area) {
                createAlert('', 'Failed!', 'Pilih Option Building dan Area Dulu', 'danger', true, true,
                    'pageMessages');
                return false;
            }

            building = $('#select_building option:selected').text();
            area = $('#select_area option:selected').text();

            // Set image source
            $('#image_modal')
                .attr('src', `/images/maping/${building}/${area}/mapping.png`)
                .removeClass('d-none');

            // ✅ Tambahkan class custom untuk centering
            var myModal = new bootstrap.Modal(document.getElementById('modal_map'), {
                centered: true, // Ini kunci utamanya!
                keyboard: true // Optional: bisa close pake ESC
            });

            myModal.show();
        });

        function getDataIoT() {
            var building = $('#select_building_data').val();
            // var dateRange = $('#daterange').val();
            var date = $('#dateBiasa').val();
            var dataHourFrom = $('#dataHourFrom').val();
            var dataHourTo = $('#dataHourTo').val();
            var hourRange = "";
            if (dataHourFrom != "" && dataHourTo != "") {
                hourRange = dataHourFrom + " - " + dataHourTo;
            }
            var area_value = $("#select_area_data").val();

            $.ajax({
                url: "{{ route('TH.Dashboard.change_area_data') }}",
                method: "get",
                data: {
                    building: building,
                    area: area_value,
                    date: date,
                    hourRange: hourRange
                },
                dataType: 'JSON',
                success: function(data) {
                    let totalData = Object.keys(data).length;
                    ubahStyleGridDynamic(totalData);
                    let container = $('#main_data_iot');

                    // 🔥 Destroy chart lama supaya tidak memory leak
                    Object.keys(dataCharts).forEach(id => {
                        if (dataCharts[id]) {
                            dataCharts[id].destroy();
                        }
                    });

                    dataCharts = {};
                    container.empty();

                    // 🔥 SORT DULU berdasarkan nomor area
                    let sortedEntries = Object.entries(data).sort(function(a, b) {

                        let areaA = parseInt(a[0].split(" | ")[0].replace("Area ",
                            ""));
                        let areaB = parseInt(b[0].split(" | ")[0].replace("Area ",
                            ""));

                        return areaA - areaB;
                    });

                    // 🔥 LOOP HASIL SORT
                    sortedEntries.forEach(function(entry) {

                        let index = entry[0]; // "Area 1 | 38810010"
                        let value = entry[1]; // isi data

                        let parts = index.split(" | ");
                        let area = parts[0].trim();
                        let id_device = parts[1].trim();

                        let chartId = `data-chart-${id_device}`;

                        // buat card + div chart
                        container.append(`
                                <div class="card shadow-sm chart-card-detail" data-device="${id_device}" style="cursor:pointer;">
                                    <div class="card-header fw-bold">
                                        Device ${area}
                                    </div>
                                    <div class="card-body">
                                        <div id="${chartId}" style="min-height: 300px;"></div>
                                    </div>
                                </div>
                            `);

                        renderChartData(chartId, value, area_value);
                    });

                }
            });
        }

        function ubahStyleGridDynamic(totalData) {
            let repeatValue = totalData > 4 ? 5 : 2;

            $('.dynamic-grid-humidity').css(
                'grid-template-columns',
                'repeat(' + repeatValue + ', 1fr)'
            );
        }

        function renderData(arr_data) {
            let target = document.getElementById('main_data_card');
            target.classList.add("my-4");
            // Loop setiap ID (0014, 0016, dst)
            let allCardsHtml = '';
            Object.keys(arr_data).forEach(function(id) {
                let item = arr_data[id];

                allCardsHtml += `
                    <div class="card">
                        <div class="card-header flip-header" id="header-${id}">
                            <h3 class="card-title">${item.proses}</h3>
                        </div>

                        <div class="card-body">

                            <!-- FLIP HANYA UNTUK CHART -->
                            <div class="flip-container chart-flip" style="height: 15vh;">
                                <div class="flip-card">

                                    <!-- FRONT = LINE CHART -->
                                    <div class="flip-front">
                                        <div class="radial-wrapper" style="height: 100%;">
                                            <div id="chart-${id}" style="min-height: 180px;"></div>
                                        </div>
                                    </div>

                                    <!-- BACK = RADIAL CHART -->
                                    <div class="flip-back">
                                        <div class="radial-wrapper" style="height: 100%;">
                                            <div id="radial-${id}" style="min-height: 180px;"></div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <!-- END FLIP -->

                            <!-- TABEL TETAP -->
                           <button id="toggleTableBtn-${id}" class="btn btn-outline-secondary btn-sm toggleTableBtn">
                                <i id="toggleIcon-${id}" class="bi bi-chevron-down"></i> Show Data
                            </button>
                            <div class="m-2" id="tableWrapper-${id}" style="display:none;">
                                <div class="max-h-56 overflow-y-auto">
                                    <table class="table text-center text-sm">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Point</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody class="tbody-${id}">
                                            ${item.table}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                `;
            });
            target.innerHTML = allCardsHtml;
            requestAnimationFrame(() => {
                Object.keys(arr_data).forEach(id => {
                    let item = arr_data[id];
                    if (arr_limit[id] === undefined) {
                        arr_limit[id] = {
                            min: Number(item.min),
                            max: Number(item.max)
                        };
                    }
                    if (item.data.length > 0) {
                        const chartInstance = lineChartRender(item.data, id, item.remark, item
                            .unit, item.ket_unit);
                        if (chartInstance) {
                            renderedCharts[id] = chartInstance;
                        }

                    }
                    // --- render radial chart ---
                    radialBarRender(item.performa, id, item.remark);
                });
            });
        }

        function createAlert(title, summary, details, severity, dismissible, autoDismiss, appendToId) {
            var iconMap = {
                info: "fa fa-info-circle",
                success: "fa fa-thumbs-up",
                warning: "fa fa-exclamation-triangle",
                danger: "fa ffa fa-exclamation-circle"
            };

            var iconAdded = false;

            var alertClasses = ["alert", "animated", "flipInX"];
            alertClasses.push("alert-" + severity.toLowerCase());

            if (dismissible) {
                alertClasses.push("alert-dismissible");
            }

            var msgIcon = $("<i />", {
                "class": iconMap[severity] // you need to quote "class" since it's a reserved keyword
            });

            var msg = $("<div />", {
                "class": alertClasses.join(
                    " ") // you need to quote "class" since it's a reserved keyword
            });

            if (title) {
                var msgTitle = $("<h4 />", {
                    html: title
                }).appendTo(msg);

                if (!iconAdded) {
                    msgTitle.prepend(msgIcon);
                    iconAdded = true;
                }
            }

            if (summary) {
                var msgSummary = $("<strong />", {
                    html: summary
                }).appendTo(msg);

                if (!iconAdded) {
                    msgSummary.prepend(msgIcon);
                    iconAdded = true;
                }
            }

            if (details) {
                var msgDetails = $("<p />", {
                    html: details
                }).appendTo(msg);

                if (!iconAdded) {
                    msgDetails.prepend(msgIcon);
                    iconAdded = true;
                }
            }


            if (dismissible) {
                var msgClose = $("<span />", {
                    "class": "close", // you need to quote "class" since it's a reserved keyword
                    "data-dismiss": "alert",
                    html: "<i class='fa fa-times-circle'></i>"
                }).appendTo(msg);
            }

            $('#' + appendToId).prepend(msg);

            if (autoDismiss) {
                setTimeout(function() {
                    msg.addClass("flipOutX");
                    setTimeout(function() {
                        msg.remove();
                    }, 1000);
                }, 5000);
            }
        }
    });



    function renderChart(chartId, data, option) {
        let id_device = chartId.replace('chart-', '');
        let humidityUCL = 55;
        let humidityLCL = 42;
        let humidityUSL = 60;
        if (option == 8 || option == 7) {
            humidityUCL = 65;
            humidityLCL = 55;
            humidityUSL = 70;
        }

        let options = {
            chart: {
                type: 'line',
                height: 400,
                zoom: {
                    enabled: false
                }
            },

            series: [{
                    name: 'Humidity',
                    data: data.humidity
                },
                {
                    name: 'Humidity UCL',
                    data: Array(data.humidity.length).fill(humidityUCL)
                },
                {
                    name: 'Humidity LCL',
                    data: Array(data.humidity.length).fill(humidityLCL)
                },
                {
                    name: 'Humidity USL',
                    data: Array(data.humidity.length).fill(humidityUSL)
                },
                {
                    name: 'Temperature',
                    data: data.temperature
                },
            ],

            colors: [
                "#000000", // Humidity → HITAM
                "#f1c40f", // Humidity ucl (55) → KUNING
                "#f1c40f", // Humidity lcl (42) → KUNING
                "#e74c3c", // Humidity Standard (60) → MERAH
                "#047a04", // Temperature
                // "#e74c3c", // Temp Upper
                // "#e74c3c" // Temp Lower
            ],

            annotations: {
                yaxis: [
                    // {
                    //     // 🔥 Temp Range
                    //     y: 20,
                    //     y2: 30,
                    //     fillColor: '#808080',
                    //     opacity: 0.15
                    // },
                    {
                        y: humidityLCL,
                        y2: humidityUCL,
                        fillColor: '#9e9e9e', // abu-abu
                        opacity: 0.25,
                        borderWidth: 0
                    }
                ]
            },

            stroke: {
                width: [3, 2, 2, 2, 3]
            },
            markers: {
                size: [4, 0, 0, 0, 4]
            },

            xaxis: {
                categories: data.created_at
            },

            yaxis: {
                min: 20,
                max: 85,
                tickAmount: 12,
                labels: {
                    formatter: function(val) {
                        return Math.round(val);
                    }
                }
            },
            legend: {
                position: 'bottom'
            }
        };
        // 🔥 Simpan option untuk kebutuhan zoom
        chartOptions[id_device] = $.extend(true, {}, options);

        // 🔥 Destroy dulu kalau sudah ada
        if (charts[id_device]) {
            charts[id_device].destroy();
        }

        let chart = new ApexCharts(
            document.querySelector(`#${chartId}`),
            $.extend(true, {}, options)
        );

        chart.render();

        charts[id_device] = chart;
    }

    function renderChartData(chartId, data, option) {

        let id_device = chartId.replace('chart-', '');

        let humidityUCL = 55;
        let humidityLCL = 42;
        let humidityUSL = 60;

        if (option == 8 || option == 7) {
            humidityUCL = 65;
            humidityLCL = 55;
            humidityUSL = 70;
        }

        let options = {
            chart: {
                type: 'line',
                height: 400,
                zoom: {
                    enabled: false
                }
            },

            series: [{
                    name: 'Humidity',
                    data: data.humidity
                },
                {
                    name: 'Humidity UCL',
                    data: Array(data.humidity.length).fill(humidityUCL)
                },
                {
                    name: 'Humidity LCL',
                    data: Array(data.humidity.length).fill(humidityLCL)
                },
                {
                    name: 'Humidity USL',
                    data: Array(data.humidity.length).fill(humidityUSL)
                },
                {
                    name: 'Temperature',
                    data: data.temperature
                },
            ],

            colors: [
                "#000000",
                "#f1c40f",
                "#f1c40f",
                "#e74c3c",
                "#047a04"
            ],

            annotations: {
                yaxis: [{
                    y: humidityLCL,
                    y2: humidityUCL,
                    fillColor: '#9e9e9e',
                    opacity: 0.25,
                    borderWidth: 0
                }]
            },

            stroke: {
                width: [3, 2, 2, 2, 3]
            },

            markers: {
                size: [4, 0, 0, 0, 4]
            },

            xaxis: {
                categories: data.created_at
            },

            yaxis: {
                min: 20,
                max: 85
            },

            legend: {
                position: 'bottom'
            }
        };

        if (dataCharts[id_device]) {
            dataCharts[id_device].destroy();
        }

        let chart = new ApexCharts(
            document.querySelector(`#${chartId}`),
            $.extend(true, {}, options)
        );

        chart.render();

        dataCharts[id_device] = chart;

    }

    function filterExtremeValue(newValue, oldArray, defaultValue = 0, maxDiff = 10) {

        let value = parseFloat(newValue);

        if (oldArray.length === 0) {
            return isNaN(value) ? defaultValue : value;
        }

        let lastValue = parseFloat(oldArray[oldArray.length - 1]);

        if (isNaN(value)) {
            return lastValue;
        }

        if (Math.abs(value - lastValue) >= maxDiff) {
            return lastValue;
        }

        return value;
    }
    let zoomTimeout = null;

    function openZoomChart(device, title) {

        activeZoomDevice = device;

        $('#modalChart .modal-title').text(title);
        $('#modalChart').modal('show');

        clearTimeout(zoomTimeout);

        zoomTimeout = setTimeout(function() {

            if (zoomChart) {
                zoomChart.destroy();
                zoomChart = null;
            }

            $("#chartZoom").empty();
            console.log("device =", device);
            console.log("charts =", charts);
            console.log("charts[device] =", charts[device]);


            let chart = charts[device];

            if (!chart) {
                chart = dataCharts[device];
            }

            if (!chart) {
                console.log(device);
                console.log(charts);
                console.log(dataCharts);
                return;
            }

            let zoomOption = $.extend(true, {}, chart.w.config);

            zoomOption.chart.height = 500;

            zoomChart = new ApexCharts(
                document.querySelector("#chartZoom"),
                zoomOption
            );

            zoomChart.render();

        }, 200);

    }

    function listenDevice(id_device, option) {
        let humidityUCL = 55;
        let humidityLCL = 42;
        let humidityUSL = 60;
        if (option == 8 || option == 7) {
            humidityUCL = 65;
            humidityLCL = 55;
            humidityUSL = 70;
        }

        if (subscribedChannels[id_device]) return;

        let channelName = 'dht22' + id_device;

        Echo.channel(channelName)
            .listen('.dht22.updated', (e) => {

                let chart = charts[id_device];

                if (!chart) return;

                let series = chart.w.config.series;
                let categories = [...chart.w.globals.categoryLabels];

                let humidityData = [...series[0].data];
                let humidityUclData = [...series[1].data];
                let humidityLclData = [...series[2].data];
                let humiditySpecData = [...series[3].data];
                let tempData = [...series[4].data];

                // FILTER VALUE
                let humidity = filterExtremeValue(
                    e.data.humidity,
                    humidityData,
                    60,
                    10
                );

                let temperature = filterExtremeValue(
                    e.data.temperature,
                    tempData,
                    30,
                    10
                );

                // PUSH DATA
                humidityData.push(humidity);
                humidityUclData.push(humidityUCL);
                humidityLclData.push(humidityLCL);
                humiditySpecData.push(humidityUSL);
                tempData.push(temperature);

                categories.push(e.data.time);

                // LIMIT DATA
                let maxData = 30;

                if (humidityData.length > maxData) {

                    humidityData.shift();
                    humidityUclData.shift();
                    humidityLclData.shift();
                    humiditySpecData.shift();
                    tempData.shift();

                    categories.shift();
                }

                // UPDATE SERIES ONLY
                chart.updateSeries([{
                        name: 'Humidity',
                        data: humidityData
                    },
                    {
                        name: 'Humidity UCL',
                        data: humidityUclData
                    },
                    {
                        name: 'Humidity LCL',
                        data: humidityLclData
                    },
                    {
                        name: 'Humidity USL',
                        data: humiditySpecData
                    },
                    {
                        name: 'Temperature',
                        data: tempData
                    },
                ], false);

                // UPDATE CATEGORY TERPISAH
                chart.updateOptions({
                    xaxis: {
                        categories: categories
                    }
                }, false, false);

                // ZOOM CHART
                if (zoomChart && activeZoomDevice == id_device) {

                    zoomChart.updateSeries([{
                            name: 'Humidity',
                            data: humidityData
                        },
                        {
                            name: 'Humidity UCL',
                            data: humidityUclData
                        },
                        {
                            name: 'Humidity LCL',
                            data: humidityLclData
                        },
                        {
                            name: 'Humidity USL',
                            data: humiditySpecData
                        },
                        {
                            name: 'Temperature',
                            data: tempData
                        },
                    ], false);

                    zoomChart.updateOptions({
                        xaxis: {
                            categories: categories
                        }
                    }, false, false);
                }
            });

        subscribedChannels[id_device] = true;
    }
</script>
