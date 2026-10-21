<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- apexcharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
    integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8=" crossorigin="anonymous"></script>
<script>
    $(document).ready(function() {
        main();
        const modal = bootstrap.Modal.getOrCreateInstance(
            document.getElementById('modalCoordinates')
        );
        $('#formCoordinates').on('submit', function(e) {
            e.preventDefault();


            $.ajax({
                url: "{{ route('manage.rft.device.update_coordinate') }}", // ganti dengan route kamu
                type: "POST",
                data: {
                    id_device: $('#id_device').val(),
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
                        modal.hide();
                        $('#coordinates').val("");

                        main();
                    }

                },
                error: function(xhr) {

                    alert(xhr.responseJSON?.message ?? 'Terjadi kesalahan');

                },
                complete: function() {

                    $('#btnSave')
                        .prop('disabled', false)
                        .html('Save');



                }
            });

        });
    });

    function scanBarcode(params) {
        if ($('input:radio.radioCheckbox:checked').is(':checked')) {
            var id = $("input[name='id']:checked").val();
            var barcode = $('#barcode_id').val();
            $.ajax({
                url: "{{ route('manage.rft.device.scan_barcode') }}",
                data: {
                    id: id,
                    barcode: barcode
                },
                method: "get",
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
            })
        } else {
            createAlert('', 'Failed!', 'You Must Check 1 Data', 'danger', true, true, 'pageMessages');
            resetForm();
        }
    }

    function deleteData(id) {
        if (id == null) {
            alert('Tidak ada data id device')
        }
        $.ajax({
            url: "{{ route('manage.rft.device.delete_device') }}",
            method: "get",
            data: {
                id: id
            },
            dataType: 'JSON',
            success: function(data) {
                createAlert('', data.alert, data.text, data.color, true, true, 'pageMessages');
                if (data.alert != 'Failed!') {
                    main();
                }
            }
        });
    }

    function resetForm() {
        $('.form-control').val('');
    }

    function openModalCoordinates(id) {
        $('#modalTitle').text("Update coordinate Device id " + id);

        $('#id_device').val(id);
        const modal = bootstrap.Modal.getOrCreateInstance(
            document.getElementById('modalCoordinates')
        );
        modal.show();
    }

    function main() {
        $.ajax({
            url: "{{ route('manage.rft.device.main') }}",
            method: "get",
            dataType: 'JSON',
            success: function(data) {
                $('#table_area').html(data.table_area);
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
