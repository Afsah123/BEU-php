<?php
    $server = "localhost";
   
    $user = "root";
    $password = "123456789";
    $db = "_sms";
    
    $conn = mysqli_connect($server, $user, $password, $db);

    if (!$conn) {
        header('Location: ../errors/error.html');
        exit();
    }


?>