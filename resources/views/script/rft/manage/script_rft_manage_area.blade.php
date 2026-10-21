<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- apexcharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
    integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8=" crossorigin="anonymous"></script>
<script>
    $(document).ready(function() {
        main();
        $('#import_location').on('submit', function(event) {
            event.preventDefault();
            $.ajax({
                url: "{{ route('manage.area.save_location') }}",
                method: "POST",
                data: new FormData(this),
                dataType: 'JSON',
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function() {
                    $("#loading-spinner").show();
                },
                success: function(data) {
                    createAlert('', data.alert, data.text, data.color, true, true,
                        'pageMessages');
                    if (data.alert != 'Failed!') {
                        resetForm();
                        main();
                    }
                },
                complete: function() {
                    $("#loading-spinner").hide();
                }
            })
        });
        $('#import_area').on('submit', function(event) {
            event.preventDefault();
            $.ajax({
                url: "{{ route('manage.area.save_area') }}",
                method: "POST",
                data: new FormData(this),
                dataType: 'JSON',
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function() {
                    $("#loading-spinner").show();
                },
                success: function(data) {
                    createAlert('', data.alert, data.text, data.color, true, true,
                        'pageMessages');
                    if (data.alert != 'Failed!') {
                        resetForm();
                        main();
                    }
                },
                complete: function() {
                    $("#loading-spinner").hide();
                }
            })
        });
        $('#import_bagian').on('submit', function(event) {
            event.preventDefault();
            $.ajax({
                url: "{{ route('manage.rft.area.save_bagian') }}",
                method: "POST",
                data: new FormData(this),
                dataType: 'JSON',
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function() {
                    $("#loading-spinner").show();
                },
                success: function(data) {
                    createAlert('', data.alert, data.text, data.color, true, true,
                        'pageMessages');
                    if (data.alert != 'Failed!') {
                        resetForm();
                        main();
                    }
                },
                complete: function() {
                    $("#loading-spinner").hide();
                }
            })
        });
        $('#import_proses').on('submit', function(event) {
            event.preventDefault();
            $.ajax({
                url: "{{ route('manage.rft.area.save_proses') }}",
                method: "POST",
                data: new FormData(this),
                dataType: 'JSON',
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function() {
                    $("#loading-spinner").show();
                },
                success: function(data) {
                    createAlert('', data.alert, data.text, data.color, true, true,
                        'pageMessages');
                    if (data.alert != 'Failed!') {
                        resetForm();
                        main();
                    }
                },
                complete: function() {
                    $("#loading-spinner").hide();
                }
            })
        });
        $('#id_location_area').on('change', function() {
            event.preventDefault();
            changeLocation($(this).val(), 'id_area');
        });
        $('#id_location_bagian').on('change', function() {
            event.preventDefault();
            changeLocation($(this).val(), 'id_area_bagian');
        });
        $('#id_location_proses').on('change', function() {
            event.preventDefault();
            changeLocation($(this).val(), 'id_area_proses');
        });
        $('#id_area_proses').on('change', function() {
            event.preventDefault();
            var location = $('#id_location_proses').val();
            changArea($(this).val(), 'id_bagian_proses', location);
        });
    });

    function changeLocation(data, ui) {
        $.ajax({
            url: "{{ route('manage.rft.area.change_location') }}",
            data: {
                location: data
            },
            method: "get",
            dataType: 'JSON',
            beforeSend: function() {
                $("#loading-spinner").show();
            },
            success: function(data) {
                $('#' + ui).html(data);
            },
            complete: function() {
                $("#loading-spinner").hide();
            }
        })
    }

    function changArea(data, ui, location) {

        $.ajax({
            url: "{{ route('manage.rft.area.change_area') }}",
            data: {
                area: data,
                location: location
            },
            method: "get",
            dataType: 'JSON',
            beforeSend: function() {
                $("#loading-spinner").show();
            },
            success: function(data) {
                $('#' + ui).html(data);
            },
            complete: function() {
                $("#loading-spinner").hide();
            }
        })
    }

    function deleteData(id, option) {
        if (confirm("Are you sure want to delete this?") == true) {
            $.ajax({
                url: "{{ route('manage.rft.area.deleteID') }}",
                data: {
                    id: id,
                    option: option
                },
                method: "get",
                dataType: 'JSON',
                beforeSend: function() {
                    $("#loading-spinner").show();
                },
                success: function(data) {
                    createAlert('', data.alert, data.text, data.color, true, true, 'pageMessages');
                    main();
                },
                complete: function() {
                    $("#loading-spinner").hide();
                }
            })
        } else {
            return false;
        }
    }

    function resetForm() {
        $('.form-control').val('');
        $('.form-control').prop('selectedIndex', 0);
    }

    function main() {
        $.ajax({
            url: "{{ route('manage.rft.area.main') }}",
            method: "get",
            dataType: 'JSON',
            success: function(data) {
                $('#table_location').html(data.table_location);
                $('#table_area').html(data.table_area);
                $('#table_bagian').html(data.table_bagian);
                $('#table_proses').html(data.table_proses);

                $('.select_location').html(data.option_location);
                $('.select_area').html(data.option_area);
            }
        })
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
