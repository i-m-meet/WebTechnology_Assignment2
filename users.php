<?php
include 'db.php'; 

$method = $_SERVER['REQUEST_METHOD'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

switch ($method) {
    case 'GET':
        if ($id > 0) {
            // Return a single user by ID
            $stmt = $conn->prepare("SELECT id, email,password, username,purchase_history, shipping_address FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
        } else {
            // Return all users
            $result = $conn->query("SELECT id, email,password, username, purchase_history, shipping_address FROM users");
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        echo json_encode($data);
        break;

        // Add a new user
        case 'POST':        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $username = $_POST['username'] ?? '';
        $shipping_address = $_POST['shipping_address'] ?? '';

        $stmt = $conn->prepare("INSERT INTO users (email, password, username, shipping_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $email, $password, $username, $shipping_address);
        $stmt->execute();

        echo json_encode(['id' => $conn->insert_id]);
        break;

        // Update users  information
        case 'PUT':
        parse_str(file_get_contents("php://input"), $put_vars);
        $email = $put_vars['email'] ?? '';
        $password = $put_vars['password'] ?? '';
        $username = $put_vars['username'] ?? '';
        $shipping_address = $put_vars['shipping_address'] ?? '';

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE users SET email = ?, password = ?, username = ?, shipping_address = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $email, $password, $username, $shipping_address, $id);
            $stmt->execute();

            echo json_encode(['message' => "User updated successfully"]);
        } else {
            echo json_encode(["error" => "Invalid user ID for update"]);
        }
        break;

        //  Delete a user
    case 'DELETE':
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            echo json_encode(['message' => "User deleted successfully"]);
        } else {
            echo json_encode(["error" => "Invalid user ID"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}

$conn->close();
?>
