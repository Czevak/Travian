<?php
require 'auth_header.php';  # Require that user be authenticated
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="global.css" />
</head>
<body>
<?php
include 'common.php';   # Load common functions

# Connect
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax') or die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn);

# If request comes in as coordinates, find target village karte first
if (isset($_GET['x']) && isset($_GET['y']) && $_GET['x'] && $_GET['y']) {
    $village = mysql_fetch_assoc(mysql_query("select karte from s4_us_map where x=".$_GET['x']." and y=".$_GET['y']));
    $_GET['k'] = $village["karte"];
}

#######################
##  Single village   ##
#######################
if (isset($_GET['k'])) {    # Single village analysis; takes a full karte (note, c is also passed by nature)
    if (strpos($_GET['k'], "&") === false) $_GET['k'] = $_GET['k']."&c=".$_GET['c'];    # Consolidate if separate
    # Get player info
    $player = mysql_fetch_assoc(mysql_query("select x,y, player, uid, village, aid, alliance, pop from s4_us_villages where karte='".$_GET['k']."'"));
    echo '<table class="tbg" cellspacing="1" cellpadding="2" align="center">';
    # village analysis
    echo '<tr class="rbg"><td colspan="2">Village analysis</td></tr>';
    # Player
    echo '<tr class="cbg1"><td width="21%">Player</td><td class="s7">';
    echo '<a href="http://s4.travian.us/spieler.php?uid='.$player["uid"].'">'.$player["player"].'</a></td></tr>';
    # Village
    echo '<tr class="cbg1"><td>Village</td><td class="s7">';
    echo '<a href="http://s4.travian.us/karte.php?d='.$_GET['k'].'">'.$player["village"].' ['.$player["x"].','.$player["y"].']</a>';
    echo ' - '.$player["pop"].' population</td></tr>';
    # Alliance
    echo '<tr class="cbg1"><td>Alliance</td><td class="s7">';
    if ($player["alliance"])
        echo '<a href="http://s4.travian.us/allianz.php?aid='.$player["aid"].'">'.$player["alliance"].'</a></td></tr>';
    else
        echo ' - </td></tr>';
    # Highest bounty (id, total)
    $biggestraid = mysql_fetch_assoc(mysql_query("select id, total from s4_us_reports where defkarte='".$_GET['k']."' and defcasualties is not null order by convert(total,unsigned) desc limit 1"));
    if ($biggestraid) {
        echo '<tr class="cbg1"><td>Biggest raid</td><td class="s7">'.$biggestraid["total"];
        echo ' (<a href="report.php?i='.$biggestraid["id"].'">'.$biggestraid["id"].'</a>)';
        echo ' - <a href="/?h=1&x='.$player['x'].'&y='.$player['y'].'&r=0">Go to full raid history</a></td></tr>';
    }
    # Link to this page
    echo '<tr class="cbg1"><td>This page link</td><td class="s7">';
    echo '<input type="text" readonly class="fm fm250" onclick="this.select()" value="http://travian.ulrezaj.com/?h=8&k='.$_GET['k'].'">';
    echo ' <a href="http://travian.ulrezaj.com/?h=8&k='.$_GET['k'].'"><img src="img/external.gif" /></a></td></tr>';
    
    echo '<tr><td colspan="2">'."\n";
    
    # Daily raided totals (total, date)
    $daily = mysql_query("select sum(total) total, date(time) date from s4_us_reports where defkarte='".$_GET['k']."' and defcasualties is not null group by date(time) order by date(time) desc limit 20");
    echo '<br><table cellspacing="1" cellpadding="2" class="tbg">';
    echo '<tr class="cbg1"><td colspan="3" class="b">Daily raid output</td></tr>';
    echo '<tr class="cbg1"><td>Date</td><td>Bounty</td><td>Reports</td></tr>';
    while ($r = mysql_fetch_assoc($daily)) {
        echo '<tr><td>'.$r['date'].'</td><td>'.$r['total'].'</td><td></td></tr>';
    }
    echo '</table>';
    
    # Raid history (id, attacker, time)
    $raids = mysql_query("select id, attacker, total, efficiency, time from s4_us_reports where defkarte='".$_GET['k']."' order by time desc limit 50");
    echo '<br><table cellspacing="1" cellpadding="2" class="tbg">';
    echo '<tr class="cbg1"><td colspan="5" class="b">Latest raids on this village</td></tr>';
    echo '<tr class="cbg1"><td>Attacker</td><td>Bounty</td><td>Efficiency</td><td>Report</td><td>Time</td></tr>';
    while ($r = mysql_fetch_assoc($raids)) {
        $r["attacker"] = explode(" from the village ",$r["attacker"]);
        echo '<tr><td><a href="/?h=7&p='.$r['attacker'][0].'">'.$r['attacker'][0].'</a></td>';
        echo '<td>'.$r['total'].'</td><td>'.number_format($r["efficiency"],2).'&#37</td>';
        echo '<td><a href="/?h=5&i='.$r['id'].'">'.$r['id'].'</a></td>';
        echo '<td width="175">'.str_replace("-","/",$r["time"]).'</td></tr>';
    }
    echo '</table>';
    
#######################
##  Alliance         ##
#######################
} elseif (isset($_GET['aid'])) {
    $summary = mysql_fetch_assoc(mysql_query("select * from (SELECT @row := @row + 1 as row, t.totalpop, t.avgpop, t.aid, t.alliance FROM (select sum(pop) totalpop, floor(avg(pop)) avgpop, aid, alliance from s4_us_villages where alliance!='' group by aid) t, (SELECT @row := 0) r order by t.totalpop desc) m where aid=".$_GET['aid']));
    echo '<table class="tbg" cellspacing="1" cellpadding="2" align="center">';
    # Alliance analysis
    echo '<tr class="rbg"><td colspan="2">Alliance analysis</td></tr>';
    # Alliance
    echo '<tr class="cbg1"><td width="21%">Alliance</td><td class="s7">';
    echo '<a href="http://s4.travian.us/allianz.php?aid='.$_GET["aid"].'">'.$summary["alliance"].'</a></td></tr>';
    
    echo '</table>';
#######################
##  Default page     ##
#######################
} else {
    echo '<table class="tbg" cellspacing="1" cellpadding="2" align="center">';
    # Alliance Stats
    echo '<tr class="rbg"><td colspan="2">Alliance Stats</td></tr>';
    # Number of reports (count)
    $numreports = mysql_fetch_assoc(mysql_query("select count(id) count from s4_us_reports"));    
    echo '<tr class="cbg1"><td width="25%" class="s7">Reports archived</td><td class="s7">'.$numreports["count"].'</td>';
    # Biggest raid today (id, attacker, total)
    $big = mysql_fetch_assoc(mysql_query("select id, attacker, total from s4_us_reports where date(time)=curdate() and defcasualties is not null order by convert(total,unsigned) desc limit 1"));
    echo '<tr class="cbg1"><td class="s7">Biggest raid today</td>';
    echo '<td class="s7">'.$big["total"].' resources by <a href="players.php?p='.who($big["attacker"]).'">';
    echo who($big["attacker"]).'</a> (<a href="report.php?i='.$big["id"].'">'.$big["id"].'</a>)</td></tr>';
    # Most raids today
    $most = mysql_fetch_assoc(mysql_query("select v.player, sum(c.count) count from (select attkarte, count(id) count from s4_us_reports where defcasualties is not null and date(time)=curdate() group by attkarte) c join (select player, karte from s4_us_villages) v where attkarte=karte group by v.player order by count desc"));
    echo '<tr class="cbg1"><td class="s7">Most raids today</td>';
    echo '<td class="s7">'.$most["player"].' ('.$most["count"].')'; 
    # Most efficient raider today
    $eff = mysql_fetch_assoc(mysql_query("select v.player, r.efficiency from (select attkarte, avg(efficiency) efficiency from s4_us_reports where efficiency is not null and date(time)=curdate() group by attkarte) r join (select player, aid, karte from s4_us_villages) v where attkarte=karte and aid in (401,450) order by r.efficiency desc limit 1"));
    echo '<tr class="cbg1"><td class="s7">Most efficient raider</td>';
    echo '<td class="s7">'.$eff["player"].' ('.number_format($eff["efficiency"],2).'&#37;)'; 
    
    echo '<tr><td colspan="2">'."\n";
    
    # Stats
    echo '<br><table cellspacing="1" cellpadding="2" class="tbg">';
    # Daily stats
    echo '<tr class="cbg1"><td colspan="8" class="b">Daily stats</td></tr>';
    echo '<tr class="cbg1"><td>Date</td><td>Attacks</td><td>Scouts</td><td>Defends</td><td>Bounty</td><td>Efficiency</td>';
    echo '<td><img src="img/r6.gif" /> Kills</td><td><img src="img/r6.gif"/> Deaths</td></tr>';
    # Get today's stats so far
    $fp = fopen('http://travian.ulrezaj.com/stats.php?p=0','r');   # Load stats today for player into $sql
    $row = explode(',',fgets($fp));
    foreach ($row as &$r) $r = str_replace('"','',$r);              # Get rid of quotes
    echo '<tr><td>Today</td>';
    foreach (array(2,3,4,9) as $i) echo '<td>'.$row[$i].'</td>';
    echo '<td>'.$row[11].'&#37;</td><td>'.$row[12].'</td><td>'.$row[13].'</td>';
    echo '</tr>';
    # Get stat history
    $limit = 19;
    $count = 0;
    $daily = mysql_query('select date_format(day,"%m/%d") curday, sum(attacks) attacks, sum(scouts) scouts, sum(defends) defends, sum(total) total, sum(efficiency*attacks)/sum(attacks) efficiency, sum(losses) losses, sum(rkills) rkills, sum(rdeaths) rdeaths, sum(kills) kills, sum(deaths) deaths from s4_us_stats group by day order by day desc');
    while ($r = mysql_fetch_assoc($daily)) {
        $gx[] = $r["curday"];
        $g1y1[] = $r["total"];
        $g1y2[] = $r["rdeaths"];
        $g2y1[] = $r["kills"];
        $g2y2[] = $r["deaths"];
        if ($count == $limit) continue;
        echo '<tr><td>'.$r["curday"].'</td><td>'.$r["attacks"].'</td><td>'.$r["scouts"].'</td><td>'.$r["defends"];
        echo '</td><td>'.$r["total"].'</td>';
        echo '<td>'.sprintf('%01.2f',$r["efficiency"]).'&#37;</td><td>'.$r["rkills"].'</td><td>'.$r["rdeaths"].'</tr>';
        $count++;
    }
    echo '</table><br>';

    if (count($gx)>0) {
        echo '<br><table cellspacing="1" cellpadding="2" class="tbg">';
        echo '<tr><td class="s7">Daily resources activity in combat</td></tr>';
        echo '<tr><td>';
        echo '<img src="history.php?gyax=Resources&gy1lbl=Bounty&gy2lbl=Losses (troop cost)&gx='.implode(',',array_reverse($gx)).'&gy1='.implode(',',array_reverse($g1y1)).'&gy2='.implode(',',array_reverse($g1y2)).'">';
        echo '<br><br></td></tr>';
        echo '<tr><td class="s7">Daily troop activity (wheat upkeep cost)</td></tr>';
        echo '<tr><td>';
        echo '<img src="history.php?gyax=Wheat/hr&gy1lbl=Troop kills&gy2lbl=Troop deaths&gx='.implode(',',array_reverse($gx)).'&gy1='.implode(',',array_reverse($g2y1)).'&gy2='.implode(',',array_reverse($g2y2)).'">';
        echo '<br></td></tr></table><br>';
}
    
    echo '</table>';
}
?>
</body>
</html>
