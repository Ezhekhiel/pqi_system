<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script>
    $(document).ready(function() {
        changeBuilding();
        main();
        $('#formSchedule').submit(function(e) {
            event.preventDefault();
            $.ajax({
                url: "{{ route('whatsapp.manage.addSchedule') }}",
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
                    main();
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        });
        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');

            if (confirm('Yakin ingin menghapus data ini?')) {
                deleteSchedule(id);
            }
        });
        $('#id_building').on('change', function(e) {
            changeBuilding();
        })
    });

    function main() {
        $.ajax({
            url: "{{ route('whatsapp.manage.main') }}", // atau "/your-url"
            type: "GET",
            dataType: 'JSON',

            beforeSend: function() {
                console.log('Loading get data');
            },
            success: function(response) {
                renderTableSchedule(response);
            },
            error: function(xhr) {
                console.log(xhr.responseText);
            }
        });
    }

    function renderTableSchedule(data) {
        var tableSchedule = $("#tableSchedule");
        let html = '';
        let no = 0;

        data.forEach(function(item) {
            no++;
            var status = item.is_active == '1' ? "Active" : "Non Active";
            html += `
                <tr>
                    <td>${no}</td>
                    <td>${item.name}</td>
                    <td>${item.name_group}</td>
                    <td>${item.building}</td>
                    <td>${item.area}</td>
                    <td>${item.time_range}</td>
                    <td>${item.send_time.split('.')[0]}</td>
                    <td>${status}</td>
                     <td>
                    <button type="button"
                        class="btn btn-sm btn-danger btn-delete"
                        data-id="${item.id}">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </td>
                </tr>
                        `;
        });
        $('#tableSchedule').html(html);
    }

    function changeBuilding() {
        var id_building = $('#id_building').val();
        $.ajax({
            url: "{{ route('whatsapp.manage.change_building') }}",
            type: 'POST',
            data: {
                id_building: id_building,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                let html = '<option value="">-- Pilih Area --</option>';

                response.forEach(function(item) {
                    html += `
                            <option value="${item.id}">
                                ${item.area}
                            </option>
                        `;
                });
                $("#id_area").html(html);
            },
            error: function(xhr) {
                console.log(xhr.responseJSON);
                alert('Terjadi kesalahan.');
            }
        });
    }

    function deleteSchedule(id) {
        $.ajax({
            url: "{{ route('whatsapp.manage.delete_schedule') }}",
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
