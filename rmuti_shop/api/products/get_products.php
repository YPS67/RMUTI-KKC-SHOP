<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT p.*, c.name as category_name
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.category_id
          ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute();

$products = array();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    extract($row);
    $product_item = array(
        "product_id" => $product_id,
        "name" => $name,
        "description" => html_entity_decode($description),
        "price" => (float)$price,
        "stock" => (int)$stock,
        "image_url" => $image_url,
        "category_id" => (int)$category_id,
        "category_name" => $category_name
    );
    array_push($products, $product_item);
}

echo json_encode($products);
?>