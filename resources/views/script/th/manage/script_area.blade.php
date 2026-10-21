<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script>
    $(document).ready(function() {
        showBuilding();
        showArea();
        showLocation();
        $('#import_building').submit(function(e) {
            event.preventDefault();
            $.ajax({
                url: "{{ route('manage_th.area.save_building') }}",
                type: "POST",
                data: new FormData(this),
                dataType: "json",
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    createAlert('', data.alert, data.text, data.color, true, true,
                        'pageMessages');
                    showBuilding();
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        });
        $('#import_area').submit(function(e) {
            event.preventDefault();

            $.ajax({
                url: "{{ route('manage_th.area.save_area') }}",
                type: "POST",
                data: new FormData(this),
                dataType: "json",
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    createAlert('', data.alert, data.text, data.color, true, true,
                        'pageMessages');
                    showArea();
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        });
        $('#import_location').submit(function(e) {
            event.preventDefault();

            $.ajax({
                url: "{{ route('manage_th.area.save_location') }}",
                type: "POST",
                data: new FormData(this),
                dataType: "json",
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    createAlert('', data.alert, data.text, data.color, true, true,
                        'pageMessages');
                    $('#location_id').val("");
                    showLocation();
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        });
        $('#id_building').on('change', function(event) {
            $.ajax({
                url: "{{ route('TH.Dashboard.search_area') }}",
                method: "get",
                data: {
                    building: $(this).val()
                },
                dataType: 'JSON',
                success: function(data) {
                    $('#option_area').html(data.option_area)
                }
            });
        })
    });

    function showArea() {
        $.ajax({
            url: "{{ route('TH.Dashboard.data_area') }}",
            method: "get",
            dataType: 'JSON',
            success: function(data) {
                $('#table_area').html(data.tbody_area)
            }
        });
    }

    function showBuilding() {
        $.ajax({
            url: "{{ route('TH.Dashboard.data_building') }}",
            method: "get",
            dataType: 'JSON',
            success: function(data) {
                $('.select_building').html(data.option_building)
                $('#table_building').html(data.tbody_building)
            }
        });
    }

    function showLocation() {
        $.ajax({
            url: "{{ route('TH.Dashboard.data_location') }}",
            method: "get",
            dataType: 'JSON',
            success: function(data) {
                $('#table_location').html(data.table_location)
            }
        });
    }

    function deleteBuilding(id) {
        $.ajax({
            url: "{{ route('TH.Dashboard.delete_building') }}",
            method: "get",
            data: {
                id: id
            },
            dataType: 'JSON',
            success: function(data) {
                createAlert('', data.alert, data.text, data.color, true, true,
                    'pageMessages');
                showBuilding();
            }
        });
    }

    function deleteArea(id) {
        $.ajax({
            url: "{{ route('TH.Dashboard.delete_area') }}",
            method: "get",
            data: {
                id: id
            },
            dataType: 'JSON',
            success: function(data) {
                createAlert('', data.alert, data.text, data.color, true, true,
                    'pageMessages');
                showArea();
            }
        });
    }

    function deleteLocation(id) {
        $.ajax({
            url: "{{ route('TH.Dashboard.delete_location') }}",
            method: "get",
            data: {
                id: id
            },
            dataType: 'JSON',
            success: function(data) {
                createAlert('', data.alert, data.text, data.color, true, true,
                    'pageMessages');
                showLocation();
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
