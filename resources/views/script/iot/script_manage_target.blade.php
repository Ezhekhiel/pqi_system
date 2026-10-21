<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        main();

        $('#save_add_proses').on('click', function(event) {
            var proses = $('#inp_proses').val();
            $.ajax({
                url: "{{ route('manage.device_IoT.add_proses') }}",
                method: "get",
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
        $("#select_cell").on('change', function() {
            $.ajax({
                url: "{{ route('manage.target.cange_cell') }}",
                data: {
                    cell: $(this).val()
                },
                method: "get",
                dataType: 'JSON',
                success: function(data) {
                    $('#select_model').html(data.select_model)
                }
            });
        });
        $(document).on('change', '#select_model', function() {
            var cell = $('#select_cell').val();
            showData(cell, $(this).val());
        })

    });

    function updateValue(element, kode, remark) {
        var model = $('#select_model').val();
        var cell = $('#select_cell').val();
        var value = $(element).val();
        $.ajax({
            url: "{{ route('manage.target.change_value') }}",
            data: {
                cell: cell,
                model: model,
                kode: kode,
                value: value,
                remark: remark
            },
            method: "get",
            dataType: 'JSON',
            success: function(data) {
                if (data.status == true) {
                    createAlert('', 'Success!', data.message, 'success', true, true,
                        'pageMessages');
                }
                $('#table_standar').html(data.table)
            }
        });
    }

    function showData(cell, model) {
        $.ajax({
            url: "{{ route('manage.target.cange_model') }}",
            data: {
                cell: cell,
                model: model
            },
            method: "get",
            dataType: 'JSON',
            success: function(data) {
                $('#table_standar').html(data.table)
            }
        });
    }

    function Ubahremark(id) {
        $("#id_process").val(id);
        $('#modalTambah').modal('show');
    }

    function modalAddProses() {
        var model = $('#select_model').val();
        if (!model) {
            alert('Pilih model dulu!');
            return false;
        }

        $('#modalTambahProses').modal('show');
    }

    function simpanProses() {
        var selectedIds = $('input[name="id[]"]:checked').map(function() {
            return $(this).val();
        }).get();
        var model = $('#select_model').val();
        var cell = $('#select_cell').val();

        $.ajax({
            url: "{{ route('manage.target.addProcess') }}",
            type: 'POST',
            dataType: 'json',
            data: {
                id: selectedIds,
                model: model,
            },
            success: function(response) {
                // Aksi jika permintaan berhasil
                createAlert('', response.alert, response.text, response.color, true, true,
                    'pageMessages');
                showData(cell, model);
                $('#modalTambahProses').modal('hide');
                $('input[name="id[]"]').prop('checked', false);

            },
            error: function(xhr, status, error) {
                // Aksi jika terjadi kesalahan
                createAlert('', data.alert, data.text, data.color, true, true,
                    'pageMessages');
                console.error(error);
            }
        });

    }

    function simpanRemark() {
        var id_process = $('#id_process').val();
        var remark = $('#inp_remark_update').val();
        var model = $('#select_model').val();
        var cell = $('#select_cell').val();

        $.ajax({
            url: "{{ route('manage.target.change_remark') }}",
            type: 'POST',
            dataType: 'json',
            data: {
                id_process: id_process,
                remark: remark,
                model: model
            },
            success: function(response) {
                // Aksi jika permintaan berhasil
                createAlert('', response.alert, response.text, response.color, true, true,
                    'pageMessages');
                showData(cell, model);
                $('#modalTambah').modal('hide');


            },
            error: function(xhr, status, error) {
                // Aksi jika terjadi kesalahan
                createAlert('', data.alert, data.text, data.color, true, true,
                    'pageMessages');
                console.error(error);
            }
        });
    }

    function tambahProcess(btn) {
        // 1. Ambil baris (tr) tempat tombol diklik
        var currentRow = $(btn).closest('tr');

        // 2. Ambil nilai dari baris utama jika diperlukan
        var idProcess = currentRow.find('.id_proses').val();
        var proses = currentRow.find('.proses').val();
        var model = currentRow.find('.model').val();

        // 3. Buat template TR baru yang akan disisipkan di bawahnya
        var rowSub = `
        <tr>
            <td>
                <button type='button' class='btn btn-primary' onclick='tambahProcess(this,${idProcess})' style='width: 40px; height: 40px;'>
                    +
                </button>
            </td>
            <td><input type='text' readonly class='form-control text-center model'value='${model}'></td>
            <td>
                <input type='hidden' class='id_proses' value='${idProcess}'>
                <input type='text' readonly class='form-control text-center proses' value='${proses}'>
            </td>
            <td onclick='Ubahremark(${idProcess})'>-</td>
            <td><input type='number' step='0.01' min='0' inputmode='decimal' class='form-control text-center input_min_max' id='min_value-{$a->id}' value='0'></td>
            <td><input type='number' step='0.01' min='0' inputmode='decimal' class='form-control text-center input_min_max' id='max_value-{$a->id}' value='0'></td>
        </tr>
    `;

        // 4. jQuery menyisipkan baris tepat DI BAWAH baris inisiasi
        currentRow.after(rowSub);
    }

    function resetForm() {
        $('.form-control').val('');
    }

    function main() {
        $.ajax({
            url: "{{ route('manage.target.main') }}",
            method: "get",
            dataType: 'JSON',
            success: function(data) {
                $('#select_cell').html(data.select_cell);
                $('#tbody_proses').html(data.proses_list);
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
