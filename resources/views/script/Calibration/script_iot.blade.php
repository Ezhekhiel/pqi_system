    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            main();
            $(document).on('change', '.manual_calibration', function(e) {
                e.preventDefault();
                let value = parseFloat($(this).val().replace(',', '.'));
                let id = $(this).closest('tr').data('id');
                let last_update = parseFloat($('#last_update_' + id).text().replace(',', '.'));
                if (last_update == 0) {
                    createAlert('', 'error', 'Last update masih kosong tolong jalankan dulu device anda!',
                        'danger', true, true,
                        'pageMessages');
                    return false;
                }
                let calibration = value - last_update;
                $.ajax({
                    url: "{{ route('Calibration.update') }}",
                    method: "get",
                    data: {
                        calibration: calibration,
                        id: id
                    },
                    dataType: 'JSON',
                    success: function(data) {
                        main();
                        createAlert('', data.alert, data.text, data.color, true, true,
                            'pageMessages');
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText); // ini penting buat debug
                    }
                });
            });
        });

        function resetForm() {
            $('.form-control').val('');
        }

        function main() {
            $.ajax({
                url: "{{ route('Calibration.main_iot') }}",
                method: "get",
                dataType: 'JSON',
                success: function(data) {
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
