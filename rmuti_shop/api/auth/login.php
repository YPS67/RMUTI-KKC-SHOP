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

if(!empty($data->userId) && !empty($data->password) && !empty($data->userType)) {
    if($data->userType == 'student') {
        $query = "SELECT u.*, sp.*
                  FROM users u
                  LEFT JOIN student_profiles sp ON u.user_id = sp.user_id
                  WHERE u.username = :userId
                  AND u.password = :password
                  AND u.user_type = 'student'";
    } else if ($data->userType == 'admin') {
        $query = "SELECT * FROM admins WHERE username = :userId AND password = :password";
    } else {
        echo json_encode(array("success" => false, "message" => "Invalid user type."));
        exit;
    }

    $stmt = $db->prepare($query);
    $hashed_password = md5($data->password);
    $stmt->bindParam(":userId", $data->userId);
    $stmt->bindParam(":password", $hashed_password);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_type'] = $data->userType;

        echo json_encode(array(
            "success" => true,
            "message" => "Login successful",
            "user" => $user
        ));
    } else {
        echo json_encode(array("success" => false, "message" => "Invalid credentials."));
    }
} else {
    echo json_encode(array("success" => false, "message" => "Incomplete data."));
}
?>