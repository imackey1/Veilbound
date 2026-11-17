<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

// Progress is already saved after every action, so logging out is safe.
log_out();
session_start();
flash('You have been logged out.', 'success');
redirect('login.php');
