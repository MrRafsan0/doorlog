<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . '/db.php';

try {
    $conn     = getDbConnection();
    $raw_data = file_get_contents("php://input");
    $data     = json_decode($raw_data);

    if (!isset($data->username) || !isset($data->password)) {
        http_response_code(400);
        echo json_encode(["error" => "Missing username or password"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param("s", $data->username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($data->password, $user['password'])) {
            $_SESSION['logged_in'] = true;
            echo json_encode(["message" => "Login successful"]);
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Invalid credentials"]);
        }
    } else {
        http_response_code(401);
        echo json_encode(["error" => "Invalid credentials"]);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
}
?>