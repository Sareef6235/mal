<?php
require_once __DIR__ . '/../config.php';

unset($_SESSION['admin_logged_in'], $_SESSION['admin_username']);
set_flash('success', 'Admin logged out.');
redirect('/admin/login.php');
