<script>
    if (window.Alpine && window.Alpine.store('sidebar')) {
        window.Alpine.store('sidebar').close()
    } else {
        document.addEventListener('alpine:init', () => {
            window.Alpine.store('sidebar').close()
        })
    }
</script>
