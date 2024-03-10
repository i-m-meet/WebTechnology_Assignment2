<?php
include 'db.php'; 
$method = $_SERVER['REQUEST_METHOD'];

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;


switch ($method) {
    case 'GET':
        if ($id > 0) {
            // Return a single product by ID
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
        } else {
            // Return all products
            $result = $conn->query("SELECT * FROM products");
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        echo json_encode($data);
        break;

        // Add a new product
        case 'POST':       
        $description = $_POST['description'] ?? '';
        $image = $_POST['image'] ?? '';
        $pricing = $_POST['pricing'] ?? 0;
        $shipping_cost = $_POST['shipping_cost'] ?? 0;

        $stmt = $conn->prepare("INSERT INTO products (description, image, pricing, shipping_cost) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdd", $description, $image, $pricing, $shipping_cost);
        $stmt->execute();
        
        echo json_encode(['id' => $stmt->insert_id]);
        break;

        // Update a product
        case 'PUT':            
            parse_str(file_get_contents("php://input"), $put_vars);
            $description = $put_vars['description'] ?? '';
            $image = $put_vars['image'] ?? '';
            $pricing = $put_vars['pricing'] ?? 0;
            $shipping_cost = $put_vars['shipping_cost'] ?? 0;
    
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE products SET description = ?, image = ?, pricing = ?, shipping_cost = ? WHERE id = ?");
                $stmt->bind_param("ssddi", $description, $image, $pricing, $shipping_cost, $id);
                $stmt->execute();
    
                echo json_encode(['message' => "Product updated successfully", 'rowsAffected' => $stmt->affected_rows]);
            } else {
                echo json_encode(["error" => "Invalid product ID for update"]);
            }
            break;    

        // Delete a product   
        case 'DELETE':        
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            echo json_encode(['message' => "Product deleted successfully", 'rowsAffected' => $stmt->affected_rows]);
        } else {
            echo json_encode(["error" => "Invalid product ID"]);
        }
        break;
    default:
        http_response_code(405); 
        echo json_encode(["error" => "Method not allowed"]);
        exit();
}

$conn->close();
?>
