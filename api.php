<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . '/db.php';

try {
    $conn   = getDbConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    // ── PUBLIC: Add / check entry ────────────────────────────────
    if ($method === 'POST') {

        $raw_data = file_get_contents("php://input");
        $data     = json_decode($raw_data);

        if (!$data || !isset($data->address) || !isset($data->door_code)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid data format."]);
            exit;
        }

        $address   = htmlspecialchars(trim($data->address), ENT_QUOTES, 'UTF-8');
        $door_code = htmlspecialchars(trim($data->door_code), ENT_QUOTES, 'UTF-8');
        $action    = isset($data->action) ? $data->action : 'check';

        if ($action === 'update') {
            $stmt = $conn->prepare("UPDATE locations SET door_code = ? WHERE address = ?");
            $stmt->bind_param("ss", $door_code, $address);
            if ($stmt->execute()) {
                echo json_encode(["status" => "updated", "message" => "Code updated successfully!"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Database Update Error"]);
            }
            $stmt->close();
            exit;
        }

        // 50-entry cap
        $countResult = $conn->query("SELECT COUNT(*) as total FROM locations");
        $total       = (int)$countResult->fetch_assoc()['total'];

        $checkStmt = $conn->prepare("SELECT * FROM locations WHERE address = ? LIMIT 1");
        $checkStmt->bind_param("s", $address);
        $checkStmt->execute();
        $checkResult   = $checkStmt->get_result();
        $addressExists = $checkResult->num_rows > 0;
        $checkStmt->close();

        if (!$addressExists && $total >= 50) {
            http_response_code(429);
            echo json_encode([
                "error"   => "capacity_full",
                "message" => "Demo database is full (50 entries max). Please try again later."
            ]);
            exit;
        }

        if ($addressExists) {
            $stmt2 = $conn->prepare("SELECT * FROM locations WHERE address = ? LIMIT 1");
            $stmt2->bind_param("s", $address);
            $stmt2->execute();
            $existing = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();

            if ($existing['door_code'] === $door_code) {
                echo json_encode([
                    "status"    => "exists_exact",
                    "address"   => $existing['address'],
                    "door_code" => $existing['door_code']
                ]);
            } else {
                echo json_encode([
                    "status"   => "exists_different",
                    "old_code" => $existing['door_code'],
                    "new_code" => $door_code
                ]);
            }
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO locations (address, door_code) VALUES (?, ?)");
            $insert_stmt->bind_param("ss", $address, $door_code);
            if ($insert_stmt->execute()) {
                echo json_encode(["status" => "inserted", "message" => "Location logged!"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Database Insert Error"]);
            }
            $insert_stmt->close();
        }

    // ── PUBLIC: Search / view entries ───────────────────────────
    } elseif ($method === 'GET') {

        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $searchTerm = "%" . $_GET['search'] . "%";
            $stmt = $conn->prepare("SELECT * FROM locations WHERE address LIKE ? ORDER BY id DESC");
            $stmt->bind_param("s", $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        } else {
            $result = $conn->query("SELECT * FROM locations ORDER BY id DESC");
        }

        $countResult = $conn->query("SELECT COUNT(*) as total FROM locations");
        $total       = (int)$countResult->fetch_assoc()['total'];

        $locations = [];
        while ($row = $result->fetch_assoc()) {
            $locations[] = [
                'id'        => $row['id'],
                'address'   => htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8'),
                'door_code' => htmlspecialchars($row['door_code'], ENT_QUOTES, 'UTF-8')
            ];
        }


        echo json_encode([
            "entries"   => $locations,
            "count"     => $total,
            "remaining" => max(0, 50 - $total)
        ]);

    // ── ADMIN ONLY: Delete ───────────────────────────────────────
    } elseif ($method === 'DELETE') {

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized. Admin login required."]);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"));

        if (!$data || !isset($data->id)) {
            http_response_code(400);
            echo json_encode(["error" => "Missing entry ID."]);
            exit;
        }

        $id   = intval($data->id);
        $stmt = $conn->prepare("DELETE FROM locations WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo $stmt->affected_rows > 0
                ? json_encode(["status" => "deleted"])
                : json_encode(["error"  => "Entry not found."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Database Delete Error"]);
        }
        $stmt->close();
    }

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error"]);
}
?>