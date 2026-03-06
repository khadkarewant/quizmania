<?php
    include("../db/db_conn.php");


    // check if txn no. is already inserte in the database

    if(isset($_GET['txn_no'])){
        $get_data = mysqli_query($conn, "SELECT * FROM `purchased_products` WHERE `txn_no` = '".$_GET['txn_no']."' LIMIT 1 ");
        if(mysqli_num_rows($get_data)>0){
            foreach ($get_data as $key => $value) {
                $result = "Status 200"  ;
            }
        }
    }
    // check if refrence_no is already inserte in the database

    if(isset($_GET['ref'])){
        $get_data = mysqli_query($conn, "SELECT * FROM `purchased_products` WHERE `refrence_no` = '".$_GET['ref']."' LIMIT 1 ");
        if(mysqli_num_rows($get_data)>0){
            foreach ($get_data as $key => $value) {
                $result = "Status 200"  ;
            }
        }
    }

    // check if username is already existed in database
    if(isset($_GET['username'])){
        $get_data = mysqli_query($conn, "SELECT * FROM `users` WHERE `username` = '".$_GET['username']."' LIMIT 1 ");
        if(mysqli_num_rows($get_data)>0){
            foreach ($get_data as $key => $value) {
                $result = "Status 200"  ;
            }
        }
    }

    
    // check if email is already existed in database
    if(isset($_GET['email'])){
        $get_data = mysqli_query($conn, "SELECT * FROM `users` WHERE `email` = '".$_GET['email']."' LIMIT 1 ");
        if(mysqli_num_rows($get_data)>0){
            foreach ($get_data as $key => $value) {
                $result = "Status 200"  ;
            }
        }
    }

    
    // check if phone is already existed in database
    if(isset($_GET['phone'])){
        $get_data = mysqli_query($conn, "SELECT * FROM `users` WHERE `phone` = '".$_GET['phone']."' LIMIT 1 ");
        if(mysqli_num_rows($get_data)>0){
            foreach ($get_data as $key => $value) {
                $result = "Status 200"  ;
            }
        }
    }

    echo $result;
?>
