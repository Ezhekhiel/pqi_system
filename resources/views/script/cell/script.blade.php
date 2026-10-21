<!-- apexcharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
    integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8=" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Moment.js (WAJIB ADA) -->
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script>
    $(document).ready(function() {
        main();
        // Event handler untuk pencarian realtime
        // Menggunakan 'input' agar pencarian langsung merespons setiap ketikan huruf
        $('#search-input').on('input change', function() {
            let keyword = $(this).val().toLowerCase().trim();
            filterAndRenderTable(keyword);
        });
        // 1. Saat Input DI-FOKUSKAN (Klik / Tab)
        $(document).on('focus', '.update_data', function() {
            let input = $(this);

            // Simpan nilai lama di data-attribute agar bisa dikembalikan jika batal
            input.data('old-value', input.val());

            // Kosongkan isi input
            input.val('');
        });

        // 2. Saat Input KEHILANGAN FOKUS (Klik di Luar / Tab Keluar)
        $(document).on('blur', '.update_data', function() {
            let input = $(this);
            let oldValue = input.data('old-value');
            let newValue = input.val().trim();

            // Jika input kosong atau nilainya sama dengan nilai lama (salah klik / tidak diisi)
            if (newValue === '' || newValue === oldValue) {
                // Kembalikan nilai lama
                input.val(oldValue);
            } else {
                // Ada perubahan nilai baru -> Jalankan proses update
                let id = input.data('id');
                let fieldName = input.attr('name');

                console.log(`Update ID ${id} | Field: ${fieldName} | Nilai Baru: ${newValue}`);

                // Panggil fungsi update AJAX Anda di sini jika ada
                // updateSingleField(id, fieldName, newValue);
            }
        });
        // Event delegation untuk menangkap perubahan input secara otomatis
        $(document).on('change', '.update_data', function() {
            let inputElement = $(this);

            // 1. Ambil ID dari atribut data-id (atau dari ID <tr> terdekat)
            let id = inputElement.data('id'); // alternatif: inputElement.closest('tr').attr('id')

            // 2. Ambil nama kolom/field (sesuai atribut name="...")
            let fieldName = inputElement.attr('name');

            // 3. Ambil nilai baru dari input
            let newValue = inputElement.val();

            console.log("ID Row:", id);
            console.log("Field yang diubah:", fieldName);
            console.log("Nilai Baru:", newValue);

            // Contoh: Kirim update otomatis ke server lewat AJAX
            // Tampilkan Modal Konfirmasi
            Swal.fire({
                title: 'Konfirmasi Perubahan',
                html: `Apakah Anda yakin ingin mengubah <b>${fieldName}</b> menjadi <b>"${newValue}"</b>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Update!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Jalankan update jika user klik 'Ya'
                    updateSingleField(id, fieldName, newValue);
                } else {
                    // Kembalikan ke nilai lama jika user klik 'Batal'
                    input.val(oldValue);
                }
            });
        });
    });

    function main() {
        $.ajax({
            url: "{{ route('cell.main') }}",
            method: "GET",
            dataType: 'json',
            beforeSend: function() {
                // Tampilkan loading saat request berjalan
                $('#table-body').html(`
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <span class="ms-2">Mengambil data...</span>
                        </td>
                    </tr>
                `);
            },
            success: function(response) {
                // Jika response berupa object Laravel pagination/response JSON standard (misal: response.data)
                let rawData = typeof response === 'string' ? JSON.parse(response) : response;

                masterData = Array.isArray(rawData) ? rawData : (rawData.data ?? []);

                // Render awal seluruh data
                renderTable(masterData);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", error);
                $('#table-body').html(`
                    <tr>
                        <td colspan="6" class="text-center text-danger py-4">
                            Gagal memuat data dari server.
                        </td>
                    </tr>
                `);
            }
        });
    }
    // 2. Function Filter Data berdasarkan Keyword Search
    function filterAndRenderTable(keyword) {
        if (!keyword) {
            // Jika input kosong, tampilkan kembali seluruh data master
            renderTable(masterData);
            return;
        }

        let searchKey = keyword.toString().toLowerCase().trim();

        // Filter array masterData berdasarkan properti 'cell'
        let filteredData = masterData.filter(function(item) {
            let cell = (item.cell ?? '').toString().toLowerCase();

            // Mengembalikan true jika nilai 'cell' mengandung kata kunci pencarian
            return cell.includes(searchKey);
        });

        // Render ulang tabel dengan data hasil filter
        renderTable(filteredData);
    }

    function renderTable(data) {
        var html = $('#main_table');
        html.empty();

        if (!data || data.length === 0) {
            html.append(`
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    Tidak ada data yang tersedia.
                </td>
            </tr>
        `);
            return;
        }

        let rowsHtml = '';
        $.each(data, function(index, item) {
            let id = item.id ?? '-';
            let cell = item.cell ?? '';
            let cell_lama = item.cell_lama ?? '';
            let eolr = item.eolr ?? '';
            let mp_direct = item.mp_direct ?? 0;
            let dc_area = item.dc_area ?? 0;

            rowsHtml += `
            <tr id="row-${id}">
                <td>${index + 1}</td>
                <td><input type="text" name="cell" data-id="${id}" class="form-control update_data text-center" value="${cell}"></td>
                <td><input type="text" name="cell_lama" data-id="${id}" class="form-control update_data text-center" value="${cell_lama}"></td>
                <td><input type="text" name="eolr" data-id="${id}" class="form-control update_data text-center" value="${eolr}"></td>
                <td><input type="text" name="mp_direct" data-id="${id}" class="form-control update_data text-center" value="${mp_direct}"></td>
                <td><input type="text" name="dc_area" data-id="${id}" class="form-control update_data text-center" value="${dc_area}"></td>
            </tr>
        `;
        });

        html.html(rowsHtml);
    }

    // Function opsional untuk kirim data ke Laravel Controller
    function updateSingleField(id, field, value) {
        $.ajax({
            url: "{{ route('cell.update') }}",
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'), // CSRF token Laravel
                id: id,
                field: field,
                value: value
            },
            success: function(data) {
                createAlert('', data.alert, data.text, data.color, true, true,
                    'pageMessages');
                main();
            },
            error: function(xhr) {
                console.error('Gagal update data:', xhr);
            }
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
            "class": alertClasses.join(" ") // you need to quote "class" since it's a reserved keyword
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
</script>
