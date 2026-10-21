<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    /* ==========================================================================
       GLOBAL VARIABLES
       ========================================================================== */
    let area_arr = [];
    let id_data = [];

    /* ==========================================================================
       INITIALIZATION & EVENT LISTENERS
       ========================================================================== */
    $(document).ready(function() {
        // Load data utama saat pertama kali halaman dimuat
        main();

        // Inisialisasi Instance Modal Bootstrap untuk Koordinat
        const modalCoordinates = bootstrap.Modal.getOrCreateInstance(
            document.getElementById('modalCoordinates')
        );

        // --- Event: Process Management ---
        $('#add_process').on('click', function(e) {
            $('#modal_add_process').modal('show');
        });

        $('#save_add_proses').on('click', function(event) {
            var proses = $('#inp_proses').val();
            $.ajax({
                url: "{{ route('manage.device_IoT.add_proses') }}",
                method: "GET",
                data: {
                    proses: proses
                },
                dataType: 'JSON',
                success: function(data) {
                    createAlert('', data.alert, data.text, data.color, true, true,
                        'pageMessages');
                    main();
                }
            });
        });

        $(document).on('change', '.unit_proses', function() {
            var unit = $(this).val();
            var id = $(this).data('id');
            $.ajax({
                url: "{{ route('manage.device_IoT.update_unit_proses') }}",
                method: "GET",
                data: {
                    id: id,
                    unit: unit
                },
                dataType: 'JSON',
                success: function(data) {
                    createAlert('', data.alert, data.text, data.color, true, true,
                        'pageMessages');
                    main();
                }
            });
        });

        // --- Event: Device Update & Selection ---
        $('#update_data_device').on('click', function(event) {
            var checkedBoxes = $('input[name="id_device[]"]:checked');
            var totalChecked = checkedBoxes.length;

            if (totalChecked === 0) {
                alert('Pilih salah satu device terlebih dahulu!');
            } else {
                id_data = [];
                checkedBoxes.each(function() {
                    id_data.push($(this).val());
                });
                $('#modalDevice').modal('show');
            }
        });

        $('#formUpdateDevice').on('submit', function(e) {
            e.preventDefault();

            if (!id_data || id_data.length === 0) {
                alert('Silakan pilih device terlebih dahulu!');
                return false;
            }

            let btn = $('#btnSimpan');
            let originalBtnText = btn.html();

            btn.html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);

            let formData = $(this).serialize() + '&id_device=' + encodeURIComponent(id_data);

            $.ajax({
                url: "{{ route('manage.device_IoT.update') }}",
                type: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    createAlert('', response.alert, response.text, response.color, true,
                        true, 'pageMessages');
                    $('#modalDevice').modal('hide');
                    main();
                },
                error: function(xhr) {
                    let errorMsg = 'Terjadi kesalahan sistem!';
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        errorMsg = '';
                        $.each(errors, function(key, val) {
                            errorMsg += val[0] + '\n';
                        });
                    } else if (xhr.responseJSON && xhr.responseJSON.text) {
                        errorMsg = xhr.responseJSON.text;
                    }
                    createAlert('', 'Gagal', errorMsg, 'danger', true, true,
                        'pageMessages');
                },
                complete: function() {
                    btn.html(originalBtnText).prop('disabled', false);
                }
            });
        });

        // --- Event: Delete Device ---
        $('#delete_data_device').on('click', function(event) {
            var id = $('input[name="id_device[]"]:checked').map(function() {
                return this.value;
            }).get();

            if (id.length === 0) {
                alert('Pilih device yang ingin dihapus!');
                return;
            }

            $.ajax({
                url: "{{ route('manage.device_IoT.delete_device_iot') }}",
                method: "GET",
                data: {
                    id: id
                },
                dataType: 'JSON',
                success: function(data) {
                    createAlert('', data.alert, data.text, data.color, true, true,
                        'pageMessages');
                    main();
                }
            });
        });

        // --- Event: Form Coordinates ---
        $('#formCoordinates').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('manage.device_IoT.coordinates') }}",
                type: "POST",
                data: {
                    id: $('#id_device').val(),
                    coordinates: $('#coordinates').val()
                },
                dataType: 'JSON',
                beforeSend: function() {
                    $('#btnSave')
                        .prop('disabled', true)
                        .html(
                            '<span class="spinner-border spinner-border-sm"></span> Saving...'
                        );
                },
                success: function(data) {
                    if (data.color == "success") {
                        createAlert('', data.alert, data.text, data.color, true, true,
                            'pageMessages');
                        modalCoordinates.hide();
                        $('#coordinates').val("");
                        main();
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message ?? 'Terjadi kesalahan');
                },
                complete: function() {
                    $('#btnSave').prop('disabled', false).html('Save');
                }
            });
        });

        // --- Event: Location & Area Datalist Filtering ---
        $('#location_modal').on('input change', function() {
            let selectedLocationText = $(this).val();

            let selectedOption = $('#location_list option').filter(function() {
                return $(this).val() === selectedLocationText;
            });


            if (selectedOption.length > 0) {
                let id_location = selectedOption.data('id');
                $('#id_location_modal').val(id_location);

                let rawAreas = area_arr[id_location] || [];

                let areaOptionsHtml = '';
                $.each(rawAreas, function(index, areaName) {
                    areaOptionsHtml += `<option data-id="${index}">${areaName}</option>`;
                });


                $('#area_list').html(areaOptionsHtml);
                $('#area_modal').val('');
                $('#id_area_modal').val('');
            } else {
                $('#id_location_modal').val('');
                $('#area_list').html('');
                $('#area_modal').val('');
                $('#id_area_modal').val('');
            }
        });

        $('#area_modal').on('input', function() {
            let val = $(this).val();
            let selectedOption = $('#area_list option').filter(function() {
                return $(this).val() === val;
            });

            if (selectedOption.length > 0) {
                $('#id_area_modal').val(selectedOption.attr('data-id'));
            } else {
                $('#id_area_modal').val('');
            }
        });
    });

    /* ==========================================================================
       CORE / DATA FETCHING FUNCTIONS
       ========================================================================== */

    /**
     * Mengambil data utama dari server dan memperbarui seluruh UI
     */
    function main() {
        $.ajax({
            url: "{{ route('manage.device_IoT.main') }}",
            method: "GET",
            dataType: "JSON",
            success: function(res) {
                renderTableAreaDevice(res.area_iot);
                renderTableArea(res.bagian);
                renderProcessOptionDanProcess(res.processes);
                renderLocationOption(res.location_map);
                area_arr = res.area_arr;
            },
            error: function(xhr, status, error) {
                console.error("Gagal memuat data:", error);
            }
        });
    }

    /* ==========================================================================
       UI RENDER FUNCTIONS
       ========================================================================== */

    /**
     * Render tabel list Device IoT
     * @param {Array} data
     */
    function renderTableAreaDevice(data) {
        let htmlDevice = '';
        (data || []).forEach(item => {
            htmlDevice += `
                <tr>
                    <td><input type="checkbox" value="${item.id}" class="radioCheckbox" aria-label="area-label" name="id_device[]"></td>
                    <td>${escapeHtml(item.location_data?.location)}</td>
                    <td>${escapeHtml(item.area_data?.area)}</td>
                    <td>${escapeHtml(item.bagian_data_iot?.bagian)}</td>
                    <td>${escapeHtml(item.id_device)}</td>
                    <td>${escapeHtml(item.proses_data?.proses)}</td>
                    <td>${escapeHtml(item.remark)}</td>
                    <td onclick="openModalCoordinates('${item.id}')" style="cursor:pointer">${escapeHtml(item.coordinate)}</td>
                </tr>
            `;
        });
        $('#table_area_device').html(htmlDevice);
    }

    /**
     * Render tabel list Area / Bagian
     * @param {Array} data
     */
    function renderTableArea(data) {
        let htmlArea = '';
        (data || []).forEach(item => {
            const rowClass = item.has_device ? '' : 'table-warning';
            htmlArea += `
                <tr class="${rowClass}">
                    <td><input type="radio" value="${item.id}" class="radioCheckboxArea" aria-label="area-label" name="id"></td>
                    <td>${escapeHtml(item.location)}</td>
                    <td>${escapeHtml(item.area)}</td>
                    <td>${escapeHtml(item.bagian)}</td>
                </tr>
            `;
        });
        $('#table_area').html(htmlArea);
    }

    /**
     * Render dropdown pilihan proses dan tabel daftar proses
     * @param {Array} data
     */
    function renderProcessOptionDanProcess(data) {
        let optionProcess = '<option value="">Pilih Proses</option>';
        let htmlProcess = '';

        (data || []).forEach((item, index) => {
            optionProcess += `<option value="${item.id}">${escapeHtml(item.proses)}</option>`;
            htmlProcess += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${escapeHtml(item.proses)}</td>
                    <td><input type="text" class="form-control unit_proses" value="${escapeHtml(item.unit)}" data-id="${item.id}"></td>
                </tr>
            `;
        });
        $('#proses_id').html(optionProcess);
        $('#tbody_process').html(htmlProcess);
    }

    /**
     * Render opsi data list lokasi
     * @param {Object} data
     */
    function renderLocationOption(data) {
        let optionLocation = '';
        Object.entries(data || {}).forEach(([id, name]) => {
            optionLocation += `<option data-id="${id}">${escapeHtml(name)}</option>`;
        });
        $('#location_list').html(optionLocation);
    }

    /* ==========================================================================
       ACTION & TRANSACTION FUNCTIONS
       ========================================================================== */

    /**
     * Membuka modal koordinat untuk ID device tertentu
     * @param {string|number} id
     */
    function openModalCoordinates(id) {
        $('#modalTitle').text("Update coordinate Device id " + id);
        $('#id_device').val(id);
        const modal = bootstrap.Modal.getOrCreateInstance(
            document.getElementById('modalCoordinates')
        );
        modal.show();
    }

    /**
     * Memproses scan barcode untuk area yang dipilih
     */
    function scanBarcode(params) {
        if ($('input:radio.radioCheckboxArea:checked').is(':checked')) {
            var id = $("input[name='id']:checked").val();
            var barcode = $('#barcode_id').val();
            var proses = $('#proses_id').val();

            if (proses == null || proses == 'Pilih Proses') {
                createAlert('', "Failed!", "Pilih Proses Dulu !", 'danger', true, true, 'pageMessages');
                return false;
            }

            var remark = $('#remark_id').val();

            $.ajax({
                url: "{{ route('manage.device_IoT.scan_barcode') }}",
                data: {
                    id: id,
                    barcode: barcode,
                    proses: proses,
                    remark: remark
                },
                method: "GET",
                dataType: 'JSON',
                beforeSend: function() {
                    $("#loading-spinner").show();
                },
                success: function(data) {
                    createAlert('', data.alert, data.text, data.color, true, true, 'pageMessages');
                    if (data.alert != 'Failed!') {
                        resetForm();
                        main();
                    }
                },
                complete: function() {
                    $("#loading-spinner").hide();
                }
            });
        } else {
            createAlert('', 'Failed!', 'You Must Check 1 Data', 'danger', true, true, 'pageMessages');
            resetForm();
        }
    }

    /* ==========================================================================
       HELPER & UTILITY FUNCTIONS
       ========================================================================== */

    /**
     * Mereset form input barcode & pemilhan proses
     */
    function resetForm() {
        $('.form-control').val('');
        $('#proses_id').prop('selectedIndex', 0);
    }

    /**
     * Escape string ke format HTML aman untuk mencegah XSS
     * @param {string} str
     * @returns {string}
     */
    function escapeHtml(str) {
        return (str || '').toString().replace(/[&<>"']/g, function(m) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            } [m];
        });
    }

    /**
     * Membuat elemen komponen alert Bootstrap secara dinamis
     */
    function createAlert(title, summary, details, severity, dismissible, autoDismiss, appendToId) {
        var iconMap = {
            info: "fa fa-info-circle",
            success: "fa fa-thumbs-up",
            warning: "fa fa-exclamation-triangle",
            danger: "fa fa-exclamation-circle"
        };

        var iconAdded = false;
        var alertClasses = ["alert", "animated", "flipInX", "alert-" + severity.toLowerCase()];

        if (dismissible) {
            alertClasses.push("alert-dismissible");
        }

        var msgIcon = $("<i />", {
            "class": iconMap[severity] || iconMap.info
        });
        var msg = $("<div />", {
            "class": alertClasses.join(" ")
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
            $("<span />", {
                "class": "close",
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
