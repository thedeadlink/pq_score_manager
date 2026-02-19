<?php
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
  <h1>Scoreboard</h1>
  <p>TEST 123</p>
  <div id="last-updated" class="muted">Loading...</div>
  <table>
    <thead id="score-thead">
      <tr>
        <th></th>
        <th></th>
        <!-- category headers inserted by JS -->
        <th></th>
      </tr>
    </thead>
    <tbody id="score-tbody">
      <tr><td colspan="4">Loading...</td></tr>
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

      // rebuild header
      const thead = document.getElementById('score-thead');
      const tr = document.createElement('tr');
      tr.innerHTML = '<th></th><th></th>';
      for(let i=1;i<=numCategories;i++){
        const th = document.createElement('th'); th.textContent = '' + i; tr.appendChild(th);
      }
      const thTotal = document.createElement('th'); thTotal.textContent = ''; tr.appendChild(thTotal);
      thead.innerHTML = '';
      thead.appendChild(tr);

      const tbody = document.getElementById('score-tbody');
      if (!teams.length){
        tbody.innerHTML = '<tr><td colspan="'+(3+numCategories)+'">No teams configured</td></tr>';
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
            if (entry){
              if (typeof entry === 'object'){
                score = parseInt(entry.score || 0);
                if (entry.joker) mult = 2;
              } else {
                score = parseInt(entry || 0);
              }
            }
            const effective = (isNaN(score) ? 0 : score) * mult;
            total += effective;
            cells.push('<td class="num">' + effective + '</td>');
          }
          return { teamId, teamName, total, cells };
        });

        // sort by total desc
        teamRows.sort((a,b)=> b.total - a.total);

        // render with rank
        const rows = [];
        for(let idx=0; idx<teamRows.length; idx++){
          const r = teamRows[idx];
          const rank = idx + 1;
          rows.push('<tr><td>' + rank + '</td><td>' + r.teamName + '</td>' + r.cells.join('') + '<td class="num">' + r.total + '</td></tr>');
        }
        tbody.innerHTML = rows.join('\n');
      }

      const lu = document.getElementById('last-updated');
      lu.textContent = 'Last updated: ' + (new Date()).toLocaleTimeString();

    }catch(e){
      console.error('fetch error', e);
    }
  }

  // initial fetch and poll every 5 seconds
  fetchAndUpdate();
  setInterval(fetchAndUpdate, 5000);
  </script>
</body>
</html>
