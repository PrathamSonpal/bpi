<?php
session_start();
$currentPage = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header("Location: login.html");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    echo "<script>alert('Access denied. Only admin can export data.'); window.location.href='index.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Export Data - Bhavesh Plastic Industries</title>
  <link rel="icon" href="Bhavesh Plastic Industries.png" type="image/x-icon">
  <link rel="stylesheet" href="style_ssl.css?v=<?php echo filemtime('style_ssl.css'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    .export-card {
      background: #fff;
      border-radius: 12px;
      padding: 1.5rem 2rem;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      max-width: 700px;
      margin: 30px auto;
    }
    .export-card h3 {
      color: #003366;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .export-card h3 i {
      color: #0059b3;
    }
    .export-section {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.2rem;
      margin-top: 1rem;
    }
    .checkbox-group {
      background: #f9fbff;
      border: 1px solid #e0e6f1;
      border-radius: 10px;
      padding: 1rem;
    }
    .checkbox-group h4 {
      color: #0059b3;
      margin-top: 0;
      margin-bottom: 0.8rem;
      font-size: 1rem;
    }
    .checkbox-group label {
      display: block;
      padding: 6px 0;
      cursor: pointer;
      font-size: 0.95rem;
      color: #333;
      transition: color 0.2s;
    }
    .checkbox-group label:hover {
      color: #0056b3;
    }
    .checkbox-group input[type="checkbox"] {
      transform: scale(1.2);
      margin-right: 6px;
      accent-color: #007bff;
    }
    .btn-export {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background: linear-gradient(135deg, #28a745, #20c997);
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 10px 20px;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 1.5rem;
    }
    .btn-export:hover {
      background: linear-gradient(135deg, #0056b3, #0096c7);
      transform: scale(1.05);
    }
    .select-all {
      text-align: right;
      margin-top: 1rem;
      font-size: 0.9rem;
      color: #333;
    }
    .select-all label {
      cursor: pointer;
    }
  </style>
</head>
<body>

<div class="dashboard">

  <!-- Header -->
  <header class="header-container">
    <div class="logo-section">
      <img src="Bhavesh Plastic Industries.png" alt="BPI Logo">
      <div class="header-title">
        <h2>Bhavesh Plastic Industries</h2>
        <p>Management System</p>
      </div>
    </div>
    <div class="hamburger" onclick="toggleMenu()">☰</div>
    <nav class="nav-bar" id="navMenu">
      <a href="index.php">Dashboard</a>
      <a href="stock_sales_log.php">Stock & Sales Log</a>
      <a href="order_page.php">Orders</a>
      <a href="register-item.php">Register Item</a>
      <a href="export_excel.php" class="active">Export Data</a>
      <a href="logout.php" class="logout-btn">Logout</a>
    </nav>
  </header>

  <!-- Main Export Content -->
  <main class="main-content">
    <div class="export-card">
      <h3><i class="fas fa-file-export"></i> Export Reports & Department Histories</h3>
      <form action="generate_export.php" method="POST">
        <div class="export-section">
          <!-- Core Reports -->
          <div class="checkbox-group">
            <h4><i class="fas fa-database"></i> Core Reports</h4>
            <label><input type="checkbox" name="export[]" value="items"> Items (Product List)</label>
            <label><input type="checkbox" name="export[]" value="stock_log"> Stock Log</label>
            <label><input type="checkbox" name="export[]" value="sales_log"> Sales Log</label>
            <label><input type="checkbox" name="export[]" value="orders"> Orders</label>
          </div>

          <!-- Department Logs -->
          <div class="checkbox-group">
            <h4><i class="fas fa-industry"></i> Department Recent Histories</h4>
            <label><input type="checkbox" name="export[]" value="casting_history"> Casting</label>
            <label><input type="checkbox" name="export[]" value="cutbend_history"> Cut & Bend</label>
            <label><input type="checkbox" name="export[]" value="turning_history"> Turning</label>
            <label><input type="checkbox" name="export[]" value="buff_history"> Buff</label>
            <label><input type="checkbox" name="export[]" value="packing_history"> Packing</label>
          </div>
        </div>

        <div class="select-all">
          <label><input type="checkbox" id="selectAll"> <strong>Select All</strong></label>
        </div>

        <button type="submit" class="btn-export"><i class="fas fa-download"></i> Generate Export</button>
      </form>
    </div>
  </main>
</div>

<script>
function toggleMenu() {
  document.getElementById('navMenu').classList.toggle('active');
}

document.getElementById('selectAll').addEventListener('change', function() {
  document.querySelectorAll('input[name="export[]"]').forEach(cb => cb.checked = this.checked);
});
</script>

</body>
</html>
