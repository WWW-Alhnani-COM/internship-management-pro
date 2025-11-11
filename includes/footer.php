<?php
// Footer file
?>
    </main>
</div>

<footer class="bg-dark text-white mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5><?php echo APP_NAME; ?></h5>
                <p class="mb-0">Internship Management System - Version <?php echo APP_VERSION; ?></p>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <p class="mb-0">All rights reserved &copy; <?php echo date('Y'); ?></p>
                <p class="mb-0">Technical University</p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle sidebar on mobile
        const toggleBtn = document.querySelector('.toggle-sidebar');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                mainContent.classList.toggle('sidebar-active');
            });
        }
        
        // Submenu toggle
        const submenuToggles = document.querySelectorAll('[data-toggle="submenu"]');
        submenuToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                const isSubmenuLink = this.parentElement.classList.contains('has-submenu');
                if (isSubmenuLink) {
                    e.preventDefault();
                    const submenu = this.nextElementSibling;
                    const icon = this.querySelector('.submenu-icon');
                    
                    if (submenu && submenu.classList.contains('submenu')) {
                        submenu.classList.toggle('active');
                        if (icon) {
                            icon.classList.toggle('rotate-180');
                        }
                    }
                }
            });
        });
        
        // Close sidebar when clicking on a link on mobile
        const menuLinks = document.querySelectorAll('.menu-link, .submenu-link');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('active');
                    mainContent.classList.remove('sidebar-active');
                }
            });
        });
        
        // Auto check for new notifications
        function checkNotifications() {
            const userId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
            if (userId > 0) {
                fetch('../includes/notification-handler.php?action=get_count')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update notifications badge
                            const notificationBadge = document.querySelector('.notification-bell .notification-badge');
                            if (data.count > 0) {
                                if (!notificationBadge) {
                                    const bellIcon = document.querySelector('.notification-bell i');
                                    if (bellIcon) {
                                        const badge = document.createElement('span');
                                        badge.className = 'notification-badge';
                                        badge.textContent = data.count;
                                        bellIcon.parentNode.appendChild(badge);
                                    }
                                } else {
                                    notificationBadge.textContent = data.count;
                                }
                            } else if (notificationBadge) {
                                notificationBadge.remove();
                            }
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        }
        
        // Check for notifications every 30 seconds
        setInterval(checkNotifications, 30000);
        // Initial check
        checkNotifications();
    });
</script>
</body>
</html>