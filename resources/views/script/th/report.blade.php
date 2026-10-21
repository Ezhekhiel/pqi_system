<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<!-- Daterangepicker -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    let mainData = {};
    const usl = 60;
    const ucl = 55;
    const lcl = 42;
    let device_id = [];
    let html = '';
    let val_success = 0,
        val_warning = 0,
        val_danger = 0;
    let total_device = 0;
    document.addEventListener("DOMContentLoaded", function() {
        main();

        function main() {
            const today = new Date().toISOString().split('T')[0];

            // Buat array isi jam 01:00 sampai 24:00 secara otomatis
            const time = @json($time);
            const data = @json($data);

            mainData = data;

            renderData(data);

            let [time_from, time_to] = time.split("-");
        }
        $('#mappingImage').on('load', function() {

            let width = this.naturalWidth;
            let height = this.naturalHeight;

            $('#mappingSvg')
                .attr('viewBox', `0 0 ${width} ${height}`);

        });
    });
    window.addEventListener("load", function() {
        document.body.classList.remove("sidebar-open");
        document.body.classList.add("sidebar-collapse");
    });

    function renderData(data) {

        html = '';
        let html_option = '';
        let html_data = '';
        total_device = Object.keys(data.result_data).length;
        let total_device_hidup = 0;
        var data_area = {};
        const dataGroupHumidity = {};


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
                const [area, id] = key.split("|");
                device_id.push(id);

                // Render status area & option
                html_option += `<option value="${key}">${area}</option>`;

                // Alarm history menggunakan forEach bawaan JS
                let html_alarm_history = '';
                const groupedHumidity = {};
                const THRESHOLD = 20;

                let normalizedHumidity = [];

                humidityList.forEach((humidity, index) => {

                    if (index == 0) {
                        normalizedHumidity.push(humidity);
                        return;
                    }

                    let previous = normalizedHumidity[index - 1];

                    if (Math.abs(humidity - previous) > THRESHOLD) {
                        normalizedHumidity.push(previous);
                    } else {
                        normalizedHumidity.push(humidity);
                    }
                });

                normalizedHumidity.forEach((humidity, index) => {

                    const hour = value.created_at[index].substring(0, 2);

                    if (
                        !groupedHumidity[hour] ||
                        humidity > groupedHumidity[hour].humidity
                    ) {
                        groupedHumidity[hour] = {
                            humidity,
                            created_at: value.created_at[index]
                        };
                    }
                });

                Object.entries(groupedHumidity)
                    .sort((a, b) => parseInt(a[0]) - parseInt(b[0]))
                    .forEach(([hour, item]) => {
                        let humi = '';
                        let color_humi = '';
                        let desc = '';
                        const value_humidity = item.humidity;

                        // Menyederhanakan struktur IF-ELSE agar tidak redundan
                        if (value_humidity < lcl) {
                            humi = 'LOW';
                            color_humi = 'danger';
                        } else if (value_humidity > usl) {
                            humi = 'VERY HIGH';
                            color_humi = 'danger';
                        } else if (value_humidity > ucl) {
                            humi = 'HIGH';
                            color_humi = 'warning';
                        } else {
                            humi = 'Normal';
                            color_humi = 'success';
                        }
                        let time_created_at = item.created_at.substring(0, 2) + ":00";

                        // Template string HTML (Hanya dieksekusi jika statusnya LOW/HIGH/VERY HIGH)
                        html_alarm_history += `
                        <tr>
                            <td class="ps-3 text-muted">${time_created_at}</td>
                            <td class="fw-semibold">${area}</td>
                            <td>
                                <span class="badge bg-${color_humi}-subtle text-${color_humi} border border-${color_humi}-subtle px-2 py-1">
                                    <i class="bi bi-thermometer-high"></i> ${humi}
                                </span>
                            </td>
                            <td class="fw-semibold">${value_humidity}%</td>
                        </tr>
                    `;
                    });
                html_data += `
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card h-100 card-custom">
                            <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark text-uppercase small" style="letter-spacing: 0.5px;">Alarm
                                    History</span>
                            </div>
                            <div class="card-body p-2 overflow-y-auto flex-grow-1 w-100">
                                <table class="table table-hover align-middle mb-0 small" style="font-size: 0.85rem; font-size:15px">
                                    <thead class="table-light text-secondary text-center">
                                        <tr>
                                            <th class="ps-3">Waktu</th>
                                            <th>Area</th>
                                            <th>Status</th>
                                            <th>Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody_alarm_history" class="text-center">
                                        ${html_alarm_history}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            });

        $("#main_card").html(html_data);
    }
</script>
