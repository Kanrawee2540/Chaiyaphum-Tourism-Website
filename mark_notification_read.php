<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $notification_id = $input['notification_id'] ?? null;

    if ($notification_id && isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $_SESSION['user_id']);
        if ($stmt->execute()) {
            unset($_SESSION['notification']);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}