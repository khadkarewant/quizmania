<?php
ob_start();
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

// Only agent can access
if ($type !== "agent") {
    header("Location: home.php");
    exit;
}

$product_id = $txn_no = $txn_mode = $txn_mbl_no = "";
$txn_no_erro = $error = "";

// Handle form submission
if (isset($_POST['submit']) && $_POST['submit'] === "assign_product") {

    if (empty($_POST['student_id'])) {
        $error = "Please select student.";
    } else {

        $student_id = $_POST['student_id'];
        $product_id = $_POST['product_id'];
        $txn_no     = trim($_POST['txn_no']);
        $txn_mode   = $_POST['txn_mode'];
        $txn_mbl_no = trim($_POST['txn_mbl_no']);

        /* ================== MOBILE VALIDATION (ONLY ADDITION) ================== */
        if (!preg_match('/^[0-9]{7,15}$/', $txn_mbl_no)) {
            echo '<script>
                    alert("Invalid mobile number! Use digits only (7–15 digits).");
                    history.back();
                  </script>';
            exit;
        }
        /* ======================================================================= */

        mysqli_begin_transaction($conn);

        try {
            // Lock agent row for safe credit deduction
            $agent = mysqli_query(
                $conn,
                "SELECT remaining_product_credit 
                 FROM agent_stat 
                 WHERE agent_id = '$user_id' 
                 FOR UPDATE"
            );

            if (mysqli_num_rows($agent) === 0) {
                throw new Exception("Agent record not found.");
            }

            $agentData = mysqli_fetch_assoc($agent);
            $remaining_credit = $agentData['remaining_product_credit'];

            if ($remaining_credit < 1) {
                throw new Exception("You have no product credits. Please load credit.");
            }

            // Check duplicate TXN
            $check_txn = mysqli_query($conn,
                "SELECT id FROM purchased_products WHERE txn_no = '$txn_no'"
            );
            if (mysqli_num_rows($check_txn) > 0) {
                throw new Exception("Transaction number already used.");
            }

            // Get product details
            $product = mysqli_query($conn,
                "SELECT price, sets FROM products WHERE id = '$product_id' LIMIT 1"
            );
            if (mysqli_num_rows($product) === 0) {
                throw new Exception("Invalid product selected.");
            }

            $prod = mysqli_fetch_assoc($product);
            $amount = $prod['price'] - ($prod['price'] * 0.2);
            $remaining_sets = $prod['sets'];

            // Insert purchased product
            $insert = mysqli_query($conn,
                "INSERT INTO purchased_products
                (user_id, product_id, amount, remaining_sets,
                 purchased_on, purchased_at, txn_no, txn_mode,
                 mobile, status, created_by)
                VALUES
                ('$student_id', '$product_id', '$amount', '$remaining_sets',
                 CURDATE(), CURTIME(), '$txn_no', '$txn_mode',
                 '$txn_mbl_no', 'active', '$user_id')"
            );

            if (!$insert) {
                throw new Exception("Failed to assign product.");
            }

            // Deduct agent credit
            $new_credit = $remaining_credit - 1;
            mysqli_query($conn,
                "UPDATE agent_stat 
                 SET remaining_product_credit = '$new_credit' 
                 WHERE agent_id = '$user_id'"
            );

            // Notification
            mysqli_query($conn,
                "INSERT INTO notification (user_id, notification, date, time)
                VALUES ('$student_id',
                'You just purchased a product. Thank you!',
                CURDATE(), CURTIME())"
            );

            mysqli_commit($conn);

            echo '<script>
                    alert("Product assigned successfully!");
                    window.location.href="a-assign-product.php";
                  </script>';
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

// Remaining credit
$remaining_credit = 0;
$get_agent_stat = mysqli_query($conn,
    "SELECT remaining_product_credit 
     FROM agent_stat 
     WHERE agent_id = '$user_id' LIMIT 1"
);
if(mysqli_num_rows($get_agent_stat) > 0){
    $agent_data = mysqli_fetch_assoc($get_agent_stat);
    $remaining_credit = $agent_data['remaining_product_credit'];
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign Product</title>
<?php include("src/inc/links.php"); ?>
<style>
.search { position:relative; }
#result { position:absolute; width:100%; cursor:pointer; overflow-y:auto; max-height:400px; box-sizing:border-box; }
.link-class { border:1px solid grey; padding:2px 10px; display:inline-block; }
.link-class:hover { background:#f1f1f1; }
</style>

<script>
$(document).ready(function() {

    // Student search
    $("#search").keyup(function(){
        var keyWord = this.value;
        $.ajax({
            url : 'src/api/students.php?p='+keyWord,
            type : 'GET',
            success: function(response){
                var data = $.parseJSON(response);
                $(".list-group").html(
                    '<li class="list-group link-class">'+
                    data.first_name+' '+data.middle_name+' '+data.last_name+
                    ' | <span class="text-muted">@'+data.username+'</span></li>'
                );
                $(".link-class").click(function(){
                    $(".student_id").html(
                        '<label>Student Id</label>'+
                        '<input type="number" class="form-control" value="'+
                        data.user_id+'" name="student_id" required>'
                    );
                    $(".student_username").html(
                        '<label>@Username:</label>'+
                        '<input type="text" class="form-control" value="'+
                        data.username+'" readonly>'
                    );
                    $("#result").hide();
                });
            }
        });
    });

    // Check TXN uniqueness
    $(".txn_no").keyup(function(){
        var txn_no = this.value; 
        $.ajax({
            url: "src/api/data-check-api.php?txn_no="+txn_no,
            type: "GET",
            success: function(data){
                if(data == "Status 200"){
                    $("#txn_no").css("border","2px solid red");
                    $(".txn_no_error").text("TXN No. is already submitted.");
                    $(".submit").attr("disabled","true");
                } else {
                    $("#txn_no").css("border","2px solid green");
                    $(".txn_no_error").text("");
                    $(".submit").removeAttr("disabled");
                }
            }
        });
    });
});
</script>
</head>

<body>
<?php include("src/inc/header.php"); ?>

<div class="container mt-3">
    <div class="alert alert-info">
        Your Remaining Product Credits:
        <strong><?php echo $remaining_credit; ?></strong>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 mt-1">
            <div class="search">
                <label>Search Student</label>
                <input type="text" class="form-control" id="search" placeholder="Search student">
                <div id="result">
                    <ul class="list-group">
                        <i class="text-danger"><?php echo $error; ?></i>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mt-5 p-1">
            <h5>Assign Product:</h5>
            <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST">
                <div class="row">
                    <div class="col-md-6 student_id"></div>
                    <div class="col-md-6 student_username"></div>

                    <div class="col-md-12 p-3">
                        <label>Product</label>
                        <select name="product_id" class="form-control" required>
                            <option value="" disabled selected>SELECT ONE</option>
                            <?php
                            $fetch_products = mysqli_query($conn,
                                "SELECT * FROM products WHERE `is_practice` = 0 AND status='live' AND price>0"
                            );
                            foreach ($fetch_products as $value) {
                                $course_check = mysqli_query($conn,
                                    "SELECT id FROM courses 
                                     WHERE id='".$value['course_id']."' 
                                     AND status='live'"
                                );
                                if(mysqli_num_rows($course_check)>0){
                                    echo '<option value="'.$value['id'].'">'.$value['name'].'</option>';
                                }
                            }
                            ?>
                        </select>

                        <label>Transaction No.:</label>
                        <input type="text" name="txn_no" id="txn_no"
                               value="<?php echo $txn_no;?>"
                               class="txn_no form-control" required>
                        <i class="text-danger txn_no_error"><?php echo $txn_no_erro; ?></i><br>

                        <label>Mobile Number:</label>
                        <input type="text"
                               name="txn_mbl_no"
                               value="<?php echo $txn_mbl_no;?>"
                               class="form-control"
                               required>

                        <label>Transaction Mode:</label>
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td><input type="radio" name="txn_mode" value="esewa" id="esewa"> <label for="esewa">Esewa</label></td>
                                    <td><input type="radio" name="txn_mode" value="khalti" id="khalti"> <label for="khalti">Khalti</label></td>
                                    <td><input checked type="radio" name="txn_mode" value="bank" id="bank"> <label for="bank">Bank</label></td>
                                </tr>
                            </tbody>
                        </table>

                        <button class="btn submit bg-success text-light"
                                type="submit"
                                name="submit"
                                value="assign_product">
                            Assign Product
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
