<?php
require 'app.php';
$_SESSION = [];
session_destroy();
session_start();
flash('Anda telah keluar.');
header('Location: index.php');
