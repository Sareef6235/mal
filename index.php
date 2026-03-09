<?php
session_start();
$user = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Madrasa Premium Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/hfg/css/styles.css">
</head>
<body>
<header class="app-header glass">
  <div>
    <h1>Madrasa Management Portal</h1>
    <p>Connected multi-page style portal for unlimited madrasas</p>
  </div>
  <div class="header-controls">
    <button id="themeToggle" class="btn btn-outline">🌗 Theme</button>
    <button id="notifyBtn" class="btn btn-outline">🔔</button>
    <button id="authToggle" class="btn btn-primary"><?php echo $user ? 'Logout' : 'Login'; ?></button>
  </div>
</header>

<main class="page-wrap">
  <section class="top-controls glass">
    <label for="madrasaSelect">Madrasa</label>
    <select id="madrasaSelect"></select>
    <button id="addMadrasaBtn" class="btn btn-outline role-admin">+ New Madrasa</button>
    <input id="globalSearch" type="search" placeholder="Search Name / Class / ID">
    <div id="profileInfo" class="profile-info"></div>
  </section>

  <nav class="tab-nav glass">
    <button class="tab-btn active" data-tab="dashboard">Dashboard</button>
    <button class="tab-btn" data-tab="students">Students</button>
    <button class="tab-btn" data-tab="results">Results</button>
    <button class="tab-btn" data-tab="attendance">Attendance</button>
    <button class="tab-btn" data-tab="markbook">Markbook</button>
    <button class="tab-btn" data-tab="teachers">Teachers</button>
    <button class="tab-btn" data-tab="announcements">Announcements</button>
    <button class="tab-btn" data-tab="messages">Messages</button>
    <button class="tab-btn" data-tab="payroll">Salary</button>
    <button class="tab-btn" data-tab="admin">Admin</button>
  </nav>

  <section id="dashboard" class="tab-panel active">
    <div id="dashboardCards" class="card-grid"></div>
    <div class="chart-grid">
      <article class="section-card glass"><h3>Class Distribution</h3><canvas id="distributionChart"></canvas></article>
      <article class="section-card glass"><h3>Pass Analytics</h3><canvas id="passChart"></canvas></article>
      <article class="section-card glass"><h3>Attendance Stats</h3><canvas id="attendanceChart"></canvas></article>
    </div>
  </section>

  <section id="students" class="tab-panel">
    <article class="section-card glass">
      <div class="section-title-row"><h2>Student Management</h2><button id="addStudentBtn" class="btn btn-primary role-edit">Add Student</button></div>
      <div class="toolbar">
        <input id="studentSearch" type="search" placeholder="Advanced search">
        <select id="classFilter"><option value="All">All Classes</option></select>
        <select id="genderFilter"><option value="All">All Genders</option><option>Boy</option><option>Girl</option></select>
      </div>
      <div class="table-wrap">
        <table><thead><tr><th>ID</th><th>Photo</th><th>Name</th><th>Class</th><th>Gender</th><th>Attendance</th><th>Actions</th></tr></thead><tbody id="studentsTableBody"></tbody></table>
      </div>
    </article>
    <article class="section-card glass"><h3>Student Profile</h3><div id="studentProfileArea" class="student-profile-empty">Select a student to view profile.</div></article>
  </section>

  <section id="results" class="tab-panel">
    <article class="section-card glass">
      <div class="section-title-row"><h2>Premium Exam & Results</h2><button id="addResultBtn" class="btn btn-primary role-edit">Add / Edit Result</button></div>
      <div id="topperCards" class="card-grid"></div>
      <div id="resultsSummary" class="result-summary"></div>
      <div id="resultsWrap" class="table-wrap"></div>
    </article>
  </section>

  <section id="attendance" class="tab-panel">
    <article class="section-card glass">
      <div class="section-title-row"><h2>Attendance</h2><button id="saveAttendanceBtn" class="btn btn-primary role-edit">Save Attendance</button></div>
      <div class="toolbar"><input id="attendanceDate" type="date"><input id="attendanceMonth" type="month"><button id="loadAttendanceBtn" class="btn btn-outline">Load Day</button></div>
      <div id="attendanceWrap" class="table-wrap"></div>
      <div id="attendanceStats" class="card-grid"></div>
      <article class="section-card glass"><h3>Monthly Attendance Chart</h3><canvas id="monthlyAttendanceChart"></canvas></article>
    </article>
  </section>

  <section id="markbook" class="tab-panel">
    <article class="section-card glass">
      <h2>Class-wise Markbook</h2>
      <div id="markbookArea"></div>
      <div class="toolbar"><button id="printMarkbook" class="btn btn-outline">Print</button><button id="pdfMarkbook" class="btn btn-primary">PDF Export</button></div>
      <h3>Mark Edit History</h3>
      <div id="historyArea" class="table-wrap"></div>
    </article>
  </section>

  <section id="teachers" class="tab-panel">
    <article class="section-card glass">
      <div class="section-title-row"><h2>Teacher Management</h2><button id="addTeacherBtn" class="btn btn-primary role-admin">Add Teacher</button></div>
      <div id="teacherWrap" class="table-wrap"></div>
    </article>
  </section>

  <section id="announcements" class="tab-panel">
    <article class="section-card glass">
      <div class="section-title-row"><h2>Announcements</h2><button id="addAnnouncementBtn" class="btn btn-primary role-admin">Add Notice</button></div>
      <div id="announcementList"></div>
    </article>
  </section>

  <section id="messages" class="tab-panel">
    <article class="section-card glass">
      <div class="section-title-row"><h2>Madrasa Messages</h2><button id="sendMessageBtn" class="btn btn-primary">Send Message</button></div>
      <div id="messageWrap" class="table-wrap"></div>
    </article>
  </section>

  <section id="payroll" class="tab-panel">
    <article class="section-card glass">
      <div class="section-title-row"><h2>Dynamic Salary Register</h2><button id="addPayrollBtn" class="btn btn-primary role-admin">Add Salary Entry</button></div>
      <div id="payrollSummary" class="card-grid"></div>
      <div id="payrollWrap" class="table-wrap"></div>
    </article>
  </section>

  <section id="admin" class="tab-panel">
    <article class="section-card glass">
      <h2>Login, Security, Import/Export</h2>
      <form id="loginForm" class="admin-login-form">
        <select id="roleSelect"><option>Admin</option><option>Teacher</option><option>Viewer</option></select>
        <input id="username" required placeholder="Username">
        <input id="password" type="password" required placeholder="Password">
        <button class="btn btn-primary">Login</button>
      </form>
      <p id="authState" class="admin-state">Default: admin/madrasa123, teacher/teacher123, viewer/viewer123</p>
      <div class="toolbar">
        <button id="backupJson" class="btn btn-outline">Export JSON</button>
        <button id="exportCsvAll" class="btn btn-outline">Export CSV (All)</button>
        <label class="btn btn-outline" for="importJson">Import JSON</label><input id="importJson" type="file" accept="application/json" hidden>
        <a class="btn btn-outline" href="schema.sql" download>Download SQL Schema</a>
        <a class="btn btn-outline" href="admin_bulk_upload.php">Bulk Sheet/CSV Import</a>
        <a class="btn btn-outline" href="admission_form.php">Online Admission</a>
        <a class="btn btn-outline" href="fee_management.php">Fee Management</a>
        <a class="btn btn-outline" href="id_card_generator_bulk.php">ID Card Bulk</a>
        <a class="btn btn-outline" href="self_card.php">Self Card</a>
        <a class="btn btn-outline" href="idcard_edit.php?register_no=STD-240101-0001">Edit ID Card Page</a>
        <a class="btn btn-outline" href="attendance_qr.php">QR Attendance Page</a>
        <a class="btn btn-outline" href="qr_scanner_dashboard.php">Scanner Dashboard</a>
      </div>
    </article>
  </section>
</main>

<footer class="app-footer glass"><p>© 2026 • Connected menus across all modules • Responsive premium dashboard</p></footer>
<dialog id="entityDialog"></dialog>
<script>window.initialUser = <?php echo json_encode($user); ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script type="module" src="assets/hfg/js/app.js"></script>
</body>
</html>
