<?php
    require_once 'db/connect.php';

    if (isset($_POST['btn-reg'])) {
        echo "Has submitted";
        echo "<pre>";
        print_r($_POST);
        $username = $_POST['username'];
        $fullname = $_POST['fullname'];
        $password = $_POST['password'];
        $email    = $_POST['email'];
        $address  = $_POST['address'];
        $gender   = $_POST['gender'];
        if (!empty($username) && !empty($fullname) && !empty($password) && !empty($email) && !empty($address)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO user (username, fullname, password, email, address, gender) 
                                    VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssssss", $username, $fullname, $passwordHash, $email, $address, $gender);
                if ($stmt->execute()) {
                    echo "New record created successfully";
                } else {
                    echo "Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                echo "Prepare failed: " . $conn->error;
            }
        } else {
            echo "Vui lòng nhập đầy đủ thông tin!";
        }
    }
?>
