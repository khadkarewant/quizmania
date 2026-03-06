<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");



    if(isset($_GET['set_id']) && $_GET['set_id'] !==""){
        $set_id = $_GET['set_id'];

        if($type!=='admin'){
            
           $get_result_detail = mysqli_query($conn, "SELECT * FROM `exam_stats` WHERE `set_id` = '".$set_id."' AND `user_id` = '".$user_id."' ");
            
        }else{
            $get_result_detail = mysqli_query($conn, "SELECT * FROM `exam_stats` WHERE `set_id` = '".$set_id."' ");
        }
        
        if(mysqli_num_rows($get_result_detail) == 0){
            header("Location: home.php");
        }

        while($row=mysqli_fetch_assoc($get_result_detail)){

            $product_id = $row['product_id'];
            $student_id = $row['user_id'];
            $net_mark = $row['net_mark'];
            
            $product_name = $row['product_name'];

            $total_question = $row['total_question'];
            $attempted_question = $row['total_attempt'];
            $correct_attempt = $row['correct_attempt'];
            $wrong_attempt = $row['wrong_attempt'];
            $unsolved_question = $total_question - $attempted_question;

            $get_mark = mysqli_query($conn, "SELECT * FROM `products` WHERE `id` = '".$product_id."' ");
            foreach ($get_mark as $key => $value) {
                $mark = $value['mark'];
            }
            $full_mark = $total_question * $mark;
            $negative_mark = $row['negative_mark'];

            $mark_obtained = $row['net_mark'];

            $obtained_percentage = ($mark_obtained/$full_mark)*100;
            if($obtained_percentage < 0){
                $obtained_percentage = 0 ;
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Summary</title>
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
            <div class="col-md-6  mx-auto p-2 border shadow">
                <?php
                    if($type == "student"){
                        echo'
                            <h6 style="text-align:center;"> You scored: '.$net_mark.'/'.$full_mark.'</h6>
                        ';
                    }else{
                        echo'
                            <h6 style="text-align:center;"> Student Scored: '.$net_mark.'/'.$full_mark.'</h6>
                        ';
                    }
                ?>
                <h2 style="text-align:center;"><?php echo number_format($obtained_percentage,2)."\n";?>%</h2>

                <table class="table">
                    <tbody>
                        <tr>
                            <th colspan="2">Summary:</th>
                        </tr>
                        <tr>
                            <th>Product Name</th>
                            <td><?php echo ($product_name); ?></td>
                        </tr>
                        <tr>
                            <th>Set ID</th>
                            <td><?php echo ($set_id); ?></td>
                        </tr>
                        <tr>
                            <th>Total Question</th>
                            <td><?php echo ($total_question); ?></td>
                        </tr>
                        <tr>
                            <th>Unsolved Question</th>
                            <td><?php echo ($unsolved_question); ?></td>
                        </tr>
                        <tr>
                            <th>Solved Question</th>
                            <td><?php echo ($total_question - $unsolved_question); ?></td>
                        </tr>
                        <tr>
                            <th>Correct Answer</th>
                            <td><?php echo ($correct_attempt); ?></td>
                        </tr>
                        <tr>
                            <th>Wrong Answer</th>
                            <td><?php echo ($wrong_attempt); ?></td>
                        </tr>
                        <tr>
                            <th>Negative Mark</th>
                            <td><?php echo ($negative_mark); ?></td>
                        </tr>
                    </tbody>
                </table>
                <button onclick="window.location.href='user-stats.php'" class="btn text-light bg-success">Okay</button>
                <button onclick="window.location.href='exam-result.php?set_id=<?php echo $set_id ?>'" class="btn text-light bg-info">View Details</button>
            </div>
        </div>
    </div>
    


    <?php
        include("src/inc/footer.php");
    ?>   
    
</body>
</html>