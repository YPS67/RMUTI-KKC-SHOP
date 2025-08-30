<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(empty($_SESSION['user_id'])) {
    echo json_encode(array("success" => false, "message" => "User not logged in."));
    exit;
}

if(empty($data->items) || empty($data->total)) {
    echo json_encode(array("success" => false, "message" => "Incomplete order data."));
    exit;
}

try {
    $db->beginTransaction();

    $query = "INSERT INTO orders (user_id, total_amount, status) VALUES (:user_id, :total_amount, 'pending')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->bindParam(":total_amount", $data->total);
    $stmt->execute();
    $order_id = $db->lastInsertId();

    foreach($data->items as $item) {
        $query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":order_id", $order_id);
        $stmt->bindParam(":product_id", $item->product_id);
        $stmt->bindParam(":quantity", $item->quantity);
        $stmt->bindParam(":price", $item->price);
        $stmt->execute();

        $query = "UPDATE products SET stock = stock - :quantity WHERE product_id = :product_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":quantity", $item->quantity);
        $stmt->bindParam(":product_id", $item->product_id);
        $stmt->execute();
    }

    $db->commit();
    echo json_encode(array("success" => true, "message" => "Order created successfully.", "order_id" => $order_id));
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(array("success" => false, "message" => "Transaction failed: " . $e->getMessage()));
}
?>