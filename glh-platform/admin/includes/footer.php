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
$(document).ready(function() {
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 3000);
});

// Confirm delete function
function confirmDelete(url, message) {
    Swal.fire({
        title: 'Are you sure?',
        text: message || 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}
</script>
</body>
</html>