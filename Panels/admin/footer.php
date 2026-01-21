            </main>
        </div>
    </div>

    <!-- Global Footer -->
    <footer class="app-footer">
        <div class="app-footer-inner">
            <div class="app-footer-left">
                <span class="app-footer-title">AttendEase</span>
                <span class="app-footer-divider">•</span>
                <span class="app-footer-text">
                    Admin panel for attendance management
                </span>
            </div>
            <div class="app-footer-right">
                <span class="app-footer-text">
                    © <?php echo date('Y'); ?> AttendEase
                </span>
                <span class="app-footer-divider">•</span>
                <span class="app-footer-text">
                    v1.0.0
                </span>
            </div>
        </div>
    </footer>

    <script src="<?php echo BASE_PATH; ?>assets/script.js"></script>
    <script>
        // Global search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('global-search');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        const query = this.value.trim();
                        if (query) {
                            window.location.href = BASE_PATH + 'students.php?search=' + encodeURIComponent(query);
                        }
                    }
                });
            }

            // User menu dropdown (placeholder)
            const userMenu = document.querySelector('.user-menu');
            if (userMenu) {
                userMenu.addEventListener('click', function() {
                    console.log('User menu clicked');
                });
            }
        });
    </script>
</body>
</html>
