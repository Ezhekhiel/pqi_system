<!-- apexcharts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

<script>
    $(document).ready(function() {
        $('#period_type').on('change', function() {
            var selectedType = $(this).val();

            // Hide both fields first and clear values
            $('.period-field').hide();
            $('#export_month, #export_date').val('');

            // Show the relevant field based on selection
            if (selectedType === 'monthly') {
                $('#div_monthly').fadeIn();
            } else if (selectedType === 'daily') {
                $('#div_daily').fadeIn();
            }
        });
        // Toggle Summary Interval visibility based on Data Type choice
        $('#data_type').on('change', function() {
            if ($(this).val() === 'summary') {
                $('#div_summary_interval').fadeIn();
            } else {
                $('#div_summary_interval').hide();
                $('#summary_interval').val('');
            }
        });
    });
</script>
<!-- Script Interaktif untuk Loading State & Dynamic Form Dropdown -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dataTypeSelect = document.getElementById('data_type');
        const summaryIntervalDiv = document.getElementById('div_summary_interval');
        const exportForm = document.getElementById('exportForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnIcon = document.getElementById('btnIcon');
        const btnText = document.getElementById('btnText');

        // Handle Show/Hide Summary Interval
        dataTypeSelect.addEventListener('change', function() {
            if (this.value === 'summary') {
                summaryIntervalDiv.style.display = 'block';
                document.getElementById('summary_interval').setAttribute('required', 'required');
            } else {
                summaryIntervalDiv.style.display = 'none';
                document.getElementById('summary_interval').removeAttribute('required');
            }
        });

        // Handle Interactive Loading State Saat Tombol Submit Ditekan
        exportForm.addEventListener('submit', function(e) {
            // Pastikan validasi HTML5 terpenuhi sebelum ubah tombol ke state loading
            if (exportForm.checkValidity()) {
                submitBtn.setAttribute('disabled', 'true');
                btnIcon.className = "spinner-border spinner-border-sm fs-5";
                btnText.innerText = "Processing Export...";

                // Opsional: Reset tombol otomatis setelah beberapa detik 
                // (Karena response berupa file download stream, halaman kadang tidak reload penuh)
                setTimeout(() => {
                    submitBtn.removeAttribute('disabled');
                    btnIcon.className = "bi bi-file-earmark-excel-fill fs-5";
                    btnText.innerText = "Download Excel";
                }, 5000);
            }
        });
    });
</script>
