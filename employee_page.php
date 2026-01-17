<?php
session_start();
if (!isset($_SESSION['loggedIn']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html?error=unauthorized");
    exit();
}

// DB Connection
$conn = new mysqli(
    "sql100.infinityfree.com",
    "if0_39812412",
    "Bpiapp0101",
    "if0_39812412_bpi_stock"
);
if ($conn->connect_error) die("DB Connection failed");
$conn->set_charset("utf8mb4");

// Load Users
$users = [];
$res = $conn->query("
    SELECT id, full_name, username, role, mobile, email, status, created_at
    FROM users
    ORDER BY created_at DESC
");
while ($row = $res->fetch_assoc()) {
    $users[] = $row;
}
$res->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Employee Management | BPI</title>
    <link rel="icon" href="Bhavesh Plastic Industries.png" type="image/x-icon">
    <link rel="stylesheet" href="style_emp.css?v=1.0.3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

<div class="page-wrapper"><!-- ✅ TOP WRAPPER -->

  <div class="dashboard">

    <header class="header-container">
      <div class="logo-section">
        <img src="Bhavesh Plastic Industries.png" alt="BPI Logo">
        <div class="header-title">
          <h2>Bhavesh Plastic Industries</h2>
          <p>Employee Management</p>
        </div>
      </div>
      <nav class="nav-bar">
        <a href="index.php">Dashboard</a>
        <a href="logout.php" class="logout-btn">Logout</a>
      </nav>
    </header>

    <main class="main-content">

      <?php if (isset($_GET['success'])): ?>
        <div class="form-msg success"><?php echo htmlspecialchars($_GET['success']); ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['error'])): ?>
        <div class="form-msg error"><?php echo htmlspecialchars($_GET['error']); ?></div>
      <?php endif; ?>

      <div class="actions-row">
        <button class="btn-add" onclick="openModal('addEmployeeModal')">
          <i class="fas fa-user-plus"></i> Add Employee
        </button>
      </div>

      <!-- ADD EMPLOYEE MODAL -->
      <div id="addEmployeeModal" class="modal">
        <div class="modal-panel">
          <button class="close-x" onclick="closeModal('addEmployeeModal')">&times;</button>
          <h3>Add New Employee</h3>

          <form action="save_user.php" method="POST" class="form-grid">
          <div class="form-group full">
            <label>Full Name</label>
            <input type="text" name="full_name" placeholder="Employee full name" required>
          </div>

          <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Login username" required>
          </div>

          	<div class="form-group password-group">
              <label>Password</label>

              <div class="password-wrapper">
                <input type="password"
                       name="password"
                       id="add_password"
                       placeholder="Password"
                       required>

                <span class="toggle-password"
                      onclick="togglePassword('add_password')"
                      title="Show / Hide Password">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
            </div>


          <div class="form-group">
            <label>Mobile</label>
            <input type="text" name="mobile" placeholder="10-digit mobile number">
          </div>

          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Optional email address">
          </div>

          <div class="form-group full">
            <label>Role / Department</label>
            <select name="role" required>
              <option value="">Select Role</option>
              <option value="casting">Casting</option>
              <option value="turning">Turning</option>
              <option value="buff">Buffing</option>
              <option value="packing">Packing</option>
              <option value="admin">Admin</option>
            </select>
          </div>

          <!-- STATUS TOGGLE -->
          <div class="form-group full status-row">
            <label>User Status</label>
            <div class="status-toggle">
              <span>Disabled</span>
              <label class="switch">
                <input type="checkbox" name="status" value="active" checked>
                <span class="slider"></span>
              </label>
              <span>Active</span>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-save">
              <i class="fas fa-check"></i> Create Employee
            </button>
            <button type="button" class="btn-cancel" onclick="closeModal('addEmployeeModal')">
              Cancel
            </button>
          </div>

        </form>
		</div>
      </div>
        
		<div id="editEmployeeModal" class="modal">
          <div class="modal-panel">
            <button class="close-x" onclick="closeModal('editEmployeeModal')">&times;</button>
            <h3>Edit Employee</h3>

            <form action="update_user.php" method="POST" class="form-grid">
              <input type="hidden" name="id" id="edit_id">

              <div class="form-group full">
                <label>Full Name</label>
                <input type="text" name="full_name" id="edit_full_name" required>
              </div>

              <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" id="edit_username" required>
              </div>

              <div class="form-group">
                <label>Mobile</label>
                <input type="text" name="mobile" id="edit_mobile">
              </div>

              <div class="form-group full">
                <label>Email</label>
                <input type="email" name="email" id="edit_email">
              </div>

              <div class="form-group full">
                <label>Role</label>
                <select name="role" id="edit_role" required>
                  <option value="casting">Casting</option>
                  <option value="turning">Turning</option>
                  <option value="buff">Buffing</option>
                  <option value="packing">Packing</option>
                  <option value="admin">Admin</option>
                </select>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-save">Update</button>
                <button type="button" class="btn-cancel" onclick="closeModal('editEmployeeModal')">Cancel</button>
              </div>
            </form>
          </div>
        </div>

      <!-- EMPLOYEE TABLE -->
      <div class="table-wrapper">
        <table class="log-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Username</th>
              <th>Role</th>
              <th>Mobile</th>
              <th>Email</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($users)): ?>
            <tr><td colspan="7">No users found</td></tr>
          <?php else: foreach ($users as $u): ?>
            <tr>
              <td><?php echo htmlspecialchars($u['full_name'] ?? '-'); ?></td>
              <td><?php echo htmlspecialchars($u['username']); ?></td>
              <td><?php echo strtoupper(htmlspecialchars($u['role'])); ?></td>
              <td><?php echo htmlspecialchars($u['mobile'] ?? '-'); ?></td>
              <td><?php echo htmlspecialchars($u['email'] ?? '-'); ?></td>
              <td>
                <span class="<?php echo $u['status']=='active'?'status-green':'status-red'; ?>">
                  <?php echo ucfirst($u['status']); ?>
                </span>
              </td>
              <td class="action-cell">
                  <?php if ($u['id'] != $_SESSION['user_id']): ?>

                    <!-- Status Toggle -->
                    <label class="switch" title="Enable / Disable User">
                      <input type="checkbox"
                             <?php echo $u['status']=='active'?'checked':''; ?>
                             onchange="toggleStatus(<?php echo $u['id']; ?>)">
                      <span class="slider"></span>
                    </label>

                    <!-- Edit Button -->
                    <button class="btn-icon btn-edit"
                      onclick="openEditModal(
                        <?php echo $u['id']; ?>,
                        '<?php echo htmlspecialchars($u['full_name'],ENT_QUOTES); ?>',
                        '<?php echo htmlspecialchars($u['username'],ENT_QUOTES); ?>',
                        '<?php echo $u['role']; ?>',
                        '<?php echo htmlspecialchars($u['mobile'],ENT_QUOTES); ?>',
                        '<?php echo htmlspecialchars($u['email'],ENT_QUOTES); ?>'
                      )"
                      title="Edit Employee">
                      <i class="fas fa-edit"></i>
                    </button>

                    <!-- Delete Button -->
                    <button class="btn-icon btn-delete"
                      onclick="deleteUser(<?php echo $u['id']; ?>)"
                      title="Delete Employee">
                      <i class="fas fa-trash"></i>
                    </button>

                  <?php else: ?>
                    <em>Current User</em>
                  <?php endif; ?>

                </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

    </main>
  </div>

  <footer class="footer">
    <p>Created & Developed by <strong>Pratham P Sonpal</strong></p>
  </footer>

</div>

<script>
function openModal(id){ document.getElementById(id).style.display='flex'; }
    
function closeModal(id){ document.getElementById(id).style.display='none'; }
    
function toggleStatus(id){
  if(confirm("Change employee status?")){
    window.location.href = "toggle_user.php?id=" + id;
  }
}
    
function openEditModal(id, name, username, role, mobile, email){
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_full_name').value = name;
  document.getElementById('edit_username').value = username;
  document.getElementById('edit_role').value = role;
  document.getElementById('edit_mobile').value = mobile;
  document.getElementById('edit_email').value = email;
  openModal('editEmployeeModal');
}

function deleteUser(id){
  if(confirm("This will permanently delete the employee. Continue?")){
    window.location.href = "delete_user.php?id=" + id;
  }
}   
    
function togglePassword(id) {
  const field = document.getElementById(id);
  const icon  = event.currentTarget.querySelector('i');

  if (field.type === "password") {
    field.type = "text";
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    field.type = "password";
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}
    
</script>

</body>
</html>
<?php $conn->close(); ?>
