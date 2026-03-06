<?php
include("src/db/db_conn.php");
include("src/db/session.php");
include("src/db/privileges.php");

// Group ID
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
if($group_id <= 0){
    die("Invalid group");
}

// Pagination
$per_page = 50;
$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$offset = ($page-1) * $per_page;

// Fetch group info including course
$group_res = mysqli_query($conn,"
    SELECT g.group_name, p.id AS product_id, p.course_id, c.name AS course_name
    FROM product_topic_groups g
    JOIN products p ON p.id = g.product_id
    JOIN courses c ON c.id = p.course_id
    WHERE g.id='$group_id'
    LIMIT 1
");
if(mysqli_num_rows($group_res)==0){
    die("Group not found");
}
$group = mysqli_fetch_assoc($group_res);

// Leaderboard query (all for rank calculation)
$leaderboard_res_all = mysqli_query($conn,"
    SELECT 
        u.user_id,
        CONCAT(u.first_name,' ',u.last_name) AS student_name,
        COUNT(pa.id) AS attempted,
        SUM(pa.is_correct=1) AS correct,
        SUM(pa.is_correct=0) AS wrong,
        ROUND((SUM(pa.is_correct=1)/COUNT(pa.id))*100,2) AS accuracy
    FROM practice_answers pa
    JOIN users u ON u.user_id = pa.user_id
    JOIN mcqs m ON m.id = pa.mcq_id
    JOIN product_topics pt ON pt.topic_id = m.topic_id
    WHERE pt.group_id='$group_id'
      AND m.verified='true'
      AND m.status='live'
      AND pa.product_id='{$group['product_id']}'   -- ADDED
    GROUP BY pa.user_id
    HAVING attempted > 0
    ORDER BY correct DESC, accuracy DESC
");


// Process all rows to get ranks
$leaderboard_all = [];
$user_rank_info = null;
$rank_counter = 1;
while($row = mysqli_fetch_assoc($leaderboard_res_all)){
    $row['rank'] = $rank_counter;
    if($row['user_id']==$user_id) $user_rank_info = $row;
    $leaderboard_all[] = $row;
    $rank_counter++;
}

// Pagination slice
$total_users = count($leaderboard_all);
$total_pages = ceil($total_users / $per_page);
$leaderboard = array_slice($leaderboard_all, $offset, $per_page);

// Determine page where user rank exists
$my_rank_page = $user_rank_info ? ceil($user_rank_info['rank'] / $per_page) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leaderboard - <?php echo htmlspecialchars($group['group_name']); ?></title>
<?php include("src/inc/links.php"); ?>
<style>
/* Rank Highlights */
.rank-1{background:#ffd700;font-weight:bold;}
.rank-2{background:#c0c0c0;font-weight:bold;}
.rank-3{background:#cd7f32;font-weight:bold;}

/* Table styling */
.table td, .table th { vertical-align: middle; text-align: center; padding:0.75rem; }

/* Mobile card view */
@media (max-width: 767px) {
    .table thead { display: none; }
    .table tr { display: block; margin-bottom: 10px; border:1px solid #ddd; border-radius:5px; padding:10px; }
    .table td { display: flex; justify-content: space-between; padding:5px 0; border-bottom:1px dashed #ccc; }
    .table td:last-child { border-bottom:none; }
    .table td:before { content: attr(data-label); font-weight:bold; }
}

/* User Rank Card */
.user-rank-card{
    border:2px solid var(--primary);
    padding:15px;
    border-radius:10px;
    margin-bottom:20px;
    text-align:center;
    background:#f8f9fa;
}
</style>
</head>
<body>

<?php include("src/inc/header.php"); ?>

<div class="container-fluid mt-4">

<div class="text-center mb-3">
    <h4>🏆 Leaderboard</h4>
    <p class="text-muted">
        Course: <?php echo htmlspecialchars($group['course_name']); ?><br>
        Topic: <?php echo htmlspecialchars($group['group_name']); ?>
    </p>
</div>

<!-- Back to Topics -->
<div class="mb-3">
    <a href="topics.php?product_id=<?php echo $group['product_id']; ?>" 
       class="btn btn-outline-primary btn-sm">
        ← Back to Topics
    </a>
</div>

<!-- User Rank Card -->
<?php if($user_rank_info): ?>
<div class="user-rank-card">
    <h5>Your Rank: #<?php echo $user_rank_info['rank']; ?></h5>
    <p>
        Name: <?php echo htmlspecialchars($user_rank_info['student_name']); ?><br>
        Attempted: <?php echo $user_rank_info['attempted']; ?> | 
        Correct: <?php echo $user_rank_info['correct']; ?> | 
        Wrong: <?php echo $user_rank_info['wrong']; ?> | 
        Accuracy: <?php echo $user_rank_info['accuracy']; ?>%
    </p>
</div>

<div class="text-center mb-3">
    <a href="?group_id=<?php echo $group_id; ?>&page=<?php echo $my_rank_page; ?>#my-row" 
       class="btn btn-primary btn-sm">Go to My Rank</a>
</div>
<?php else: ?>
<div class="user-rank-card">
    <p class="text-muted">You haven't attempted any questions yet.</p>
</div>
<?php endif; ?>

<!-- Leaderboard Table -->
<div class="table-responsive">
<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>Rank</th>
            <th>Student</th>
            <th>Attempted</th>
            <th>Correct</th>
            <th>Wrong</th>
            <th>Accuracy %</th>
        </tr>
    </thead>
    <tbody>
    <?php
    if(empty($leaderboard)){
        echo '<tr><td colspan="6" class="text-center text-muted">No data yet</td></tr>';
    } else {
        foreach($leaderboard as $row){
            $rank_class = ($row['rank']==1?'rank-1':($row['rank']==2?'rank-2':($row['rank']==3?'rank-3':'')));
            $rank_icon = ($row['rank']==1?'🏆':($row['rank']==2?'🥈':($row['rank']==3?'🥉':'')));
            echo '<tr id="'.($row['user_id']==$user_id?'my-row':'').'" class="'.$rank_class.'">';
            echo '<td data-label="Rank">'.$rank_icon.' '.$row['rank'].'</td>';
            echo '<td data-label="Student">'.htmlspecialchars($row['student_name']).'</td>';
            echo '<td data-label="Attempted">'.$row['attempted'].'</td>';
            echo '<td data-label="Correct">'.$row['correct'].'</td>';
            echo '<td data-label="Wrong">'.$row['wrong'].'</td>';
            echo '<td data-label="Accuracy %">'.$row['accuracy'].'%</td>';
            echo '</tr>';
        }
    }
    ?>
    </tbody>
</table>
</div>

<!-- Pagination -->
<?php if($total_pages > 1): ?>
<nav aria-label="Leaderboard pagination">
    <ul class="pagination justify-content-center mt-3">
        <?php if($page>1): ?>
        <li class="page-item">
            <a class="page-link" href="?group_id=<?php echo $group_id; ?>&page=<?php echo $page-1; ?>">Previous</a>
        </li>
        <?php endif; ?>
        <?php for($p=1;$p<=$total_pages;$p++): ?>
        <li class="page-item <?php echo ($p==$page)?'active':''; ?>">
            <a class="page-link" href="?group_id=<?php echo $group_id; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
        </li>
        <?php endfor; ?>
        <?php if($page<$total_pages): ?>
        <li class="page-item">
            <a class="page-link" href="?group_id=<?php echo $group_id; ?>&page=<?php echo $page+1; ?>">Next</a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

</div>

<!-- Smooth Scroll Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    if(window.location.hash === "#my-row"){
        const myRow = document.getElementById("my-row");
        if(myRow){
            myRow.scrollIntoView({behavior: "smooth", block: "center"});
        }
    }
});
</script>

<?php include("src/inc/footer.php"); ?>
</body>
</html>
