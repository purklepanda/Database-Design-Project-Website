<?php
require_once 'settings.php';

// Getting the archers data from archer_table
$archers_result = mysqli_query($conn, "SELECT archery_vic_id, first_name, last_name, default_equipment_id FROM archer_table ORDER BY first_name, last_name");

// Getting all of the rounds
$rounds_result = mysqli_query($conn, "SELECT id, round_name FROM round_table ORDER BY round_name");

// Getting the equipment
$equipment_result = mysqli_query($conn, "SELECT id, bow_type FROM equipment_table ORDER BY bow_type");

// When the form is submitted
$error = '';        // just in case smth goes wrong when submitting the form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // From the drop down list
    $archer_id    = (int)$_POST['archer_id'];
    $equipment_id = (int)$_POST['equipment_id'];
    $round_id     = (int)$_POST['round_id'];

    if (!$archer_id) {
        $error = 'Please select an archer.';
    } elseif (!$equipment_id) {
        $error = 'Please select equipment.';
    } elseif (!$round_id) {
        $error = 'Please select a round.';
    } else {
        // Going to the score page
        header("Location: score.php?archer_id=$archer_id&equipment_id=$equipment_id&round_id=$round_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archery Information</title>
    <meta charset="utf-8" >
    <meta name="description" content="Choosing an archer for the archery recording page" >
    <meta name="keywords" content="Archery, Score, Rounds, Bows" >
    <link rel="stylesheet" href="../styles/style.css">

</head>
<body>
<div class="container">
    <h1>Archery Score Recorder</h1>

    <?php if ($error): ?>
        <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <!--     the form itself in the website -->
    <form method="POST" action="index.php">

        <div class="field">
            <label for="archer_id">Archer</label>
            <select name="archer_id" id="archer_id" required>   <!-- name is for form submission -->
                <option value="">— Select archer —</option>
                <?php
                mysqli_data_seek($archers_result, 0);           // to like be able to loop through the results of the query again
                while ($a = mysqli_fetch_assoc($archers_result)):   // to be able to create the multiple archers in the datase as a drop down option
                    $selected = (isset($_POST['archer_id']) && $_POST['archer_id'] == $a['archery_vic_id']) ? 'selected' : ''; // to be able to keep the selected archer after form submission jic not all fields filled
                ?>
                // to be able to create the multiple archers in the datase as a drop down option
                    <option value="<?= $a['archery_vic_id'] ?>" data-equip="<?= $a['default_equipment_id'] ?>" <?= $selected ?>>
                        <?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="field">
            <label for="equipment_id">Equipment</label>
            <select name="equipment_id" id="equipment_id" required>
                <option value="">— Select equipment —</option>
                <?php
                mysqli_data_seek($equipment_result, 0);
                while ($eq = mysqli_fetch_assoc($equipment_result)):
                    $selected = (isset($_POST['equipment_id']) && $_POST['equipment_id'] == $eq['id']) ? 'selected' : '';
                ?>
                    <option value="<?= $eq['id'] ?>" <?= $selected ?>><?= htmlspecialchars($eq['bow_type']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="field">
            <label for="round_id">Round</label>
            <select name="round_id" id="round_id" required>
                <option value="">— Select round —</option>
                <?php
                mysqli_data_seek($rounds_result, 0);
                while ($r = mysqli_fetch_assoc($rounds_result)):
                    $selected = (isset($_POST['round_id']) && $_POST['round_id'] == $r['id']) ? 'selected' : '';
                ?>
                    <option value="<?= $r['id'] ?>" <?= $selected ?>><?= htmlspecialchars($r['round_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit" class="btn">Done</button>
    </form>
</div>
</body>
</html>
