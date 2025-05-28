<?php
session_start();
header('Content-Type: application/json');

$recent = $_SESSION['recent_searches'] ?? [];

echo json_encode(array_reverse($recent));
