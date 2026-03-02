// FinanceFlow - Main JavaScript

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('show');
}

// Auto-dismiss alerts
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.ff-alert[data-auto-dismiss]');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    });

    // Animate stat values
    const statValues = document.querySelectorAll('.stat-value[data-value]');
    statValues.forEach(el => {
        const target = parseFloat(el.dataset.value);
        const prefix = el.dataset.prefix || '';
        const duration = 800;
        const step = target / (duration / 16);
        let current = 0;
        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            el.textContent = prefix + current.toLocaleString('en-BD', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }, 16);
    });
});

// Confirm delete
function confirmDelete(url, message) {
    if (confirm(message || 'Are you sure you want to delete this?')) {
        window.location.href = url;
    }
}

// Progress bar animation
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.ff-progress-bar[data-width]').forEach(bar => {
        const width = bar.dataset.width;
        setTimeout(() => {
            bar.style.width = width + '%';
        }, 300);
    });
});
