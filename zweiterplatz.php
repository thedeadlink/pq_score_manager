<?php
// dritterplatz.php: Show the team with the third highest total score
$scoresFile = __DIR__ . '/scores.json';
$configFile = __DIR__ . '/game_config.json';

// Load config for categories
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$numCategories = isset($config['Categories']) ? intval($config['Categories']) : 12;

// Load scores
$scores = file_exists($scoresFile) ? json_decode(file_get_contents($scoresFile), true) : [];

// Calculate total scores for each team
$teamTotals = [];
foreach ($scores as $team => $teamScores) {
    $sum = 0;
    for ($i = 0; $i < $numCategories; $i++) {
        if (isset($teamScores[$i])) {
            if (is_array($teamScores[$i]) && isset($teamScores[$i]['score'])) {
                $sum += intval($teamScores[$i]['score']);
            } else {
                $sum += intval($teamScores[$i]);
            }
        }
    }
    $teamTotals[$team] = $sum;
}

// Sort teams by total score descending
arsort($teamTotals);
$teams = array_keys($teamTotals);

// Get second place team
$secondTeam = isset($teams[1]) ? $teams[1] : null;
$secondScore = $secondTeam !== null ? $teamTotals[$secondTeam] : null;

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=ADLaM+Display&display=swap" rel="stylesheet">
    <meta http-equiv="refresh" content="5">
    <title>Zweiter Platz</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background: #f7f7f7;
            font-family: Arial, sans-serif;
        }
        .centerbox {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.08);
            padding: 48px 64px;
            text-align: center;
        }
        .teamname {
            font-size: 2.2em;
            font-weight: bold;
            margin-bottom: 16px;
        }
        .score {
            font-size: 1.6em;
            color: #2a7ae2;
        }
        .label {
            font-size: 1.1em;
            color: #888;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
    <div class="centerbox">
        <div class="label">2. Platz</div>
        <?php if ($secondTeam !== null): ?>
            <div class="teamname"><?php echo htmlspecialchars($secondTeam, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></div>
            <div class="score">Punkte: <?php echo $secondScore; ?></div>
        <?php else: ?>
            <div class="teamname">Nicht genug Teams</div>
            <div class="score">-</div>
        <?php endif; ?>
    </div>
</body>
</html>
