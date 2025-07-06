<?php
/**************************************************************************************************************************************
 * 
 * Authentication
 * 
 **************************************************************************************************************************************/ 
$authCookieName = 'pq_auth';
$clientsFile = __DIR__ . '/clients.json';
$authPassword = 'QWer1234';
$authenticated = false;
$numCategories = 3; // Fixed Number of Categories; If changed, delete game.json 

// Load client hashes
$clientHashes = file_exists($clientsFile) ? json_decode(file_get_contents($clientsFile), true) : [];
if (!is_array($clientHashes)) $clientHashes = [];

if (isset($_COOKIE[$authCookieName]) && in_array($_COOKIE[$authCookieName], $clientHashes, true)) {
    $authenticated = true;
}

if (!$authenticated && isset($_POST['auth_password'])) {
    if ($_POST['auth_password'] === $authPassword) {
        // Generate a random hash
        $hash = bin2hex(random_bytes(32));
        $clientHashes[] = $hash;
        file_put_contents($clientsFile, json_encode($clientHashes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        setcookie($authCookieName, $hash, time() + 60*60*24*30, '/'); // 30 days
        $authenticated = true;
        // Reload to set cookie
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $authError = 'Incorrect password.';
    }
}

if (!$authenticated) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Login</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<link rel="stylesheet" type="text/css" href="styles.css">';
    echo '</head><body>';
    echo '<div class="container">';
    echo '<h2>Authentication Required</h2>';
    if (!empty($authError)) echo '<div style="color:red;">' . htmlspecialchars($authError) . '</div>';
    echo '<form method="post">';
    echo '<input type="password" name="auth_password" placeholder="Enter password" required autofocus> ';
    echo '<button type="submit">Login</button>';
    echo '</form>';
    echo '</div>';
    echo '</body></html>';
    exit;
}

/**************************************************************************************************************************************
 * 
 * load game configuration or create it if not exists
 * 
 **************************************************************************************************************************************/ 
$game_config_file = __DIR__ . '/game.json';
if (file_exists($game_config_file)) {
    $game_config = json_decode(file_get_contents($game_config_file), true);
} else {
    $game_config = [
        'Teams' => [
            '0' => ['name' => 'Team A','categories' => array()],
            '1' => ['name' => 'Team B','categories' => array()],
            '2' => ['name' => 'Team C','categories' => array()],
        ]
    ];
    foreach($game_config['Teams'] as $idx => $team) {
        for($i=0; $i < $numCategories; $i++) {
            $game_config['Teams'][$idx]['categories'][$i] = ['score' => 0, 'joker' => false];
        }
    }
    file_put_contents($game_config_file, json_encode($game_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$teams = $game_config['Teams'];

#print_r($game_config); // Debugging line to check game configuration
#die();

/**************************************************************************************************************************************
 * 
 * Handle delete request
 * 
 **************************************************************************************************************************************/ 
if (isset($_POST['delete_team'])) {
    $deleteIndex = intval($_POST['delete_team']);
    if (isset($teams[$deleteIndex])) {
        $deletedTeam = $teams[$deleteIndex];
        array_splice($teams, $deleteIndex, 1);
        $game_config['Teams'] = $teams;
        file_put_contents($game_config_file, json_encode($game_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?manage_teams=1');
    exit;
}

/**************************************************************************************************************************************
 * 
 * Handle rename request
 * 
 **************************************************************************************************************************************/ 
if (!isset($_POST['delete_team']) && isset($_POST['rename_team_submit']) && !empty(trim($_POST['new_team_name']))) {
    $renameIndex = intval($_POST['rename_index']);
    if (isset($game_config['Teams'][$renameIndex])) {
        $game_config['Teams'][$renameIndex]['name'] = trim($_POST['new_team_name']);
        file_put_contents($game_config_file, json_encode($game_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?manage_teams=1');
    exit;
}

/**************************************************************************************************************************************
 * 
 * Handle add request (only if not deleting)
 * 
 **************************************************************************************************************************************/ 
if (!isset($_POST['delete_team']) && isset($_POST['add_team']) && !empty(trim($_POST['new_team']))) {
    $newTeam = trim($_POST['new_team']);
    if (!in_array($newTeam, $teams)) {
        $newTeamArray = [
            'name' => $newTeam
        ];
        for($i=0; $i < $numCategories; $i++) {
            $newTeamArray['categories'][$i] = ['score' => 0, 'joker' => false];
        }
        
        #print_r($newTeamArray); // Debugging line to check new team structure
        #die();
        
        $game_config['Teams'][] = $newTeamArray;
        //write updated teams to game_config.json
        file_put_contents($game_config_file, json_encode($game_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?manage_teams=1');
    exit;
}


/**************************************************************************************************************************************
 * 
 * Determine which section to show
 * 
 **************************************************************************************************************************************/ 

$showManageTeams = isset($_GET['manage_teams']) || isset($_POST['manage_teams']) || isset($_POST['delete_team']) || isset($_POST['add_team']);
$showModifyScore = isset($_GET['modify_score']) || isset($_POST['modify_score']) || isset($_GET['edit_category']) || isset($_POST['save_scores']);
$showRenameTeam = isset($_GET['rename_team']) || isset($_POST['rename_team']);

/**************************************************************************************************************************************
 * 
 * Show rename team section
 * 
 **************************************************************************************************************************************/ 
if ($showRenameTeam) {
    $renameIndex = intval($_POST['rename_team']);
    #print_r($renameIndex);
    #print_r($teams[$renameIndex]);

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Main Menu</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<link rel="stylesheet" type="text/css" href="styles.css">';
    echo '</head><body>';
    echo '<div class="container">';
    echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">&larr; Back to main menu</a>';
    echo '<h2>Rename Team</h2>';
    echo '<form method="post">';
    echo '<input type="text" name="new_team_name" placeholder="New team name" value="' . htmlspecialchars($teams[$renameIndex]['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '" required autofocus>';
    echo '<input type="hidden" name="rename_index" value="' . $renameIndex . '">';
    echo '&nbsp;<button type="submit" name="rename_team_submit">Rename Team</button>';
    echo '</form>';
    echo '</body></html>';
    exit;   
}
/**************************************************************************************************************************************
 * 
 * Show main menu 
 * 
 **************************************************************************************************************************************/ 

if (!$showManageTeams && !$showModifyScore && !$showRenameTeam) {
    // Main menu
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Main Menu</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<link rel="stylesheet" type="text/css" href="styles.css">';
    echo '</head><body>';
    echo '<div class="container">';
    echo '<h2>Main Menu</h2>';
    echo '<ul>';
    echo '<li><a href="?manage_teams=1">Manage Teams</a></li>';
    echo '<li><a href="?modify_score=1">Modify Score</a></li>';
    // ...add more menu entries here if needed...
    echo '</ul>';
    // List of teams section
    echo '<div style="margin-top:32px;">';
    echo '<h3>Current Number of Categories: <span style="font-weight:normal">' . $numCategories . '</span></h3>';
    echo '<h3>Current Teams</h3>';
    if (!empty($teams)) {
        echo '<ul>';
        foreach ($teams as $idx => $team) {
            echo '<li>' . htmlspecialchars($team['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<em>No teams available.</em>';
    }
    echo '</div>';
    echo '</div>';
    echo '</body></html>';
    exit;
}


/**************************************************************************************************************************************
 * 
 * show scores modification section
 * 
 **************************************************************************************************************************************/ 

if ($showModifyScore) {
    // Step 1: Select category
    if (!isset($_GET['edit_category']) && !isset($_POST['save_scores'])) {
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Modify Score</title>';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<link rel="stylesheet" type="text/css" href="styles.css">';
        echo '</head><body>';
        echo '<div class="container">';
        echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">&larr; Back to main menu</a>';
        echo '<h2>Select Category to Modify</h2>';
        echo '<ul>';
        for ($cat = 1; $cat <= $numCategories; $cat++) {
            echo '<li><a href="?edit_category=' . $cat . '">Category ' . $cat . '</a></li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '</body></html>';
        exit;
    }
    // Step 2: Show form for selected category
    $catIdx = isset($_GET['edit_category']) ? intval($_GET['edit_category']) - 1 : (isset($_POST['category']) ? intval($_POST['category']) : -1);
    if ($catIdx >= 0 && $catIdx < $numCategories && !isset($_POST['save_scores'])) {
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Modify Score</title>';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<link rel="stylesheet" type="text/css" href="styles.css">';
        echo '</head><body>';
        echo '<div class="container">';
        echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?modify_score=1">&larr; Back to category selection</a>';
        echo '<h2>Modify Scores for Category ' . ($catIdx + 1) . '</h2>';
        echo '<form method="post">';
        echo '<input type="hidden" name="category" value="' . ($catIdx + 1) . '">';
        foreach ($teams as $idx => $team) {
            $score = (isset($game_config['Teams'][$idx]['categories'][$catIdx]) && is_array($game_config['Teams'][$idx]['categories'][$catIdx])) ? (int)$game_config['Teams'][$idx]['categories'][$catIdx]['score'] : 0;
            $joker = (isset($game_config['Teams'][$idx]['categories'][$catIdx]) && is_array($game_config['Teams'][$idx]['categories'][$catIdx]) && !empty($game_config['Teams'][$idx]['categories'][$catIdx]['joker'])) ? true : false;
            echo '<div style="margin-bottom:8px;">';
            echo htmlspecialchars($team['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . ': ';
            echo '<div>';
            echo '<input type="number" name="scores[' . $idx . ']" value="' . $score . '" style="width:60px;"> ';
            echo '&nbsp;<label><input type="checkbox" name="joker[' . $idx . ']" value="1"' . ($joker ? ' checked' : '') . '> joker</label>';
            echo "</div>";
            echo '</div>';
        }
        echo '<button type="submit" name="save_scores">Save</button>';
        echo '</form>';
        echo '</div>';
        echo '</body></html>';
        exit;
    }
    // Step 3: Save scores
    if (isset($_POST['save_scores']) && isset($_POST['scores']) && isset($_POST['category'])) {
        $catIdx = intval($_POST['category']) - 1;
        foreach ($teams as $idx => $team) {
            $val = isset($_POST['scores'][$idx]) ? intval($_POST['scores'][$idx]) : 0;
            $joker = isset($_POST['joker'][$idx]) ? true : false;
            $game_config['Teams'][$idx]['categories'][$catIdx] = [ 'score' => $val, 'joker' => $joker ];
        }
        file_put_contents($game_config_file, json_encode($game_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        // Confirmation and back to category selection
        header('Location: ' . $_SERVER['PHP_SELF'] . '?modify_score=1');
        exit;
    }
}

/**************************************************************************************************************************************
 * 
 * show teams management section
 * 
 **************************************************************************************************************************************/ 

// Output the HTML form
echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Team Manager</title>';
echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
echo '<link rel="stylesheet" type="text/css" href="styles.css">';
echo '</head><body>';
echo '<div class="container">';
echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">&larr; Back to main menu</a>';
echo '<h2>Teams</h2>';
echo '<form method="post">';
foreach ($teams as $idx => $team) {
    echo '<div style="margin-bottom:6px;">';
    echo htmlspecialchars($team['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    echo '<div>';
    echo '<button type="submit" name="rename_team" value="' . $idx . '" ">Rename</button>&nbsp;';
    echo '<button type="submit" name="delete_team" value="' . $idx . '" onclick="return confirm(\'Delete this team?\');">Delete</button>';
    echo '</div>';
    echo '</div>';
}
echo '<div style="margin-top:16px;">';
echo '<input type="text" name="new_team" placeholder="New team name"> ';
echo '<button type="submit" name="add_team">Add Team</button>';
echo '</div>';
echo '</form>';
echo '</div>';
echo '</body></html>';
/**************************************************************************************************************************************
 * 
 * END OF - teams management section
 * 
 **************************************************************************************************************************************/
