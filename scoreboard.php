<?php
require_once __DIR__ . '/secret.php';

// No-cache headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$gameFile = __DIR__ . '/game.json';
// Serve a static page that will fetch game.json periodically via JS
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Scoreboard</title>
  <link rel="stylesheet" href="frontend.css">
</head>
<body>
  <div id="last-updated" class="last_update">Loading...</div>
  <table width="100%" style="table-layout: fixed;">
    <thead id="score-thead">
      <tr>
          <th></th>
          <th style="width: 40%;"></th>
        <!-- category headers inserted by JS -->
          <th class="total_header" style="text-align: center; width: 10%;"></th>
      </tr>
    </thead>
    <tbody id="score-tbody">
      <tr><td colspan="4"></td></tr>
    </tbody>
  </table>

  <script>
  // Escape for HTML insertion
  function escapeHtml(s){
    return String(s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});
  }

  let lastData = null;

  async function fetchAndUpdate(){
    try{
      const res = await fetch('game.json?ts=' + Date.now());
      if (!res.ok) return;
      const data = await res.json();
      const jsonStr = JSON.stringify(data);
      if (jsonStr === lastData) return; // no change
      lastData = jsonStr;

      const teams = data.Teams || [];
      let numCategories = data.numCategories ? parseInt(data.numCategories) : 0;
      if (!numCategories){
        let max = 0;
        teams.forEach(t=>{
          if (t.scores && typeof t.scores === 'object'){
            Object.keys(t.scores).forEach(k=>{const ki=parseInt(k); if (!isNaN(ki) && ki>max) max=ki;});
          }
        });
        numCategories = max;
      }

      // rebuild header: each category spans two columns (score + joker)
      const thead = document.getElementById('score-thead');
      const trTitle = document.createElement('tr');
      const trHeader = document.createElement('tr');
      trTitle.innerHTML = '<th></th><th style="width: 30%;"></th>';
      trHeader.innerHTML = '<th></th><th style="width: 30%;"></th>';
      for(let i=1;i<=numCategories;i++){
        const thTitle = document.createElement('th'); 
          thTitle.setAttribute('colspan','2'); 
          thTitle.className = 'cat_header_title'; 
          thTitle.textContent = 'Runde'; 
          trTitle.appendChild(thTitle);
        const th = document.createElement('th'); 
          th.setAttribute('colspan','2'); 
          th.className = 'cat_header'; 
          th.textContent = '' + i; 
          trHeader.appendChild(th);
      }
      const thTotalTitel = document.createElement('th'); 
        thTotalTitel.setAttribute('style','text-align: center; width: 10%;');
        thTotalTitel.className = 'total_header'; 
        thTotalTitel.textContent = ''; 
        trTitle.appendChild(thTotalTitel);
      
      const thTotal = document.createElement('th'); 
        thTotal.setAttribute('style','text-align: center; width: 10%;');
        thTotal.className = 'total_header'; 
        thTotal.textContent = 'Gesamt'; 
        trHeader.appendChild(thTotal);
      
      thead.innerHTML = '';
      thead.appendChild(trTitle);
      thead.appendChild(trHeader);

      const tbody = document.getElementById('score-tbody');
      if (!teams.length){
        tbody.innerHTML = '<tr><td colspan="'+(3 + (2 * numCategories))+'">No teams configured</td></tr>';
      } else {
        // build array of team rows with totals
        const teamRows = teams.map(team=>{
          const teamId = team.id ?? 0;
          const teamName = escapeHtml(team.name ?? '');
          let total = 0;
          const cells = [];
          for(let i=1;i<=numCategories;i++){
            let entry = (team.scores && team.scores[i]) !== undefined ? team.scores[i] : (team.scores && team.scores[String(i)] ? team.scores[String(i)] : null);
            let score = 0, mult = 1;
            let hasJoker = false;
            if (entry){
              if (typeof entry === 'object'){
                score = parseInt(entry.score || 0);
                if (entry.joker) { mult = 2; hasJoker = true; }
              } else {
                score = parseInt(entry || 0);
              }
            }
            const effective = (isNaN(score) ? 0 : score) * mult;
            total += effective;
            cells.push('<td><div class="score_cat" style="text-align: right;">' + effective + '</div></td>');
            cells.push('<td><div class="joker_cat" style="text-align: left;">' + (hasJoker ? 'J' : '') + '</div></td>');
          }
          return { teamId, teamName, total, cells };
        });

        // sort by total desc
        teamRows.sort((a,b)=> b.total - a.total);

        // render with rank (teams with same total get the same rank)
        const rows = [];
        let prevTotal = null;
        let prevRank = 0;
        for(let idx=0; idx<teamRows.length; idx++){
          const r = teamRows[idx];
          const rank = (r.total === prevTotal) ? prevRank : (idx + 1);
          prevTotal = r.total;
          prevRank = rank;
          rows.push('<tr><td class="rank">' + rank + '.</td><td><div class="team_name">' + r.teamName + '</div></td>' + r.cells.join('') + '<td class="total_team" style="text-align: right;">' + r.total + '</td></tr>');
        }
        tbody.innerHTML = rows.join('\n');
      }

      const lu = document.getElementById('last-updated');
      lu.textContent = 'LU:' + (new Date()).toLocaleTimeString();

    }catch(e){
      console.error('fetch error', e);
    }
  }

  // initial fetch and poll every 1 second
  fetchAndUpdate();
  setInterval(fetchAndUpdate, 1000);

  </script>
</body>
</html>
