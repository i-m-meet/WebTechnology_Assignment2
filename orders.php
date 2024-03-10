<?php
include 'db.php'; 

$method = $_SERVER['REQUEST_METHOD'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Capture query parameters for filtering
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;

function parseInputData() {
    $rawData = file_get_contents("php://input");
    $parsedData = [];
    parse_str($rawData, $parsedData);
    return $parsedData;
}

switch ($method) { 
    // To see order details
    case 'GET':
        $query = "SELECT * FROM orders";
        $conditions = [];
        $params = [];
        $types = "";

        if ($user_id !== null) {
            $conditions[] = "user_id = ?";
            $params[] = $user_id;
            $types .= "i";
        }

        if ($product_id !== null) {
            $conditions[] = "product_id = ?";
            $params[] = $product_id;
            $types .= "i";
        }

        if (count($conditions) > 0) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $conn->prepare($query);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($orders);
        break;

        // Add a new order
        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) {
                echo json_encode(["error" => "Invalid or missing JSON data"]);
                exit();
            }
        
            $user_id = $data['user_id'] ?? null;
            $product_id = $data['product_id'] ?? null;
            $quantity = $data['quantity'] ?? null;
            $total_amount = $data['total_amount'] ?? null;
        
            if ($user_id === null || $product_id === null || $quantity === null || $total_amount === null) {
                echo json_encode(["error" => "Missing data for order creation"]);
                exit();
            }
        
            $stmt = $conn->prepare("INSERT INTO orders (user_id, product_id, quantity, total_amount) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $user_id, $product_id, $quantity, $total_amount);
            if ($stmt->execute()) {
                echo json_encode(['id' => $conn->insert_id]);
            } else {
                echo json_encode(["error" => "Order creation failed"]);
            }
            break;

            // Update Order details
            case 'PUT':
                if ($id > 0) {
                    $data = parseInputData();
                    $quantity = isset($data['quantity']) ? (int)$data['quantity'] : null;
                    $total_amount = isset($data['total_amount']) ? (float)$data['total_amount'] : null;
        
                    if ($quantity === null || $total_amount === null) {
                        echo json_encode(["error" => "Missing data for order update"]);
                        exit();
                    }
        
                    $stmt = $conn->prepare("UPDATE orders SET quantity = ?, total_amount = ? WHERE id = ?");
                    if (!$stmt) {
                        echo json_encode(['error' => "Prepare failed: " . $conn->error]);
                        exit();
                    }
        
                    $stmt->bind_param("idi", $quantity, $total_amount, $id);
                    if ($stmt->execute()) {
                        echo json_encode(['message' => "Order updated successfully"]);
                    } else {
                        echo json_encode(["error" => "Order update failed: " . $stmt->error]);
                    }
                } else {
                    echo json_encode(["error" => "Invalid order ID for update"]);
                }
                break;

                // Delete the order
            case 'DELETE':
                if ($id > 0) {
                    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $success = $stmt->execute();
            
                    if ($success && $stmt->affected_rows > 0) {
                        echo json_encode(['message' => "Order deleted successfully"]);
                    } else {
                        echo json_encode(["error" => "Order not found or could not be deleted"]);
                    }
                } else {
                    echo json_encode(["error" => "Invalid order ID"]);
                }
                break;


    default:
        http_response_code(405); 
        echo json_encode(["error" => "Method not allowed"]);
        break;
}

$conn->close();
?>
