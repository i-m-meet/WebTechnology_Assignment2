<?php
include 'db.php'; 

$method = $_SERVER['REQUEST_METHOD'];
function parseInputData() {
    $rawData = file_get_contents("php://input");
    return json_decode($rawData, true);
}

switch ($method) {
    case 'GET':// Retrieve comments for a specific product
        $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
        if ($product_id > 0) {
            $stmt = $conn->prepare("SELECT * FROM comments WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $comments = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($comments);
        } else {
            echo json_encode(["error" => "Product ID is required"]);
        }
        break;

    case 'POST':// Add a new comment
        $data = parseInputData();
        $product_id = $data['product_id'] ?? 0;
        $user_id = $data['user_id'] ?? 0;
        $rating = $data['rating'] ?? 0;
        $image = $data['image'] ?? '';
        $comment_text = $data['comment_text'] ?? '';

        if ($product_id > 0 && $user_id > 0 && $rating > 0) {
            $stmt = $conn->prepare("INSERT INTO comments (product_id, user_id, rating, image, comment_text) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $product_id, $user_id, $rating, $image, $comment_text);
            $stmt->execute();
            echo json_encode(['message' => 'Comment added successfully']);
        } else {
            echo json_encode(["error" => "Missing required fields"]);
        }
        break;

    case 'PUT': // To Update comment
        $comment_id = isset($_GET['comment_id']) ? (int)$_GET['comment_id'] : 0;
        $data = parseInputData();
        $rating = $data['rating'] ?? null;
        $image = $data['image'] ?? '';
        $comment_text = $data['comment_text'] ?? '';

        if ($comment_id > 0 && $rating !== null) {
            $stmt = $conn->prepare("UPDATE comments SET rating = ?, image = ?, comment_text = ? WHERE id = ?");
            $stmt->bind_param("issi", $rating, $image, $comment_text, $comment_id);
            $stmt->execute();
            echo json_encode(['message' => 'Comment updated successfully']);
        } else {
            echo json_encode(["error" => "Invalid comment ID or missing rating"]);
        }
        break;

    case 'DELETE':// Delete a comment
        $comment_id = isset($_GET['comment_id']) ? (int)$_GET['comment_id'] : 0;
        if ($comment_id > 0) {
            $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->bind_param("i", $comment_id);
            $stmt->execute();
            echo json_encode(['message' => 'Comment deleted successfully']);
        } else {
            echo json_encode(["error" => "Invalid comment ID"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}

$conn->close();
?>
