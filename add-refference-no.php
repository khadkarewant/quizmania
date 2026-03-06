<?php
    ob_start();
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");
    
    if(isset($_GET['purchased_id'])){
        $purchased_id = htmlentities($_GET['purchased_id']);

        $get_data = mysqli_query($conn, "SELECT * FROM `purchased_products` WHERE `id` = '".$purchased_id."' ");

        if(mysqli_num_rows($get_data) == 1){
            foreach ($get_data as $key => $value) {
                $txn_no = $value['txn_no'];
                $txn_mode = $value['txn_mode'];
                $amount = $value['amount'];
            }
        }else{
            header("Location: home.php");
        }

        if(isset($_GET['submit']) && $_GET['submit'] == "add_reff_no"){
            $purchased_id = htmlentities($_GET['purchased_id']);
            $refference_no =  htmlentities($_GET['refference_no']);
            
            $insert_reff_no = mysqli_query($conn, "UPDATE `purchased_products` SET `refrence_no` = '".$refference_no."' WHERE `id` = '".$purchased_id."' ");
    
            if ($insert_reff_no) {
                echo'
                    <script>
                        alert("Refference No. Added.");
                        window.location.href="sales-bill.php";
                    </script>
                ';
            }
        }
    }

    ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Refference no.</title>
    <?php
        include("src/inc/links.php");
    ?>
   
</head>
<body>
    <?php
      include("src/inc/header.php");
    ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 p-2 m-1">
                <h2>Add Reference No.</h2>
                <form  action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="GET" enctype="multipart/form-data">
            
                    <input type="text" hidden name="purchased_id" value="<?php echo $purchased_id ?>" class="form-control" required/>
                    
                    <label>Amount:</label>
                    <input disabled type="text" value="<?php echo $amount; ?>"  class="form-control" required/>

                    <label>TXN No.:</label>
                    <input disabled type="text" value="<?php echo $txn_no; ?>"  class="form-control" required/>

                    <label>TXN Mode:</label>
                    <input disabled type="text" value="<?php echo $txn_mode; ?>"  class="form-control" required/>
                    
                    <label>Refference No.:</label>
                    <input type="text" placeholder="Enter Refference No." name="refference_no" class="form-control" required/>
            
                    <br>
                    <button type="submit" name="submit" value="add_reff_no" class="btn text-light" style="background:var(--primary);">Add Refference Number</button>
            
                </form>
            </div>
        </div>
    </div>


    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>