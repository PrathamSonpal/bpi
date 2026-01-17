<?php
session_start();
$currentPage = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Orders - Bhavesh Plastic Industries</title>
  <link rel="icon" href="Bhavesh Plastic Industries.png" type="image/x-icon">
  <link rel="stylesheet" href="style_order.css?v=<?php echo filemtime('style_order.css'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="dashboard">

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
      <a href="order_page.php" class="active">Orders</a>
      <a href="register-item.php">Register Item</a>
      <a href="logout.php" class="logout-btn">Logout</a>
    </nav>
  </header>

  <main class="main-content">

    <div class="card action-card">
      <h3><i class="fas fa-filter"></i> Filter Orders</h3>
      <div class="filter-section">
        <select id="filterCustomer">
          <option value="">All Customers</option>
        </select>
        <select id="filterStatus">
          <option value="">All Status</option>
        </select>
        <button class="btn-primary" id="applyFilters"><i class="fas fa-search"></i> Apply</button>
        <button class="btn-secondary" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
      </div>
    </div>

    <div class="card">
      <h3><i class="fas fa-list"></i> Orders</h3>
      <div class="table-wrapper">
        <table class="log-table" id="ordersTable">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Customer</th>
              <th>Date</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="5">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<!-- Modal -->
<div id="details-modal">
  <div class="modal-content">
    <span class="close-btn" id="modalClose">&times;</span>
    <h3>Order Details</h3>
    <div id="modalBody">Loading...</div>
  </div>
</div>
    
	<footer class="footer">
    	<p>Created & Developed by <strong>Pratham P Sonpal</strong></p>
    </footer>

<script>
function toggleMenu() {
    document.querySelector('.nav-bar').classList.toggle('active');
}

async function fetchJSON(url) {
    const res = await fetch(url + (url.includes('?') ? '&' : '?') + 't=' + new Date().getTime());
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}

let allOrders = [];

async function loadAndRenderOrders() {
    const tbody = document.querySelector('#ordersTable tbody');
    tbody.innerHTML = '<tr><td colspan="5">Loading...</td></tr>';
    try {
        const fetchedOrders = await fetchJSON('get_orders.php');
        if (!Array.isArray(fetchedOrders)) throw new Error("Invalid data");
        allOrders = fetchedOrders;
        populateCustomerFilter();
        populateStatusFilter();
        applyCurrentFilters();
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" style="color:red;">Error loading: ${err.message}</td></tr>`;
    }
}

function populateCustomerFilter() {
    const sel = document.getElementById('filterCustomer');
    const val = sel.value;
    sel.innerHTML = '<option value="">All Customers</option>';
    [...new Set(allOrders.map(o => o.customer_name))].sort().forEach(c => {
        const opt = document.createElement('option');
        opt.value = c; opt.textContent = c;
        sel.appendChild(opt);
    });
    sel.value = val;
}

function populateStatusFilter() {
    const sel = document.getElementById('filterStatus');
    const val = sel.value;
    sel.innerHTML = '<option value="">All Status</option>';
    [...new Set(allOrders.map(o => o.status))].sort().forEach(s => {
        const opt = document.createElement('option');
        opt.value = s; opt.textContent = s;
        sel.appendChild(opt);
    });
    sel.value = val;
}

function renderOrders(orders) {
    const tbody = document.querySelector('#ordersTable tbody');
    tbody.innerHTML = '';
    if (!orders.length) {
        tbody.innerHTML = '<tr><td colspan="5">No orders found</td></tr>';
        return;
    }

    orders.forEach(o => {
        const tr = document.createElement('tr');
        const statusClass = (o.status || 'unknown').toLowerCase().replace(/\s+/g, '-');
        let buttons = '';

        if (o.status === 'Completed') {
            buttons = `
                <button class="btn-success dispatch-btn" data-order="${o.order_number}">Dispatch</button>
                <button class="btn-danger cancel-btn" data-order="${o.order_number}">Cancel</button>`;
        } else if (o.status === 'Dispatched') {
            buttons = `
                <button class="btn-info deliver-btn" data-order="${o.order_number}">Deliver</button>
                <button class="btn-danger cancel-btn" data-order="${o.order_number}">Cancel</button>`;
        } else if (o.status === 'Delivered') {
            buttons = `<span class="status-badge-simple status-delivered">Delivered</span>`;
        } else if (o.status === 'Cancelled') {
            buttons = `<span class="status-badge-simple status-cancelled">Cancelled</span>`;
        } else {
            buttons = `<button class="btn-danger cancel-btn" data-order="${o.order_number}">Cancel</button>`;
        }

        tr.innerHTML = `
            <td>${o.order_number}</td>
            <td>${o.customer_name}</td>
            <td>${o.date || ''}</td>
            <td><span class="status-badge status-${statusClass}">${o.status}</span></td>
            <td><button class="btn-primary view-btn" data-order="${o.order_number}">View</button> ${buttons}</td>
        `;
        tbody.appendChild(tr);
    });
}

function applyCurrentFilters() {
    const c = document.getElementById('filterCustomer').value;
    const s = document.getElementById('filterStatus').value;
    const filtered = allOrders.filter(o => (!c || o.customer_name === c) && (!s || o.status === s));
    renderOrders(filtered);
}

async function updateOrderStatus(orderNum, newStatus, buttonEl) {
    buttonEl.disabled = true;
    buttonEl.textContent = 'Updating...';
    try {
        const res = await fetch('update_order_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ order_number: orderNum, new_status: newStatus })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        await loadAndRenderOrders();
    } catch (err) {
        alert(err.message);
    }
}

document.getElementById('applyFilters').addEventListener('click', applyCurrentFilters);
document.getElementById('refreshBtn').addEventListener('click', loadAndRenderOrders);

const modal = document.getElementById('details-modal');
document.getElementById('modalClose').addEventListener('click', () => modal.style.display='none');
modal.addEventListener('click', e => { if (e.target === modal) modal.style.display='none'; });

document.addEventListener('click', async (e) => {
    const target = e.target.closest('button');
    if (!target) return;

    if (target.classList.contains('view-btn')) {
        const orderNum = target.dataset.order;
        modal.style.display = 'flex';
        const modalBody = document.getElementById('modalBody');
        modalBody.innerHTML = 'Loading...';
        try {
            const details = await fetchJSON(`get_orders.php?order=${orderNum}`);
            let html = `
                <h4>Order #${details.order_number}</h4>
                <p><strong>Customer:</strong> ${details.customer_name}</p>
                <p><strong>Date:</strong> ${details.date}</p>
                <h5>Items:</h5>
            `;
            if (details.items?.length) {
                html += '<ul>' + details.items.map(i => `<li>${i.quantity_ordered}x ${i.item_name}</li>`).join('') + '</ul>';
            }
            modalBody.innerHTML = html;
        } catch {
            modalBody.innerHTML = '<p style="color:red;">Error loading details</p>';
        }
    } else if (target.classList.contains('dispatch-btn')) {
        const num = target.dataset.order;
        if (confirm(`Dispatch order #${num}?`)) await updateOrderStatus(num, 'DISPATCHED', target);
    } else if (target.classList.contains('deliver-btn')) {
        const num = target.dataset.order;
        if (confirm(`Mark order #${num} as delivered?`)) await updateOrderStatus(num, 'DELIVERED', target);
    } else if (target.classList.contains('cancel-btn')) {
        const num = target.dataset.order;
        if (confirm(`Cancel order #${num}? This cannot be undone.`)) await updateOrderStatus(num, 'CANCELLED', target);
    }
});

document.addEventListener('DOMContentLoaded', loadAndRenderOrders);
</script>
</body>
</html>
