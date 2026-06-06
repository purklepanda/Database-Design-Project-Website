<?php
require_once 'settings.php';

// Grabbing the stuff from the post from the index form which is the form to put in the archer and the range round bow thing
$archer_id    = (int)($_GET['archer_id']    ?? $_POST['archer_id']    ?? 0);
$round_id     = (int)($_GET['round_id']     ?? $_POST['round_id']     ?? 0);
$equipment_id = (int)($_GET['equipment_id'] ?? $_POST['equipment_id'] ?? 0);

// will go back to the index page if there isnt like a valid archer, round or equipment
if (!$archer_id || !$round_id || !$equipment_id) {
    header('Location: index.php');
    exit;
}

// Load names for display based on the archer id given
$res = mysqli_query($conn, "SELECT first_name, last_name FROM archer_table WHERE archery_vic_id = $archer_id");
$archer = mysqli_fetch_assoc($res);

$res = mysqli_query($conn, "SELECT round_name FROM round_table WHERE id = $round_id");
$round = mysqli_fetch_assoc($res);

$res = mysqli_query($conn, "SELECT bow_type FROM equipment_table WHERE id = $equipment_id");
$equip = mysqli_fetch_assoc($res);

// jic it comes back empty
if (!$archer || !$round || !$equip) {
    header('Location: index.php');
    exit;
}

// Valid arrow values
$valid_values = ['X', 'M', '10', '9', '8', '7', '6', '5', '4', '3', '2', '1', '0'];


function arrow_score($val) {
    $v = strtoupper(trim($val));
    if ($v === 'X') return 10; // triple equals is like theyre for sure the same down to like whitespace and stuff
    if ($v === 'M') return 0;
    if (is_numeric($v) && (int)$v >= 0 && (int)$v <= 10) return (int)$v;
    return null;
}

$errors   = [];
$saved    = false;  // whether or not the scores have been saved. based on that save button of the form
$score_id = null;
$ends     = []; // holds all of the arrow values

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {

    // the ends
    for ($e = 1; $e <= 6; $e++) {
        $ends[$e] = [];
        // the arrows themselves and like savings them as like e1a2 or smth liek that -> end 1 arrow 2
        for ($a = 1; $a <= 6; $a++) {
            $ends[$e][$a] = strtoupper(trim($_POST["e{$e}a{$a}"] ?? ''));
        }
    }

    // Validation
    for ($e = 1; $e <= 6; $e++) {
        for ($a = 1; $a <= 6; $a++) {
            $v = $ends[$e][$a];
            if ($v === '') {
                $errors[] = "End $e, Arrow $a is empty.";
            } elseif (!in_array($v, $valid_values)) {
                $errors[] = "End $e, Arrow $a: \"$v\" is not valid. Use X, M, or 0–10.";
            }
        }
        // Check descending order within end
        if (empty($errors)) {
            $scores = [];
            for ($a = 1; $a <= 6; $a++) {
                $scores[$a] = arrow_score($ends[$e][$a]);
            }
            for ($a = 2; $a <= 6; $a++) {
                if ($scores[$a] > $scores[$a - 1]) {
                    $errors[] = "End $e: Arrow $a (" . $ends[$e][$a] . ") is larger than Arrow " . ($a-1) . " (" . $ends[$e][$a-1] . "). Enter scores largest first.";
                }
            }
        }
    }

    if (empty($errors)) {
        // Insert into score_table
        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO score_table (archer_id, round_id, equipment_id, submit_datetime, is_approved)
                VALUES ($archer_id, $round_id, $equipment_id, '$now', 0)";
        mysqli_query($conn, $sql);
        $score_id = mysqli_insert_id($conn);

        // Insert each end into end_table
        for ($e = 1; $e <= 6; $e++) {
            $arrows = [];
            for ($a = 1; $a <= 6; $a++) {
                $arrows[] = "'" . mysqli_real_escape_string($conn, $ends[$e][$a]) . "'";
            }
            $arrow_vals = implode(', ', $arrows);
            $sql2 = "INSERT INTO end_table (score_id, range_position, end_position, arrow_1, arrow_2, arrow_3, arrow_4, arrow_5, arrow_6)
                     VALUES ($score_id, 1, $e, $arrow_vals)";
            mysqli_query($conn, $sql2);
        }

        $saved = true;
    }
}

// Calculate totals for display (after submit attempt)
$end_totals = [];
$grand_total = 0;
for ($e = 1; $e <= 6; $e++) {
    $t = 0;
    if (!empty($ends[$e])) {
        for ($a = 1; $a <= 6; $a++) {
            $s = arrow_score($ends[$e][$a] ?? '');
            if ($s !== null) $t += $s;
        }
    }
    $end_totals[$e] = $t;
    $grand_total += $t;
}

function val($ends, $e, $a) {
    return htmlspecialchars($ends[$e][$a] ?? '');
}

function is_invalid($ends, $errors_text, $e, $a) {
    // Simple check: mark cell red if its value appears in an error message for that end/arrow
    return strpos($errors_text, "End $e, Arrow $a") !== false;
}

$errors_text = implode(' | ', $errors);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archery Score Record</title>
    <meta charset="utf-8" >
    <meta name="description" content="Archery Scoring Website" >
    <meta name="keywords" content="Archery, Score, Rounds, Bows" >
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
<div class="container">
    <h1>Archery Score Recorder</h1>

    <!-- going back to the index if everything works and the score is saved -->
    <?php if ($saved): ?>
        <div class="msg success">
            Score saved successfully! (Score ID: <?= $score_id ?>)
        </div>
        <a href="index.php" class="btn">Record Another Score</a>

    <?php else: ?>

        <div class="info-box">
            <strong><?= htmlspecialchars($archer['first_name'] . ' ' . $archer['last_name']) ?></strong><br>
            <?= htmlspecialchars($round['round_name']) ?> &mdash; <?= htmlspecialchars($equip['bow_type']) ?>
        </div>

        <p style="font-size:12px; color:#555; margin-bottom:16px;">
            Enter each end with the <strong>largest score first</strong>. Use <strong>X</strong> for a bullseye (10) and <strong>M</strong> for a miss (0).
        </p>

        <?php if (!empty($errors)): ?>
            <div class="msg error">
                <?php foreach ($errors as $err): ?>
                    <?= htmlspecialchars($err) ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="score.php">
            <!-- from the index page when the archer gave the details -->
            <input type="hidden" name="archer_id"    value="<?= $archer_id ?>">
            <input type="hidden" name="round_id"     value="<?= $round_id ?>">
            <input type="hidden" name="equipment_id" value="<?= $equipment_id ?>">

            <!-- to make 6 ends grid box thing -> previously used from web tech assignment -->  
            <?php for ($e = 1; $e <= 6; $e++): ?>
            <div class="end-block">
                <div class="end-label">End <?= $e ?></div>
                <div class="arrows-row">
                    <?php for ($a = 1; $a <= 6; $a++):
                        $cell_invalid = is_invalid($ends, $errors_text, $e, $a);
                    ?>
                    <div class="arrow-cell">
                        <input
                            type="text"
                            name="e<?= $e ?>a<?= $a ?>"
                            class="arrow-input<?= $cell_invalid ? ' invalid' : '' ?>"
                            value="<?= val($ends, $e, $a) ?>"
                            maxlength="2"
                            placeholder="—"
                        >
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endfor; ?>

            <a href="index.php" class="btn btn-back">Back</a>
            <button type="submit" name="save" value="1" class="btn">Save Score</button>
        </form>

    <?php endif; ?>
</div>
</body>
</html>
