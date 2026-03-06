<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

$user_id  = $_SESSION['id'];
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$subset   = isset($_GET['subset']) ? intval($_GET['subset']) : 1;
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;


// Fix: if group_id is missing in URL
if($group_id == 0 && $topic_id > 0){
    $res = mysqli_query($conn, "SELECT group_id FROM product_topics WHERE topic_id='$topic_id' LIMIT 1");
    if($row = mysqli_fetch_assoc($res)){
        $group_id = $row['group_id'];
    }
}

// Only validate topic/group access if not an AJAX request
if (!isset($_POST['ajax'])) {
    $topic_check = mysqli_query($conn, "
        SELECT t.*, pt.group_id
        FROM topics t
        JOIN product_topics pt ON t.id = pt.topic_id
        WHERE t.id = '$topic_id'
          AND pt.group_id = '$group_id'
        LIMIT 1
    ");

    if (!$topic = mysqli_fetch_assoc($topic_check)) {
        die("You do not have access to this topic or it is not a practice topic.");
    }
}

// FETCH COURSE ID
$course_id = 0;
$course_res = mysqli_query($conn,"SELECT course_id FROM topics WHERE id='$topic_id' LIMIT 1");
if($row = mysqli_fetch_assoc($course_res)){
    $course_id = $row['course_id'];
}

// ===================== AJAX: SAVE ANSWER =====================
// Inside AJAX save answer block:
if(isset($_POST['ajax']) && $_POST['ajax']=='1'){
    $mcq_id   = intval($_POST['mcq_id']);
    $selected = strtoupper(trim($_POST['selected']));
    $topic_id_ajax = intval($_POST['topic_id']);
    $product_id_ajax = intval($_POST['product_id']); // pass product_id via JS

    $qres = mysqli_query($conn,"SELECT answer, explanation FROM questions WHERE mcq_id='$mcq_id' LIMIT 1");
    $q = mysqli_fetch_assoc($qres);

    if(!$q){
        echo json_encode(['error'=>'Question not found']);
        exit;
    }

    $correct    = strtoupper($q['answer']);
    $is_correct = ($selected === $correct) ? 1 : 0;

    mysqli_query($conn,"
    INSERT INTO practice_answers (user_id, topic_id, mcq_id, selected_option, is_correct, product_id)
    VALUES ('$user_id','$topic_id_ajax','$mcq_id','$selected','$is_correct','$product_id_ajax')
    ON DUPLICATE KEY UPDATE
        selected_option='$selected',
        is_correct='$is_correct',
        updated_at=CURRENT_TIMESTAMP
");


    echo json_encode([
        'correct'     => $correct,
        'is_correct'  => $is_correct,
        'explanation' => $q['explanation']
    ]);
    exit;
}
// ===================== AJAX: RESET ANSWERS =====================
if(isset($_POST['ajax_reset']) && $_POST['ajax_reset']=='1'){
    $type = $_POST['type'];
    // Use product_id from URL / JS
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if($type=='subset'){
        mysqli_query($conn,"
            DELETE pa FROM practice_answers pa
            JOIN mcqs m ON pa.mcq_id=m.id
            WHERE pa.user_id='$user_id'
              AND pa.topic_id='$topic_id'
              AND m.sub_set_number='$subset'
              AND pa.product_id='$product_id'
        ");
    } elseif($type=='topic'){
        mysqli_query($conn,"
            DELETE FROM practice_answers 
            WHERE user_id='$user_id' 
              AND topic_id='$topic_id' 
              AND product_id='$product_id'
        ");
    } elseif($type=='course'){
        mysqli_query($conn,"
            DELETE FROM practice_answers 
            WHERE user_id='$user_id' 
              AND product_id='$product_id'
        ");
    }

    echo json_encode(['status'=>'ok']);
    exit;
}

// ===================== FETCH QUESTIONS =====================
$mcqs_res = mysqli_query($conn,"
    SELECT * FROM mcqs
    WHERE topic_id='$topic_id' AND sub_set_number='$subset'
    AND verified='true'
    AND status='live'
    ORDER BY id ASC
");

$questions = [];
while($mcq = mysqli_fetch_assoc($mcqs_res)){
    $qres = mysqli_query($conn,"SELECT * FROM questions WHERE mcq_id='{$mcq['id']}' LIMIT 1");
    $q = mysqli_fetch_assoc($qres);

    if($q){
        $ans_res = mysqli_query($conn,"
            SELECT selected_option
            FROM practice_answers
            WHERE user_id='$user_id' AND mcq_id='{$mcq['id']}'
            AND product_id='$product_id'
            LIMIT 1
        ");
        $ans = mysqli_fetch_assoc($ans_res);

        $q['mcq'] = $mcq;
        $q['selected'] = $ans['selected_option'] ?? null;
        $questions[] = $q;
    }
}

$total_questions = count($questions);
if($total_questions==0) die("No practice questions available.");

// Resume where left off
$q_index = 0;
$last_ans_res = mysqli_query($conn, "
    SELECT pa.mcq_id
    FROM practice_answers pa
    JOIN mcqs m ON pa.mcq_id = m.id
    WHERE pa.user_id = '$user_id'
      AND pa.topic_id = '$topic_id'
      AND pa.product_id = '$product_id'
      AND m.sub_set_number = '$subset'
    ORDER BY pa.id DESC
    LIMIT 1
");

if ($last_ans_row = mysqli_fetch_assoc($last_ans_res)) {
    $resume_mcq_id = $last_ans_row['mcq_id'];
    foreach ($questions as $idx => $q) {
        if ($q['mcq']['id'] == $resume_mcq_id) {
            $q_index = $idx;
            break;
        }
    }
} elseif(isset($_GET['q'])) {
    $q_index = max(0, min(intval($_GET['q']), $total_questions-1));
}

$current = $questions[$q_index];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Practice Mode</title>

<?php include("src/inc/links.php"); ?>
<style>
.option-box{border:1px solid #ddd;padding:12px;margin-bottom:8px;border-radius:5px;cursor:pointer;}
.option-box.correct{background:#d4edda;border-color:#28a745;}
.option-box.wrong{background:#f8d7da;border-color:#dc3545;}
.question-wrapper{position: relative;}
.report-btn{position: absolute;top: 10px; right: 10px; z-index: 10;}
@media (max-width: 767px) {
    #q-palette {display: flex; flex-wrap: nowrap; overflow-x: auto;}
    #q-palette .q-badge {flex: 0 0 auto; margin-right: 5px;}
}
/* Optional: subtle swipe hint for mobile */
#question-container::after {
    content: "Swipe ⬅️ ➡️";
    display: block;
    text-align: center;
    font-size: 0.75rem;
    color: #6c757d;
    opacity: 0.6;           /* subtle */
    margin-top: 5px;        /* tighter spacing */
    font-style: italic;      /* makes it look less heavy */
}

@media(min-width:768px){
    #question-container::after { display: none; } /* hide on desktop */
}


#question-wrapper {
    overflow: hidden;
    position: relative;
}

#question-container {
    transition: transform 0.3s ease, opacity 0.3s ease;
    position: relative;
    opacity: 1;
}


</style>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid mt-4">
<div class="mb-3">
    <a href="subsets.php?group_id=<?php echo $group_id; ?>&product_id=<?php echo $product_id; ?>" 
   class="btn btn-outline-primary btn-sm">← Back to Subsets</a>

</div>

<h5 style="color:var(--primary)">
    Practice Mode – <?php echo htmlspecialchars($topic['name']); ?> (Subset <?php echo $subset; ?>)
</h5>

<div class="row mt-3">
<div class="col-md-8">
<div class="border rounded shadow p-4">
<div class="question-wrapper border rounded shadow p-4" id="question-container">
    <button class="btn btn-warning btn-sm report-btn" id="report-btn">🚩 Report</button>
    <h6>Question <span id="q-number"><?php echo $q_index+1; ?></span> / <?php echo $total_questions; ?></h6>
    <div id="q-text"><?php echo $current['question']; ?></div>

    <?php
    $opts = ['A'=>$current['option_a'],'B'=>$current['option_b'],'C'=>$current['option_c'],'D'=>$current['option_d']];
    foreach($opts as $key=>$val):
        $checked = ($current['selected']==$key) ? 'checked' : '';
        $disabled = !empty($current['selected']) ? 'disabled' : '';
        $class = '';
        if(!empty($current['selected'])){
            $correct_answer = strtoupper($current['answer']);
            if($key==$correct_answer) $class='correct';
            elseif($key==$current['selected']) $class='wrong';
        }
    ?>
    <div class="option-box <?php echo $class; ?>" data-opt="<?php echo $key; ?>">
        <label>
        <input type="radio" class="answer" name="answer" value="<?php echo $key; ?>" data-mcq="<?php echo $current['mcq']['id']; ?>" <?php echo $checked.' '.$disabled; ?>>
        <?php echo htmlspecialchars($val); ?>
        </label>
    </div>
    <?php endforeach; ?>

    <div id="explanation" class="alert alert-info mt-3 <?php echo empty($current['selected'])?'d-none':''; ?>">
        <?php echo !empty($current['selected']) ? '<b>Explanation:</b><br>'.$current['explanation'] : ''; ?>
    </div>
</div>

<div class="d-flex justify-content-between mt-3">
    <button id="prev-btn" class="btn btn-secondary btn-sm" <?php echo ($q_index==0)?'disabled':''; ?>>Previous</button>
    <button id="next-btn" class="btn btn-primary btn-sm" <?php echo ($q_index==$total_questions-1)?'disabled':''; ?>>Next</button>
</div>
</div>
</div>

<div class="col-md-4">
<div class="border rounded shadow p-3">
<h6>Questions</h6>
<div class="d-flex flex-wrap" id="q-palette">
<?php for($i=0;$i<$total_questions;$i++): ?>
<a href="#" class="badge m-1 q-badge <?php
$sel=$questions[$i]['selected']??'';
if($sel){
    echo (strtoupper($questions[$i]['answer'])==$sel)?'bg-success':'bg-danger';
}else{
    echo ($i==$q_index)?'bg-primary':'bg-secondary';
}
?>" data-index="<?php echo $i; ?>"><?php echo $i+1; ?></a>
<?php endfor; ?>
</div>
</div>
</div>

</div>
</div>

<!-- REPORT MODAL -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Report Issue</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <textarea id="reportText" class="form-control" rows="4" placeholder="Describe the issue with this question"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="submitReport" class="btn btn-warning">Submit Report</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>
<script>
let questions = <?php echo json_encode($questions); ?>;
let currentIndex = <?php echo $q_index; ?>;
let totalQuestions = <?php echo $total_questions; ?>;
let currentMcqId = questions[currentIndex].mcq.id;
let isAnimating = false; // Prevent multiple rapid swipes

function updateCurrentMcqId() {
    currentMcqId = questions[currentIndex].mcq.id;
}

function loadQuestion(index) {
    currentIndex = index;
    updateCurrentMcqId();
    let q = questions[index];
    $('#q-number').text(index + 1);
    $('#q-text').html(q.question);

    // Only show explanation if it exists
    if (q.explanation && q.explanation.trim() !== '') {
        $('#explanation').removeClass('d-none').html('<b>Explanation:</b><br>' + q.explanation);
    } else {
        $('#explanation').addClass('d-none').html('');
    }

    let $container = $('#question-container');
    $container.find('.option-box').remove();

    let opts = {'A': q.option_a, 'B': q.option_b, 'C': q.option_c, 'D': q.option_d};
    for (let key in opts) {
        let val = opts[key];
        let selected = q.selected;
        let correct_answer = q.answer.toUpperCase();
        let className = '';
        if (selected) {
            if (key === correct_answer) className = 'correct';
            else if (key === selected) className = 'wrong';
        }
        let html = `<div class="option-box ${className}" data-opt="${key}">
                        <label>
                            <input type="radio" class="answer" name="answer" value="${key}" data-mcq="${q.mcq.id}" ${selected ? 'disabled' : ''}>
                            ${val}
                        </label>
                    </div>`;
        $container.append(html);
    }

    $('#prev-btn').prop('disabled', index === 0);
    $('#next-btn').prop('disabled', index === totalQuestions - 1);

    // UPDATE PALETTE COLORS
    $('#q-palette .q-badge').each(function(i){
        let sel = questions[i].selected;
        $(this).removeClass('bg-primary bg-secondary bg-success bg-danger');
    
        if(i === index){
            $(this).addClass('bg-primary'); // current question
        } else if(sel){
            if(sel.toUpperCase() === questions[i].answer.toUpperCase()) $(this).addClass('bg-success');
            else $(this).addClass('bg-danger');
        } else {
            $(this).addClass('bg-secondary'); // unanswered
        }
    });
}

function saveAnswer(mcq_id, selected, topic_id, product_id) {
    $.post('practice.php', {
        ajax: 1,
        mcq_id: mcq_id,
        selected: selected,
        topic_id: topic_id,
        product_id: product_id
    }, function(res) {
        if (res.error) return alert(res.error);
        questions[currentIndex].selected = selected;

        $('#question-container .option-box').each(function() {
            let opt = $(this).data('opt');
            $(this).removeClass('correct wrong');
            if (opt === res.correct) $(this).addClass('correct');
            if (opt === selected && opt !== res.correct) $(this).addClass('wrong');
            $(this).find('input.answer').prop('disabled', true);
        });

        // Only show explanation if returned
        if(res.explanation && res.explanation.trim() !== '') {
            $('#explanation').removeClass('d-none').html('<b>Explanation:</b><br>' + res.explanation);
        } else {
            $('#explanation').addClass('d-none').html('');
        }

        let $badge = $('#q-palette .q-badge').eq(currentIndex);
        if (selected === res.correct) $badge.removeClass('bg-secondary bg-primary bg-danger').addClass('bg-success');
        else $badge.removeClass('bg-secondary bg-primary bg-success').addClass('bg-danger');
    }, 'json');
}

// Handle option selection
$(document).on('click', '.answer', function() {
    let mcq_id = $(this).data('mcq');
    let selected = $(this).val();
    saveAnswer(mcq_id, selected, <?php echo $topic_id; ?>, <?php echo $product_id; ?>);
});

// Swipe animation
function animateSwipe(nextIndex, direction) {
    if (nextIndex < 0 || nextIndex >= totalQuestions) return;
    if (isAnimating) return;
    isAnimating = true;

    const $container = $('#question-container');

    // Slide out current question
    $container.css({
        'transition': 'transform 0.3s ease, opacity 0.3s ease',
        'transform': direction === 'left' ? 'translateX(-100%)' : 'translateX(100%)',
        'opacity': 0
    });

    $container.one('transitionend', function() {
        // Load next question
        currentIndex = nextIndex;
        loadQuestion(nextIndex);

        // Update palette highlight
        $('#q-palette .q-badge').removeClass('bg-primary').eq(currentIndex).addClass('bg-primary');

        // Position new question off-screen opposite to swipe
        $container.css('transition', 'none');
        $container.css('transform', direction === 'left' ? 'translateX(100%)' : 'translateX(-100%)');
        $container.css('opacity', 1);

        // Force reflow
        $container[0].offsetHeight;

        // Slide new question into view
        $container.css('transition', 'transform 0.3s ease, opacity 0.3s ease');
        $container.css('transform', 'translateX(0)');

        $container.one('transitionend', function() {
            isAnimating = false;
        });
    });
}

// Hammer.js swipe gestures
const questionContainer = document.getElementById('question-container');
if (questionContainer) {
    const hammer = new Hammer(questionContainer);
    hammer.on('swipeleft', () => animateSwipe(currentIndex + 1, 'left'));
    hammer.on('swiperight', () => animateSwipe(currentIndex - 1, 'right'));
}

// Next / Previous buttons
$('#next-btn').off('click').on('click', () => animateSwipe(currentIndex + 1, 'left'));
$('#prev-btn').off('click').on('click', () => animateSwipe(currentIndex - 1, 'right'));

// Question palette clicks
$(document).off('click', '.q-badge').on('click', '.q-badge', function() {
    let index = $(this).data('index');
    if (index === currentIndex) return;
    let direction = (index > currentIndex) ? 'left' : 'right';
    animateSwipe(index, direction);
});

// Report button
$('#report-btn').click(function() {
    updateCurrentMcqId();
    $('#reportText').val('');
    var reportModal = new bootstrap.Modal(document.getElementById('reportModal'));
    reportModal.show();
});

// Submit report
$('#submitReport').click(function() {
    let reason = $('#reportText').val().trim();
    if (!reason) { alert('Please enter a description'); return; }
    $.post('report-mcq.php', { mcq_id: currentMcqId, reason: reason }, function(res) {
        if (res.status === 'ok') {
            alert('Thank you! The issue has been reported.');
            var reportModal = bootstrap.Modal.getInstance(document.getElementById('reportModal'));
            reportModal.hide();
        } else {
            alert('Error: ' + res.message);
        }
    }, 'json');
});
</script>


<?php include("src/inc/footer.php"); ?>
</body>
</html>
