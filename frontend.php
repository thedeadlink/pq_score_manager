<?php
// Data for teams and scores
$game_config_default = [
    'teams' => [
        'Team 1'
    ],
    'Categories' => 1
];
if (file_exists('game_config.json')) {
    $game_config = json_decode(file_get_contents('game_config.json'), true);
} else {
    $game_config = $game_config_default;
    file_put_contents('game_config.json', json_encode($game_config_default, JSON_PRETTY_PRINT));
}
// Load scores and calculate sum for each team
$scores = [];
if (file_exists('scores.json')) {
    $scores = json_decode(file_get_contents('scores.json'), true);
}
$teamSums = [];
foreach ($game_config['teams'] as $team) {
    $teamSums[$team] = isset($scores[$team]) ? array_sum($scores[$team]) : 0;
}
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