<?php
// Ensure no whitespace before this line
session_start();

// 1. Security & Session Check
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header("Location: login.html");
    exit();
}

// 2. Safe Role Check
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
if ($userRole !== "admin") {
    echo "<script>alert('Access denied. Admin only.'); window.location.href='index.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>BPI Stock & Sales</title>
    <meta charset="UTF-8">
    <link rel="icon" href="Bhavesh Plastic Industries.png" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <link rel="stylesheet" href="style_ssl.css?v=<?php echo (file_exists('style_ssl.css') ? filemtime('style_ssl.css') : time()); ?>">
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
            <a href="stock_sales_log.php" class="active">Stock & Sales Log</a>
            <a href="order_page.php">Orders</a>
            <a href="register-item.php">Register Item</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>
    </header>

    <main class="main-content">

        <div id="lowStockContainer" class="alert-card" style="display:none;">
            <div class="alert-header">
                <h4><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h4>
                <button id="closeLowStock" class="close-btn">&times;</button>
            </div>
            <div id="lowStockList"></div>
        </div>

        <div class="top-action-buttons">
            <button onclick="openModal('addStockModal')" class="btn-main-action">
                <div class="btn-icon-large">
                    <i class="fas fa-dolly"></i>
                </div>
                <div class="btn-content">
                    <span class="btn-title">Add Stock</span>
                </div>
            </button>

            <button onclick="openModal('recordSaleModal')" class="btn-main-action">
                <div class="btn-icon-large">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="btn-content">
                    <span class="btn-title">Record Sale</span>
                </div>
            </button>

            <button onclick="openModal('handleReportModal')" class="btn-main-action">
                <div class="btn-icon-large">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="btn-content">
                    <span class="btn-title">Handle Report</span>
                </div>
            </button>
        </div>

        <div class="log-container">
            <nav class="log-nav">
                <button id="showStock" onclick="showLog('stock')" class="active"><i class="fas fa-box-open"></i><span>Stock In</span></button>
                <button id="showSales" onclick="showLog('sales')"><i class="fas fa-receipt"></i><span>Sales</span></button>
                <button id="showBalance" onclick="showLog('balance')"><i class="fas fa-balance-scale"></i><span>Balance</span></button>
            </nav>

            <div id="balanceToolbar" class="balance-toolbar" style="display:none;">
                <div class="filter-group">
                    <i class="fas fa-filter" style="color:#64748b;"></i>
                    <select id="materialDropdown" onchange="onMaterialChange()">
                        <option value="">All Materials</option>
                    </select>
                </div>

                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="balanceSearch" placeholder="Search item..." oninput="filterBalanceTable()" class="search-box">
                </div>
            </div>

            <div id="stockSearchContainer" style="display:none; padding: 15px;">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="stockSearch" placeholder="Search stock log..." oninput="filterStockTable()" class="search-box">
                </div>
            </div>

            <div id="salesSearchContainer" style="display:none; padding: 15px;">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="salesSearch" placeholder="Search sales log..." oninput="filterSalesTable()" class="search-box">
                </div>
            </div>

            <div class="table-wrapper">
                <table id="stockTable">
                    <thead>
                    <tr>
                        <th>Item Details</th>
                        <th>Qty</th><th>By</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <table id="salesTable" style="display:none;">
                    <thead>
                    <tr>
                        <th>Item Details</th>
                        <th>Party</th>
                        <th>Qty</th>
                        <th>By</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <table id="balanceTable" style="display:none;">
                    <thead>
                    <tr>
                        <th>Item Details</th>
                        <th>Balance Qty</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <!-- Add Stock Modal -->
        <div id="addStockModal" class="modal">
            <div class="modal-panel">
                <div class="modal-header">
                    <h3><i class="fas fa-dolly"></i> Add New Stock</h3>
                    <button class="close-x" onclick="closeModal('addStockModal')">&times;</button>
                </div>

                <div class="modal-body">
                    <div class="input-group">
                        <label>Select Item</label>
                        <select id="itemName"><option value="">-- Select Item --</option></select>
                    </div>
                    <div class="input-group">
                        <label>Quantity</label>
                        <input type="number" id="itemQty" placeholder="Enter Quantity">
                    </div>
                </div>

                <div class="modal-footer">
                    <button onclick="closeModal('addStockModal')" class="btn-modal btn-cancel-modal">Cancel</button>
                    <button onclick="addStock()" class="btn-modal btn-submit">Save Stock</button>
                </div>
            </div>
        </div>

        <!-- Record Sale Modal -->
        <div id="recordSaleModal" class="modal">
            <div class="modal-panel">
                <div class="modal-header">
                    <h3><i class="fas fa-chart-line"></i> Record New Sale</h3>
                    <button class="close-x" onclick="closeModal('recordSaleModal')">&times;</button>
                </div>

                <div class="modal-body">
                    <div class="sale-type-group">
                        <input type="radio" id="typeFinished" name="saleType" value="finished" checked onchange="updateAllRowDropdowns()">
                        <label for="typeFinished" class="sale-option"><i class="fas fa-box"></i> Ready Stock</label>

                        <input type="radio" id="typeRaw" name="saleType" value="raw" onchange="updateAllRowDropdowns()">
                        <label for="typeRaw" class="sale-option"><i class="fas fa-cubes"></i> Casting</label>
                    </div>

                    <div class="input-group">
                        <label>Party Name</label>
                        <input type="text" id="partyName" list="partyList" placeholder="Start typing party name..." autocomplete="off">
                        <datalist id="partyList"></datalist>
                    </div>

                    <div class="input-group">
                        <label>Items Sold</label>
                        <div id="saleItemsContainer"></div>

                        <button type="button" class="btn-add-row" onclick="addSaleRow()">
                            <i class="fas fa-plus-circle"></i> Add Another Item
                        </button>
                    </div>
                </div>

                <div class="modal-footer">
                    <button onclick="closeModal('recordSaleModal')" class="btn-modal btn-cancel-modal">Cancel</button>
                    <button onclick="recordSale()" class="btn-modal btn-submit">Save Entire Sale</button>
                </div>
            </div>
        </div>

        <!-- Edit Stock Modal -->
        <div id="editStockModal" class="modal">
            <div class="modal-panel">
                <div class="modal-header">
                    <h3>Edit Stock Entry</h3>
                    <button class="close-x" onclick="closeModal('editStockModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="editStockForm">
                        <input type="hidden" id="editStockId" name="id">
                        <div class="input-group">
                            <label>Item Name</label>
                            <input type="text" id="editStockItemName" readonly style="background:#f1f5f9; color:#64748b;">
                        </div>
                        <div class="input-group">
                            <label>Quantity</label>
                            <input type="number" id="editStockQty" name="quantity" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-cancel-modal" onclick="closeModal('editStockModal')">Cancel</button>
                    <button type="button" class="btn-modal btn-submit" onclick="submitEditStock()">Update</button>
                </div>
            </div>
        </div>

        <!-- Edit Sale Modal -->
        <div id="editSaleModal" class="modal">
            <div class="modal-panel">
                <div class="modal-header">
                    <h3>Edit Sale Entry</h3>
                    <button class="close-x" onclick="closeModal('editSaleModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="editSaleForm">
                        <input type="hidden" id="editSaleId" name="id">
                        <div class="input-group">
                            <label>Item Name</label>
                            <input type="text" id="editSaleItemName" readonly style="background:#f1f5f9; color:#64748b;">
                        </div>
                        <div class="input-group">
                            <label>Party Name</label>
                            <input type="text" id="editSaleParty" name="party_name" required>
                        </div>
                        <div class="input-group">
                            <label>Quantity</label>
                            <input type="number" id="editSaleQty" name="quantity" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-cancel-modal" onclick="closeModal('editSaleModal')">Cancel</button>
                    <button type="button" class="btn-modal btn-submit" onclick="submitEditSale()">Update</button>
                </div>
            </div>
        </div>

        <!-- Handle Report Modal -->
        <div id="handleReportModal" class="modal">
            <div class="modal-panel report-modal-panel">

                <div class="modal-header">
                    <h3><i class="fas fa-chart-pie"></i> Analytics & Reports</h3>
                    <button class="close-x" onclick="closeModal('handleReportModal')">&times;</button>
                </div>

                <div class="modal-tabs">
                    <button class="tab-btn active" onclick="switchReportTab('item')">
                        <i class="fas fa-box"></i> Item Report
                    </button>
                    <button class="tab-btn" onclick="switchReportTab('party')">
                        <i class="fas fa-users"></i> Party History
                    </button>
                </div>

                <div class="modal-body">

                    <div id="tabItemContent" class="tab-content active">

                        <div class="report-controls">
                            <div class="control-group main-select">
                                <label>Select Item</label>
                                <select id="reportItemSelect" onchange="loadHandleReportDetailed()">
                                    <option value="">-- Choose Handle --</option>
                                </select>
                            </div>

                            <div class="control-group date-group">
                                <div class="date-input">
                                    <label>From</label>
                                    <input type="date" id="handleFromDate" onchange="applyHandleFilters()">
                                </div>
                                <div class="date-input">
                                    <label>To</label>
                                    <input type="date" id="handleToDate" onchange="applyHandleFilters()">
                                </div>
                            </div>

                            <div class="control-actions">
                                <button type="button" class="btn-icon-action btn-refresh" onclick="resetHandleFilters()" title="Reset">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>

                        <div id="handleSummary" class="stats-grid" style="display:none;">
                            <div class="stat-card blue">
                                <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
                                <div class="stat-info">
                                    <span class="stat-label">Stock In</span>
                                    <strong class="stat-value" id="repStockIn">0</strong>
                                </div>
                            </div>
                            <div class="stat-card green">
                                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                                <div class="stat-info">
                                    <span class="stat-label">Total Sold</span>
                                    <strong class="stat-value" id="repSold">0</strong>
                                </div>
                            </div>
                            <div class="stat-card purple">
                                <div class="stat-icon"><i class="fas fa-boxes"></i></div>
                                <div class="stat-info">
                                    <span class="stat-label">Current Balance</span>
                                    <strong class="stat-value" id="repBalance">0</strong>
                                </div>
                            </div>
                        </div>

                        <div class="report-data-grid">
    
                            <div class="data-section">
                                <div class="section-header">
                                    <h4>Stock In Log</h4>
                                    <span class="badge-header" style="color:#059669; background:#ecfdf5;">
                                        <i class="fas fa-arrow-down"></i> IN
                                    </span>
                                </div>
                                <div class="report-table-wrapper">
                                    <table class="report-table">
                                        <thead>
                                            <tr>
                                                <th><i class="far fa-calendar-alt"></i> Date</th>
                                                <th><i class="fas fa-cubes"></i> Qty</th>
                                                <th><i class="fas fa-user-cog"></i> Added By</th>
                                                <th><i class="fas fa-tag"></i> Type</th>
                                            </tr>
                                        </thead>
                                        <tbody id="handleStockTable">
                                            <tr><td colspan="4" class="report-empty">Select item to view</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="data-section">
                                <div class="section-header">
                                    <h4>Sales Log</h4>
                                    <span class="badge-header" style="color:#3b82f6; background:#eff6ff;">
                                        <i class="fas fa-arrow-up"></i> OUT
                                    </span>
                                </div>
                                <div class="report-table-wrapper">
                                    <table class="report-table">
                                        <thead>
                                            <tr>
                                                <th><i class="far fa-calendar-alt"></i> Date</th>
                                                <th><i class="fas fa-building"></i> Client</th>
                                                <th><i class="fas fa-cubes"></i> Qty</th>
                                                <th><i class="fas fa-user-tie"></i> Sold By</th>
                                                <th><i class="fas fa-info-circle"></i> Type</th>
                                            </tr>
                                        </thead>
                                        <tbody id="handleSalesTable">
                                            <tr><td colspan="5" class="report-empty">Select item to view</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div id="tabPartyContent" class="tab-content">

                        <div class="report-controls">
                            <div class="control-group main-select">
                                <label>Select Party</label>
                                <select id="reportPartySelect" onchange="loadPartyReportDetailed()">
                                    <option value="">-- Choose Party --</option>
                                </select>
                            </div>

                            <div class="control-group date-group">
                                <div class="date-input">
                                    <label>From</label>
                                    <input type="date" id="partyFromDate" onchange="applyPartyFilters()">
                                </div>
                                <div class="date-input">
                                    <label>To</label>
                                    <input type="date" id="partyToDate" onchange="applyPartyFilters()">
                                </div>
                            </div>

                            <div class="control-actions">
                                 <button type="button" class="btn-icon-action btn-refresh" onclick="resetPartyFilters()" title="Reset">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>

                        <div class="data-section full-width">
                            <div class="report-table-wrapper large-table">
                                <table class="report-table">
                                    <thead>
                                        <th><i class="far fa-calendar-alt"></i> Date</th>
                                        <th><i class="fas fa-box-open"></i> Item Details</th>
                                        <th><i class="fas fa-cubes"></i> Qty</th>
                                        <th><i class="fas fa-user-tie"></i> Sold By</th>
                                        <th><i class="fas fa-info-circle"></i> Type</th>
                                    </thead>
                                    <tbody id="partyReportTable">
                                        <tr><td colspan="5" class="report-empty">Select a party to view history</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div> <div class="modal-footer">
                    <button class="btn-modal btn-cancel-modal" onclick="closeModal('handleReportModal')">Close</button>
                </div>
            </div>
        </div>

    </main>

    <footer class="footer"><p>Created & Developed by <strong>Pratham P Sonpal</strong></p></footer>

</div>

<script>
const LOGGED_IN_USER_ID = <?php echo isset($_SESSION['user_id']) ? json_encode($_SESSION['user_id']) : 'null'; ?>;

// Global Data
let registeredItems = [];
let availableStock = [];
let stockLog = [];
let salesLog = [];

const $ = s => document.querySelector(s);

/* =========================================================
   DATA LOADER
   ========================================================= */
function fetchAndRenderLogs() {
    Promise.all([
        fetch("get_items.php").then(r => r.json()),
        fetch("get_available_items.php").then(r => r.json()),
        fetch("get_stock_log.php").then(r => r.json()),
        fetch("get_sales_log.php").then(r => r.json())
    ]).then(([items, available, stock, sales]) => {

        registeredItems = items || [];
        availableStock = available || [];
        stockLog = stock || [];
        salesLog = sales || [];

        populateAddStockDropdown();
        populatePartyRecommendations();

        const container = document.getElementById("saleItemsContainer");
        if(container.innerHTML.trim() === "") {
            addSaleRow();
        }

        renderStock(stockLog);
        renderSales(salesLog);
        renderBalance(registeredItems, stockLog, salesLog);
        populateMaterialFilter();
        populateHandleReportDropdowns();

    }).catch(err => {
        console.error("Error loading data:", err);
    });
}

function populateHandleReportDropdowns() {
    const itemDD = document.getElementById("reportItemSelect");
    const partyDD = document.getElementById("reportPartySelect");

    itemDD.innerHTML = '<option value="">-- Select Handle --</option>';
    registeredItems.forEach(i => {
        itemDD.add(new Option(`${i.name} - ${i.size} - ${i.material}`, i.id));
    });

    partyDD.innerHTML = '<option value="">-- Select Party --</option>';
    const parties = [...new Set(salesLog.map(s => s.party_name))].filter(Boolean).sort();
    parties.forEach(p => partyDD.add(new Option(p, p)));
}

/* =========================================================
   DATE RANGE HELPERS + BADGE HELPERS
   ========================================================= */
function parseDateOnly(ts) {
    if (!ts) return null;
    const d = new Date(ts.replace(" ", "T"));
    if (isNaN(d.getTime())) return null;
    return new Date(d.getFullYear(), d.getMonth(), d.getDate());
}

function getDateRange(fromId, toId) {
    const fromVal = document.getElementById(fromId)?.value;
    const toVal = document.getElementById(toId)?.value;

    const fromDate = fromVal ? new Date(fromVal + "T00:00:00") : null;
    const toDate = toVal ? new Date(toVal + "T00:00:00") : null;

    return { fromDate, toDate };
}

function isWithinRange(ts, fromDate, toDate) {
    const d = parseDateOnly(ts);
    if (!d) return true;
    if (fromDate && d < fromDate) return false;
    if (toDate && d > toDate) return false;
    return true;
}

function getTypeBadge(typeString) {
    const raw = (typeString || "").toLowerCase();

    // Check for raw/casted status
    if (raw === "raw" || raw.includes("casted")) {
        // CHANGED: 'report-badge' -> 'badge-pill'
        return `
          <span class="badge-pill badge-casted">
            <i class="fas fa-cubes"></i> CASTED
          </span>`;
    }

    // Default: Packed
    // CHANGED: 'report-badge' -> 'badge-pill'
    return `
      <span class="badge-pill badge-packed">
        <i class="fas fa-check-circle"></i> PACKED
      </span>`;
}

/* =========================================================
   HANDLE REPORT - DETAILED (WITH DATE RANGE)
   ========================================================= */
function loadHandleReportDetailed() {
    const itemId = document.getElementById("reportItemSelect").value;
    if (!itemId) return;

    const stockBody = document.getElementById("handleStockTable");
    const salesBody = document.getElementById("handleSalesTable");
    stockBody.innerHTML = "";
    salesBody.innerHTML = "";

    const { fromDate, toDate } = getDateRange("handleFromDate", "handleToDate");

    const stockItem = availableStock.find(i => String(i.id) === String(itemId));

    let packedBalance = 0;
    let castedBalance = 0;

    if (stockItem) {
        packedBalance = Number(stockItem.finished_stock) || 0;
        castedBalance = Number(stockItem.raw_stock) || 0;
    }

    const totalBalance = packedBalance + castedBalance;

    let stockInTotal = 0;
    let soldTotal = 0;

    // STOCK IN DETAILS (filtered)
    stockLog.forEach(s => {
        if (String(s.item_id) === String(itemId) && isWithinRange(s.timestamp, fromDate, toDate)) {
            const qty = Number(s.quantity) || 0;
            stockInTotal += qty;

            stockBody.innerHTML += `
              <tr>
                <td>${formatDate(s.timestamp)}</td>
                <td>${qty}</td>
                <td>${s.added_by_name || 'N/A'}</td>
                <td>${getTypeBadge(s.log_type || 'packed')}</td>
              </tr>`;
        }
    });

    // SALES DETAILS (filtered)
    salesLog.forEach(s => {
        if (String(s.item_id) === String(itemId) && isWithinRange(s.timestamp, fromDate, toDate)) {
            const qty = Number(s.quantity) || 0;
            soldTotal += qty;

            salesBody.innerHTML += `
              <tr>
                <td>${formatDate(s.timestamp)}</td>
                <td>${s.party_name}</td>
                <td>${qty}</td>
                <td>${s.sold_by_username || s.sold_by || 'N/A'}</td>
                <td>${getTypeBadge(s.sale_type === 'raw' ? 'casted' : 'packed')}</td>
              </tr>`;
        }
    });

    // Summary values follow filters
    document.getElementById("repStockIn").innerText = stockInTotal;
    document.getElementById("repSold").innerText = soldTotal;
    document.getElementById("repBalance").innerText = totalBalance;

    document.getElementById("handleSummary").style.display = "grid";

    if (!stockBody.innerHTML)
        stockBody.innerHTML = `<tr><td colspan="4">No stock entries in this date range</td></tr>`;
    if (!salesBody.innerHTML)
        salesBody.innerHTML = `<tr><td colspan="5">No sales entries in this date range</td></tr>`;
}

function applyHandleFilters() {
    loadHandleReportDetailed();
}

function resetHandleFilters() {
    document.getElementById("handleFromDate").value = "";
    document.getElementById("handleToDate").value = "";
    loadHandleReportDetailed();
}

/* =========================================================
   PARTY REPORT (WITH DATE RANGE)
   ========================================================= */
function loadPartyReportDetailed() {
    const party = document.getElementById("reportPartySelect").value;
    const tbody = document.getElementById("partyReportTable");
    tbody.innerHTML = "";

    if (!party) return;

    const { fromDate, toDate } = getDateRange("partyFromDate", "partyToDate");

    salesLog.forEach(s => {
        if (s.party_name === party && isWithinRange(s.timestamp, fromDate, toDate)) {
            const item = registeredItems.find(i => i.id == s.item_id);
            const itemDetails = item ? `${item.name} - ${item.size} <span style="color:#666; font-size:0.9em;">(${item.material})</span>` : 'Unknown Item';
            tbody.innerHTML += `
              <tr>
                <td>${formatDate(s.timestamp)}</td>
                <td>${itemDetails}</td>
                <td>${s.quantity}</td>
                <td>${s.sold_by_username || s.sold_by || 'N/A'}</td>
                <td>${getTypeBadge(s.sale_type === 'raw' ? 'casted' : 'packed')}</td>
              </tr>`;
        }
    });

    if (!tbody.innerHTML) {
        tbody.innerHTML = `<tr><td colspan="5">No purchases found in this date range</td></tr>`;
    }
}
    
// --- NEW TAB FUNCTION ---
function switchReportTab(tabName) {
    // 1. Remove active class from all buttons and contents
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

    // 2. Add active class to clicked button
    // Find button that calls this specific tabName
    const buttons = document.querySelectorAll('.tab-btn');
    if(tabName === 'item') buttons[0].classList.add('active');
    if(tabName === 'party') buttons[1].classList.add('active');

    // 3. Show Content
    if(tabName === 'item') {
        document.getElementById('tabItemContent').classList.add('active');
    } else {
        document.getElementById('tabPartyContent').classList.add('active');
    }
}

function applyPartyFilters() {
    loadPartyReportDetailed();
}

function resetPartyFilters() {
    document.getElementById("partyFromDate").value = "";
    document.getElementById("partyToDate").value = "";
    loadPartyReportDetailed();
}

/* =========================================================
   UI HELPERS
   ========================================================= */
function openModal(id) {
    const el = document.getElementById(id);
    el.style.display = 'flex';
    setTimeout(() => el.classList.add('show'), 10);
}

function closeModal(id) {
    const el = document.getElementById(id);
    el.classList.remove('show');

    setTimeout(() => {
        el.style.display = 'none';

        if(id === 'recordSaleModal') {
            document.getElementById('partyName').value = '';
            document.getElementById('typeFinished').checked = true;
            document.getElementById("saleItemsContainer").innerHTML = "";
            addSaleRow();
            updateAllRowDropdowns();
        }
        if(id === 'addStockModal') {
            document.getElementById('itemName').value = '';
            document.getElementById('itemQty').value = '';
        }
    }, 300);
}

function toggleMenu() {
    document.getElementById('navMenu').classList.toggle('active');
}

function formatDate(ts) {
    if (!ts || ts === "0000-00-00 00:00:00") return "N/A";
    const d = new Date(ts.replace(" ", "T"));
    if (isNaN(d.getTime())) return ts;
    return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
}

/* =========================================================
   PARTY RECOMMENDATIONS
   ========================================================= */
function populatePartyRecommendations() {
    const dataList = document.getElementById("partyList");
    if(!dataList) return;
    dataList.innerHTML = "";
    const parties = [...new Set(salesLog.map(s => s.party_name))].filter(Boolean).sort();
    parties.forEach(name => {
        const option = document.createElement("option");
        option.value = name;
        dataList.appendChild(option);
    });
}

/* =========================================================
   STOCK LOGIC
   ========================================================= */
function populateAddStockDropdown() {
    const el = document.getElementById("itemName");
    if(!el) return;
    el.innerHTML = '<option value="">-- Select Item --</option>';
    registeredItems.forEach(item => {
        const label = `${item.name} - ${item.size} - ${item.material}`;
        el.appendChild(new Option(label, item.id));
    });
}

function addStock() {
    const id = document.getElementById("itemName").value;
    const qty = document.getElementById("itemQty").value;
    if (!id || !qty) { alert("Select item and quantity"); return; }

    const fd = new FormData();
    fd.append('item_id', id);
    fd.append('quantity', qty);
    fd.append('user_id', LOGGED_IN_USER_ID);

    fetch('record_stock.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.success) { alert("Stock Added!"); location.reload(); }
            else { alert(res.message); }
        })
        .catch(e => { alert("Failed to add stock."); });
}

/* =========================================================
   MULTI-ITEM SALES LOGIC
   ========================================================= */
function getSaleOptionsHTML() {
    const radioRaw = document.getElementById("typeRaw");
    const isRawSelected = radioRaw.checked;

    const validItems = availableStock.filter(item => {
        return isRawSelected ? (Number(item.raw_stock) > 0) : (Number(item.finished_stock) > 0);
    });

    if (validItems.length === 0) return '<option value="">No Stock Available</option>';

    let html = '<option value="">Select Item</option>';
    validItems.forEach(item => {
        const stockToShow = isRawSelected ? item.raw_stock : item.finished_stock;
        const label = `${item.name} - ${item.size} (${item.material}) [Qty: ${stockToShow}]`;
        html += `<option value="${item.id}">${label}</option>`;
    });
    return html;
}

function addSaleRow() {
    const container = document.getElementById("saleItemsContainer");
    const div = document.createElement("div");
    div.className = "sale-row";

    div.innerHTML = `
        <select class="row-item-select">
            ${getSaleOptionsHTML()}
        </select>
        <input type="number" class="row-qty-input" placeholder="Qty" min="1">
        <button type="button" class="btn-remove-row" onclick="removeSaleRow(this)" title="Remove">
            <i class="fas fa-trash-alt"></i>
        </button>
    `;
    container.appendChild(div);
}

function removeSaleRow(btn) {
    const container = document.getElementById("saleItemsContainer");
    if(container.children.length > 1) {
        btn.parentElement.remove();
    } else {
        alert("At least one item is required.");
    }
}

function updateAllRowDropdowns() {
    const selects = document.querySelectorAll(".row-item-select");
    const newOptions = getSaleOptionsHTML();
    selects.forEach(select => {
        select.innerHTML = newOptions;
    });
}

function recordSale() {
    const party = document.getElementById("partyName").value;
    const radio = document.querySelector('input[name="saleType"]:checked');
    const saleType = radio ? radio.value : 'finished';

    if(!party) { alert("Please enter Party Name"); return; }

    const rows = document.querySelectorAll(".sale-row");
    const payload = [];

    for(let row of rows) {
        const itemId = row.querySelector(".row-item-select").value;
        const qtyVal = row.querySelector(".row-qty-input").value;

        if(!itemId || !qtyVal || qtyVal <= 0) {
            alert("Please ensure all rows have an Item and valid Quantity.");
            return;
        }

        const stockItem = availableStock.find(i => i.id == itemId);
        if(!stockItem) { alert("Invalid item selected"); return; }

        let availableQty = 0;
        if(saleType === 'raw') availableQty = Number(stockItem.raw_stock);
        else availableQty = Number(stockItem.finished_stock);

        const requestedQty = Number(qtyVal);

        if (requestedQty > availableQty) {
            alert(`Insufficient stock for item: ${stockItem.name} (${stockItem.size}).\nAvailable: ${availableQty}\nRequested: ${requestedQty}`);
            return;
        }

        payload.push({ item_id: itemId, quantity: requestedQty });
    }

    const promises = payload.map(data => {
        const fd = new FormData();
        fd.append('item_id', data.item_id);
        fd.append('party_name', party);
        fd.append('quantity', data.quantity);
        fd.append('user_id', LOGGED_IN_USER_ID);
        fd.append('sale_type', saleType);

        return fetch('record_sale.php', { method: 'POST', body: fd }).then(r => r.json());
    });

    Promise.all(promises)
        .then(results => {
            const errors = results.filter(res => !res.success);
            if(errors.length > 0) {
                alert("Some items failed to record: " + errors[0].message);
            } else {
                alert("All items recorded successfully!");
                location.reload();
            }
        })
        .catch(e => { alert("System Error: " + e); });
}

/* =========================================================
   EDIT / DELETE LOGIC
   ========================================================= */
function openEditStock(id, itemName, qty) {
    $('#editStockId').value = id;
    $('#editStockItemName').value = itemName;
    $('#editStockQty').value = qty;
    openModal('editStockModal');
}
function submitEditStock() {
    const fd = new FormData($('#editStockForm'));
    fetch('update_stock.php', { method:'POST', body:fd })
        .then(r=>r.json()).then(res=>{
        if(res.success){ alert("Updated!"); location.reload(); }
        else alert(res.message);
    });
}
function deleteStock(id) {
    if(!confirm("Are you sure?")) return;
    const fd = new FormData(); fd.append('id', id);
    fetch('delete_stock.php', { method:'POST', body:fd }).then(r=>r.json()).then(res=>{
        if(res.success) location.reload(); else alert("Error");
    });
}

function openEditSale(id, itemName, party, qty) {
    $('#editSaleId').value = id;
    $('#editSaleItemName').value = itemName;
    $('#editSaleParty').value = party;
    $('#editSaleQty').value = qty;
    openModal('editSaleModal');
}
function submitEditSale() {
    const fd = new FormData($('#editSaleForm'));
    fetch('update_sale.php', { method:'POST', body:fd })
        .then(r=>r.json()).then(res=>{
        if(res.success){ alert("Updated!"); location.reload(); }
        else alert(res.message);
    });
}

function deleteSale(id) {
    if (!confirm("Are you sure? This will delete the record AND restore the stock.")) return;
    const fd = new FormData(); fd.append('id', id);
    fetch('delete_sale.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
        if (res.success) { alert("Deleted."); fetchAndRenderLogs(); }
        else { alert("Error: " + res.message); }
    });
}

/* =========================================================
   RENDER FUNCTIONS
   ========================================================= */
function renderStock(log) {
    const tbody = document.querySelector("#stockTable tbody");
    tbody.innerHTML = "";
    log.forEach(entry => {
        const item = registeredItems.find(i => i.id == entry.item_id);
        const itemName = item ? item.name.toUpperCase() : 'UNKNOWN';
        const itemSize = item ? item.size : '';
        const itemMaterial = item ? item.material : '';
        const addedBy = entry.added_by_name || "Unknown";

        let badgeHtml = '';
        let badgeClass = 'badge-log';
        let icon = '<i class="fas fa-box"></i>';
        let displayType = 'FINISHED';
        let isCasted = false;

        if (entry.log_type) {
            let rawType = entry.log_type.toLowerCase();
            if (rawType.includes('raw') || rawType.includes('casted')) {
                badgeClass = 'badge-raw'; icon = '<i class="fas fa-cubes"></i>';
                displayType = 'CASTED'; isCasted = true;
            } else { displayType = entry.log_type.toUpperCase(); }
        }
        badgeHtml = `<span class="badge-pill ${badgeClass}">${icon} &nbsp;${displayType}</span>`;
        const safeName = (item ? item.name : 'Unknown').replace(/'/g, "\\'");

        tbody.innerHTML += `<tr>
            <td data-label="Item Details">
                <div style="display:flex; flex-direction:column; align-items:flex-start;">
                    <span class="item-name-styled">${itemName} - ${itemSize}</span>
                    <span style="font-size:0.85em; color:#666;">${itemMaterial}</span>
                    ${badgeHtml}
                </div>
            </td>
            <td data-label="Qty" style="font-weight:600;">${entry.quantity}</td>
            <td data-label="By">${addedBy}</td>
            <td data-label="Date">${formatDate(entry.timestamp)}</td>
            <td>
                ${ isCasted ?
            `<button class="btn-icon" disabled style="opacity:0.4"><i class="fas fa-lock"></i></button>` :
            `<button class="btn-icon btn-edit" onclick="openEditStock(${entry.id}, '${safeName}', ${entry.quantity})"><i class="fas fa-edit"></i></button>
                     <button class="btn-icon btn-delete" onclick="deleteStock(${entry.id})"><i class="fas fa-trash"></i></button>`
        }
            </td>
        </tr>`;
    });
}

function renderSales(log) {
    const tbody = document.querySelector("#salesTable tbody");
    tbody.innerHTML = "";
    log.forEach(entry => {
        const item = registeredItems.find(i => i.id == entry.item_id);
        const itemName = item ? item.name.toUpperCase() : 'UNKNOWN';
        const itemSize = item ? item.size : '';
        const itemMaterial = item ? item.material : '';
        const soldBy = entry.sold_by_username || entry.sold_by || "Unknown";

        let badgeHtml = '';
        let badgeClass = 'badge-log';
        let icon = '<i class="fas fa-check-circle"></i>';
        let displayType = 'PACKED';
        if (entry.sale_type === 'raw') {
            badgeClass = 'badge-raw'; icon = '<i class="fas fa-cubes"></i>'; displayType = 'CASTED';
        }
        badgeHtml = `<span class="badge-pill ${badgeClass}">${icon} &nbsp;${displayType}</span>`;
        const safeName = (item ? item.name : 'Unknown').replace(/'/g, "\\'");

        tbody.innerHTML += `<tr>
            <td data-label="Item Details">
                <div style="display:flex; flex-direction:column; align-items:flex-start;">
                    <span class="item-name-styled">${itemName} - ${itemSize}</span>
                    <span style="font-size:0.85em; color:#666;">${itemMaterial}</span>
                    ${badgeHtml}
                </div>
            </td>
            <td data-label="Party" style="font-weight:500;">${entry.party_name}</td>
            <td data-label="Qty" style="font-weight:bold;">${entry.quantity}</td>
            <td data-label="By">${soldBy}</td>
            <td data-label="Date">${formatDate(entry.timestamp)}</td>
            <td>
                <button class="btn-icon btn-edit" onclick="openEditSale(${entry.id}, '${safeName}', '${entry.party_name}', ${entry.quantity})"><i class="fas fa-edit"></i></button>
                <button class="btn-icon btn-delete" onclick="deleteSale(${entry.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`;
    });
}

function renderBalance(items, stock, sales) {
    const tbody = document.querySelector("#balanceTable tbody");
    if (!tbody) return;

    tbody.innerHTML = "";

    const filterMat = document.getElementById("materialDropdown")?.value;
    const filterSearch = document.getElementById("balanceSearch")?.value.toLowerCase();

    if (!availableStock || availableStock.length === 0) {
        tbody.innerHTML = "<tr><td colspan='2' style='text-align:center; padding: 20px;'>No items found in database.</td></tr>";
        return;
    }

    availableStock.forEach(item => {
        if (filterMat && item.material !== filterMat) return;
        if (filterSearch && !item.name.toLowerCase().includes(filterSearch)) return;

        const itemName = item.name.toUpperCase();
        const itemSize = item.size;
        const itemMaterial = item.material;

        tbody.innerHTML += `
            <tr>
                <td data-label="Item Details">
                    <div style="display:flex; flex-direction:column; align-items:flex-start;">
                        <span class="item-name-styled">${itemName} - ${itemSize}</span>
                        <span style="font-size:0.85em; color:#666;">${itemMaterial}</span>
                        <span class="badge-pill badge-log">
                            <i class="fas fa-check-circle"></i> &nbsp;PACKED
                        </span>
                    </div>
                </td>
                <td data-label="Balance Qty" style="font-weight:bold; font-size:1.1em; color:${item.finished_stock <= 0 ? '#ff4d4d' : '#000'};">
                    ${item.finished_stock}
                </td>
            </tr>`;

        tbody.innerHTML += `
            <tr>
                <td data-label="Item Details">
                    <div style="display:flex; flex-direction:column; align-items:flex-start;">
                        <span class="item-name-styled">${itemName} - ${itemSize}</span>
                        <span style="font-size:0.85em; color:#666;">${itemMaterial}</span>
                        <span class="badge-pill badge-raw">
                            <i class="fas fa-cubes"></i> &nbsp;CASTED
                        </span>
                    </div>
                </td>
                <td data-label="Balance Qty" class="qty-raw" style="font-weight:bold; font-size:1.1em; color:${item.raw_stock <= 0 ? '#ff4d4d' : 'inherit'};">
                    ${item.raw_stock}
                </td>
            </tr>`;
    });

    if (tbody.innerHTML === "") {
        tbody.innerHTML = "<tr><td colspan='2' style='text-align:center; padding: 20px;'>No matches found for your search.</td></tr>";
    }
}

/* =========================================================
   NAV AND FILTERS
   ========================================================= */
function showLog(type) {
    document.querySelectorAll('.log-nav button').forEach(b => b.classList.remove('active'));
    document.getElementById('show' + type.charAt(0).toUpperCase() + type.slice(1)).classList.add('active');

    document.getElementById('stockTable').style.display = (type === 'stock') ? 'table' : 'none';
    document.getElementById('salesTable').style.display = (type === 'sales') ? 'table' : 'none';
    document.getElementById('balanceTable').style.display = (type === 'balance') ? 'table' : 'none';

    const balToolbar = document.getElementById('balanceToolbar');
    if(balToolbar) balToolbar.style.display = (type === 'balance') ? 'flex' : 'none';

    const stockSearch = document.getElementById('stockSearchContainer');
    if(stockSearch) stockSearch.style.display = (type === 'stock') ? 'block' : 'none';

    const salesSearch = document.getElementById('salesSearchContainer');
    if(salesSearch) salesSearch.style.display = (type === 'sales') ? 'block' : 'none';

    if (type !== 'stock') {
        const ss = document.getElementById("stockSearch");
        if (ss) ss.value = "";
        filterStockTable();
    }
    if (type !== 'sales') {
        const sss = document.getElementById("salesSearch");
        if (sss) sss.value = "";
        filterSalesTable();
    }
}

function populateMaterialFilter() {
    const dd = document.getElementById("materialDropdown");
    if(!dd) return;
    const mats = [...new Set(registeredItems.map(i => i.material).filter(Boolean))];
    mats.forEach(m => dd.add(new Option(m, m)));
}

function filterStockTable() {
    const val = (document.getElementById("stockSearch")?.value || "").toLowerCase();
    const rows = document.querySelectorAll("#stockTable tbody tr");
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(val) ? "" : "none";
    });
}

function filterSalesTable() {
    const val = (document.getElementById("salesSearch")?.value || "").toLowerCase();
    const rows = document.querySelectorAll("#salesTable tbody tr");
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(val) ? "" : "none";
    });
}

function onMaterialChange() { renderBalance(registeredItems, stockLog, salesLog); }
function filterBalanceTable() { renderBalance(registeredItems, stockLog, salesLog); }

document.addEventListener('DOMContentLoaded', fetchAndRenderLogs);
</script>

</body>
</html>
