// app.js — Byabsayee client-side helpers

// Active page detection (called from layout.php via PHP helper)
// Adds 'active' class to current nav item

// Confirm before delete forms
document.addEventListener('DOMContentLoaded', function () {

    // Delete confirmation
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // Auto-dismiss flash messages after 4 seconds
    document.querySelectorAll('.flash').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.4s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 4000);
    });

    // Modal open/close
    document.querySelectorAll('[data-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.dataset.modal);
            if (target) target.classList.add('open');
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.closest('.modal-backdrop').classList.remove('open');
        });
    });

    // Close modal when clicking the backdrop
    document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) backdrop.classList.remove('open');
        });
    });

});
