<!-- Daterangepicker -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/image-map-resizer/1.0.10/js/imageMapResizer.min.js"></script>

<script>
    imageMapResize();
</script>

<!-- ChartJS -->
<script>
    $(document).ready(function() {
        $(function() {
            var startDate = moment().subtract(7, 'days');
            var endDate = moment().add(0, 'days');

            $('#daterange').daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD'
                },
                startDate: startDate,
                endDate: endDate
            });
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const body = document.body;

        // Simpan state sidebar
        document.querySelector('[data-lte-toggle="sidebar"]')
            ?.addEventListener('click', function() {
                setTimeout(() => {
                    localStorage.setItem(
                        'sidebar-collapsed',
                        body.classList.contains('sidebar-collapse')
                    );
                }, 300);
            });

        // Saat scroll (khusus mobile/tablet)
        window.addEventListener('scroll', function() {
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                body.classList.add('sidebar-collapse');
                body.classList.remove('sidebar-open');
            }
        });

    });

    function fullscreen(btn, status) {

        let sidebar = document.getElementById("display_sidebar");
        let header = document.getElementById("display_header");
        let content = document.getElementsByClassName("app-content")[0];

        let icon = btn.querySelector("i");

        let elements = [sidebar, header];

        if (status === true) {

            elements.forEach(el => {
                el.classList.remove("smooth-show");
                el.classList.add("smooth-hide");

                setTimeout(() => {
                    el.style.display = "none";
                }, 300);
            });

            content.classList.add("mt-4");

            btn.onclick = function() {
                fullscreen(this, false);
            };

            icon.classList.remove("bi-arrows-fullscreen");
            icon.classList.add("bi-fullscreen-exit");

        } else {

            elements.forEach(el => {

                // tampilkan dulu
                el.style.display = "";

                setTimeout(() => {
                    el.classList.remove("smooth-hide");
                    el.classList.add("smooth-show");
                }, 10);

            });

            content.classList.remove("mt-4");

            btn.onclick = function() {
                fullscreen(this, true);
            };

            icon.classList.remove("bi-fullscreen-exit");
            icon.classList.add("bi-arrows-fullscreen");
        }
    }
</script>
