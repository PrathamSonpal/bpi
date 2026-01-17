<?php
session_start();
$currentPage = basename($_SERVER['PHP_SELF']); // Get current filename
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Bhavesh Plastic Industries</title>
  <link rel="icon" href="Bhavesh Plastic Industries.png" type="image/x-icon">
  <link rel="stylesheet" href="style_db.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

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
    <a href="logout.php" class="logout-btn">Logout</a>
  </nav>
</header>
    
<div class="main-content">
    
    <div class="department-section">
        <h2>Factory Departments</h2>
        <div class="dept-grid">
          <a href="casting_page.php"><div class="dept-card">Casting</div></a>
          <!--<a href="cut_bend_page.php"><div class="dept-card">Cut & Bend</div></a>-->
          <a href="turning_page.php"><div class="dept-card">Turning</div></a>
          <a href="buff_page.php"><div class="dept-card">Buff</div></a>
          <a href="packing_page.php"><div class="dept-card">Packing</div></a>
          <a href="employee_page.php"><div class="dept-card">Employees</div></a>
          <a href="attendance_page.php"><div class="dept-card">Attendance</div></a>
          <a href="salary_page.php"><div class="dept-card">Salary</div></a>
        </div>
    </div>
	<br><hr><br> 
    
  <div class="stats-grid">
    <div class="stat-card">
      <h3>Total Stock Items</h3>
      <table class="stat-table" id="totalStockTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Size</th>
            <th>Material</th>
            <th>Stock</th>
          </tr>
        </thead>
        <tbody>
          <tr><td colspan="5">Loading...</td></tr>
        </tbody>
      </table>
    </div>

    <div class="stat-card">
      <h3>Pending Orders</h3>
      <p id="pendingOrders">--</p>
    </div>

    <div class="stat-card">
      <h3>Low Stock Alerts</h3>
      <table class="stat-table" id="lowStockTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Stock</th>
          </tr>
        </thead>
        <tbody>
          <tr><td colspan="3">Loading...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="highlights">
    <div class="highlight-box">
      <h3>Recent Orders</h3>
      <ul id="recentOrders"><li>Loading...</li></ul>
    </div>
    <div class="highlight-box">
      <h3>Recent Sales</h3>
      <ul id="recentSales"><li>Loading...</li></ul>
    </div>
  </div>

  <div class="scroll-popup">
    <button class="scroll-main" id="scrollToggle">⇵</button>
    <div class="scroll-options" id="scrollOptions">
      <button class="scroll-btn" id="scrollUpBtn">⬆</button>
      <button class="scroll-btn" id="scrollDownBtn">⬇</button>
    </div>
  </div>
    
  	<!-- Floating Export Button -->
    <button class="export-float" onclick="window.location.href='export_excel.php'">
      <i class="fas fa-file-export"></i>
    </button>

</div>
    
<footer class="footer">
  <p>Created & Developed by <strong>Pratham P Sonpal</strong></p>
</footer>

<script>
function toggleMenu() {
  const nav = document.querySelector('.nav-bar');
  nav.classList.toggle('active');
}

document.addEventListener('DOMContentLoaded', function() {

  async function fetchJSON(url) {
    const res = await fetch(url);
    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
    return res.json();
  }

  async function loadDashboard() {
      try {
        // 1. Fetch all required data
        // We replace 'get_items.php' with 'get_stock_balance.php' for accurate math
        const [inventory, ordersData, stockLog, salesLog] = await Promise.all([
          fetchJSON('get_stock_balance.php'),
          fetchJSON('get_pending_orders.php'),
          fetchJSON('get_stock_log.php'),
          fetchJSON('get_sales_log.php')
        ]);

        const orders = (ordersData && Array.isArray(ordersData.orders)) ? ordersData.orders : [];

        // --- POPULATE TOTAL STOCK TABLE ---
        const totalStockTable = document.getElementById('totalStockTable').querySelector('tbody');
        totalStockTable.innerHTML = '';

        inventory.forEach(item => {
          const tr = document.createElement('tr');
          // item.balance comes directly from the SQL SUM calculation
          tr.innerHTML = `
            <td>${item.id}</td>
            <td>${item.name}</td>
            <td>${item.size}</td>
            <td>${item.material}</td>
            <td><strong>${item.balance}</strong></td>
          `;
          totalStockTable.appendChild(tr);
        });

        // --- UPDATE PENDING ORDERS COUNT ---
        document.getElementById('pendingOrders').textContent = orders.length;

        // --- POPULATE LOW STOCK ALERTS ---
        const lowStockTable = document.getElementById('lowStockTable').querySelector('tbody');
        lowStockTable.innerHTML = '';

        const lowStockItems = inventory.filter(item => item.balance <= 1500);

        if (lowStockItems.length === 0) {
          lowStockTable.innerHTML = '<tr><td colspan="3">All stock levels adequate</td></tr>';
        } else {
          lowStockItems.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>${item.id}</td>
              <td>${item.name}</td>
              <td style="color: red; font-weight: bold;">${item.balance}</td>
            `;
            lowStockTable.appendChild(tr);
          });
        }

        // --- POPULATE RECENT ORDERS ---
        const recentOrdersList = document.getElementById('recentOrders');
        recentOrdersList.innerHTML = '';
        if (orders.length === 0) {
          recentOrdersList.innerHTML = '<li>No pending orders</li>';
        } else {
          // Show only the 5 most recent orders
          orders.slice(0, 5).forEach(o => {
            const li = document.createElement('li');
            li.textContent = `#${o.order_number} - ${o.customer_name}`;
            recentOrdersList.appendChild(li);
          });
        }

        // --- POPULATE RECENT SALES ---
        const recentSalesList = document.getElementById('recentSales');
        recentSalesList.innerHTML = '';
        if (!salesLog || salesLog.length === 0) {
          recentSalesList.innerHTML = '<li>No recent sales</li>';
        } else {
          // Sort sales by timestamp descending and take the top 5
          const sortedSales = [...salesLog].sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
          sortedSales.slice(0, 5).forEach(s => {
            const li = document.createElement('li');
            li.textContent = `${s.item_name} - ${s.quantity} pcs`;
            recentSalesList.appendChild(li);
          });
        }

      } catch (err) {
        console.error("Dashboard Load Error:", err);
        document.getElementById('totalStockTable').querySelector('tbody').innerHTML = '<tr><td colspan="5">Error loading data. Check console for details.</td></tr>';
        document.getElementById('lowStockTable').querySelector('tbody').innerHTML = '<tr><td colspan="3">Error loading data</td></tr>';
        document.getElementById('pendingOrders').textContent = '--';
      }
    }

  loadDashboard();

  const scrollToggle = document.getElementById('scrollToggle');
  const scrollOptions = document.getElementById('scrollOptions');
  const scrollUpBtn = document.getElementById('scrollUpBtn');
  const scrollDownBtn = document.getElementById('scrollDownBtn');

  scrollToggle.addEventListener('click', () => {
    scrollOptions.classList.toggle('show');
  });

  scrollUpBtn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
    scrollOptions.classList.remove('show');
  });

  scrollDownBtn.addEventListener('click', () => {
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    scrollOptions.classList.remove('show');
  });

  document.addEventListener('click', (e) => {
    if (!scrollToggle.contains(e.target) && !scrollOptions.contains(e.target)) {
      scrollOptions.classList.remove('show');
    }
  });

}); // ✅ closes DOMContentLoaded


</script>

</body>
</html>
