<?php
require __DIR__ . '/lib.php';
session_destroy();
flash('success', 'Logged out successfully.');
redirect('/');
