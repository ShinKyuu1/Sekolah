<?php if (!defined('APP_NAME')) {
    exit;
} ?>
</main>
</div> <!-- .app-content-wrapper -->
</div> <!-- .app-layout -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const appLayout = document.querySelector('.app-layout');

        if (sidebarToggle && appLayout) {
            sidebarToggle.addEventListener('click', function() {
                appLayout.classList.toggle('sidebar-open');
            });
        }

        // Logika Dropdown Rekap Tingkatan
        const rekapToggle = document.getElementById('rekapToggle');
        const rekapMenu = document.getElementById('rekapMenu');
        if (rekapToggle && rekapMenu) {
            rekapToggle.addEventListener('click', function() {
                rekapMenu.classList.toggle('collapsed');
                rekapToggle.classList.toggle('open');
            });
        }
    });
</script>
</body>

</html>