    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            main();
            $('#delete_data_device').on('click', function(event) {
                var id = $('input[name="id_device"]:checked').map(function() {
                    return this.value;
                }).get();

                $.ajax({
                    url: "{{ route('manage.pqi.device.delete_device') }}",
                    method: "get",
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
            $(document).on('change', '.unit_proses', function() {
                var unit = $(this).val();
                var id = $(this).data('id');
                $.ajax({
                    url: "{{ route('manage.device_IoT.update_unit_proses') }}",
                    method: "get",
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

            })
        });

        function scanBarcode(params) {
            if ($('input:radio.radioCheckbox:checked').is(':checked')) {
                var id = $("input[name='id']:checked").val();
                var barcode = $('#barcode_id').val();

                $.ajax({
                    url: "{{ route('manage.pqi.device.scan_barcode') }}",
                    data: {
                        id: id,
                        barcode: barcode,
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

        function resetForm() {
            $('.form-control').val('');
        }

        function main() {
            $.ajax({
                url: "{{ route('manage.pqi.device.main') }}",
                method: "get",
                dataType: 'JSON',
                success: function(data) {
                    $('#table_area').html(data.table_area);
                    $('#proses_id').html(data.option_process);
                    $('#tbody_process').html(data.table_process)
                    $('#table_area_device').html(data.table_data_device)
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
