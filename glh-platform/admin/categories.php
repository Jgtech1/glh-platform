<?php
require_once 'includes/header.php';

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $icon = trim($_POST['icon']);
        $parent_id = $_POST['parent_id'] ?: null;
        
        $stmt = $conn->prepare("INSERT INTO categories (name, description, icon, parent_id, status) VALUES (?, ?, ?, ?, 'active')");
        if ($stmt->execute([$name, $description, $icon, $parent_id])) {
            echo '<script>Swal.fire("Success!", "Category added successfully!", "success").then(() => { location.reload(); });</script>';
        }
    } elseif (isset($_POST['update_category'])) {
        $id = $_POST['category_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $icon = trim($_POST['icon']);
        $parent_id = $_POST['parent_id'] ?: null;
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, icon = ?, parent_id = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$name, $description, $icon, $parent_id, $status, $id])) {
            echo '<script>Swal.fire("Success!", "Category updated successfully!", "success").then(() => { location.reload(); });</script>';
        }
    } elseif (isset($_POST['delete_category'])) {
        $id = $_POST['category_id'];
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$id])) {
            echo '<script>Swal.fire("Deleted!", "Category deleted successfully!", "success").then(() => { location.reload(); });</script>';
        }
    }
}

// Get all categories
$stmt = $conn->prepare("SELECT * FROM categories ORDER BY parent_id, name");
$stmt->execute();
$categories = $stmt->fetchAll();

// Build category tree
function buildCategoryTree($categories, $parentId = 0, $level = 0) {
    $tree = [];
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parentId) {
            $category['level'] = $level;
            $tree[] = $category;
            $tree = array_merge($tree, buildCategoryTree($categories, $category['id'], $level + 1));
        }
    }
    return $tree;
}

$categoryTree = buildCategoryTree($categories);
?>

<div class="row">
    <div class="col-md-5">
        <div class="table-container">
            <h5><i class="fas fa-plus"></i> Add New Category</h5>
            <form method="POST">
                <div class="mb-3">
                    <label>Category Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label>Icon (Font Awesome class)</label>
                    <input type="text" name="icon" class="form-control" placeholder="fa-apple-alt">
                    <small class="text-muted">Example: fa-apple-alt, fa-carrot, fa-cheese</small>
                </div>
                <div class="mb-3">
                    <label>Parent Category</label>
                    <select name="parent_id" class="form-select">
                        <option value="">None (Top Level)</option>
                        <?php foreach ($categoryTree as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>">
                                <?php echo str_repeat('--', $cat['level']) . ' ' . htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
            </form>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="table-container">
            <h5><i class="fas fa-tags"></i> All Categories</h5>
            <div class="table-responsive">
                <table id="categoriesTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Icon</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoryTree as $category): ?>
                        <tr>
                            <td><?php echo $category['id']; ?></td>
                            <td><i class="fas <?php echo $category['icon'] ?: 'fa-tag'; ?>"></i></td>
                            <td>
                                <?php echo str_repeat('&nbsp;&nbsp;&nbsp;', $category['level']); ?>
                                <?php if ($category['level'] > 0): ?>
                                    <i class="fas fa-level-down-alt fa-rotate-90"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($category['name']); ?>
                             </td>
                            <td><?php echo htmlspecialchars(substr($category['description'], 0, 50)); ?>...</td>
                            <td>
                                <span class="badge <?php echo $category['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo ucfirst($category['status']); ?>
                                </span>
                             </td>
                            <td>
                                <button class="btn btn-sm btn-warning edit-category" 
                                        data-id="<?php echo $category['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                        data-desc="<?php echo htmlspecialchars($category['description']); ?>"
                                        data-icon="<?php echo $category['icon']; ?>"
                                        data-parent="<?php echo $category['parent_id']; ?>"
                                        data-status="<?php echo $category['status']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display: inline-block;" onsubmit="return confirm('Delete this category?')">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
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
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Category Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Icon</label>
                        <input type="text" name="icon" id="edit_icon" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Parent Category</label>
                        <select name="parent_id" id="edit_parent_id" class="form-select">
                            <option value="">None (Top Level)</option>
                            <?php foreach ($categoryTree as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo str_repeat('--', $cat['level']) . ' ' . htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_category" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#categoriesTable').DataTable({
        pageLength: 25,
        order: [[0, 'asc']]
    });
    
    $('.edit-category').click(function() {
        $('#edit_category_id').val($(this).data('id'));
        $('#edit_name').val($(this).data('name'));
        $('#edit_description').val($(this).data('desc'));
        $('#edit_icon').val($(this).data('icon'));
        $('#edit_parent_id').val($(this).data('parent'));
        $('#edit_status').val($(this).data('status'));
        $('#editCategoryModal').modal('show');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>