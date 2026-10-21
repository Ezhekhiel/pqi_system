<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
    crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

<script src="{{ asset('/js/adminlte.js') }}"></script>
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>
<script>
    const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
    const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
    };
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
        if (sidebarWrapper && OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined) {
            OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                scrollbars: {
                    theme: Default.scrollbarTheme,
                    autoHide: Default.scrollbarAutoHide,
                    clickScroll: Default.scrollbarClickScroll,
                },
            });
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" crossorigin="anonymous"></script>
<script>
    new Sortable(document.querySelector('.connectedSortable'), {
        group: 'shared',
        handle: '.card-header',
    });

    const cardHeaders = document.querySelectorAll('.connectedSortable .card-header');
    cardHeaders.forEach((cardHeader) => {
        cardHeader.style.cursor = 'move';
    });
</script>

<script>
    const bodyElement = document.body;
    const toggleButton = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');

    const savedTheme = localStorage.getItem('theme');
    const defaultTheme = bodyElement.getAttribute('data-bs-theme') || 'light';
    const initialTheme = savedTheme || defaultTheme;

    function applyTheme(theme) {
        bodyElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);

        if (themeIcon) {
            if (theme === 'dark') {
                themeIcon.classList.remove('bi-moon');
                themeIcon.classList.add('bi-sun');
            } else {
                themeIcon.classList.remove('bi-sun');
                themeIcon.classList.add('bi-moon');
            }
        }

        const newChartTheme = theme;
        const newLabelColor = theme === 'dark' ? '#ffffff' : '#333333';
        const newGridColor = theme === 'dark' ? '#555' : '#e0e0e0';

        let allCharts = [];
        allCharts.forEach(chart => {
            chart.updateOptions({
                theme: {
                    mode: newChartTheme
                },
                xaxis: {
                    labels: {
                        style: {
                            colors: newLabelColor
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: newLabelColor
                        }
                    },
                    grid: {
                        borderColor: newGridColor
                    }
                },
                tooltip: {
                    theme: newChartTheme
                }
            });
        });
    }

    function toggleTheme() {
        const currentTheme = bodyElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        applyTheme(newTheme);
    }

    applyTheme(initialTheme);
    if (toggleButton) {
        toggleButton.addEventListener('click', toggleTheme);
    }
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".flip-header").forEach(header => {
            header.addEventListener("click", function(e) {
                e.stopPropagation();
                let container = this.closest(".flip-container");
                if (container) {
                    container.classList.toggle("active");
                }
            });
        });
    });
</script>
