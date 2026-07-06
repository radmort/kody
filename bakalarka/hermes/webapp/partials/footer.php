</main><!-- /container-fluid -->

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Shared JS utilities -->
<script>
// Confirm-delete helper
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
        if (!confirm(el.dataset.confirm || 'Naozaj?')) e.preventDefault();
    });
});

// Auto-dismiss alerts after 4 s
setTimeout(() => {
    document.querySelectorAll('.alert.alert-success, .alert.alert-info')
        .forEach(a => bootstrap.Alert.getOrCreateInstance(a).close());
}, 4000);
</script>
</body>
</html>
