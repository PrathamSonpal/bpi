<?php
session_start();
// Check session & role
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header("Location: login.html");
    exit();
}
if ($_SESSION['role'] !== "admin") {
    echo "<script>alert('Access denied.'); window.location.href='index.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register Item - Bhavesh Plastic Industries</title>
  <link rel="icon" href="Bhavesh Plastic Industries.png" type="image/x-icon">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style_register.css?v=<?php echo filemtime('style_register.css'); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="dashboard">

    <header class="header-container">
        <div class="header-left">
            <img src="Bhavesh Plastic Industries.png" alt="BPI Logo" class="logo">
            <div class="company-block">
                <h2>Bhavesh Plastic Industries</h2>
                <p>Management System</p>
            </div>

            <nav class="nav-links" id="navMenu">
                <a href="index.php">Dashboard</a>
                <a href="stock_sales_log.php">Stock & Sales Log</a>
                <a href="order_page.php">Orders</a>
                <a href="register-item.php" class="active">Register Item</a>
                
                <a href="logout.php" class="logout-btn mobile-only-btn">Logout</a>
            </nav>
        </div>

        <a href="logout.php" class="logout-btn desktop-only-btn">Logout</a>

        <div class="hamburger" onclick="toggleMenu()">☰</div>
    </header>


    <main class="main-content">

        <div class="card action-card">
            <div class="card-header">
                <h3><i class="fas fa-box"></i> Add New Item</h3>
            </div>
            
            <form id="addItemForm" enctype="multipart/form-data" method="POST" class="modern-form">
                
                <div class="input-group">
                    <i class="fas fa-tag input-icon"></i>
                    <input type="text" name="name" placeholder="Product Name" required>
                </div>

                <div class="input-group">
                    <i class="fas fa-ruler-combined input-icon"></i>
                    <input type="text" name="size" placeholder="Size" required>
                </div>

                <div class="input-group">
                    <i class="fas fa-layer-group input-icon"></i>
                    <select name="material" required>
                        <option value="">Select Material</option>
                        <option value="Stainless Steel (SS)">Stainless Steel (SS)</option>
                        <option value="Malleable">Malleable</option>
                        <option value="Plastic">Plastic</option>
                    </select>
                    <i class="fas fa-chevron-down select-arrow"></i>
                </div>

                <div class="input-group file-group">
                    <i class="fas fa-camera input-icon"></i>
                    <input type="file" name="image" accept="image/*">
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-plus-circle"></i> Add Item
                </button>
            </form>
        </div>

        <div class="card">
            <div class="card-header row-between">
                <h3><i class="fas fa-clipboard-list"></i> Registered Items</h3>
                <div class="table-search">
                    <button class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Search items..."
                        onkeyup="filterItems()"
                    >
                </div>
            </div>
            
            <div class="table-wrapper">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Size</th>
                            <th>Material</th>
                            <th>Image</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="itemTable"></tbody>
                </table>
            </div>
        </div>
    </main>
    
    <footer class="footer">
        <p>Created & Developed by <strong>Pratham P Sonpal</strong></p>
    </footer>
    
</div>

<div id="editModal" class="modal-overlay">
  <div class="modal-content">
    
    <div class="modal-header">
        <h3><i class="fas fa-pen-to-square"></i> Edit Item Details</h3>
        <span class="close-btn" onclick="closeModal()">&times;</span>
    </div>

    <form id="editItemForm" enctype="multipart/form-data" method="POST" class="modal-form">
        <input type="hidden" name="id" id="editId">
        
        <div class="form-row">
            <div class="input-group">
                <i class="fas fa-tag input-icon"></i>
                <input type="text" name="name" id="editName" placeholder="Product Name" required>
            </div>
            
            <div class="input-group">
                <i class="fas fa-ruler-combined input-icon"></i>
                <input type="text" name="size" id="editSize" placeholder="Size" required>
            </div>
        </div>
        
        <div class="input-group">
            <i class="fas fa-layer-group input-icon"></i>
            <select name="material" id="editMaterial" required>
                <option value="">Select Material</option>
                <option value="Stainless Steel (SS)">Stainless Steel (SS)</option>
                <option value="Malleable">Malleable</option>
                <option value="Plastic">Plastic</option>
            </select>
            <i class="fas fa-chevron-down select-arrow"></i>
        </div>
        
        <div class="image-edit-section">
            <div class="current-image-box">
                <span class="label-text">Current Image</span>
                <div id="currentImage"></div>
            </div>
            
            <div class="new-image-box">
                <span class="label-text">Upload New (Optional)</span>
                <div class="input-group file-group">
                    <i class="fas fa-camera input-icon"></i>
                    <input type="file" name="image" id="editImage" accept="image/*">
                </div>
            </div>
        </div>
        
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button type="submit" class="btn-submit modal-save-btn">
                <i class="fas fa-save"></i> Update Item
            </button>
        </div>
    </form>
  </div>
</div>

<script>
function toggleMenu() {
    const nav = document.getElementById('navMenu');
    nav.classList.toggle('active');
    document.querySelector('.hamburger').innerHTML = nav.classList.contains('active') ? '&times;' : '&#9776;';
}

document.getElementById("addItemForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const res = await fetch("add_items.php", { method: "POST", body: formData });
    const data = await res.json();
    alert(data.message);
    if (data.success) e.target.reset();
    loadItems();
});

async function loadItems() {
    const res = await fetch("get_items.php");
    const items = await res.json();
    const tbody = document.getElementById("itemTable");
    tbody.innerHTML = "";
    items.forEach(item => {
        tbody.innerHTML += `
        <tr>
            <td><strong>${item.name}</strong></td>
            <td>${item.size}</td>
            <td><span class="badge">${item.material}</span></td>
            <td>${item.image_path ? `<img src="uploads/${item.image_path}" class="table-img">` : '<span class="no-img">N/A</span>'}</td>
            <td style="text-align: center;">
                <button class="btn-icon edit" onclick="editItem(${item.id}, '${item.name}', '${item.size}', '${item.material}', '${item.image_path || ''}')"><i class="fas fa-edit"></i> Edit</button>
                <button class="btn-icon delete" onclick="removeItem(${item.id})"><i class="fas fa-trash"></i> Delete</button>
            </td>
        </tr>`;
    });
}

async function removeItem(id) {
    if (!confirm("Are you sure you want to delete this item?")) return;
    const res = await fetch("delete_item.php", { method: "POST", body: new URLSearchParams({ id })});
    const data = await res.json();
    alert(data.message);
    loadItems();
}

function editItem(id, name, size, material, image_path) {
    document.getElementById("editModal").style.display = "flex";
    document.getElementById("editId").value = id;
    document.getElementById("editName").value = name;
    document.getElementById("editSize").value = size;
    document.getElementById("editMaterial").value = material;
    document.getElementById("currentImage").innerHTML = image_path
        ? `<p>Current Image:</p><img src="uploads/${image_path}" class="table-img" style="width:80px">`
        : "<p>No current image</p>";
}

function closeModal() {
    document.getElementById("editModal").style.display = "none";
    document.getElementById("editItemForm").reset();
}
    
function filterItems() {
    const searchValue = document.getElementById("searchInput").value.toLowerCase();
    const rows = document.querySelectorAll("#itemTable tr");

    rows.forEach(row => {
        const name = row.children[0].innerText.toLowerCase();
        const size = row.children[1].innerText.toLowerCase();
        const material = row.children[2].innerText.toLowerCase();

        if (
            name.includes(searchValue) ||
            size.includes(searchValue) ||
            material.includes(searchValue)
        ) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

window.onclick = function(e) {
    if (e.target === document.getElementById("editModal")) closeModal();
};

document.getElementById("editItemForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const res = await fetch("update_item.php", { method: "POST", body: new FormData(e.target)});
    const data = await res.json();
    alert(data.message);
    if (data.success) closeModal();
    loadItems();
});

// initial render
loadItems();
</script>

</body>
</html>