<?php
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");



    if(isset($_GET['set_id']) && $_GET['set_id'] !==""){
        $set_id = $_GET['set_id'];

        $get_result_detail = mysqli_query($conn, "SELECT * FROM `results` WHERE `set_id` = '".$set_id."' ");
        if($type!=='admin'){
            
            $get_exam_date = mysqli_query($conn, "SELECT * FROM `exam_stats` WHERE `set_id` = '".$set_id."' AND `user_id` = '".$user_id."' ");
        }else{
           $get_exam_date = mysqli_query($conn, "SELECT * FROM `exam_stats` WHERE `set_id` = '".$set_id."' "); 
        }

        if(mysqli_num_rows($get_exam_date) == 0){
            header("Location: home.php");
        }

        foreach ($get_exam_date as $key => $value) {
            $product_id = $value['product_id'];
            $total_question = $value['total_question'];
            $attempted_on = $value['attempted_date'];

            $get_product_name = mysqli_query($conn, "SELECT * FROM `products` WHERE `id` = '".$product_id."' ");

            while ($row = mysqli_fetch_assoc($get_product_name)) {
                $product_name = $row['name'];
                $description = $row['description'];
                $exam_duration = $row['exam_duration'];
            }

        }


        while($row=mysqli_fetch_assoc($get_result_detail)){
            $student_id = $row['user_id'];
        }

       
        $get_student_detail = mysqli_query($conn, "SELECT * FROM `users` WHERE `user_id` = '".$student_id."' ");

        while($row=mysqli_fetch_assoc($get_student_detail)){
            $student_username = $row['username'];
        }

       
    }else{
        header("Location: home.php");
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Result</title>
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
            <div class="col-md-12 table-responsive">
                <table class="table">
                    <thead>
                        <?php
                            if($type !== "student"){
                        ?>
                            <tr>
                                <th>Username</th>
                                <td><?php echo $student_username; ?></td>
                            </tr>
                        <?php
                            }
                        ?>
                    </thead>
                    <tbody>
                        <tr>
                            <th>Set Id:</th>
                            <td><?php echo $set_id; ?></td>
                        </tr>
                        <tr>
                            <th>Product Name:</th>
                            <td><?php echo $product_name; ?></td>
                        </tr>
                        <tr>
                            <th>Description:</th>
                            <td><?php echo $description; ?></td>
                        </tr>
                        <tr>
                            <th>Exam Duration:</th>
                            <td><?php echo $exam_duration; ?> Minutes</td>
                        </tr>
                        <tr>
                            <th>Date:</th>
                            <td><?php echo $attempted_on; ?></td>
                        </tr>
                        <tr>
                            <th>Total Question:</th>
                            <td><?php echo $total_question; ?></td>
                        </tr>
                    </tbody>
                </table>

                <h4>Details</h4>

                <?php
                    $get_set_questions = mysqli_query($conn, "SELECT * FROM `results` WHERE `set_id` = '".$set_id."' ");
                    $count=1;
                    while($row=mysqli_fetch_assoc($get_set_questions)){
                        $get_questions = mysqli_query($conn, "SELECT * FROM `questions` WHERE `id` = '".$row['question_id']."' ");

                        foreach ($get_questions as $key => $value) {

                            $attempted_answer = $row['attempted_answer'];

                            if($row['attempted_answer']=="nan"){
                                echo'
                                <div><strong>('.$count.') '.$value['question'].'</strong></div>
                                <table class="table">
                                    <tbody>
                                        <tr>
                                            <td>A: '.$value['option_a'].'</td>
                                            <td>B: '.$value['option_b'].'</td>
                                            <td>C: '.$value['option_c'].'</td>
                                            <td>D: '.$value['option_d'].'</td>
                                        </tr>
                                    </tbody>
                                </table> 
                                
                                <div class="text-danger">Not Attempted</div>
                                <div>
                                    <strong>The Correct answer is: <span style="text-decoration:upper-case">'.$value['answer'].'</span> </strong>
                                </div><br>
                            ';
                            }
                            else{
                                if($row['attempted_answer']==$value['answer']){
                                    if($attempted_answer == 'a'){
                                        echo'
                                            <div><strong>('.$count.') '.$value['question'].'</strong></div>
                                            <table class="table">
                                                <tbody>
                                                    <tr>
                                                        <td class="text-success">A: '.$value['option_a'].'</td>
                                                        <td>B: '.$value['option_b'].'</td>
                                                        <td>C: '.$value['option_c'].'</td>
                                                        <td>D: '.$value['option_d'].'</td>
                                                    </tr>
                                                </tbody>
                                            </table> <br>
                                            <br>
                                            <hr>                                   
                                        ';
                                    }
                                    if($attempted_answer == 'b'){
                                        echo'
                                            <div><strong>('.$count.') '.$value['question'].'</strong></div>
                                            <table class="table">
                                                <tbody>
                                                    <tr>
                                                        <td>A: '.$value['option_a'].'</td>
                                                        <td class="text-success">B: '.$value['option_b'].'</td>
                                                        <td>C: '.$value['option_c'].'</td>
                                                        <td>D: '.$value['option_d'].'</td>
                                                    </tr>
                                                </tbody>
                                            </table><br>
                                            <br>
                                            <hr>
                                            
                                        ';
                                    }
                                    if($attempted_answer == 'c'){
                                        echo'
                                            <div><strong>('.$count.') '.$value['question'].'</strong></div>
                                            <table class="table">
                                                <tbody>
                                                    <tr>
                                                        <td>A: '.$value['option_a'].'</td>
                                                        <td>B: '.$value['option_b'].'</td>
                                                        <td class="text-success">C: '.$value['option_c'].'</td>
                                                        <td>D: '.$value['option_d'].'</td>
                                                    </tr>
                                                </tbody>
                                            </table><br>
                                            <br>
                                            <hr>
                                        ';
                                    }
                                    if($attempted_answer == 'd'){
                                        echo'
                                            <div><strong>('.$count.') '.$value['question'].'</strong></div>
                                            <table class="table">
                                                <tbody>
                                                    <tr>
                                                        <td>A: '.$value['option_a'].'</td>
                                                        <td>B: '.$value['option_b'].'</td>
                                                        <td>C: '.$value['option_c'].'</td>
                                                        <td class="text-success">D: '.$value['option_d'].'</td>
                                                    </tr>
                                                </tbody>
                                            </table><br>
                                            <br>
                                            <hr>
                                        ';
                                    }     
                                }
                                if($row['attempted_answer']!==$value['answer']){
                                    if($attempted_answer == 'a'){
                                        echo'
                                            <div><strong>('.$count.') '.$value['question'].'</strong></div>
                                            <table class="table">
                                                <tbody>
                                                    <tr>
                                                        <td class="text-danger">A: '.$value['option_a'].'</td>
                                                        <td>B: '.$value['option_b'].'</td>
                                                        <td>C: '.$value['option_c'].'</td>
                                                        <td>D: '.$value['option_d'].'</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <div>Correct Answer is: <strong style="text-transform:uppercase"> '.$value['answer'].'</strong> </div>
                                            <br>
                                            <br>
                                            <hr>
                                        ';
                                    }
                                    if($attempted_answer == 'b'){
                                        echo'
                                            <div><strong>('.$count.') '.$value['question'].'</strong></div>
                                            <table class="table">
                                                <tbody>
                                                    <tr>
                                                        <td>A: '.$value['option_a'].'</td>
                                                        <td class="text-danger">B: '.$value['option_b'].'</td>
                                                        <td>C: '.$value['option_c'].'</td>
                                                        <td>D: '.$value['option_d'].'</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <div>Correct Answer is: <strong style="text-transform:uppercase"> '.$value['answer'].'</strong> </div>
                                            <br>
                                            <br>
                                            <hr>

                                            
                                        ';
                                    }
                                    if($attempted_answer == 'c'){
                                        echo'
                                            <div><strong>('.$count.') '.$value['question'].'</strong></div>
                                            <table class="table">
                                                <tbody>
                                                    <tr>
                                                        <td>A: '.$value['option_a'].'</td>
                                                        <td>B: '.$value['option_b'].'</td>
                                                        <td class="text-danger">C: '.$value['option_c'].'</td>
                                                        <td>D: '.$value['option_d'].'</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <div>Correct Answer is: <strong style="text-transform:uppercase"> '.$value['answer'].'</strong> </div>
                                            <br>
                                            <br>
                                            <hr>

                                        ';
                                    }
                                    if($attempted_answer == 'd'){
                                        echo'
                                            <div><strong>('.$count.') '.$value['question'].'</strong></div>
                                            <table class="table">
                                                <tbody>
                                                    <tr>
                                                        <td>A: '.$value['option_a'].'</td>
                                                        <td>B: '.$value['option_b'].'</td>
                                                        <td>C: '.$value['option_c'].'</td>
                                                        <td class="text-danger">D: '.$value['option_d'].'</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <div>Correct Answer is: <strong style="text-transform:uppercase"> '.$value['answer'].'</strong> </div>
                                            <br>
                                            <br>
                                            <hr>
                                        ';
                                    }
                                }
                            }
                            
                            
                        }
                        
                        $count++;
                    }
                ?>


            </div>
            <div class="col-md-12 mt-4 mb-4">
                <button class="btn text-light bordered shadow" style="background:var(--primary)" onclick="window.location.href='home.php'">Okay</button>
            </div>
        </div>
    </div>
    




    <?php
        include("src/inc/footer.php");
    ?>   
    
</body>
</html>