    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Show loading overlay
function showLoading() {
    $('#loadingOverlay').fadeIn();
}

function hideLoading() {
    $('#loadingOverlay').fadeOut();
}

// Auto-hide alerts after 3 seconds
setTimeout(function() {
    $('.alert').fadeOut();
}, 3000);
</script>
</body>
</html>