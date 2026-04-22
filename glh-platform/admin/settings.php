<?php
require_once 'includes/header.php';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $site_title = $_POST['site_title'];
    $site_tagline = $_POST['site_tagline'];
    $contact_email = $_POST['contact_email'];
    $contact_phone = $_POST['contact_phone'];
    $footer_text = $_POST['footer_text'];
    
    $contentManager->set('site_title', $site_title);
    $contentManager->set('site_tagline', $site_tagline);
    $contentManager->set('contact_email', $contact_email);
    $contentManager->set('contact_phone', $contact_phone);
    $contentManager->set('footer_text', $footer_text);
    
    echo '<script>Swal.fire("Saved!", "Settings updated successfully!", "success");</script>';
}

// Get current settings
$site_title = $contentManager->get('site_title', 'Greenfield Local Hub');
$site_tagline = $contentManager->get('site_tagline', 'Fresh from Local Farms');
$contact_email = $contentManager->get('contact_email', 'support@greenfieldhub.com');
$contact_phone = $contentManager->get('contact_phone', '+1 (555) 123-4567');
$footer_text = $contentManager->get('footer_text', 'Connecting local farmers with communities');
?>

<div class="table-container">
    <h5><i class="fas fa-cog"></i> System Settings</h5>
    
    <form method="POST">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label>Site Title</label>
                    <input type="text" name="site_title" class="form-control" value="<?php echo htmlspecialchars($site_title); ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label>Site Tagline</label>
                    <input type="text" name="site_tagline" class="form-control" value="<?php echo htmlspecialchars($site_tagline); ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label>Contact Email</label>
                    <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($contact_email); ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label>Contact Phone</label>
                    <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($contact_phone); ?>" required>
                </div>
            </div>
            <div class="col-12">
                <div class="mb-3">
                    <label>Footer Text</label>
                    <textarea name="footer_text" class="form-control" rows="3"><?php echo htmlspecialchars($footer_text); ?></textarea>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>