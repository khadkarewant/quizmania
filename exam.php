<?php
ob_start();
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

// Redirect if no set_id
if(!isset($_GET['set_id']) || $_GET['set_id'] === ""){
    header("Location: my-courses.php");
    exit;
}

$set_id = $_GET['set_id'];

// Fetch the set for this user
$verify_set = mysqli_query($conn, "SELECT * FROM `exam_sets` WHERE `id` = '$set_id' AND `user_id` = '$user_id' LIMIT 1");
if(mysqli_num_rows($verify_set) !== 1){
    header("Location: my-products.php");
    exit;
}

$set = mysqli_fetch_assoc($verify_set);
$product_id = $set['product_id'];

// Fetch product info
$product_query = mysqli_query($conn, "SELECT * FROM `products` WHERE `id` = '$product_id' LIMIT 1");
$product = mysqli_fetch_assoc($product_query);
$product_name = $product['name'];
$exam_duration = $product['exam_duration'];

// Deduct one set only if exam not started
if($set['attempted'] == "no" && $set['started'] == "no"){

    $purchased_query = mysqli_query($conn, "SELECT * FROM `purchased_products` WHERE `user_id` = '$user_id' AND `product_id` = '$product_id' AND `remaining_sets` > 0 LIMIT 1");
    if(mysqli_num_rows($purchased_query) > 0){
        $purchased = mysqli_fetch_assoc($purchased_query);
        $purchased_id = $purchased['id'];
        $new_available_sets = $purchased['remaining_sets'] - 1;

        // Update remaining sets
        mysqli_query($conn, "UPDATE `purchased_products` SET `remaining_sets` = '$new_available_sets' WHERE `id` = '$purchased_id'");

        // Mark the set as started
        mysqli_query($conn, "UPDATE `exam_sets` SET `started` = 'yes' WHERE `id` = '$set_id'");
    }
}

// Prevent reloading or opening after completion
if($set['attempted'] == "yes"){
    echo "<script>alert('You have already attempted this set.');window.location='my-products.php';</script>";
    exit;
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Exam in Progress</title>
<?php include("src/inc/links.php"); ?>
<style>
.countdown{
    position: fixed;
    right: 0px;
    box-shadow: 0px 0px 10px grey;
    padding: 2px;
    background: white;
    z-index: 1;
    top: 72px;
}
.question-content {
    border: 1px solid #ccc;
    padding: 12px;
    border-radius: 6px;
    background: white;
    font-size: 17px;
    white-space: normal;
}
</style>
<script>
$(document).ready(function(){
    let second = 0;
    let minute = 0;
    $(".minute").text("0"+minute);
    $(".second").text("0"+second);

    setInterval(() => {
        second++;
        if(second == 60){
            minute++;
            second = 0;
        }
        $(".minute").text(minute < 10 ? "0"+minute : minute);
        $(".second").text(second < 10 ? "0"+second : second);
    }, 1000);

    let exam_duration = <?php echo $exam_duration ?>;
    let isSubmitting = false;

    // Auto-submit when time is up
    setTimeout(() => {
        isSubmitting = true;
        $("#exam_form_submit").click();
    }, exam_duration * 60 * 1000);

    // Warning before refresh/close
    window.onbeforeunload = function(){
        if(!isSubmitting){
            return "If you refresh or close, your answers might not be saved.";
        }
    };

    // Disable warning when form is submitted
    $("#exam_form_submit").click(function(){
        isSubmitting = true;
    });
});
</script>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h4 class="text-center"><?php echo $product_name; ?></h4>
            <div class="text-center">(Exam in progress)</div>
            <div class="text-center">Set No.: <?php echo $set_id; ?></div>
            <div class="text-center">Exam Duration: <?php echo $exam_duration; ?> Minute</div>
            <div class="countdown">Time: <span class="minute"></span>:<span class="second"></span></div>

            <form action="exam-attempt-query.php" method="POST">
                <input type="hidden" name="set_id" value="<?php echo $set_id ?>">

                <hr>
                <?php
                $get_mcq_tag = mysqli_query($conn, "SELECT DISTINCT `tag` FROM `exam_questions` WHERE `set_id` = '$set_id'");
                $count = 1;
                while($tag_row = mysqli_fetch_assoc($get_mcq_tag)){
                    $tag = $tag_row['tag'];
                    $get_mcq_id = mysqli_query($conn, "SELECT * FROM `exam_questions` WHERE `set_id` = '$set_id' AND `tag` = '$tag' ORDER BY RAND()");
                    while($mcq_row = mysqli_fetch_assoc($get_mcq_id)){
                        $mcq_id = $mcq_row['mcq_id'];
                        $mcq_query = mysqli_query($conn, "SELECT * FROM `mcqs` WHERE `id` = '$mcq_id' LIMIT 1");
                        $mcq = mysqli_fetch_assoc($mcq_query);
                        $question_query = mysqli_query($conn, "SELECT * FROM `questions` WHERE `mcq_id` = '$mcq_id'");
                        while($question = mysqli_fetch_assoc($question_query)){
                            echo '
                            <input type="hidden" name="question_id'.$count.'" value="'.$question['id'].'">
                            <div><strong>('.$count.') <div class="question-content">'.$question['question'].'</div></strong></div><br>
                            <div>Select Answer: </div>
                            <div class="table-responsive">
                                <table class="table">
                                    <tr>
                                        <td><input type="radio" value="nan" checked hidden name="answer'.$count.'">
                                            <input type="radio" value="a" id="a'.$count.'" name="answer'.$count.'"><label for="a'.$count.'">&nbsp; (A) &nbsp;'.$question['option_a'].'</label></td>
                                        <td><input type="radio" value="b" id="b'.$count.'" name="answer'.$count.'"><label for="b'.$count.'">&nbsp; (B) &nbsp;'.$question['option_b'].'</label></td>
                                        <td><input type="radio" value="c" id="c'.$count.'" name="answer'.$count.'"><label for="c'.$count.'">&nbsp; (C) &nbsp;'.$question['option_c'].'</label></td>
                                        <td><input type="radio" value="d" id="d'.$count.'" name="answer'.$count.'"><label for="d'.$count.'">&nbsp; (D) &nbsp;'.$question['option_d'].'</label></td>
                                    </tr>
                                </table>
                            </div><hr><br>
                            ';
                            $count++;
                        }
                    }
                }
                ?>
                <br>
                <button type="submit" id="exam_form_submit" class="btn" name="submit" value="submit_exam" style="background:var(--primary);color:white;">Submit Paper</button>
            </form>
        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
