<?php
require_once 'config.php';

$students = $db->query('SELECT COUNT(*) FROM students')->fetchColumn();
$subjects = $db->query('SELECT COUNT(*) FROM subjects')->fetchColumn();
$exams = $db->query('SELECT COUNT(*) FROM exams')->fetchColumn();
$marks = $db->query('SELECT COUNT(*) FROM marks')->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
<title>Madrasa Dashboard</title>
<style>
body{font-family:Arial;background:#eef2f7;margin:0}
.header{background:#2c3e50;color:white;padding:15px}
.container{padding:20px}
.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
.card{background:white;padding:20px;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,0.1);text-align:center}
.card h3{margin:10px 0}
.menu{margin-top:20px}
.menu a{display:inline-block;padding:10px 15px;background:#3498db;color:white;text-decoration:none;margin:5px;border-radius:5px}
</style>
</head>
<body>
<div class="header"><h2>Madrasa Result System</h2></div>
<div class="container">
<div class="cards">
<div class="card"><h3><?= (int)$students ?></h3><p>Total Students</p></div>
<div class="card"><h3><?= (int)$subjects ?></h3><p>Total Subjects</p></div>
<div class="card"><h3><?= (int)$exams ?></h3><p>Total Exams</p></div>
<div class="card"><h3><?= (int)$marks ?></h3><p>Total Marks</p></div>
</div>
<div class="menu">
<a href="pages/students.php">Students</a>
<a href="pages/subjects.php">Subjects</a>
<a href="pages/exams.php">Exams</a>
<a href="pages/marks.php">Marks</a>
<a href="pages/result.php">Results</a>
<a href="import/import_excel.php">Import Excel</a>
</div>
</div>
</body>
</html>
