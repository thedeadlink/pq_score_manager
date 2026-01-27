<?php


// load game configuration or die it if not exists
$game_config_file = __DIR__ . '/game_config.json';
if (file_exists($game_config_file)) {
    $game_config = json_decode(file_get_contents($game_config_file), true);
} else {
    echo "game_config.json not found. Please create it with calling backend.php first.";
    die();
}

// Load scores from a file or initialize if not present
$scoresFile = __DIR__ . '/scores.json';
if (file_exists($scoresFile)) {
    $scores = json_decode(file_get_contents($scoresFile), true);
} else {
    // Initialize scores: team => [0, 0, ...]
    $scores = [];
    
    foreach ($teams as $number => $team) {
            for ($i = 0; $i < $numCategories; $i++) {
                $scores[$team][$i] = 
                    ['score' => 0, 'joker' => false]; // Initialize with score and joker
            }
    }
    print_r($scores); // Debugging line to check initial scores structure
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
#echo "Scores\n";
#print_r($scores); // Debugging line to check scores structure

// Remove scores for teams that no longer exist
foreach (array_keys($scores) as $team) {
    if (!in_array($team, $teams)) {
        unset($scores[$team]);
    }
}
#print_r($teams); // Debugging line to check scores after cleanup

// Calculate sums and sort
$teamSums = [];
foreach ($teams as $team) {
    $teamSums[$team] = 0; // Initialize team sums

    for ($i = 0; $i < $numCategories; $i++) {
        if($scores[$team][$i]['joker'] === true) {
            $teamSums[$team] += 2*($scores[$team][$i]['score']);
        } else {
            $teamSums[$team] += $scores[$team][$i]['score'];
        }       
    }        
}
arsort($teamSums);

?>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="styles.css">
        <link href="https://fonts.googleapis.com/css2?family=ADLaM+Display&display=swap" rel="stylesheet">
        <meta http-equiv="refresh" content="5">
    </head>
    <body>
        <table class="scorebox_table">
            <tr>
                <?php foreach ($game_config['teams'] as $team): 
                    $len = mb_strlen($team);
                    if ($len < 10) {
                        $class = 'scorebox_headline_large';
                    } elseif ($len < 20) {
                        $class = 'scorebox_headline_normal';
                    } else {
                        $class = 'scorebox_headline_small';
                    }
                ?>
                    <td class="<?php echo $class; ?>"><?php echo htmlspecialchars($team); ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <?php foreach ($game_config['teams'] as $team): ?>
                    <td class="scorebox_scoreline"><?php echo $teamSums[$team]; ?></td>
                <?php endforeach; ?>
            </tr>
        </table>
    </body>
</html>