<?php
$_SESSION = [];
session_destroy();
header('Location: ' . url('beranda'));
exit;
