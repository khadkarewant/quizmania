<?php
ob_start();
    include("src/db/db_conn.php");
    include("src/db/session.php");
    include("src/db/privileges.php");

    if(isset($_GET['purchased_id']) && $_GET['purchased_id'] !== ""){

        $purchased_id = $_GET['purchased_id'];
        
        $get_product_id = mysqli_query($conn, "SELECT * FROM `purchased_products` WHERE `id` = '".$purchased_id."' ");

        if(mysqli_num_rows($get_product_id) == 0){
            header("Location: home.php");
        }
        
        while ($row = mysqli_fetch_assoc($get_product_id)) {
            if($row['remaining_sets'] >0){
                $product_id = $row['product_id'];

                $create_exam_set = mysqli_query($conn, "INSERT INTO `exam_sets`(`product_id`,`user_id`,`date`,`time`)VALUES('".$product_id."','".$user_id."','".date("Y-m-d")."','".date("H:i:s")."') ");

            }else{
                header("Location: my-products.php");
            }
        }

        $get_product_details =  mysqli_query($conn, "SELECT * FROM `products` WHERE `id` = '".$product_id."' ");

        while ($row =mysqli_fetch_assoc($get_product_details)) {
            $course_id = $row['course_id'];
            $product_name = $row['name'];
            $exam_duration = $row['exam_duration'];
            $level_1 = $row['level_1'];
            $level_2 = $row['level_2'];
            $total_question = $row['total_question'];
            $mark_each_question = $row['mark'];
            $exam_tag = $row['tag'];
            $product_description = $row['description'];
        }

        $get_last_set = mysqli_query($conn, "SELECT * FROM `exam_sets` WHERE `user_id` = '".$user_id."' ORDER BY `id` DESC  LIMIT 1 ");
        while ($row = mysqli_fetch_assoc($get_last_set)) {
            $set_id =$row['id'];
        }
    }else{
        header("Location: my-products.php");
    }
ob_end_flush();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Guidelines</title>
    <?php
        include("src/inc/links.php");
    ?>
</head>
<body>
    <?php
        include("src/inc/header.php");
    ?>
    
    <!-- EXAM ATTEMPT DISCLAIMER -->
    <div class="alert alert-danger mt-4 shadow-sm" role="alert">
      <h6 class="alert-heading mb-2">
        <i class="bi bi-exclamation-octagon-fill"></i> Important Exam Disclaimer
      </h6>
    
      <ul class="mb-2">
        <li>
          Once you click <strong>“Start Test”</strong>, one exam set is immediately
          <strong>deducted</strong> from your account.
        </li>
        <li>
          If you <strong>refresh</strong>, <strong>close</strong>, <strong>go back</strong>,
          lose internet connection, or <strong>leave the exam page</strong> for any reason,
          the exam will be considered <strong>attempted</strong>.
        </li>
        <li>
          <strong>The deducted exam set will NOT be restored</strong> under any circumstances.
        </li>
        <li>
          Ensure a <strong>stable internet connection</strong> and sufficient time before starting.
        </li>
      </ul>
    
      <p class="mb-0">
        By clicking <strong>“Start Test”</strong>, you acknowledge that you have read,
        understood, and agreed to the above conditions.
      </p>
    </div>
    
    <div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-md-10">
      <div class="card shadow-sm">
        <div class="card-body">

          <!-- PRODUCT TITLE + DETAILS -->
          <h4 class="mb-1" style="color:var(--primary)"><?php echo $product_name; ?></h4>
          <p class="text-muted mb-2">Set Number: <?php echo $set_id; ?></p>
          <hr>

          <!-- ROW-WISE SYLLABUS / INFO -->
          <h6 class="text-secondary mb-3">Test Details</h6>

          <div class="row g-3">

            <div class="col-sm-6">
              <div class="p-2 border rounded">
                <small class="text-muted">Total MCQs</small>
                <div><strong><?php echo $total_question; ?></strong></div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="p-2 border rounded">
                <small class="text-muted">Level 1 Questions</small>
                <div><strong><?php echo $level_1; ?></strong></div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="p-2 border rounded">
                <small class="text-muted">Level 2 Questions</small>
                <div><strong><?php echo $level_2; ?></strong></div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="p-2 border rounded">
                <small class="text-muted">Exam Duration</small>
                <div><strong><?php echo $exam_duration;  ?> minutes</strong></div>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="p-2 border rounded">
                <small class="text-muted">Negative Marking</small>
                <div><strong>YES (20%)</strong></div>
              </div>
            </div>
            
            <div class="col-sm-6">
              <div class="p-2 border rounded">
                <small class="text-muted">Mark Per Question</small>
                <div><strong><?php echo $mark_each_question; ?></strong></div>
              </div>
            </div>

          </div>

          <!-- BUTTONS -->
          <div class="mt-4 text-center">
            <!--<a href="#" class="btn btn-outline-primary me-2">Preview</a>-->
            <a href="exam.php?set_id=<?php echo $set_id ?>" class="btn btn-primary">Start Test</a>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

    <?php
        $get_question_pattern = mysqli_query($conn, "SELECT * FROM `question_patterns` WHERE `product_id` = '".$product_id."' ");

        while($row = mysqli_fetch_assoc($get_question_pattern)) {
            $question_weight = $row['question_weight'];
            $pattern_id = $row['id'];
            
            for($i = 1; $i<= $question_weight; $i++){
                
                $get_question_topic = mysqli_query($conn, "SELECT * FROM `question_topics` WHERE `pattern_id` = '".$pattern_id."' ORDER BY RAND() LIMIT 1");

                foreach ($get_question_topic as $key => $value) {
                    
                    $get_tag = mysqli_query($conn, "SELECT `tag` FROM `topics` WHERE `id` = '".$value['topic_id']."' ");
                    
                    while ($row = mysqli_fetch_assoc($get_tag)) {
                        $tag = $row['tag'];
                    }

                    $get_mcq = mysqli_query($conn, "SELECT `id` FROM `mcqs` WHERE `topic_id` = '".$value['topic_id']."' AND `verified` = 'true' AND `status` = 'live'  ORDER BY RAND() LIMIT 1 ");

                    if(mysqli_num_rows($get_mcq)==1){
                        while ($row = mysqli_fetch_assoc($get_mcq)) {
                            
                            $new_mcq_id = $row['id'];

                            $check_mcq = mysqli_query($conn, "SELECT * FROM `exam_questions` WHERE `mcq_id` = '".$new_mcq_id."' AND `set_id` = '".$set_id."'");

                            if(mysqli_num_rows($check_mcq)>0){
                                $i--;
                            }else{

                                $insert_question = mysqli_query($conn, "INSERT INTO `exam_questions`(`set_id`,`mcq_id`,`tag`)VALUES('".$set_id."','".$row['id']."', '".$tag."')");
                            }
                        }
                    }else{
                        $i--;
                    }
                }

            }
        }

    
        
    
    ?>

    <?php
        include("src/inc/footer.php");
    ?>
</body>
</html>