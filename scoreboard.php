<?php
// Load config and scores
$jsonFile = __DIR__ . '/game_config.json';
$config = json_decode(file_get_contents($jsonFile), true);
$teams = isset($config['teams']) ? $config['teams'] : [];
$numCategories = isset($config['Categories']) ? intval($config['Categories']) : 1;

// Load scores from a file or initialize if not present
$scoresFile = __DIR__ . '/scores.json';
if (file_exists($scoresFile)) {
    $scores = json_decode(file_get_contents($scoresFile), true);
} else {
    // Initialize scores: team => [0, 0, ...]
    $scores = [];
    foreach ($teams as $team) {
        $scores[$team] = array_fill(0, $numCategories, 0);
    }
    file_put_contents($scoresFile, json_encode($scores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Ensure all teams and categories are present in scores
foreach ($teams as $team) {
    if (!isset($scores[$team]) || !is_array($scores[$team])) {
        $scores[$team] = array_fill(0, $numCategories, 0);
    } elseif (count($scores[$team]) < $numCategories) {
        $scores[$team] = array_pad($scores[$team], $numCategories, 0);
    } elseif (count($scores[$team]) > $numCategories) {
        $scores[$team] = array_slice($scores[$team], 0, $numCategories);
    }
}

// Remove scores for teams that no longer exist
foreach (array_keys($scores) as $team) {
    if (!in_array($team, $teams)) {
        unset($scores[$team]);
    }
}

// Calculate sums and sort
$teamSums = [];
foreach ($teams as $team) {
    $teamSums[$team] = array_sum($scores[$team]);
}
arsort($teamSums);

// Output HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scoreboard</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=ADLaM+Display&display=swap" rel="stylesheet">
    <meta http-equiv="refresh" content="5">
</head>
<body>
    <h2>Scoreboard</h2>
    <table cellpadding="6" style="border-collapse:collapse;">
        <tr>
            <th class="scoreboard_headline">#</th>
            <th class="scoreboard_headline">Team</th>
            <?php for ($cat = 1; $cat <= $numCategories; $cat++): ?>
                <th class="scoreboard_headline">Runde <?php echo $cat; ?></th>
            <?php endfor; ?>
            <th class="scoreboard_headline">Total</th>
        </tr>
        <?php 
        $rowNum = 1;
        foreach ($teamSums as $team => $sum): ?>
            <tr>
                <td class="scoreboard_cell"><?php echo $rowNum . '.'; ?></td>
                <?php
                $len = mb_strlen($team);
                if ($len < 10) {
                    $class = 'scorebox_headline_large';
                } elseif ($len < 20) {
                    $class = 'scorebox_headline_normal';
                } else {
                    $class = 'scorebox_headline_small';
                }
                ?>
                <td class="<?php echo $class; ?>"><?php echo htmlspecialchars($team, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></td>
                <?php 
                $total = 0;
                for ($cat = 0; $cat < $numCategories; $cat++) {
                    $cell = isset($scores[$team][$cat]) ? $scores[$team][$cat] : 0;
                    $joker = false;
                    $score = 0;
                    if (is_array($cell)) {
                        $score = isset($cell['score']) ? (int)$cell['score'] : 0;
                        $joker = !empty($cell['joker']);
                    } else {
                        $score = (int)$cell;
                    }
                    $displayScore = $joker ? ($score * 2) : $score;
                    $total += $displayScore;
                    echo '<td class="scoreboard_cell">' . $displayScore;
                    if ($joker) {
                        echo ' 🃏';
                    }
                    echo '</td>';
                }
                for ($i = $cat; $i < $numCategories; $i++) {
                    echo '<td class="scoreboard_cell">0</td>';
                }
                echo '<td class="scoreboard_cell"><strong>' . $total . '</strong></td>';
                ?>
            </tr>
        <?php $rowNum++; endforeach; ?>
    </table>
</body>
</html>
