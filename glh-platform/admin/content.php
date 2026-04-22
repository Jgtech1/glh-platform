<?php
require_once 'includes/header.php';

// Handle content updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_content'])) {
        $content_key = $_POST['content_key'];
        $content_value = $_POST['content_value'];
        $content_type = $_POST['content_type'];
        $category = $_POST['category'];
        
        $contentManager->set($content_key, $content_value, $content_type, $category);
        echo '<script>Swal.fire("Success!", "Content updated successfully!", "success");</script>';
    } elseif (isset($_POST['add_content'])) {
        $content_key = $_POST['content_key'];
        $content_value = $_POST['content_value'];
        $content_type = $_POST['content_type'];
        $category = $_POST['category'];
        
        $contentManager->set($content_key, $content_value, $content_type, $category);
        echo '<script>Swal.fire("Success!", "Content added successfully!", "success").then(() => { location.reload(); });</script>';
    } elseif (isset($_POST['delete_content'])) {
        $content_key = $_POST['content_key'];
        $contentManager->deleteContent($content_key);
        echo '<script>Swal.fire("Deleted!", "Content deleted successfully!", "success").then(() => { location.reload(); });</script>';
    }
}

// Get all content
$contents = $contentManager->getAllContent();
$categories = $contentManager->getCategories();

// Group content by category
$groupedContent = [];
foreach ($contents as $content) {
    $groupedContent[$content['category']][] = $content;
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="table-container">
            <h5><i class="fas fa-plus"></i> Add New Content</h5>
            <form method="POST">
                <div class="mb-3">
                    <label>Content Key *</label>
                    <input type="text" name="content_key" class="form-control" placeholder="e.g., hero_title, about_text" required>
                    <small class="text-muted">Use lowercase with underscores</small>
                </div>
                <div class="mb-3">
                    <label>Content Value *</label>
                    <textarea name="content_value" class="form-control" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label>Content Type</label>
                    <select name="content_type" class="form-select">
                        <option value="text">Text</option>
                        <option value="html">HTML</option>
                        <option value="json">JSON</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Category</label>
                    <input type="text" name="category" class="form-control" value="general">
                </div>
                <button type="submit" name="add_content" class="btn btn-primary w-100">Add Content</button>
            </form>
        </div>
        
        <div class="table-container mt-4">
            <h5><i class="fas fa-info-circle"></i> Content Categories</h5>
            <div class="list-group">
                <?php foreach ($categories as $category): ?>
                    <a href="#category-<?php echo $category; ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-folder"></i> <?php echo ucfirst($category); ?>
                        <span class="badge bg-primary float-end"><?php echo count($groupedContent[$category] ?? []); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <?php foreach ($groupedContent as $category => $items): ?>
            <div class="table-container mb-4" id="category-<?php echo $category; ?>">
                <h5><i class="fas fa-tag"></i> Category: <?php echo ucfirst($category); ?></h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Key</th>
                                <th>Value</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $content): ?>
                            <tr>
                                <td>
                                    <code><?php echo $content['content_key']; ?></code>
                                    <br>
                                    <small class="text-muted"><?php echo $content['content_type']; ?></small>
                                 </td>
                                <td>
                                    <?php 
                                    $preview = strip_tags($content['content_value']);
                                    echo strlen($preview) > 100 ? substr($preview, 0, 100) . '...' : $preview;
                                    ?>
                                 </td>
                                <td><?php echo $content['content_type']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning edit-content" 
                                            data-key="<?php echo $content['content_key']; ?>"
                                            data-value="<?php echo htmlspecialchars($content['content_value']); ?>"
                                            data-type="<?php echo $content['content_type']; ?>"
                                            data-category="<?php echo $content['category']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline-block;" onsubmit="return confirm('Delete this content?')">
                                        <input type="hidden" name="content_key" value="<?php echo $content['content_key']; ?>">
                                        <button type="submit" name="delete_content" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                 </td>
                             </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Edit Content Modal -->
<div class="modal fade" id="editContentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Content</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="content_key" id="edit_content_key">
                <input type="hidden" name="update_content" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Content Key</label>
                        <input type="text" id="edit_key_display" class="form-control" disabled>
                    </div>
                    <div class="mb-3">
                        <label>Content Value</label>
                        <textarea name="content_value" id="edit_content_value" class="form-control" rows="6" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Content Type</label>
                        <select name="content_type" id="edit_content_type" class="form-select">
                            <option value="text">Text</option>
                            <option value="html">HTML</option>
                            <option value="json">JSON</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Category</label>
                        <input type="text" name="category" id="edit_category" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Content</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.edit-content').click(function() {
        $('#edit_content_key').val($(this).data('key'));
        $('#edit_key_display').val($(this).data('key'));
        $('#edit_content_value').val($(this).data('value'));
        $('#edit_content_type').val($(this).data('type'));
        $('#edit_category').val($(this).data('category'));
        $('#editContentModal').modal('show');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>