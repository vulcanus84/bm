<?php
session_start();

// Beispiel: Session gilt als "aktiv", wenn ein bestimmter Key gesetzt ist
if (isset($_SESSION['login_user']) && $_SESSION['login_user'] !== null) {
    echo json_encode(['status' => 'active']);
} else {
    echo json_encode(['status' => 'expired']);
}
exit;
