<?php
require_once __DIR__ . '/secret.php';

// No-cache headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$gameFile = __DIR__ . '/game.json';
// Serve a static page that will fetch game.json periodically via JS
$requestedRankRaw = isset($_GET['n']) ? strtolower(trim((string) $_GET['n'])) : null;
$isNumericRequestedRank = in_array($requestedRankRaw, ['1', '2', '3'], true);
$isLastRequestedRank = $requestedRankRaw === 'last';
$showRankView = $isNumericRequestedRank || $isLastRequestedRank;
$initialHeadline = $isNumericRequestedRank ? ($requestedRankRaw . '. Platz') : '';

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
  <title>Scoreboard</title>
  <link rel="stylesheet" href="frontend.css">
  <link rel="stylesheet" href="rank.css">
</head>
<body>
  <div id="last-updated">Loading...</div>
  <div id="first-place-names">
    <?php if ($showRankView): ?>
    <h1 id="rank-headline" class="first-place-headline"><?php echo htmlspecialchars($initialHeadline, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></h1>
    <?php endif; ?>
    <div id="first-place-team-list"><?php echo $showRankView ? 'Loading...' : '---'; ?></div>
  </div>

  <script>
  // Escape for HTML insertion
  function escapeHtml(s){
    return String(s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});
  }

  let lastData = null;
  const targetRankParam = <?php echo $showRankView ? json_encode($requestedRankRaw) : 'null'; ?>;
  const headline = document.getElementById('rank-headline');

  async function fetchAndUpdate(){
    try{
      if (targetRankParam !== '1' && targetRankParam !== '2' && targetRankParam !== '3' && targetRankParam !== 'last') {
        const namesContainer = document.getElementById('first-place-team-list');
        namesContainer.textContent = '---';
        return;
      }

      const res = await fetch('game.json?ts=' + Date.now());
      if (!res.ok) return;
      const data = await res.json();
      const jsonStr = JSON.stringify(data);
      if (jsonStr === lastData) return; // no change
      lastData = jsonStr;

      const namesContainer = document.getElementById('first-place-team-list');
      const teams = data.Teams || [];
      if (!teams.length){
        namesContainer.textContent = '---';
        if (headline && targetRankParam === 'last') {
          headline.style.display = 'none';
        }
      } else {
        // Build team totals and include joker multipliers.
        const teamRows = teams.map(team => {
          const teamName = escapeHtml(team.name ?? '');
          let total = 0;
          const scoreEntries = (team.scores && typeof team.scores === 'object') ? Object.values(team.scores) : [];

          scoreEntries.forEach(entry => {
            let score = 0;
            let multiplier = 1;

            if (entry && typeof entry === 'object') {
              score = parseInt(entry.score || 0);
              if (entry.joker) {
                multiplier = 2;
              }
            } else {
              score = parseInt(entry || 0);
            }

            if (isNaN(score)) {
              score = 0;
            }

            total += score * multiplier;
          });

          return { teamName, total };
        });

        teamRows.sort((a, b) => b.total - a.total);

        // Compute competition ranks (ties share rank; next rank may be skipped).
        const rankedRows = [];
        let prevTotal = null;
        let prevRank = 0;
        let lastComputedRank = null;

        for (let idx = 0; idx < teamRows.length; idx++) {
          const row = teamRows[idx];
          const rank = (row.total === prevTotal) ? prevRank : (idx + 1);
          prevTotal = row.total;
          prevRank = rank;
          rankedRows.push({ ...row, rank });
          lastComputedRank = rank;
        }

        let effectiveTargetRank = null;
        if (targetRankParam === 'last') {
          effectiveTargetRank = lastComputedRank;
          if (headline && effectiveTargetRank !== null) {
            headline.textContent = effectiveTargetRank + '. Platz';
            headline.style.display = '';
          }
        } else {
          effectiveTargetRank = parseInt(targetRankParam, 10);
        }

        const rankedMatches = rankedRows.filter(row => row.rank === effectiveTargetRank);

        if (!rankedMatches.length) {
          namesContainer.textContent = '---';
        } else {
          namesContainer.innerHTML = rankedMatches
            .map(r => '<div class="first-place-team">' + r.teamName + '</div>')
            .join('');
        }
      }

    }catch(e){
      console.error('fetch error', e);
    }
  }

  // initial fetch and poll every 1 second
  fetchAndUpdate();
  setInterval(fetchAndUpdate, 1000);

      const lu = document.getElementById('last-updated');
      lu.textContent = 'LU:' + (new Date()).toLocaleTimeString();

  </script>
</body>
</html>
