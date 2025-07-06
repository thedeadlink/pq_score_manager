<?php
// --- Simple authentication ---
$authCookieName = 'pq_auth';
$clientsFile = __DIR__ . '/clients.json';
$authPassword = 'QWer1234';
$authenticated = false;

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

// Path to the JSON config file
$jsonFile = __DIR__ . '/game_config.json';

// Read the JSON file
$config = json_decode(file_get_contents($jsonFile), true);
$teams = isset($config['teams']) ? $config['teams'] : [];

// Handle delete request
if (isset($_POST['delete_team'])) {
    $deleteIndex = intval($_POST['delete_team']);
    if (isset($teams[$deleteIndex])) {
        array_splice($teams, $deleteIndex, 1);
        $config['teams'] = $teams;
        file_put_contents($jsonFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?manage_teams=1');
    exit;
}

// Handle add request (only if not deleting)
if (!isset($_POST['delete_team']) && isset($_POST['add_team']) && !empty(trim($_POST['new_team']))) {
    $newTeam = trim($_POST['new_team']);
    if (!in_array($newTeam, $teams)) {
        $teams[] = $newTeam;
        $config['teams'] = $teams;
        file_put_contents($jsonFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?manage_teams=1');
    exit;
}

// Show main menu or manage teams form or change categories form
$showManageTeams = isset($_GET['manage_teams']) || isset($_POST['manage_teams']) || isset($_POST['delete_team']) || isset($_POST['add_team']);
$showChangeCategories = isset($_GET['change_categories']) || isset($_POST['change_categories']) || isset($_POST['set_categories']);
$showModifyScore = isset($_GET['modify_score']) || isset($_POST['modify_score']) || isset($_GET['edit_category']) || isset($_POST['save_scores']);

if (!$showManageTeams && !$showChangeCategories && !$showModifyScore) {
    // Main menu
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Main Menu</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<link rel="stylesheet" type="text/css" href="styles.css">';
    echo '</head><body>';
    echo '<div class="container">';
    echo '<h2>Main Menu</h2>';
    echo '<ul>';
    echo '<li><a href="?manage_teams=1">Manage Teams</a></li>';
    echo '<li><a href="?change_categories=1">Change number of categories</a></li>';
    echo '<li><a href="?modify_score=1">Modify Score</a></li>';
    // ...add more menu entries here if needed...
    echo '</ul>';
    // List of teams section
    echo '<div style="margin-top:32px;">';
    $currentCategories = isset($config['Categories']) ? intval($config['Categories']) : 12;
    echo '<h3>Current Number of Categories: <span style="font-weight:normal">' . $currentCategories . '</span></h3>';
    echo '<h3>Current Teams</h3>';
    if (!empty($teams)) {
        echo '<ul>';
        foreach ($teams as $team) {
            echo '<li>' . htmlspecialchars($team, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</li>';
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

// Handle change categories request
if ($showChangeCategories) {
    // If form submitted, update categories
    if (isset($_POST['set_categories']) && isset($_POST['categories'])) {
        $newCategories = intval($_POST['categories']);
        if ($newCategories >= 1 && $newCategories <= 100) {
            $config['Categories'] = $newCategories;
            file_put_contents($jsonFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            // Reload config to show updated value
            $config = json_decode(file_get_contents($jsonFile), true);
        }
    }
    $currentCategories = isset($config['Categories']) ? intval($config['Categories']) : 12;
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Change Categories</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<link rel="stylesheet" type="text/css" href="styles.css">';
    echo '</head><body>';
    echo '<div class="container">';
    echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">&larr; Back to main menu</a>';
    echo '<h2>Change Number of Categories</h2>';
    echo '<form method="post">';
    echo '<div>Current number of categories: <strong>' . $currentCategories . '</strong></div>';
    echo '<div style="margin-top:12px;">';
    echo '<input type="number" name="categories" min="1" max="100" value="' . $currentCategories . '" required> ';
    echo '<button type="submit" name="set_categories">Change Categories</button>';
    echo '</div>';
    echo '</form>';
    echo '</div>';
    echo '</body></html>';
    exit;
}

// Handle modify score flow
if ($showModifyScore) {
    $scoresFile = __DIR__ . '/scores.json';
    // Load or initialize scores
    if (file_exists($scoresFile)) {
        $scores = json_decode(file_get_contents($scoresFile), true);
    } else {
        $scores = [];
        foreach ($teams as $team) {
            $scores[$team] = array_fill(0, $config['Categories'], 0);
        }
    }
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
        for ($cat = 1; $cat <= $config['Categories']; $cat++) {
            echo '<li><a href="?edit_category=' . $cat . '">Category ' . $cat . '</a></li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '</body></html>';
        exit;
    }
    // Step 2: Show form for selected category
    $catIdx = isset($_GET['edit_category']) ? intval($_GET['edit_category']) - 1 : (isset($_POST['category']) ? intval($_POST['category']) : -1);
    if ($catIdx >= 0 && $catIdx < $config['Categories'] && !isset($_POST['save_scores'])) {
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Modify Score</title>';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<link rel="stylesheet" type="text/css" href="styles.css">';
        echo '</head><body>';
        echo '<div class="container">';
        echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?modify_score=1">&larr; Back to category selection</a>';
        echo '<h2>Modify Scores for Category ' . ($catIdx + 1) . '</h2>';
        echo '<form method="post">';
        echo '<input type="hidden" name="category" value="' . ($catIdx + 1) . '">';
        foreach ($teams as $team) {
            $score = isset($scores[$team][$catIdx]) ? (is_array($scores[$team][$catIdx]) ? (int)$scores[$team][$catIdx]['score'] : (int)$scores[$team][$catIdx]) : 0;
            $joker = (isset($scores[$team][$catIdx]) && is_array($scores[$team][$catIdx]) && !empty($scores[$team][$catIdx]['joker'])) ? true : false;
            echo '<div style="margin-bottom:8px;">';
            echo htmlspecialchars($team, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ': ';
            echo '<input type="number" name="scores[' . htmlspecialchars($team, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ']" value="' . $score . '" style="width:60px;"> ';
            echo '<label><input type="checkbox" name="joker[' . htmlspecialchars($team, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ']" value="1"' . ($joker ? ' checked' : '') . '> joker</label>';
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
        foreach ($teams as $team) {
            $val = isset($_POST['scores'][$team]) ? intval($_POST['scores'][$team]) : 0;
            $joker = isset($_POST['joker'][$team]) ? true : false;
            if (!isset($scores[$team])) {
                $scores[$team] = array_fill(0, $config['Categories'], 0);
            }
            $scores[$team][$catIdx] = [ 'score' => $val, 'joker' => $joker ];
        }
        file_put_contents($scoresFile, json_encode($scores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        // Confirmation and back to category selection
        header('Location: ' . $_SERVER['PHP_SELF'] . '?modify_score=1');
        exit;
    }
}

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
    echo htmlspecialchars($team, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    echo ' <button type="submit" name="delete_team" value="' . $idx . '" onclick="return confirm(\'Delete this team?\');">Delete</button>';
    echo '</div>';
}
echo '<div style="margin-top:16px;">';
echo '<input type="text" name="new_team" placeholder="New team name"> ';
echo '<button type="submit" name="add_team">Add Team</button>';
echo '</div>';
echo '</form>';
echo '</div>';
echo '</body></html>';