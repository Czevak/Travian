<?php
#require 'auth_header.php';  # Require that user be authenticated

# Check cache
$cachefile = 'cache/'.md5($_SERVER['REQUEST_URI']);
if (file_exists($cachefile) && (time()-600 < filemtime($cachefile))) {
    include $cachefile;
    exit;
}
ob_start();
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="global.css" />
<script type="text/javascript" src="common.js"></script>
</head>
<body>
<?php
include 'common.php';   # Load common functions
include 'connect.php';  # Connect

# If request comes in as coordinates, find target village karte first
if (isset($_GET['x']) && isset($_GET['y']) && $_GET['x'] && $_GET['y']) {
    $village = mysql_fetch_assoc(mysql_query("select karte from s4_us_map where x=".$_GET['x']." and y=".$_GET['y']));
    $_GET['k'] = $village["karte"];
}

#######################
##  Alliance Stats   ##
#######################

echo '<table class="tbg" cellspacing="1" cellpadding="2" align="center">';
# Alliance Stats
echo '<tr class="rbg"><td colspan="2">Alliance Stats</td></tr>';
# Number of reports (count)
$numreports = mysql_fetch_assoc(mysql_query("select count(id) count from s4_us_reports"));    
echo '<tr class="cbg1"><td width="25%" class="s7">Reports archived</td><td class="s7">'.$numreports["count"].'</td>';
# Biggest raid today (id, attacker, total)
$big = mysql_fetch_assoc(mysql_query('select id, attplayer, total from s4_us_reports where attaid in ('.implode(',',$const_wings).') and date(time)=curdate() and type in (1,8,10,18) order by total desc limit 1'));
echo '<tr class="cbg1"><td class="s7">Biggest raid today</td>';
echo '<td class="s7">'.$big["total"].' resources by <a href="/?h=7&p='.$big["attplayer"].'">';
echo $big["attplayer"].'</a> (<a href="/?h=5&i='.$big["id"].'">'.$big["id"].'</a>)</td></tr>';
# Most raids today
$most = mysql_fetch_assoc(mysql_query('select count(id) count,attplayer from s4_us_reports where type in (1,8,10,18) and attaid in ('.implode(',',$const_wings).') and date(time)=date(timestampadd(HOUR,3,now())) group by attplayer order by count(id) desc limit 1'));
echo '<tr class="cbg1"><td class="s7">Most raids today</td>';
echo '<td class="s7">'.$most["attplayer"].' ('.$most["count"].')'; 

echo '<tr><td colspan="2">'."\n";

#######################
##  Weekly rankings  ##
#######################

echo '<table cellspacing="0" cellpadding="3" class="tbg tbg3" align="center">';
echo '<tr><td colspan="3" class="c2 b">Weekly Top 10 Rankings</td></tr><tr><td>';
# Attacks
echo '<table cellspacing="1" cellpadding="2" class="tbg tbg3">';
echo '<tr class="cbg1"><td colspan="3" class="b">Attacks Sent</td></tr>';
$result = mysql_query('select * from w_attacks limit 10');
$rank = 1;
while ($r = mysql_fetch_assoc($result)) { 
    echo '<tr><td width="15%">'.$rank++.'</td><td class="s7">'.$r['player'].'</td><td class="s7" width="30%">'.$r['value'].'</td></tr>';
}
echo '</table></td>';

# Stolen
echo '<td><table cellspacing="1" cellpadding="2" class="tbg tbg3">';
echo '<tr class="cbg1"><td colspan="3" class="b">Resources Stolen</td></tr>';
$result = mysql_query('select * from w_stolen limit 10');
$rank = 1;
while ($r = mysql_fetch_assoc($result)) {
    echo '<tr><td width="15%">'.$rank++.'</td><td class="s7">'.$r['player'].'</td><td class="s7" width="30%">'.$r['value'].'</td></tr>';
}
echo '</table></td>';

# Razed
echo '<td valign="top"><table cellspacing="1" cellpadding="2" class="tbg tbg3">';
echo '<tr class="cbg1"><td colspan="3" class="b">Buildings Razed</td></tr>';
$result = mysql_query('select * from w_razed limit 10');
$rank = 1;
while ($r = mysql_fetch_assoc($result)) {
    echo '<tr><td width="15%">'.$rank++.'</td><td class="s7">'.$r['player'].'</td><td class="s7" width="30%">'.$r['value'].'</td></tr>';
}
echo '</table></td></tr>';
echo '<tr>';

# Population growth
echo '<td><table cellspacing="1" cellpadding="2" class="tbg tbg3">';
echo '<tr class="cbg1"><td colspan="3" class="b">Population Growth</td></tr>';
$result = mysql_query('select * from w_pop limit 10');
$rank = 1;
while ($r = mysql_fetch_assoc($result)) {
    echo '<tr><td width="15%">'.$rank++.'</td><td class="s7">'.$r['player'].'</td><td class="s7" width="30%">'.$r['value'].'</td></tr>';
}
echo '</table></td>';

# Largest army
echo '<td><table cellspacing="1" cellpadding="2" class="tbg tbg3">';
echo '<tr class="cbg1"><td colspan="3" class="b">Largest Army Sent <img src="/img/r5.gif"></td></tr>';
$result = mysql_query('select * from w_army limit 10');
$rank = 1;
while ($r = mysql_fetch_assoc($result)) {
    echo '<tr><td width="15%">'.$rank++.'</td><td class="s7">'.$r['player'].'</td><td class="s7" width="30%">'.$r['value'].'</td></tr>';
}
echo '</table></td>';

# Most kills
echo '<td><table cellspacing="1" cellpadding="2" class="tbg tbg3">';
echo '<tr class="cbg1"><td colspan="3" class="b">Most Kills <img src="/img/r5.gif"></td></tr>';
$result = mysql_query('select * from w_kills limit 10');
$rank = 1;
while ($r = mysql_fetch_assoc($result)) {
    echo '<tr><td width="15%">'.$rank++.'</td><td class="s7">'.$r['player'].'</td><td class="s7" width="30%">'.$r['value'].'</td></tr>';
}
echo '</table></td>';
echo '</tr>';

echo '</table>';

#######################
##  Alliance Stats   ##
#######################

echo '<br><table cellspacing="1" cellpadding="2" class="tbg">';
# Daily stats
echo '<tr class="cbg1"><td colspan="8" class="b">Daily stats</td></tr>';
echo '<tr class="cbg1"><td>Date</td><td>Attacks</td><td>Scouts</td><td>Defends</td><td>Bounty</td><td>Efficiency</td>';
echo '<td><img src="img/r6.gif" /> Kills</td><td><img src="img/r6.gif"/> Deaths</td></tr>';
# Get today's stats so far
$result = mysql_fetch_assoc(mysql_query('select count(id) attacks, sum(total) total, sum(efficiency) efficiency, sum(defcasualtiescost)+sum(rein1casualtiescost)+sum(rein2casualtiescost) attkills, sum(attcasualtiescost) attdeaths from s4_us_reports where date(time)=date(timestampadd(hour,3,now())) and type in (1,8,10,18) and attkarte in (select karte from s4_us_villages where aid in (401,450))'));
$attacks = $result['attacks'];
$total = $result['total'];
$efficiency = ($attacks>0) ? $result['efficiency']/$attacks : 0;
$attkills = $result['attkills'];
$attdeaths = $result['attdeaths'];
$result = mysql_fetch_assoc(mysql_query('select count(id) scouts from s4_us_reports where date(time)=date(timestampadd(hour,3,now())) and type=4 and attkarte in (select karte from s4_us_villages where aid in (401,450))'));
$scouts = $result['scouts'];
$result = mysql_fetch_assoc(mysql_query('select count(id) defends, sum(defcasualtiescost)+sum(rein1casualtiescost)+sum(rein2casualtiescost) attkills, sum(attcasualtiescost) attdeaths from s4_us_reports where date(time)=date(timestampadd(hour,3,now())) and type in (1,8,10,18) and attkarte not in (select karte from s4_us_villages where aid in (401,450))'));
$defends = $result['defends'];
$attkills += $result['attkills'];
$attdeaths += $result['attdeaths'];
echo '<tr><td>Today</td><td>'.$attacks.'</td><td>'.$scouts.'</td><td>'.$defends.'</td><td>'.$total.'</td>';
echo '<td>'.sprintf('%01.2f',$efficiency).'&#37;<td>'.$attkills.'</td><td>'.$attdeaths.'</td></tr>';
# Get stat history
$limit = 9;
$count = 0;
$daily = mysql_query('select date_format(day,"%m/%d") curday, sum(attacks) attacks, sum(scouts) scouts, sum(defends) defends, sum(total) total, sum(efficiency*attacks)/sum(attacks) efficiency, sum(losses) losses, sum(rkills) rkills, sum(rdeaths) rdeaths, sum(kills) kills, sum(deaths) deaths from s4_us_stats group by day order by day desc limit 60');
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

echo '</table></body></html>';

# Begin caching
$fp = fopen($cachefile, 'w');
fwrite($fp, ob_get_contents());
fclose($fp);
ob_end_flush(); 
?>
