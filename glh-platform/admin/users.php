<?php
require_once 'includes/header.php';

// Handle user deletion
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    $userObj->deleteUser($userId);
    echo '<script>Swal.fire("Deleted!", "User deleted successfully", "success").then(() => { window.location.href = "users.php"; });</script>';
}

// Handle role update
if (isset($_POST['update_role'])) {
    $userId = (int)$_POST['user_id'];
    $role = $_POST['role'];
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ? AND role != 'admin'");
    $stmt->execute([$role, $userId]);
    echo '<script>Swal.fire("Updated!", "User role updated", "success").then(() => { location.reload(); });</script>';
}

// Get all users
$stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<div class="table-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5><i class="fas fa-users"></i> All Users</h5>
        <button class="btn btn-primary" onclick="showAddUserModal()">
            <i class="fas fa-plus"></i> Add New User
        </button>
    </div>
    
    <div class="table-responsive">
        <table id="usersTable" class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Loyalty Points</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                    <td>
                        <?php if ($user['role'] != 'admin'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <select name="role" onchange="this.form.submit()" class="form-select form-select-sm" style="width: auto; display: inline-block;">
                                <option value="customer" <?php echo $user['role'] == 'customer' ? 'selected' : ''; ?>>Customer</option>
                                <option value="producer" <?php echo $user['role'] == 'producer' ? 'selected' : ''; ?>>Producer</option>
                            </select>
                            <input type="hidden" name="update_role" value="1">
                        </form>
                        <?php else: ?>
                            <span class="badge bg-danger">Admin</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $user['loyalty_points']; ?></td>
                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="viewUser(<?php echo $user['id']; ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if ($user['role'] != 'admin'): ?>
                            <button class="btn btn-sm btn-danger" onclick="confirmDelete('users.php?delete=<?php echo $user['id']; ?>', 'Delete this user?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="ajax/add-user.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Role *</label>
                        <select name="role" class="form-control" required>
                            <option value="customer">Customer</option>
                            <option value="producer">Producer</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        pageLength: 25,
        order: [[0, 'desc']],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries"
        }
    });
});

function showAddUserModal() {
    $('#addUserModal').modal('show');
}

function viewUser(userId) {
    window.location.href = 'profile.php?id=' + userId;
}
</script>

<?php require_once 'includes/footer.php'; ?>