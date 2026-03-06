<?php

    ob_start();
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if(isset($_POST["submit"]) && $_POST["submit"] !== ""){
        $set_id = $_POST['set_id'];

        $get_product_id = mysqli_query($conn, "SELECT `product_id` FROM `exam_sets` WHERE `id` = '".$set_id."' ");

        if(mysqli_num_rows($get_product_id) == 0){
            header("Location: home.php");
        }

        $update_set_status = mysqli_query($conn, "UPDATE `exam_sets` SET `attempted` = 'yes' WHERE `id` = '".$set_id."' ");
        foreach ($get_product_id as $key => $value) {
            $product_id = $value['product_id'];
        }

        $get_product_name = mysqli_query($conn, "SELECT * FROM `products` WHERE `id` = '".$product_id."' ");
        foreach ($get_product_name as $key => $value) {
            $product_name = $value['name'];
        }

        $get_total_question = mysqli_query($conn, "SELECT * FROM `products` WHERE `id` = '".$product_id."' ");
        foreach ($get_total_question as $key => $value) {
            $total_question = $value['total_question'];
            $mark = $value['mark'];
        }

        $total_attempt = 0;
        $correct_attempt = 0;
        $wrong_attempt = 0;

        for ($i=1; $i <= $total_question; $i++) {

            if(isset($_POST["answer".$i])){

                $question_id = $_POST["question_id".$i];
                $answer = $_POST["answer".$i];

                if($answer !== "nan"){
                    $total_attempt++;
                }

                $get_mcq_id = mysqli_query($conn, "SELECT `mcq_id` FROM `questions` WHERE `id` = '".$question_id."'  ");
                foreach ($get_mcq_id as $key => $value) {
                    $mcq_id = $value['mcq_id'];
                }

                $result='';
                
                $check_result=mysqli_query($conn, "SELECT * FROM `questions` WHERE `id` = '".$question_id."' AND `answer` = '".$answer."' ");

                if(mysqli_num_rows($check_result)==1){
                    $result = 'true';
                    $correct_attempt++;
                }
                else{

                    if($answer !== "nan"){
                        $result = 'false';
                        $wrong_attempt++;
                    }
                }
                
                $check_correct_answer=mysqli_query($conn, "SELECT * FROM `questions` WHERE `id` = '".$question_id."'  ");

                foreach ($check_correct_answer as $key => $value) {
                    $correct_answer = $value['answer'];
                }
            }

            $gross_mark = $correct_attempt * $mark;
            $negative_mark = $wrong_attempt*$mark * 0.20;

            $net_mark = $gross_mark - $negative_mark;
            
            
            $insert_set = mysqli_query($conn, "INSERT INTO `results`(`set_id`, `user_id`,`mcq_id`, `question_id`, `attempted_answer`, `correct_answer`, `submitted_on`) VALUES ('".$set_id."','".$user_id."','".$mcq_id."' ,'".$question_id."','".$answer."','".$correct_answer."','".date("Y-m-d H:i:s")."' )");

            
        }

        $percentage = ($net_mark/ ($total_question * $mark))*100;

        $insert_stats = mysqli_query($conn, "INSERT INTO `exam_stats`(`user_id`,`set_id`,`attempted_date`,`product_id`,`product_name`,`total_question`,`total_attempt`,`correct_attempt`,`wrong_attempt`,`gross_mark`,`negative_mark`,`net_mark`, `percentage`)VALUES('".$user_id."','".$set_id."','".date("Y-m-d H:i:s")."','".$product_id."','".$product_name."','".$total_question."','".$total_attempt."','".$correct_attempt."','".$wrong_attempt."','".$gross_mark."','".$negative_mark."','".$net_mark."','".$percentage."')");

        $send_notification = mysqli_query($conn, "INSERT INTO `notification`(`user_id`,`notification`,`date`,`time`)VALUES('".$user_id."', 'You just attempted exam. Please check your stats to know more.', '".date("Y-m-d")."', '".date("H:i:s")."')") ;

        if($insert_stats && $send_notification){
            header("Location:exam-summery.php?set_id=".$set_id);
            exit();
        }
    }else{
        header("Location: home.php");
    }


    ob_end_flush();
?>

