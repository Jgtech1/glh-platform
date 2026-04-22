<?php
session_start();
require_once '../classes/User.php';
require_once '../classes/ContentManager.php';

$userObj = new User();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../public/login.php');
    exit();
}

$contentManager = new ContentManager();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update') {
            $contentManager->set($_POST['content_key'], $_POST['content_value'], $_POST['content_type'], $_POST['category']);
            $success = "Content updated successfully!";
        } elseif ($_POST['action'] == 'delete') {
            $contentManager->deleteContent($_POST['content_key']);
            $success = "Content deleted successfully!";
        }
    }
}

$contents = $contentManager->getAllContent();
$categories = $contentManager->getCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Dynamic Content - GLH Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-user-shield"></i> Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="manage-content.php">Manage Content</a>
                <a class="nav-link" href="manage-currency.php">Manage Currency</a>
                <a class="nav-link" href="users.php">Users</a>
                <a class="nav-link" href="products.php">Products</a>
                <a class="nav-link" href="orders.php">Orders</a>
                <a class="nav-link" href="../public/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Add/Edit Dynamic Content</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="update">
                            <div class="mb-3">
                                <label>Content Key</label>
                                <input type="text" name="content_key" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Content Value</label>
                                <textarea name="content_value" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label>Content Type</label>
                                <select name="content_type" class="form-control">
                                    <option value="text">Text</option>
                                    <option value="html">HTML</option>
                                    <option value="json">JSON</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Category</label>
                                <input type="text" name="category" class="form-control" value="general">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Content</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>All Dynamic Content</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr><th>Key</th><th>Value</th><th>Category</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contents as $content): ?>
                                <tr>
                                    <td><?php echo $content['content_key']; ?></td>
                                    <td><?php echo substr($content['content_value'], 0, 50); ?>...</td>
                                    <td><?php echo $content['category']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-content" 
                                                data-key="<?php echo $content['content_key']; ?>"
                                                data-value="<?php echo htmlspecialchars($content['content_value']); ?>"
                                                data-type="<?php echo $content['content_type']; ?>"
                                                data-category="<?php echo $content['category']; ?>">
                                            Edit
                                        </button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="content_key" value="<?php echo $content['content_key']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this content?')">Delete</button>
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
    </div>

    <script>
    $('.edit-content').click(function() {
        var key = $(this).data('key');
        var value = $(this).data('value');
        var type = $(this).data('type');
        var category = $(this).data('category');
        
        $('input[name="content_key"]').val(key);
        $('textarea[name="content_value"]').val(value);
        $('select[name="content_type"]').val(type);
        $('input[name="category"]').val(category);
        
        $('html, body').animate({
            scrollTop: 0
        }, 500);
    });
    </script>
</body>
</html>