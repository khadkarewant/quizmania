<?php
    ob_start();
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");
    if($type !== "admin"){
        header("Location: Home.php");
    }

    $txn_no = "";
    $txn_mode = "";
    $txn_mbl_no = "";
    $txn_no_erro = "";
    $error = "";
    $agent_id = "";

    if(isset($_POST['submit']) && $_POST['submit'] == "add_credit"){
        if(isset($_POST['agent_id'])){
            $agent_id = $_POST['agent_id'];
            $txn_no = $_POST['txn_no'];
            $txn_mode = $_POST['txn_mode'];
            $credit = $_POST['credit'];
            $amount = $_POST['amount'];

            $insert_agent_txn = mysqli_query($conn, "INSERT INTO `agent_txn`(`agent_id`, `amount`, `product_credit`, `date`,`time`,`txn_no`,`txn_mode`, `refrence_no`)VALUES('".$agent_id."','".$amount."','".$credit."','".date("Y-m-d")."','".date("H:i:s")."','".$txn_no."','".$txn_mode."', 'null')");

            $get_agent_stat = mysqli_query($conn, "SELECT * FROM `agent_stat` WHERE `agent_id` = '".$agent_id."' ");

            if(mysqli_num_rows($get_agent_stat) == 1){
                foreach ($get_agent_stat as $key => $value) {
                    echo $new_total_product = $credit + $value['product_credited'];
                    echo $new_remaining_product_credit = $credit + $value['remaining_product_credit'];
                    echo $new_payment_deposited = $amount + $value['payment_deposited'];

                    $update_stat = mysqli_query($conn, "UPDATE `agent_stat` SET `product_credited`='".$new_total_product."',`remaining_product_credit`='".$new_remaining_product_credit."',`payment_deposited`='".$new_payment_deposited."' WHERE `agent_id`='".$agent_id."'");
                    
                }
            }else{
                $insert_stat = mysqli_query($conn, "INSERT INTO `agent_stat`(`agent_id`,`product_credited`,`remaining_product_credit`,`payment_deposited`)VALUES('".$agent_id."','".$credit."','".$credit."','".$amount."')");
                
            }
            
            header("Location: agent-stat.php");

        }else{
            $error = "Please select Agent.";
        }

    }

    ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Product</title>
    <?php
        include("src/inc/links.php");
    ?>
    <style>
        .search{
            position:relative;
        }
        #result{
            position:absolute;
            width:100%;
            cursor:pointer;
            overflow-y:auto;
            max-height:400px;
            box-sizing:border-box;
        }
        
        .link-class{
            border:1px solid grey;
            padding:2px 10px;
            display:inline-block;
        }
        .link-class:hover{
            background:#f1f1f1;
        }
    </style>
    <script>
        $(document).ready(function(){
            $("#search").keyup(function(){
                var keyWord = this.value;
                $.ajax({
                    url : 'src/api/agents.php?p='+keyWord,
                    type : 'GET',
                    success: function(response){

                        var data = $.parseJSON(response);

                        $(".list-group").html('<li class="list-group link-class">'+data.first_name+' '+data.middle_name+' '+data.last_name+' | <span class="text-muted" style="display:inline">@'+data.username+'</span></li>' );

                        $(".link-class").click(function(){
                            $(".agent_id").html('<label>Agent Id</label><input type="text" class="form-control" value="'+data.user_id+'" name="agent_id" required>');
                            
                            $(".agent_username").html('<label>Agent Username</label><input type="text" class="form-control" value="'+data.username+'" name="agent_username" required>');

                            ("#result").css("display", "none")

                        })
                    }
                });
            });

            $(".txn_no").keyup(function(){
                var txn_no = this.value; 
                $.ajax({
                    url: "src/api/agent-data-check-api.php?txn_no="+txn_no,
                    type: "GET",
                    datatype: "text",
                    success: function(data){
                        if(data == "Status 200"){
                            $("#txn_no").css("border","2px solid red");
                            $(".txn_no_error").text("TXN No. is already submitted.");
                            $(".submit").attr("disabled","true");
                            
                        }else{
                            $("#txn_no").css("border","2px solid green");
                            $(".txn_no_error").text("");
                            $(".submit").removeAttr("disabled");
                        }
                    }
                }) ;
            });


        })
    </script>
</head>
<body>
    <?php
        include("src/inc/header.php");
    ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 mt-1">
                <div class="search">
                    <label>Search Agent</label>
                    <input type="text" class="form-control" id="search" placeholder="Search agent">
                    <div id="result">
                        <ul class="list-group">
                            <i class="text-danger txn_no_error"><?php echo $error; ?></i>

                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-6">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">

                        <div class="col-md-6 agent_id"></div>
                        <div class="col-md-6 agent_username"></div>
                        <div class="col-md-12">

                            <label>Amount Deposited:</label>
                            <input type="number" name="amount" class="form-control" required>


                            <label>Transaction No.:</label>
                            <input type="text" name="txn_no" id="txn_no" value="<?php echo $txn_no;?>" class="txn_no form-control" required>
                            <i class="text-danger txn_no_error"><?php echo $txn_no_erro; ?></i>

                            <br>
                            <label>Product Credit.:</label>
                            <input type="number" name="credit" value="<?php echo $txn_mbl_no;?>" class="form-control" required>

                            <label>Transaction Mode:</label>
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td>
                                            <input type="radio" name="txn_mode" value="esewa"  id="esewa"> &nbsp; <label for="esewa">Esewa</label>
                                        </td>
                                        <td>
                                            <input type="radio" name="txn_mode" value="khalti" id="khalti"> &nbsp; <label for="khalti">Khalti</label>
                                        </td>
                                        <td>
                                            <input type="radio" name="txn_mode" value="bank" id="bank"> &nbsp; <label for="bank">Bank</label>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        

                            <button class="btn submit bg-success text-light" type="submit" name="submit" value="add_credit">Add Credit</button>

                        </div>
                        <div class="col-md-12 submit">
                            <br>
                          
                        </div>
                    </div>
                   

                </form>
            </div>
        </div>
    </div>

    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>