<!-- CDN LIBRARIES -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

<!-- amCharts v5 Core & Modules -->
<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>

<script>
    // |||||||||||||||||||||||||||||||||||||||| VARIABLE GLOBAL ||||||||||||||||||||||||||||||||||||||||||||||||||
    /* =========================================================================
     * GLOBAL VARIABLES & STATE MANAGEMENT
     * ========================================================================= */
    let masterData = []; // Data mentah hasil fetch API
    let activeFilteredData = []; // Data aktif yang sudah melalui filter
    let currentDataCache = null; // Cache string JSON untuk mencegah re-render sia-sia
    let fetchInterval = null; // Storage instance setInterval

    // Global Instance khusus amCharts v5 (Top 3 Defect)
    let top3DefectChartRoot = null;
    let top3DefectChartSeries = null;

    const chartInstances = {};
    // |||||||||||||||||||||||||||||||||||||||| VARIABLE GLOBAL END ||||||||||||||||||||||||||||||||||||||||||||||||||


    // |||||||||||||||||||||||||||||||||||||||| HELPER ||||||||||||||||||||||||||||||||||||||||||||||||||
    function showLoading() {
        $('.chartLoader  , #tableLoader').addClass('active');
    }

    function hideLoading() {
        // Beri sedikit jeda 150ms agar animasi transisi terasa halus
        setTimeout(function() {
            $('.chartLoader, #tableLoader').removeClass('active');
        }, 150);
    }
    // |||||||||||||||||||||||||||||||||||||||| HELPER END||||||||||||||||||||||||||||||||||||||||||||||||||


    // |||||||||||||||||||||||||||||||||||||||| RENDER ALL COMPONENT DALAM 1 WAKTU ||||||||||||||||||||||||||||||||||||||||||||||||||
    /* =========================================================================
     * MAIN ORCHESTRATOR (Pusat Kendali UI & Render)
     * ========================================================================= */
    function renderAllComponents(data) {
        // 1. Render Tabel

        renderTableEfisien(data);

        // 2. Olah Data untuk masing-masing Chart
        const top3DefectNames = getTop3DefectNames(data); // Format: [{ DFT_NM: "...", count: 10 }]
        const top3Lines = getTop3DefectsByLine(data); // Format: [{ LINE_NM: "...", count: 10 }]
        const paretoData = getParetoDefectData(data);
        const stopLineData = analyzeDefectConditions(data);


        // 3. Render Chart 1: Top 3 Defect Name
        renderTop3Chart("top3ChartByName", top3DefectNames, "DFT_NM");

        // 4. Render Chart 2: Top 3 Defect by Line
        renderTop3Chart("top3ChartByLine", top3Lines, "LINE_NM");

        // 5. Render Pareto Chart
        renderParetoChart("paretoChart", paretoData); // <--- Call Pareto Chart

        // 5. Render StopLine
        renderStopLine("live_stop_line_table", stopLineData); // <--- Call Pareto Chart

        // 5. Hide Loading Overlay
        hideLoading();
    }
    // |||||||||||||||||||||||||||||||||||||||| RENDER ALL COMPONENT DALAM 1 WAKTU END ||||||||||||||||||||||||||||||||||||||||||||||||||


    // |||||||||||||||||||||||||||||||||||||||| GET DATA API ||||||||||||||||||||||||||||||||||||||||||||||||||
    /* =========================================================================
     * DATA FETCHING (AJAX Engine)
     * ========================================================================= */
    function loadDashboardData() {
        const dateInput = $('#search_date').val() || '';
        const formattedDate = dateInput.replace(/-/g, '');

        $.ajax({
            url: '/api/pqi_data/' + formattedDate,
            type: 'GET',
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                const jsonString = JSON.stringify(response);

                // Mencegah re-render jika data dari server tidak ada perubahan
                if (jsonString === currentDataCache) return;

                currentDataCache = jsonString;
                const rawData = typeof response === 'string' ? JSON.parse(response) : response;
                masterData = Array.isArray(rawData) ? rawData : (rawData.data ?? []);

                // Cek apakah user sedang melakukan pencarian saat data auto-refresh
                const currentSearch = $('#search-input').val();
                if (currentSearch) {
                    filterData(currentSearch);
                } else {
                    activeFilteredData = masterData;
                    renderAllComponents(activeFilteredData);
                }
            },
            error: function(xhr, status, error) {
                console.error("[PQI Dashboard] Gagal mengambil data via AJAX:", status, error);
            }
        });
    }
    // |||||||||||||||||||||||||||||||||||||||| GET DATA API END ||||||||||||||||||||||||||||||||||||||||||||||||||


    // |||||||||||||||||||||||||||||||||||||||| MANAGE DATA ||||||||||||||||||||||||||||||||||||||||||||||||||
    // TOP 3 DATA
    function getTop3DefectNames(dataArray) {
        if (!Array.isArray(dataArray) || dataArray.length === 0) return [];

        const counts = dataArray.reduce(function(acc, item) {
            const name = item.DFT_NM;
            if (name) acc[name] = (acc[name] || 0) + 1;
            return acc;
        }, {});

        return Object.keys(counts)
            .map(key => ({
                DFT_NM: key,
                count: counts[key]
            }))
            .sort((a, b) => b.count - a.count)
            .slice(0, 3);
    }

    function getTop3DefectsByLine(dataArray) {
        if (!Array.isArray(dataArray) || dataArray.length === 0) return [];

        // 1. Hitung frekuensi defect berdasarkan LINE_NM
        const counts = dataArray.reduce(function(acc, item) {
            const lineName = item.LINE_NM;
            if (lineName) {
                acc[lineName] = (acc[lineName] || 0) + 1;
            }
            return acc;
        }, {});

        // 2. Ubah ke bentuk Array Objek, Urutkan (Descending), dan Ambil Top 3
        return Object.keys(counts)
            .map(key => ({
                LINE_NM: key,
                count: counts[key]
            }))
            .sort((a, b) => b.count - a.count)
            .slice(0, 3);
    }
    // TOP 3 DATA END

    // PARETO DATA
    function parseHourLabel(item) {
        // 1. Prioritas utama dari TIME_CD (misal: "A006" -> "06:00")
        if (item.TIME_CD && item.TIME_CD.length >= 4) {
            const hourDigits = item.TIME_CD.substring(1, 4); // Mengambil 006
            const hourNum = parseInt(hourDigits, 10);
            if (!isNaN(hourNum)) {
                return `JAM ${String(hourNum).padStart(2, '0')}:00`;
            }
        }

        // 2. Fallback: Parse dari CREATE_DATE (misal: "2026-09-17 06:45:08")
        if (item.CREATE_DATE) {
            const dateObj = new Date(item.CREATE_DATE);
            if (!isNaN(dateObj.getTime())) {
                const hour = String(dateObj.getHours()).padStart(2, '0');
                return `JAM ${hour}:00`;
            }
        }

        return "JAM --:--";
    }

    function getParetoDefectData(dataArray) {
        if (!Array.isArray(dataArray) || dataArray.length === 0) return [];

        // 1. Group & Count berdasarkan DFT_NM
        const counts = dataArray.reduce(function(acc, item) {
            const name = item.DFT_NM || "Unknown Defect";
            acc[name] = (acc[name] || 0) + 1;
            return acc;
        }, {});

        // 2. Ubah ke Array Objek & Urutkan Descending
        const sortedData = Object.keys(counts)
            .map(key => ({
                DFT_NM: key,
                count: counts[key]
            }))
            .sort((a, b) => b.count - a.count);

        // 3. Hitung Total Defect
        const totalDefects = sortedData.reduce((sum, item) => sum + item.count, 0);

        // 4. Hitung Persentase Kumulatif (% Pareto)
        let cumulative = 0;
        return sortedData.map(item => {
            cumulative += item.count;
            const percentage = totalDefects > 0 ? (cumulative / totalDefects) * 100 : 0;
            return {
                DFT_NM: item.DFT_NM,
                count: item.count,
                pareto: parseFloat(percentage.toFixed(1)) // % Kumulatif (0 - 100%)
            };
        });
    }
    // PARETO DATA END

    //STOP LINE
    function analyzeDefectConditions(dataArray) {
        if (!Array.isArray(dataArray) || dataArray.length === 0) {
            console.warn("[Rule Check] Data kosong atau tidak valid.");
            return [];
        }

        // Helper untuk mengekstrak format JAM
        function getHourLabel(item) {
            if (item.TIME_CD && item.TIME_CD.length >= 4) {
                const h = parseInt(item.TIME_CD.substring(1, 4), 10);
                if (!isNaN(h)) return `${String(h).padStart(2, '0')}:00`;
            }
            if (item.CREATE_DATE) {
                const d = new Date(item.CREATE_DATE);
                if (!isNaN(d.getTime())) {
                    return `${String(d.getHours()).padStart(2, '0')}:00`;
                }
            }
            return "Unknown";
        }

        // 1. Grouping Data by Cell (LINE_NM) & by Hour
        const groupedData = {};
        dataArray.forEach(item => {
            const cell = item.LINE_NM || "UNKNOWN_CELL";
            const hour = getHourLabel(item);

            if (!groupedData[cell]) groupedData[cell] = {};
            if (!groupedData[cell][hour]) groupedData[cell][hour] = [];

            groupedData[cell][hour].push(item);
        });

        const combinedResults = [];
        let counter = 1;

        // 2. Loop per Cell dan per Jam
        Object.keys(groupedData).forEach(cell => {
            Object.keys(groupedData[cell]).forEach(hour => {
                const items = groupedData[cell][hour];

                // Urutkan item berdasarkan CREATE_DATE asc
                items.sort((a, b) => new Date(a.CREATE_DATE) - new Date(b.CREATE_DATE));

                const lineCapa = parseFloat(items[0].LINE_CAPA_HOUR) || 0;

                // Penampung flag agar 🚨 hanya push 1x per jenis defect di jam yang sama
                const flaggedConsecutiveDefects = new Set();

                // -----------------------------------------------------------------
                // SYARAT 3: Cek 5 kali defect yang sama BERURUTAN (Max 1 alert per dft_nm)
                // -----------------------------------------------------------------
                let streakCount = 1;
                let lastDefectName = null;

                items.forEach((item) => {
                    const currentDefect = item.DFT_NM;

                    if (currentDefect && currentDefect === lastDefectName) {
                        streakCount++;
                    } else {
                        streakCount = 1;
                        lastDefectName = currentDefect;
                    }

                    // Cek jika sudah mencapai 5x DAN belum pernah di-push sebelumnya
                    if (streakCount >= 5 && !flaggedConsecutiveDefects.has(currentDefect)) {
                        flaggedConsecutiveDefects.add(currentDefect);

                        combinedResults.push({
                            no: counter++,
                            cell: cell,
                            line_capa: lineCapa,
                            dft_nm: currentDefect,
                            hour: hour,
                            massage: `🚨 Defect "${currentDefect}" occurred 5 times IN A ROW at ${hour}`
                        });
                    }
                });

                // -----------------------------------------------------------------
                // SYARAT 4: Cek Threshold Kapasitas Jam (Max 1 alert per dft_nm)
                // -----------------------------------------------------------------
                let cekDefectThreshold = lineCapa > 120 ? 12 : Math.round(lineCapa * 0.1);

                // Hitung total kemunculan masing-masing DFT_NM di jam tersebut
                const defectCountsInHour = {};
                items.forEach(item => {
                    const dName = item.DFT_NM || "Unknown";
                    defectCountsInHour[dName] = (defectCountsInHour[dName] || 0) + 1;
                });

                // Evaluasi batas ambang/threshold
                Object.keys(defectCountsInHour).forEach(dName => {
                    const count = defectCountsInHour[dName];
                    if (count >= cekDefectThreshold && cekDefectThreshold > 0) {
                        combinedResults.push({
                            no: counter++,
                            cell: cell,
                            line_capa: lineCapa,
                            dft_nm: dName,
                            hour: hour,
                            massage: `⚠️ Defect "${dName}" reached ${count}x (Limit threshold: ${cekDefectThreshold}x out of ${lineCapa} capacity)`
                        });
                    }
                });
            });
        });

        // Re-index nomor urut hasil akhir
        combinedResults.forEach((res, idx) => res.no = idx + 1);

        // Output ke Console
        // console.group("📌 HASIL ANALISIS DEFECT (FILTERED & UNIQUE)");
        // console.log(`Total Temuan Syarat: ${combinedResults.length}`);
        // console.table(combinedResults);
        // console.groupEnd();

        return combinedResults;
    }
    //STOP LINE END
    // |||||||||||||||||||||||||||||||||||||||| MANAGE DATA END ||||||||||||||||||||||||||||||||||||||||||||||||||


    // |||||||||||||||||||||||||||||||||||||||| FILTER / FUNCTION SEARCH DATA ||||||||||||||||||||||||||||||||||||||||||||||||||
    /* =========================================================================
     * FILTERING & SEARCH LOGIC
     * ========================================================================= */
    function filterData(keyword) {
        if (!keyword) {
            activeFilteredData = masterData;
        } else {
            const searchKey = keyword.toString().toLowerCase().trim();
            activeFilteredData = masterData.filter(function(item) {
                const line_nm = (item.LINE_NM ?? '').toString().toLowerCase();
                const article = (item.ARTICLEDESC ?? '').toString().toLowerCase();
                const o_no = (item.O_NO ?? '').toString().toLowerCase();
                const category = (item.DFT_CATG ?? '').toString().toLowerCase();
                const defect_name = (item.DFT_NM ?? '').toString().toLowerCase();

                return line_nm.includes(searchKey) ||
                    article.includes(searchKey) ||
                    o_no.includes(searchKey) ||
                    category.includes(searchKey) ||
                    defect_name.includes(searchKey);
            });
        }

        renderAllComponents(activeFilteredData);
    }
    // |||||||||||||||||||||||||||||||||||||||| FILTER / FUNCTION SEARCH DATA END ||||||||||||||||||||||||||||||||||||||||||||||||||


    // |||||||||||||||||||||||||||||||||||||||| FUNCTION RENDER ||||||||||||||||||||||||||||||||||||||||||||||||||
    /* ========================================================================= RENDERER: LIVE TABLE ========================================================================= */
    function renderTableEfisien(dataList) {

        // Pastikan jika data dibungkus dalam object (misal: response.data)
        if (dataList && !Array.isArray(dataList) && Array.isArray(dataList.data)) {
            dataList = dataList.data;
        }

        if (!Array.isArray(dataList) || dataList.length === 0) {
            $('#live_table').html('<tr><td colspan="7" class="text-center">Tidak ada data ditemukan</td></tr>');
            return;
        }

        const rows = dataList.map((item, index) => `
        <tr data-id="${item.id ?? ''}">
            <td>${index + 1}</td>
            <td>${item.LINE_NM ?? ''}</td>
            <td>${item.ARTICLEDESC ?? ''}</td>
            <td>${item.O_NO ?? ''}</td>
            <td>${item.DFT_CATG ?? ''}</td>
            <td>${item.DFT_NM ?? ''}</td>
            <td>${item.CREATE_DATE ?? ''}</td>
        </tr>
    `);

        $('#live_table').html(rows.join(''));
    }
    /* ========================================================================= RENDERER: LIVE TABLE END ========================================================================= */


    /* ========================================================================= RENDERER: RENDER TOP3 DEFFECT ========================================================================= */
    function renderTop3Chart(divId, chartData, categoryField) {
        const container = document.getElementById(divId);

        if (!container) {
            console.warn(`[amCharts Error] Elemen #${divId} tidak ditemukan di HTML!`);
            return;
        }

        // Cek apakah chart untuk divId ini sudah pernah diinisialisasi
        const instance = chartInstances[divId];

        if (!instance || !instance.root || instance.root.isDisposed()) {
            initTop3DefectChart(divId, chartData, categoryField);
        } else {
            updateTop3DefectChart(divId, chartData);
        }
    }

    function initTop3DefectChart(divId, initialData, categoryField) {
        // 1. Cleanup Instance Lama di DOM ID yang sama
        am5.array.each(am5.registry.rootElements, function(root) {
            if (root.dom && root.dom.id === divId) {
                root.dispose();
            }
        });

        // 2. Inisialisasi Root amCharts & Tema
        const root = am5.Root.new(divId);
        root.setThemes([am5themes_Animated.new(root)]);

        // 3. Buat Container Chart
        const chart = root.container.children.push(
            am5xy.XYChart.new(root, {
                panX: false,
                panY: false,
                wheelX: "none",
                wheelY: "none",
                layout: root.verticalLayout
            })
        );

        // 4. Konfigurasi Y-Axis (Category Axis Dinamis)
        const yRenderer = am5xy.AxisRendererY.new(root, {
            inversed: true,
            cellStartLocation: 0.1,
            cellEndLocation: 0.9,
            paddingRight: 10
        });
        yRenderer.grid.template.set("visible", false);

        yRenderer.labels.template.setAll({
            maxWidth: 130,
            oversizedBehavior: "truncate",
            ellipsis: "...",
            tooltipText: "{category}",
            textAlign: "right"
        });

        const yAxis = chart.yAxes.push(
            am5xy.CategoryAxis.new(root, {
                categoryField: categoryField, // <--- Dinamis ("DFT_NM" atau "LINE_NM")
                renderer: yRenderer
            })
        );
        yAxis.data.setAll(initialData);

        // 5. Konfigurasi X-Axis (Value Axis)
        const xRenderer = am5xy.AxisRendererX.new(root, {
            minGridDistance: 30
        });
        xRenderer.grid.template.setAll({
            strokeDasharray: [3],
            strokeOpacity: 0.2
        });

        const xAxis = chart.xAxes.push(
            am5xy.ValueAxis.new(root, {
                min: 0,
                renderer: xRenderer
            })
        );

        // 6. Konfigurasi Series & Tooltip
        const seriesTooltip = am5.Tooltip.new(root, {
            labelText: "[bold]{categoryY}[/]\nTotal: {valueX}",
            pointerOrientation: "horizontal"
        });

        const series = chart.series.push(
            am5xy.ColumnSeries.new(root, {
                name: "Top Defects",
                xAxis: xAxis,
                yAxis: yAxis,
                valueXField: "count",
                categoryYField: categoryField, // <--- Dinamis
                tooltip: seriesTooltip
            })
        );

        // 7. Cursor & Snap
        const cursor = chart.set("cursor", am5xy.XYCursor.new(root, {
            behavior: "none",
            snapToSeries: [series]
        }));
        cursor.lineX.set("visible", false);
        cursor.lineY.set("visible", false);

        // 8. Column Styling
        series.columns.template.setAll({
            cornerRadiusTR: 8,
            cornerRadiusBR: 8,
            strokeOpacity: 0,
            height: am5.percent(60),
            interactive: true,
            tooltipText: "[bold]{categoryY}[/]\nTotal: {valueX}"
        });

        series.columns.template.adapters.add("fill", function(fill, target) {
            return chart.get("colors").getIndex(series.columns.indexOf(target));
        });

        series.columns.template.adapters.add("stroke", function(stroke, target) {
            return chart.get("colors").getIndex(series.columns.indexOf(target));
        });

        // 9. Bullets
        series.bullets.push(function() {
            return am5.Bullet.new(root, {
                locationX: 1,
                sprite: am5.Label.new(root, {
                    text: "{valueX}",
                    fill: am5.color(0x333333),
                    centerY: am5.p50,
                    centerX: am5.p0,
                    populateText: true,
                    dx: 10,
                    fontSize: 13,
                    fontWeight: "bold"
                })
            });
        });

        // 10. Load Data
        series.data.setAll(initialData);
        series.appear(1000);
        chart.appear(1000, 100);

        // Simpan instance ke Global Store berdasarkan divId
        chartInstances[divId] = {
            root: root,
            series: series
        };
    }

    function updateTop3DefectChart(divId, newData) {
        const instance = chartInstances[divId];
        if (!instance || !instance.series || !instance.root) return;

        const series = instance.series;
        const yAxis = series.get("yAxis");

        const tooltip = series.get("tooltip");
        if (tooltip) {
            tooltip.hide(0);
        }

        yAxis.data.setAll(newData);
        series.data.setAll(newData);

        yAxis.zoom(0, 1);
        series.markDirtyValues();
    }
    /* ========================================================================= RENDERER: RENDER TOP3 DEFFECT END ========================================================================= */


    /* ========================================================================= RENDERER: LIVE TABLE STOPLINE========================================================================= */
    function renderStopLine(divId, dataList) {
        $('#total_stop_line').html(dataList.length);
        if (!dataList || dataList.length === 0) {
            $('#' + divId).html('<tr><td colspan="7" class="text-center">Tidak ada data ditemukan</td></tr>');
            return;
        }

        const rows = dataList.map((item, index) => `
                                        <tr>
                                            <td>${item.no}</td>
                                            <td>${item.cell ?? ''}</td>
                                            <td>${item.dft_nm ?? ''}</td>
                                            <td>${item.hour ?? ''}</td>
                                            <td>${item.massage ?? ''}</td>
                                        </tr>
                                    `);

        $('#' + divId).html(rows.join(''));
    }
    /* ========================================================================= RENDERER: LIVE TABLE STOPLINE END========================================================================= */


    /* ========================================================================= RENDERER: PARETO ========================================================================= */
    function renderParetoChart(divId, chartData) {
        const container = document.getElementById(divId);
        if (!container) return;

        const instance = chartInstances[divId];

        if (!instance || !instance.root || instance.root.isDisposed()) {
            // --- INIT PARETO CHART ---
            am5.array.each(am5.registry.rootElements, function(root) {
                if (root.dom && root.dom.id === divId) root.dispose();
            });

            const root = am5.Root.new(divId);
            root.setThemes([am5themes_Animated.new(root)]);

            const chart = root.container.children.push(
                am5xy.XYChart.new(root, {
                    panX: false,
                    panY: false,
                    wheelX: "none",
                    wheelY: "none",
                    layout: root.verticalLayout
                })
            );

            // 1. X-Axis (Category: Defect Name)
            const xRenderer = am5xy.AxisRendererX.new(root, {
                minGridDistance: 30
            });
            xRenderer.labels.template.setAll({
                rotation: -25,
                centerY: am5.p50,
                centerX: am5.p100,
                paddingRight: 15,
                fontSize: 11
            });
            xRenderer.grid.template.set("visible", false);

            const xAxis = chart.xAxes.push(
                am5xy.CategoryAxis.new(root, {
                    categoryField: "DFT_NM",
                    renderer: xRenderer
                })
            );
            xAxis.data.setAll(chartData);

            // 2. Y-Axis Kiri (Count / Jumlah Defect)
            const yRendererLeft = am5xy.AxisRendererY.new(root, {});
            const yAxisLeft = chart.yAxes.push(
                am5xy.ValueAxis.new(root, {
                    min: 0,
                    renderer: yRendererLeft
                })
            );

            // 3. Y-Axis Kanan (Percentage / Pareto Kumulatif 0 - 100%)
            const yRendererRight = am5xy.AxisRendererY.new(root, {
                opposite: true
            });
            yRendererRight.grid.template.set("visible", false); // Matikan grid kanan agar tidak menumpuk

            const yAxisRight = chart.yAxes.push(
                am5xy.ValueAxis.new(root, {
                    min: 0,
                    max: 100,
                    strictMinMax: true,
                    numberFormat: "#'%'",
                    renderer: yRendererRight
                })
            );

            // 4. Series 1: BAR COLUMN (Jumlah Defect)
            const barTooltip = am5.Tooltip.new(root, {
                labelText: "[bold]{categoryX}[/]\nJumlah: {valueY} defect"
            });

            const columnSeries = chart.series.push(
                am5xy.ColumnSeries.new(root, {
                    name: "Defect Count",
                    xAxis: xAxis,
                    yAxis: yAxisLeft,
                    valueYField: "count",
                    categoryXField: "DFT_NM",
                    tooltip: barTooltip
                })
            );

            columnSeries.columns.template.setAll({
                cornerRadiusTL: 5,
                cornerRadiusTR: 5,
                width: am5.percent(50),
                interactive: true
            });

            // 5. Series 2: LINE CHART (% Pareto)
            const lineTooltip = am5.Tooltip.new(root, {
                labelText: "Kumulatif: {valueY}%"
            });

            const lineSeries = chart.series.push(
                am5xy.LineSeries.new(root, {
                    name: "Pareto %",
                    xAxis: xAxis,
                    yAxis: yAxisRight,
                    valueYField: "pareto",
                    categoryXField: "DFT_NM",
                    stroke: am5.color(0xe53e3e), // Warna Merah untuk Garis Pareto
                    maskBullets: false,
                    tooltip: lineTooltip
                })
            );

            lineSeries.strokes.template.setAll({
                strokeWidth: 3
            });

            // Tambahkan Titik/Bullet pada Line Chart
            lineSeries.bullets.push(function() {
                return am5.Bullet.new(root, {
                    sprite: am5.Circle.new(root, {
                        radius: 5,
                        fill: am5.color(0xe53e3e),
                        stroke: am5.color(0xffffff),
                        strokeWidth: 2
                    })
                });
            });

            // 6. Cursor & Interaction
            const cursor = chart.set("cursor", am5xy.XYCursor.new(root, {
                behavior: "none"
            }));
            cursor.lineY.set("visible", false);

            // Load Initial Data & Animation
            columnSeries.data.setAll(chartData);
            lineSeries.data.setAll(chartData);

            columnSeries.appear(1000);
            lineSeries.appear(1000);
            chart.appear(1000, 100);

            // Simpan instance
            chartInstances[divId] = {
                type: 'pareto',
                root: root,
                columnSeries: columnSeries,
                lineSeries: lineSeries,
                xAxis: xAxis
            };

        } else {
            // --- UPDATE PARETO CHART ---
            const columnSeries = instance.columnSeries;
            const lineSeries = instance.lineSeries;
            const xAxis = instance.xAxis;

            // Hide Tooltips saat update
            if (columnSeries.get("tooltip")) columnSeries.get("tooltip").hide(0);
            if (lineSeries.get("tooltip")) lineSeries.get("tooltip").hide(0);

            xAxis.data.setAll(chartData);
            columnSeries.data.setAll(chartData);
            lineSeries.data.setAll(chartData);

            columnSeries.markDirtyValues();
            lineSeries.markDirtyValues();
        }
    }
    /* ========================================================================= RENDERER: PARETO END ========================================================================= */
    // |||||||||||||||||||||||||||||||||||||||| FUNCTION RENDER END ||||||||||||||||||||||||||||||||||||||||||||||||||


    // |||||||||||||||||||||||||||||||||||||||| MAIN FUNCTION ||||||||||||||||||||||||||||||||||||||||||||||||||
    function updateFetchInterval() {
        // 1. Hentikan interval lama jika sedang berjalan
        if (fetchInterval) {
            clearInterval(fetchInterval);
        }

        // 2. Ambil nilai ms dari <select> (default ke 300000 / 5 menit jika kosong)
        const selectedTime = parseInt($('#relaoadTime').val()) || 300000;

        // 3. Jalankan interval baru dengan durasi yang dipilih
        fetchInterval = setInterval(loadDashboardData, selectedTime);
    }


    $(document).ready(function() {
        // Ambil data awal saat DOM siap
        loadDashboardData();

        // Inisialisasi interval sesuai pilihan default di <select> (5 Menit)
        updateFetchInterval();

        // Event listener ketika pengguna mengubah durasi reload
        $('#relaoadTime').on('change', function() {
            updateFetchInterval();
        });

        // Realtime Search Filter Event Listener
        $('#search-input').on('input', function() {
            filterData($(this).val());
        });

        // Reload data ketika input Tanggal diubah
        $('#search_date').on('change', function() {
            currentDataCache = null; // Reset cache untuk memaksa re-fetch
            loadDashboardData();
        });
    });

    // Cleanup Interval Memory Leak
    $(window).on('beforeunload', function() {
        if (fetchInterval) clearInterval(fetchInterval);
    });
    document.addEventListener("DOMContentLoaded", function() {
        const infoEl = document.getElementById("defectRulesInfo");
        if (infoEl) {
            new bootstrap.Tooltip(infoEl, {
                html: true,
                placement: "top",
                title: `
                <div class="text-start p-1" style="max-width: 280px;">
                    <strong class="d-block mb-1 text-warning">⚡ Defect Detection Rules</strong>
                    <ul class="mb-0 ps-3 small text-white-50">
                        <li class="mb-1 text-white">
                            <strong class="text-info">Consecutive Rule:</strong><br>
                            Same defect occurs <strong>5x sequentially</strong> within 1 hour.
                        </li>
                        <li class="text-white">
                            <strong class="text-info">Capacity Threshold Rule:</strong><br>
                            • If <strong>Capacity > 120</strong>: Triggers at <strong>12 defects</strong>/hr.<br>
                            • If <strong>Capacity ≤ 120</strong>: Triggers at <strong>10% capacity</strong> <i>(e.g., 90 capa = 9 defects)</i>.
                        </li>
                    </ul>
                </div>
            `
            });
        }
    });
    // |||||||||||||||||||||||||||||||||||||||| MAIN FUNCTION END ||||||||||||||||||||||||||||||||||||||||||||||||||
</script>
