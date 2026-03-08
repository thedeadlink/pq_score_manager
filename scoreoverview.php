<?php
require_once __DIR__ . '/secret.php';

// No-cache headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Token and Authentication
$providedToken = $_GET['token'] ?? $_POST['token'] ?? null;

// Token Validation
if ($providedToken !== $secretToken) {
    http_response_code(403);
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nothing to see here</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    </head>
    <body>
     <h1>Nothing to see here</h1>
    </body>
    </html>
    ';
    exit;
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Score Overview</title>
  <link rel="stylesheet" href="frontend.css">
</head>
<body>
  <div id="last-updated">Loading...</div>
  <table id="overview-table" width="100%" aria-label="Team score overview"></table>

  <script>
    function escapeHtml(value) {
      return String(value).replace(/[&<>"']/g, function (c) {
        return {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#39;'
        }[c];
      });
    }

    function getScoreEntries(team) {
      if (!team || !team.scores || typeof team.scores !== 'object') {
        return [];
      }
      return Object.values(team.scores);
    }

    function calculateTotal(team) {
      return getScoreEntries(team).reduce(function (sum, entry) {
        if (entry && typeof entry === 'object') {
          const base = parseInt(entry.score || 0, 10);
          const score = Number.isNaN(base) ? 0 : base;
          return sum + (entry.joker ? score * 2 : score);
        }

        const plain = parseInt(entry || 0, 10);
        return sum + (Number.isNaN(plain) ? 0 : plain);
      }, 0);
    }

    function hasJokerUsed(team) {
      return getScoreEntries(team).some(function (entry) {
        return entry && typeof entry === 'object' && entry.joker === true;
      });
    }

    function buildRow(label, values, labelClass, valueClass) {
      return '<tr>' + values.map(function (value) {
        return '<td class="team_cell ' + valueClass + '">' + value + '</td>';
      }).join('') + '</tr>';
    }

    function getTeamNameClass(name) {
      const length = (name || '').trim().length;
      if (length < 10) {
        return 'team_name_overview_large';
      }
      if (length < 20) {
        return 'team_name_overview_normal';
      }
      return 'team_name_overview_small';
    }

    function buildTeamNameRow(teams) {
      const teamCells = teams.map(function (team) {
        const name = team && team.name ? String(team.name) : '';
        const cssClass = getTeamNameClass(name);
        return '<td class="team_cell"><div class="' + cssClass + '">' + escapeHtml(name) + '</div></td>';
      }).join('');

      return '<tr>' + teamCells + '</tr>';
    }

    let lastData = '';

    async function fetchAndUpdate() {
      try {
        const response = await fetch('game.json?ts=' + Date.now());
        if (!response.ok) {
          return;
        }

        const data = await response.json();
        const serialized = JSON.stringify(data);
        if (serialized === lastData) {
          return;
        }
        lastData = serialized;

        const teams = Array.isArray(data.Teams) ? data.Teams : [];
        const table = document.getElementById('overview-table');

        if (!teams.length) {
          table.innerHTML = '<tr><td class="cat_header_title">No teams configured</td></tr>';
        } else {
          const jokerUsed = teams.map(function (team) {
            return hasJokerUsed(team)
              ? '<div class="joker_cat joker_cat_used">x2</div>'
              : '<div class="joker_cat">x2</div>';
          });

          const totalScores = teams.map(function (team) {
            return '<div class="total_team">' + String(calculateTotal(team)) + '</div>';
          });

          table.innerHTML = [
            buildTeamNameRow(teams),
            buildRow('Joker used', jokerUsed, 'cat_header_title', ''),
            buildRow('Total score', totalScores, 'total_header', 'score_cat')
          ].join('');
        }

        document.getElementById('last-updated').textContent = 'LU: ' + new Date().toLocaleTimeString();
      } catch (error) {
        console.error('Failed to update score overview', error);
      }
    }

    fetchAndUpdate();
    setInterval(fetchAndUpdate, 1000);

  </script>
</body>
</html>
