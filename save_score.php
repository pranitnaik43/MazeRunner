<?php
    $conn = new mysqli("localhost", "root", "", "maze");
    if ($conn->connect_error) {
        return "Connection failed: " . $conn->connect_error;
    }
    $sql = "SELECT * from highscore where username='".$_POST['name']."'";
    $result = $conn->query($sql);

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        if($_POST['highscore']>$row['HighScore']){
            $sql = "UPDATE highscore SET HighScore=" . $_POST['highscore'] . " WHERE username='" . $_POST['name'] ."'";
            $conn->query($sql);
        }
    }else{
        $sql = "INSERT INTO highscore (username, HighScore) VALUES (?, ?)";
        if($query = $conn->prepare($sql)){
            $query->bind_param("si", $_POST['name'], $_POST['highscore']);
            $query->execute();
        }else{
            return $conn->error;
        }
    }

    $query->close();
    $conn->close();

?>