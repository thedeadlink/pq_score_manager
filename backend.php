<?php
/**************************************************************************************************************************************
 * 
 * Configuration & Variables
 * 
 **************************************************************************************************************************************/

// Token and Authentication
$secretToken = 'GbJuRBpqamPcZRZU3pVfGxb7sonVzxCBfksgnjkkRHfbZneW7FKDFqK2Xw2FyHq2cLJavcYuiiqRGfrYyCJoQr34yH4jag6ecKP9';
$providedToken = $_GET['token'] ?? $_POST['token'] ?? null;
$authPassword = 'QWer1234';
$authCookieName = 'pq_auth';

// File Paths
$clientsFile = __DIR__ . '/clients.json';
$gameFile = __DIR__ . '/game.json';

// State Variables
$authenticated = false;
$authError = '';
$currentClientInfo = null;
$showManageTeams = false;
$showCreateNewGame = false;

// HTML Template
$htmlHead = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">';
$htmlTitle = 'Pub Quiz Score Manager';

// Load existing clients
$clients = [];
if (file_exists($clientsFile)) {
    $clients = json_decode(file_get_contents($clientsFile), true);
    if (!is_array($clients)) {
        $clients = [];
    }
}

// Load game data
$game = [];
if (file_exists($gameFile)) {
    $game = json_decode(file_get_contents($gameFile), true);
    if (!is_array($game)) {
        $game = [];
    }
} else {
    $game = ['Teams' => []];
    // Create the file with initial structure
    file_put_contents($gameFile, json_encode($game, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Ensure Teams key exists
if (!isset($game['Teams'])) {
    $game['Teams'] = [];
}

/**************************************************************************************************************************************
 * 
 * Helper Functions
 * 
 **************************************************************************************************************************************/

// Helper function to parse browser info from user agent
function parseBrowserInfo($userAgent) {
    $browser = 'Unknown';
    $version = 'Unknown';
    
    // Chrome
    if (preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches)) {
        $browser = 'Chrome';
        $version = $matches[1];
    }
    // Firefox
    elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent, $matches)) {
        $browser = 'Firefox';
        $version = $matches[1];
    }
    // Safari
    elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent, $matches) && !preg_match('/Chrome/', $userAgent)) {
        $browser = 'Safari';
        $version = $matches[1];
    }
    // Edge
    elseif (preg_match('/Edg[e|A]?\/([0-9.]+)/', $userAgent, $matches)) {
        $browser = 'Edge';
        $version = $matches[1];
    }
    // Opera
    elseif (preg_match('/OPR\/([0-9.]+)/', $userAgent, $matches)) {
        $browser = 'Opera';
        $version = $matches[1];
    }
    
    return ['browser' => $browser, 'version' => $version];
}

// Helper function to get client IP address
function getClientIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP']; // Cloudflare
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        return $_SERVER['HTTP_X_FORWARDED'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
        return $_SERVER['HTTP_FORWARDED'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**************************************************************************************************************************************
 * 
 * Token Validation
 * 
 **************************************************************************************************************************************/

// Check if user has valid authentication cookie
if (isset($_COOKIE[$authCookieName])) {
    foreach ($clients as $client) {
        if (isset($client['hash']) && $client['hash'] === $_COOKIE[$authCookieName]) {
            $authenticated = true;
            // Update last_seen timestamp
            $client['last_seen'] = date('Y-m-d H:i:s');
            // Find and update the client in the array
            foreach ($clients as &$c) {
                if ($c['hash'] === $_COOKIE[$authCookieName]) {
                    $c = $client;
                    break;
                }
            }
            unset($c);
            file_put_contents($clientsFile, json_encode($clients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            break;
        }
    }
}

// Handle login form submission
if (!$authenticated && isset($_POST['auth_password'])) {
    if ($_POST['auth_password'] === $authPassword) {
        // Generate random hash for client
        $hash = bin2hex(random_bytes(32));
        $browserInfo = parseBrowserInfo($_SERVER['HTTP_USER_AGENT'] ?? '');
        $ipAddress = getClientIP();
        
        // Create new client record
        $newClient = [
            'hash' => $hash,
            'authenticated_at' => date('Y-m-d H:i:s'),
            'last_seen' => date('Y-m-d H:i:s'),
            'browser' => $browserInfo['browser'],
            'browser_version' => $browserInfo['version'],
            'ip_address' => $ipAddress,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        $clients[] = $newClient;
        
        // Save updated client list
        file_put_contents($clientsFile, json_encode($clients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Set cookie (30 days expiry)
        setcookie($authCookieName, $hash, time() + (60 * 60 * 24 * 30), '/');
        $authenticated = true;
        
        // Reload to initialize session with token
        header('Location: ' . $_SERVER['PHP_SELF'] . '?token=' . urlencode($providedToken));
        exit;
    } else {
        $authError = 'Incorrect password.';
    }
}

// Display login form if not authenticated
if (!$authenticated) {
    echo $htmlHead;
    echo '<title>Authentication Required</title>';
    echo '<link rel="stylesheet" type="text/css" href="styles.css">';
    echo '<body>';
    echo '<div class="container login">';
    echo '<h2 class="login-title">Authentication Required</h2>';
    if (!empty($authError)) {
        echo '<div class="error">' . htmlspecialchars($authError, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</div>';
    }
    echo '<form method="post">';
    echo '<input type="hidden" name="token" value="' . htmlspecialchars($providedToken, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">';
    echo '<input type="password" name="auth_password" placeholder="Enter password" required autofocus>';
    echo '<button type="submit">Login</button>';
    echo '</form>';
    echo '</div>';
    echo '</body>';
    echo '</html>';
    exit;
}

/**************************************************************************************************************************************
 * 
 * Logout Handler
 * 
 **************************************************************************************************************************************/

if (isset($_POST['logout'])) {
    // Remove current client from clients list
    foreach ($clients as $key => $client) {
        if (isset($client['hash']) && $client['hash'] === $_COOKIE[$authCookieName]) {
            unset($clients[$key]);
            break;
        }
    }
    
    // Save updated client list
    file_put_contents($clientsFile, json_encode($clients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Clear cookie
    setcookie($authCookieName, '', time() - 3600, '/');
    
    // Redirect to login
    header('Location: ' . $_SERVER['PHP_SELF'] . '?token=' . urlencode($providedToken));
    exit;
}

// Check if manage teams button was clicked
if (isset($_POST['manage_teams'])) {
    $showManageTeams = true;
}

// Back to Menu Handler
if (isset($_POST['back_to_menu'])) {
    $showManageTeams = false;
}

// Check if create new game button was clicked
if (isset($_POST['create_new_game'])) {
    $showCreateNewGame = true;
}

// Back from Create New Game Handler
if (isset($_POST['back_to_menu_from_create'])) {
    $showCreateNewGame = false;
}

/**************************************************************************************************************************************
 * 
 * Team Management Handlers
 * 
 **************************************************************************************************************************************/

// Add Team Handler
if ($showManageTeams && isset($_POST['add_team'])) {
    $teamName = trim($_POST['new_team_name'] ?? '');
    if (!empty($teamName) && strlen($teamName) <= 255) {
        // Get next ID
        $nextId = 1;
        foreach ($game['Teams'] as $team) {
            if (isset($team['id']) && $team['id'] >= $nextId) {
                $nextId = $team['id'] + 1;
            }
        }
        
        // Add new team
        $game['Teams'][] = [
            'id' => $nextId,
            'name' => $teamName
        ];
        
        // Save game file
        file_put_contents($gameFile, json_encode($game, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

// Delete Team Handler
if ($showManageTeams && isset($_POST['delete_team_id'])) {
    $deleteId = intval($_POST['delete_team_id']);
    foreach ($game['Teams'] as $key => $team) {
        if (isset($team['id']) && $team['id'] === $deleteId) {
            unset($game['Teams'][$key]);
            break;
        }
    }
    $game['Teams'] = array_values($game['Teams']); // Reindex array
    file_put_contents($gameFile, json_encode($game, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Rename Team Handler
if ($showManageTeams && isset($_POST['rename_team_id']) && isset($_POST['rename_team_name'])) {
    $renameId = intval($_POST['rename_team_id']);
    $newName = trim($_POST['rename_team_name']);
    if (!empty($newName) && strlen($newName) <= 255) {
        foreach ($game['Teams'] as &$team) {
            if (isset($team['id']) && $team['id'] === $renameId) {
                $team['name'] = $newName;
                break;
            }
        }
        unset($team);
        file_put_contents($gameFile, json_encode($game, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

/**************************************************************************************************************************************
 * 
 * Create New Game Handler
 * 
 **************************************************************************************************************************************/

if ($showCreateNewGame && isset($_POST['confirm_create_new_game'])) {
    $confirmText = $_POST['confirm_text'] ?? '';
    
    if ($confirmText === 'delete game') {
        // Backup existing game file
        if (file_exists($gameFile)) {
            $timestamp = date('Y-m-d_Hi');
            $backupFile = __DIR__ . '/game.json.' . $timestamp . '.backup';
            rename($gameFile, $backupFile);
        }
        
        // Create new game file
        $newGame = ['Teams' => []];
        file_put_contents($gameFile, json_encode($newGame, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Reload game data
        $game = $newGame;
        
        // Reset state and go back to menu
        $showCreateNewGame = false;
    }
}

// Create New Game Section
if ($showCreateNewGame) {
    echo $htmlHead;
    echo '<title>' . $htmlTitle . '</title>';
    echo '<link rel="stylesheet" type="text/css" href="styles.css">';
    echo '</head>';
    echo '<body>';
    echo '<div class="container">';
    echo '<h1>Create New Game</h1>';
    
    echo '<div class="alert-warning">';
    echo '<strong>⚠ Warning:</strong> This will backup the current game and create a new one.';
    echo '</div>';
    
    echo '<p>To confirm this action, type <strong>"delete game"</strong> in the field below:</p>';
    
    echo '<form method="post">';
    echo '<input type="hidden" name="create_new_game" value="1">';
    echo '<input type="text" name="confirm_text" placeholder="Type: delete game" required autofocus>';
    echo '<button type="submit" name="confirm_create_new_game" value="1" class="button-danger">Create New Game</button>';
    echo '</form>';
    
    echo '<form method="post" class="form-cancel">';
    echo '<input type="hidden" name="create_new_game" value="1">';
    echo '<button type="submit" name="back_to_menu_from_create" value="1">← Cancel</button>';
    echo '</form>';
    
    echo '</div>';
    echo '</body>';
    echo '</html>';
    exit;
}

// Manage Teams Section
if ($showManageTeams) {
    echo $htmlHead;
    echo '<title>' . $htmlTitle . '</title>';
    echo '<link rel="stylesheet" type="text/css" href="styles.css">';
    echo '</head>';
    echo '<body>';
    echo '<div class="container">';
    echo '<h1>Manage Teams</h1>';
    
    echo '<form method="post" class="form-back-button">';
    echo '<input type="hidden" name="manage_teams" value="1">';
    echo '<button type="submit" name="back_to_menu" value="1">← Back to Menu</button>';
    echo '</form>';
    
    echo '<h2>Teams</h2>';
    if (!empty($game['Teams'])) {
        echo '<div class="teams-list-container">';
        foreach ($game['Teams'] as $team) {
            $teamId = $team['id'] ?? 0;
            $teamName = htmlspecialchars($team['name'] ?? 'Unnamed Team', ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            echo '<div class="team-card">';
            echo '<span><strong>ID: ' . $teamId . '</strong> - ' . $teamName . '</span>';
            
            echo '<form method="post" class="team-actions-form">';
            echo '<input type="hidden" name="manage_teams" value="1">';
            echo '<input type="hidden" name="rename_team_id" value="' . $teamId . '">';
            echo '<input type="text" name="rename_team_name" value="' . $teamName . '" maxlength="255" class="team-name-input">';
            echo '<button type="submit" name="rename_team" value="1" class="rename-button">Rename</button>';
            
            echo '<button type="submit" name="delete_team_id" value="' . $teamId . '" onclick="return confirm(\'Delete this team?\');" class="button-danger">Delete</button>';
            echo '</form>';
            
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>No teams available.</p>';
    }
    
    echo '<h2 style="margin-top: 30px;">Add New Team</h2>';
    echo '<form method="post">';
    echo '<input type="hidden" name="manage_teams" value="1">';
    echo '<input type="text" name="new_team_name" placeholder="Team name (max 255 characters)" maxlength="255" required>';
    echo '<button type="submit" name="add_team" value="1">Add Team</button>';
    echo '</form>';
    
    echo '</div>';
    echo '</body>';
    echo '</html>';
    exit;
}

// Main Menu Display
echo $htmlHead;
echo '<title>' . $htmlTitle . '</title>';
echo '<link rel="stylesheet" type="text/css" href="styles.css">';
echo '</head>';
echo '<body>';
echo '<div class="container">';
echo '<h1>Menu</h1>';
echo '<form method="post">';
echo '<div class="button-group">';
echo '<button type="submit" name="manage_teams" value="1">Manage Teams</button>';
echo '<button type="submit" name="create_new_game" value="1" class="button-create-game">Create New Game</button>';
echo '<button type="submit" name="logout" value="1">Logout</button>';
echo '</div>';
echo '</form>';
echo '</div>';
echo '</body>';
echo '</html>';

?>
