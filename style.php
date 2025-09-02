<?php header("Content-type: text/css"); ?>

/* Base & Body */
body {
  font-family: 'Inter', sans-serif;
  background: #1e1e2d;
  color: #e0e0e0;
  margin: 0;
  padding: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
}

.container {
  background: #27293d;
  padding: 30px;
  border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
  width: 100%;
  max-width: 1100px;
  animation: fadeIn 0.6s ease-in-out;
}

/* Typography */
h1, h2, h3 {
  margin: 0 0 15px;
  color: #fff;
}

h1 {
  font-size: 2rem;
  position: relative;
}
h1::after {
  content: "";
  display: block;
  height: 3px;
  width: 50px;
  margin-top: 5px;
  border-radius: 3px;
  background: linear-gradient(90deg, #ff416c, #ff4b2b);
  animation: slide 2s infinite alternate;
}
@keyframes slide {
  from { width: 40px; }
  to { width: 120px; }
}

h2 {
  font-size: 1.8rem;
}
h3 {
  font-size: 1.4rem;
  margin-top: 25px;
}

/* Header */
.header-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 30px;
  flex-wrap: wrap;
  gap: 15px;
  background: linear-gradient(135deg, #1f1c2c, #928dab);
  padding: 15px 25px;
  border-radius: 15px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.4);
}
.logo-section {
  display: flex;
  align-items: center;
  gap: 15px;
}
.logo-section img {
  height: 60px;
}

/* Forms */
.form-sections {
  display: flex;
  justify-content: center;
  gap: 25px;
  flex-wrap: wrap;
  margin-top: 25px;
}
.form-box {
  flex: 1;
  min-width: 300px;
  background: rgba(51, 54, 79, 0.6);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 25px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  box-shadow: 0 8px 20px rgba(0,0,0,0.3);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.form-box:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 28px rgba(0,0,0,0.45);
}

input, select {
  width: 100%;
  padding: 12px;
  margin: 10px 0;
  border: 1px solid #4a4d62;
  background: #2a2c41;
  color: #e0e0e0;
  border-radius: 10px;
  font-size: 16px;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
  box-sizing: border-box;
}
input:focus, select:focus {
  border-color: #00bcd4;
  box-shadow: 0 0 6px rgba(0, 188, 212, 0.5);
  outline: none;
}

/* Buttons */
button, .nav-buttons a, .btn-secondary {
  font-size: 1rem;
  padding: 12px 25px;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

/* Ripple Effect */
button::after {
  content: "";
  position: absolute;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  transform: scale(0);
  opacity: 0;
}
button:active::after {
  top: 50%;
  left: 50%;
  width: 120px;
  height: 120px;
  transform: translate(-50%, -50%) scale(1);
  opacity: 1;
  transition: transform 0.6s, opacity 0.8s;
}

/* Button Variants */
button {
  background: linear-gradient(45deg, #00bcd4, #0097a7);
  color: #1e1e2d;
  box-shadow: 0 2px 6px rgba(0, 188, 212, 0.4);
}
button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0, 188, 212, 0.6);
}

.btn-danger {
  background: linear-gradient(45deg, #e74c3c, #c0392b);
  width: 100px;
  color: #fff;
}
.btn-danger:hover {
  box-shadow: 0 4px 12px rgba(231, 76, 60, 0.6);
}

.btn-secondary {
  background: #fff;
  color: #1e1e2d;
  border: 1px solid #ccc;
}
.btn-secondary:hover {
  background: #f5f5f5;
}

.btn-stock {
  background: linear-gradient(45deg, #0078D7, #005a9e);
  color: #fff;
}
.btn-sales {
  background: linear-gradient(45deg, #2ecc71, #27ae60);
  color: #fff;
}
.btn-balance {
  background: linear-gradient(45deg, #9b59b6, #8e44ad);
  color: #fff;
}

.log-buttons {
  text-align: center;
  margin-top: 30px;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 15px;
}
.log-buttons button {
  flex: 1;
  max-width: 250px;
}

/* Tables */
table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 10px;
  margin-top: 20px;
  font-size: 15px;
}
th, td {
  padding: 15px 12px;
  text-align: center;
  border: none;
}
th {
  background: #1f2937;
  color: #fff;
  border-radius: 10px 10px 0 0;
}
td {
  background: #2c2f42;
  border-radius: 0 0 10px 10px;
  transition: background 0.2s;
}
tr:hover td {
  background: #374151;
}

/* 🏷 Badges */
.badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}
.badge-ok { background: #2ecc71; color: #fff; }
.badge-low { background: #f39c12; color: #fff; }
.badge-out { background: #e74c3c; color: #fff; }

/* Floating Action Button */
.fab {
  position: fixed;
  bottom: 30px;
  right: 30px;
  background: #00bcd4;
  color: #1e1e2d;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  font-size: 2rem;
  display: flex;
  justify-content: center;
  align-items: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  cursor: pointer;
  transition: background 0.3s ease, transform 0.3s ease;
}
.fab:hover {
  background: #0097a7;
  transform: rotate(15deg) scale(1.1);
}

/* Filters */
#balanceFilters {
  margin: 20px 0 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  align-items: center;
  justify-content: center;
  color: #ccc;
}
#balanceFilters label {
  font-weight: 600;
  margin-right: 8px;
  min-width: 120px;
}
#balanceFilters select {
  min-width: 180px;
  max-width: 220px;
}

/* Utility */
.link-btn {
  color: #00bcd4;
  text-decoration: none;
  font-weight: 600;
}
.link-btn:hover {
  text-decoration: underline;
}

/* Responsive */
@media (max-width: 768px) {
  .container { padding: 20px; }
  .header-container { flex-direction: column; gap: 15px; }
  .logo-section h1 { font-size: 1.6rem; }
  .form-sections { flex-direction: column; gap: 20px; }
  .log-buttons { flex-direction: column; gap: 10px; }
}

.tab-content {
  opacity: 0;
  transform: translateY(10px);
  transition: opacity 0.4s ease, transform 0.4s ease;
  display: none;
}

.tab-content.active {
  display: block;
  opacity: 1;
  transform: translateY(0);
}

/* Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Segoe UI", sans-serif;
}

body {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: linear-gradient(-45deg, #0f172a, #1e293b, #0f172a, #1e293b);
  background-size: 400% 400%;
  animation: gradientBG 12s ease infinite;
  color: #fff;
}

@keyframes gradientBG {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.login-box {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 2rem;
  width: 100%;
  max-width: 360px;
  text-align: center;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
  animation: fadeIn 0.8s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.login-header img {
  width: 70px;
  margin-bottom: 10px;
}

.login-header h2 {
  margin-bottom: 20px;
  font-weight: 600;
  letter-spacing: 1px;
}

input {
  width: 100%;
  padding: 12px;
  margin: 10px 0;
  border: none;
  border-radius: 8px;
  outline: none;
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
  font-size: 1rem;
  transition: 0.3s ease;
}

input::placeholder {
  color: rgba(255, 255, 255, 0.6);
}

input:focus {
  background: rgba(255, 255, 255, 0.18);
  box-shadow: 0 0 8px #38bdf8;
}

button {
  width: 100%;
  padding: 12px;
  margin: 12px 0;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  background: linear-gradient(135deg, #38bdf8, #2563eb);
  color: #fff;
  cursor: pointer;
  transition: transform 0.2s, background 0.3s;
}

button:hover {
  background: linear-gradient(135deg, #2563eb, #1e40af);
  transform: scale(1.02);
}

button:active {
  transform: scale(0.98);
}

.change-password-button button {
  background: linear-gradient(135deg, #f97316, #ea580c);
}

.change-password-button button:hover {
  background: linear-gradient(135deg, #ea580c, #9a3412);
}

.change-password-box {
  margin-top: 15px;
  display: none;
  animation: fadeIn 0.6s ease-in-out;
}

.error {
  color: #f87171;
  font-size: 0.9rem;
  margin-top: 10px;
}

.success {
  color: #34d399;
  font-size: 0.9rem;
  margin-top: 10px;
}

/* Responsive */
@media (max-width: 480px) {
  .login-box {
    padding: 1.5rem;
    max-width: 90%;
  }
  .login-header img {
    width: 55px;
  }
  input, button {
    font-size: 0.95rem;
    padding: 10px;
  }
}

/* Password row styling */
.password-row {
  position: relative;
  margin: 10px 0;
}

.password-row input {
  width: 100%;
  padding: 10px 40px 10px 10px;  /* extra space on right for emoji */
  font-size: 1rem;
  border-radius: 5px;
  border: 1px solid #ccc;
  outline: none;
}

.password-row .pass-toggle-btn {
  position: absolute;
  right: 10px;   /* inside the input */
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  font-size: 1.2rem; /* normal emoji size */
  background: none;
  border: none;
  padding: 0;
  line-height: 1;
}

/* Keep everything you pasted already, just make sure these overrides are included at the end */

/* ✅ Password row styling for emoji toggle */
.password-row {
  position: relative;
  margin: 10px 0;
}

.password-row input {
  width: 100%;
  padding: 12px 40px 12px 12px; /* space for emoji toggle */
  font-size: 1rem;
  border-radius: 8px;
  border: none;
  outline: none;
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
}

.password-row input::placeholder {
  color: rgba(255, 255, 255, 0.6);
}

.password-row input:focus {
  background: rgba(255, 255, 255, 0.18);
  box-shadow: 0 0 8px #38bdf8;
}

/* ✅ The emoji toggle button */
.pass-toggle-btn {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  font-size: 1.2rem;
  background: none;
  border: none;
  color: #fff;
  opacity: 0.8;
  transition: opacity 0.2s ease;
}

.pass-toggle-btn:hover {
  opacity: 1;
}

/* Navigation buttons (Register + Logout) */
.nav-buttons {
  display: flex;
  align-items: center;
  gap: 10px; /* spacing between them */
}

.nav-buttons a,
.nav-buttons button {
  margin: 0; /* remove extra spacing */
}

.stats-row {
  display: flex;
  gap: 20px;
  margin: 20px 0;
  justify-content: space-around;
}

.stat-card {
  flex: 1;
  padding: 20px;
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  text-align: center;
  font-size: 18px;
  font-weight: bold;
}

.stat-value {
  font-size: 26px;
  margin-top: 8px;
  color: #007BFF;
}

/* New Low Stock Alert Styling */
.low-stock-container {
    /* Main container styling */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 400px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.18);
    color: #e0e0e0;
    padding: 20px;
    z-index: 1000;
    transition: all 0.5s ease-in-out;
    animation: glow 2s infinite alternate; /* Keep the glow animation */
}

/* Header with close button */
.low-stock-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 10px;
    margin-bottom: 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.low-stock-header h4 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 600;
    color: #ffb74d; /* A soft orange color */
}

.close-btn {
    background: none;
    border: none;
    color: #fff;
    font-size: 1.5rem; /* Significantly decreased font size for a smaller button */
    cursor: pointer;
    line-height: 1;
    opacity: 0.7;
    transition: opacity 0.3s ease; /* Removed transform from transition */
    padding: 3px 3px; /* Further decreased horizontal padding */
    width: 30px; /* Explicitly set a small fixed width */
    height: 30px; /* Explicitly set a small fixed height */
    display: flex; /* Use flexbox to center the 'x' */
    justify-content: center;
    align-items: center;
    border-radius: 50%; /* Make it circular */
    border: 1px solid rgba(255, 255, 255, 0.3); /* Add a subtle border */
}

.close-btn:hover {
    opacity: 1;
    /* Removed transform: rotate(90deg); */
    background-color: rgba(255, 255, 255, 0.1); /* Subtle background on hover */
}

/* Low Stock Table */
#lowStockAlert {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255, 255, 255, 0.05); /* Lighter background for the table itself */
    border-radius: 10px;
}

#lowStockAlert th {
    padding: 12px 10px;
    background-color: rgba(255, 255, 255, 0.15);
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.3);
}

#lowStockAlert td {
    padding: 12px 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: #e0e0e0;
    text-align: left;
    transition: background-color 0.2s ease;
}

#lowStockAlert tr:last-child td {
    border-bottom: none;
}

#lowStockAlert tr:hover td {
    background-color: rgba(255, 255, 255, 0.08);
}

/* Enhanced glowing animation */
@keyframes glow {
    0% {
        box-shadow: 0 0 10px #ffb74d, 0 0 20px #ffb74d; /* Increased initial glow */
    }
    100% {
        box-shadow: 0 0 25px #ffb74d, 0 0 40px #ffb74d, 0 0 55px #ffb74d; /* More intense glow */
    }
}
