<?php
include 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
function parseInputData() {
    $rawData = file_get_contents("php://input");
    return json_decode($rawData, true);
}

switch ($method) {
    case 'GET':// to see items in cart
        if ($user_id > 0) {
            $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $items = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($items);
        } else {
            echo json_encode(["error" => "User ID is required"]);
        }
        break;

    case 'POST': //Add new item in cart
        $data = parseInputData();
        $user_id = $data['user_id']?? 0;
        $product_id = $data['product_id'] ?? 0;
        $quantity = $data['quantity'] ?? 0;

        if ($user_id > 0 && $product_id > 0 && $quantity > 0) {
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $user_id, $product_id, $quantity);
            $stmt->execute();
            echo json_encode(['message' => 'Item added to cart']);
        } else {
            echo json_encode(["error" => "Missing data for adding item to cart"]);
        }
        break;

        case 'PUT': //Update the cart
            $data = parseInputData();
            $product_id = $data['product_id'] ?? null;
            $quantity = $data['quantity'] ?? null;
        
            if ($user_id > 0 && $product_id && $quantity !== null) {
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("iii", $quantity, $user_id, $product_id);
                $stmt->execute();
        
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['message' => 'Cart item updated successfully']);
                } else {
                    echo json_encode(["error" => "Cart item update failed or no changes made"]);
                }
            } else {
                echo json_encode(["error" => "Missing data for updating cart item"]);
            }
            break;
        

    case 'DELETE': //Delete item from cart
        $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    
        if ($user_id > 0 && $product_id > 0) {
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
    
            if ($stmt->affected_rows > 0) {
                echo json_encode(['message' => 'Cart item deleted successfully']);
            } else {
                echo json_encode(["error" => "Cart item not found or already deleted"]);
            }
        } else {
            echo json_encode(["error" => "Invalid user ID or product ID for deletion"]);
        }
        break;
    

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}

$conn->close();
?>
