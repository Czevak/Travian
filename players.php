<?php
require 'auth_header.php';  # Require that user be authenticated
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="global.css" />
<script type="text/javascript" src="common.js"></script>
</head>
<body>
<?php
if (!isset($_GET['p'])) die();  # require a player parameter for now

include 'common.php';   # Load common functions
include 'connect.php';  # Connect  

echo '<table class="tbg" cellspacing="1" cellpadding="2" align="center">';
# player analysis
echo '<tr class="rbg"><td colspan="2">'.$_GET['p'].'&#39;s Summary</td></tr>';
# Number of reports (count)
$numreports = mysql_fetch_assoc(mysql_query("select count(id) count from s4_us_reports where attacker like '".$_GET['p']." from the village %'"));    
echo '<tr class="cbg1"><td width="21%">Reports archived</td><td class="s7">'.$numreports["count"].'</td>';
# Biggest raid today (id, total, defender, defkarte)
$big = mysql_fetch_assoc(mysql_query("select id, total, defender, defkarte from s4_us_reports where attacker like '".$_GET['p']." from the village %' and date(time)=curdate() and defcasualties is not null order by convert(total,unsigned) desc limit 1"));
echo '<tr class="cbg1"><td>Biggest raid today</td>';
echo '<td class="s7">'.$big["total"].' resources from <a href="/?h=8&k='.$big["defkarte"].'">';
echo where($big["defender"]).'</a> (<a href="report.php?i='.$big["id"].'">'.$big["id"].'</a>)</td></tr>';
# Report history
echo '<tr class="cbg1"><td>Full report history</td><td class="s7 c2">';
echo '<a href="http://travian.ulrezaj.com/?h=1&p='.$_GET['p'].'">Raid reports - '.$_GET['p'].'</a></td></tr>';
# Link to this page
echo '<tr class="cbg1"><td>This page link</td><td class="s7">';
echo '<input type="text" readonly class="fm fm250" onclick="this.select()" value="http://travian.ulrezaj.com/?h=7&p='.$_GET['p'].'">';
echo '<a href="http://travian.ulrezaj.com/?h=7&p='.$_GET['p'].'"> <img src="img/external.gif" /> </a></td></tr>';

echo '<tr><td colspan="2">'."\n";

# Stats
echo '<br><table cellspacing="1" cellpadding="2" class="tbg">';
# Daily stats
echo '<tr class="cbg1"><td colspan="9" class="b">Daily stats</td></tr>';
echo '<tr class="cbg1"><td>Date</td><td>Attacks</td><td>Scouts</td><td>Defends</td><td>Bounty</td><td>Profit</td><td>Efficiency</td>';
echo '<td><img src="img/r6.gif" /> Kills</td><td><img src="img/r6.gif"/> Deaths</td></tr>';
# Get today's stats so far
$fp = fopen('http://travian.ulrezaj.com/stats.php?p='.urlencode($_GET['p']),'r');   # Load stats today for player into $sql
$row = explode(',',fgets($fp));
foreach ($row as &$r) $r = str_replace('"','',$r);              # Get rid of quotes
echo '<tr><td>Today</td>';
foreach (array(2,3,4,9) as $i) echo '<td>'.$row[$i].'</td>';
echo '<td>'.$row[16];
if ($r["profit"]!="-") echo '&#37;';
echo '</td><td>'.$row[11].'&#37;</td><td>'.$row[12].'</td><td>'.$row[13].'</td>';
echo '</tr>';
# Get stat history
$limit = 9;
$count = 0;
$daily = mysql_query('select *, date_format(day,"%m/%d") curday from s4_us_stats where player="'.$_GET['p'].'" order by day desc');
while ($r = mysql_fetch_assoc($daily)) {
    $gx[] = $r["curday"];
    $g1y1[] = $r["total"];
    $g1y2[] = $r["rdeaths"];
    $g2y1[] = $r["kills"];
    $g2y2[] = $r["deaths"];
    if ($count == $limit) continue;
    echo '<tr><td>'.$r["curday"].'</td><td>'.$r["attacks"].'</td><td>'.$r["scouts"].'</td><td>'.$r["defends"];
    echo '</td><td>'.$r["total"].'</td><td>'.$r["profit"];
    if ($r["profit"]!="-") echo '&#37;';
    echo '</td><td>'.$r["efficiency"].'&#37;</td><td>'.$r["rkills"].'</td><td>'.$r["rdeaths"].'</tr>';
    $count++;
}
echo '</table><br>';

if (count($gx)>0) {
    echo '<br><table cellspacing="1" cellpadding="2" class="tbg">';
    echo '<tr><td class="s7">Daily resources activity in combat</td></tr>';
    echo '<tr><td>';
    echo '<img src="history.php?gyax=Resources&gy1lbl=Bounty&gy2lbl=Losses (troop cost)&gx='.implode(',',array_reverse($gx)).'&gy1='.implode(',',array_reverse($g1y1)).'&gy2='.implode(',',array_reverse($g1y2)).'">';
    echo '<br><br></td></tr>';
    echo '<tr><td class="s7"></td></tr>';
    echo '<tr><td>';
    echo '<img src="activity.php?p='.$_GET['p'].'">';
    echo '<br></td></tr></table><br>';
}

# Efficiency (id, defender, defkarte, efficiency)
$raids = mysql_query('select id, defender, attcasualties, attrace, defkarte, total, efficiency, date_format(time,"%m/%d %T") t from s4_us_reports where id>0 and attplayer="'.$_GET['p'].'" and efficiency is not null order by time desc limit 20');
echo '<table cellspacing="1" cellpadding="2" class="tbg">';

# Latest raids
echo '<tr class="cbg1"><td colspan="6" class="b">Latest raids</td></tr>';
echo '<tr class="cbg1"><td>Target</td><td>Resources</td><td>Efficiency</td><td>Losses</td><td>Report</td><td>Time</td></tr>';
while ($r = mysql_fetch_assoc($raids)) {
    echo '<tr><td><a href="/?h=8&k='.$r["defkarte"].'">'.where($r["defender"]).'</a></td>';
    echo '<td>'.$r["total"].'</td><td>'.$r["efficiency"].'&#37;</td>';
    echo '<td>'.food($r["attcasualties"],$r["attrace"]).'</td>';
    echo '<td><a href="/?h=5&i='.$r["id"].'">'.$r["id"].'</a></td>';
    echo '<td>'.$r["t"].'</td></tr>';   # NOTE t is time preformatted (see query)
}
echo '</table>';

echo '</table>';


?>
</body>
</html>

