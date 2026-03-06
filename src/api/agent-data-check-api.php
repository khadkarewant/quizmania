<?php
    include("../db/db_conn.php");

    // check if txn no. is already inserte in the database

    if(isset($_GET['txn_no'])){
        $get_data = mysqli_query($conn, "SELECT * FROM `agent_txn` WHERE `txn_no` = '".$_GET['txn_no']."' LIMIT 1 ");
        if(mysqli_num_rows($get_data)>0){
            foreach ($get_data as $key => $value) {
                $result = "Status 200"  ;
            }
        }
    }


    echo $result;
?>
