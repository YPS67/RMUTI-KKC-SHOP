<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : die(json_encode(array("success" => false, "message" => "Order ID is missing.")));
    $amount = isset($_POST['amount']) ? $_POST['amount'] : die(json_encode(array("success" => false, "message" => "Amount is missing.")));
    
    $slip_image = '';
    if (isset($_FILES['slip_image'])) {
        $upload_dir = '../../uploads/payment_slips/';
        $file_name = uniqid() . '_' . basename($_FILES['slip_image']['name']);
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['slip_image']['tmp_name'], $target_file)) {
            $slip_image = 'uploads/payment_slips/' . $file_name;
        } else {
            die(json_encode(array("success" => false, "message" => "Failed to upload file.")));
        }
    }

    $query = "INSERT INTO payments (order_id, amount, slip_image, status, payment_date) VALUES (:order_id, :amount, :slip_image, 'pending', NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':slip_image', $slip_image);

    if($stmt->execute()) {
        echo json_encode(array("success" => true, "message" => "Payment slip submitted successfully."));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to record payment."));
    }
} else {
    echo json_encode(array("success" => false, "message" => "Invalid request method."));
}
?>