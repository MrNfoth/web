<?php
session_start();
unset($_SESSION['admin_id']);
header('Location: adminlog.php');
exit;
