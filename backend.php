<?php
/**************************************************************************************************************************************
 * 
 * Authentication System with Client Tracking
 * 
 **************************************************************************************************************************************/

$authCookieName = 'pq_auth';
$clientsFile = __DIR__ . '/clients.json';
$authPassword = 'QWer1234';
$authenticated = false;
$authError = '';

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

// Load existing clients
$clients = [];
if (file_exists($clientsFile)) {
    $clients = json_decode(file_get_contents($clientsFile), true);
    if (!is_array($clients)) {
        $clients = [];
    }
}

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
        
        // Reload to initialize session
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $authError = 'Incorrect password.';
    }
}

// Display login form if not authenticated
if (!$authenticated) {
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Authentication Required</title>';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }';
    echo '.container { max-width: 400px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }';
    echo 'h2 { color: #333; text-align: center; }';
    echo 'form { display: flex; flex-direction: column; }';
    echo 'input[type="password"] { padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }';
    echo 'button { padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold; }';
    echo 'button:hover { background: #0056b3; }';
    echo '.error { color: red; margin-bottom: 15px; text-align: center; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<div class="container">';
    echo '<h2>Authentication Required</h2>';
    if (!empty($authError)) {
        echo '<div class="error">' . htmlspecialchars($authError, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</div>';
    }
    echo '<form method="post">';
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
 * Authenticated Content Area
 * 
 **************************************************************************************************************************************/

// Get current client info
$currentClientInfo = null;
foreach ($clients as $client) {
    if (isset($client['hash']) && $client['hash'] === $_COOKIE[$authCookieName]) {
        $currentClientInfo = $client;
        break;
    }
}

echo '<!DOCTYPE html>';
echo '<html lang="en">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
echo '<title>Pub Quiz Score Manager</title>';
echo '<style>';
echo 'body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }';
echo '.container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }';
echo 'h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }';
echo 'h2 { color: #555; margin-top: 25px; }';
echo 'p { color: #666; }';
echo '.success { color: green; font-weight: bold; }';
echo '.info-grid { display: grid; grid-template-columns: 200px 1fr; gap: 15px; margin: 20px 0; }';
echo '.info-label { font-weight: bold; color: #333; }';
echo '.info-value { color: #666; word-break: break-all; }';
echo '.divider { border-top: 1px solid #ddd; margin: 20px 0; }';
echo '</style>';
echo '</head>';
echo '<body>';
echo '<div class="container">';
echo '<h1>✓ Authentication Successful</h1>';
echo '<p>Welcome to the Pub Quiz Score Manager! Your client has been authenticated.</p>';

if ($currentClientInfo) {
    echo '<h2>Client Information</h2>';
    echo '<div class="info-grid">';
    echo '<div class="info-label">Browser:</div>';
    echo '<div class="info-value">' . htmlspecialchars($currentClientInfo['browser'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . ' ' . htmlspecialchars($currentClientInfo['browser_version'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</div>';
    echo '<div class="info-label">IP Address:</div>';
    echo '<div class="info-value">' . htmlspecialchars($currentClientInfo['ip_address'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</div>';
    echo '<div class="info-label">Authenticated:</div>';
    echo '<div class="info-value">' . htmlspecialchars($currentClientInfo['authenticated_at'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</div>';
    echo '<div class="info-label">Last Seen:</div>';
    echo '<div class="info-value">' . htmlspecialchars($currentClientInfo['last_seen'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</div>';
    echo '<div class="info-label">Cookie Hash:</div>';
    echo '<div class="info-value">' . htmlspecialchars(substr($currentClientInfo['hash'], 0, 16), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '...</div>';
    echo '</div>';
    
    echo '<div class="divider"></div>';
    echo '<h2>Additional Details</h2>';
    echo '<p><strong>Full User Agent:</strong></p>';
    echo '<p style="font-family: monospace; background: #f9f9f9; padding: 10px; border-radius: 4px; overflow-x: auto;">' . htmlspecialchars($currentClientInfo['user_agent'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';
}

echo '<p style="color: #999; font-size: 12px;">Your authentication will expire in 30 days.</p>';
echo '</div>';
echo '</body>';
echo '</html>';

?>
