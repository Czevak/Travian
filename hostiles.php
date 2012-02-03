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

# Connect
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax')or die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn);

echo '<table class="tbg" cellspacing="1" cellpadding="2" align="center">';
$v = mysql_fetch_assoc(mysql_query('select distinct player, uid, aid, alliance, tribe from s4_us_villages where player="'.$_GET['p'].'"'));
$_GET['p'] = $v['player'];
$r = mysql_fetch_assoc(mysql_query('select sum(p.pop) pop, count(p.pop) villages from (select max(pop) pop  from s4_us_pop where uid='.$v['uid'].' group by karte) p'));
# Hostile analyzer
echo '<tr class="rbg"><td colspan="4">Hostile Analyzer</td></tr>';
echo '<tr class="cbg1"><td></td><td colspan="4" class="f16 s7 c2 b">'.$v['player'].' <span class="f135 b">- ';
echo ($v['alliance']) ? '<a href="/?h=9&aid='.$v['aid'].'">'.$v['alliance'].'</a>' : 'unallied';
echo '</span></td></tr>';
echo '<tr class="cbg1"><td width="21%">Population</td><td class="s7">'.$r['pop'].'</td>';
echo '<td width="21%">Villages</td><td class="s7">'.$r['villages'].'</td></tr>';
# Report history
echo '<tr class="cbg1"><td>Race</td><td class="s7">'.$const_racemap[$v['tribe']].'</td>';
echo '<td>Full report history</td><td class="s7 c2">';
echo '<a href="http://travian.ulrezaj.com/?h=1&p='.$_GET['p'].'">Report History - '.$_GET['p'].'</a></td>';
echo '</tr>';

echo '<tr><td colspan="4">'."\n";

# Villages
echo '<br><table cellspacing="1" cellpadding="2" class="tbg">';
echo '<tr class="rbg"><td colspan="6">Villages</td></tr>';
echo '<tr class="cbg1"><td>Name</td><td width="12%">Population</td><td width="12%">Coordinates</td><td colspan="3">Links</td></tr>';
$result = mysql_query('select p.karte,v.karte short,v.x,v.y,v.village,max(p.pop)pop from s4_us_villages v, s4_us_pop p where v.karte=p.karte and v.uid='.$v['uid'].' group by v.karte order by v.newdid');
while ($r = mysql_fetch_Assoc($result)) {
    echo '<tr><td class="s7"><a href="http://s4.travian.us/karte.php?d='.$r['karte'].'">'.$r['village'].'</a></td>';
    echo '<td>'.$r['pop'].'</td><td><a href="/?h=3&cx='.$r['x'].'&cy='.$r['y'].'">['.$r['x'].','.$r['y'].']</td>';
    echo '<td width="15%"><a href="/?h=8&k='.$r['karte'].'">Analyzer</a></td>';
    echo '<td width="15%"><a href="http://s4.travian.us/a2b.php?z='.$r['short'].'">Send troops</a></td>';
    echo '<td width="15%"><a href="http://s4.travian.us/build.php?z='.$r['karte'].'&gid=17">Supply</a></td></tr>';
}
echo '</table>';

# Army tracking
$armythreshold = 200;
$cols = 13;
echo '<br><table cellspacing="1" cellpadding="2" class="tbg">';
echo '<tr class="rbg"><td colspan="'.$cols.'">Army tracking</td></tr>';
# Recent armies
echo '<tr class="cbg1"><td colspan="'.$cols.'" class="b">Recent armies sent</td></tr>';
echo '<tr class="unit"><td width="120">Time</td><td width="80">Report</td>'.$raceheader[$const_racemap[$v['tribe']]].'</tr>';
$reports = mysql_query('select *, date_format(time,"%m/%d %T") t from (select * from s4_us_reports where attplayer="'.$_GET['p'].'" and attunitsfood-attcasualtiesfood>'.$armythreshold.' order by id desc limit 5) u order by u.id');
while ($r = mysql_fetch_assoc($reports)) {
    if ($r['id']<0) $r['id'] = -$r['id'];
    if (count(unitct($r['attunits']))==10) $r['attunits'] .= ',0';
    echo '<tr><td>'.$r['t'].'</td><td><a href="/?h=5&i='.$r['id'].'">'.$r['id'].'</a></td>';
    echo unitclr(array_sub(unitct($r["attunits"]),unitct($r["attcasualties"]))).'</tr>';
}
echo '<tr><td colspan="'.$cols.'">&nbsp;</td></tr>';
# Recent scouted
echo '<tr class="cbg1"><td colspan="'.$cols.'" class="b">Recent armies scouted</td></tr>';
echo '<tr class="unit"><td width="120">Time</td><td width="80">Report</td>'.$raceheader[$const_racemap[$v['tribe']]].'</tr>';
$reports = mysql_query('select *, date_format(time,"%m/%d %T") t from (select * from s4_us_reports where type=4 and defplayer="'.$_GET['p'].'" and defunitsfood>'.$armythreshold.' order by id desc limit 5) u order by u.id');
while ($r = mysql_fetch_assoc($reports)) {
    if (count(unitct($r['defunits']))==10) $r['defunits'] .= ',0';
    echo '<tr><td>'.$r['t'].'</td><td>';
    echo ($r['id']<0) ? -$r['id'] : '<a href="/?h=5&i='.$r['id'].'">'.$r['id'].'</a></td>';
    echo unitclr(unitct($r["defunits"])).'</tr>';
}
echo '<tr><td colspan="'.$cols.'">&nbsp;</td></tr>';
echo '</table>';

# Activity
echo '<br><img src="activity.php?p='.$_GET['p'].'">';
echo '<br><br><img src="activity.php?p='.$_GET['p'].'&daily">';

# Latest reports
echo '<br><table cellspacing="1" cellpadding="2" class="tbg">';
echo '<tr class="cbg1"><td colspan="7" class="b">Latest reports</td></tr>';
$reports = mysql_query('select *, date_format(time,"%m/%d %T") t from s4_us_reports where id>0 and (defplayer="'.$_GET['p'].'" or attplayer="'.$_GET['p'].'") order by id desc limit 20');
echo '<tr class="cbg1"><td width="18">&nbsp;</td><td>Players</td><td>Resources</td><td width="8%">Kills</td>';
echo '<td width="8%">Losses</td><td>Report</td><td width="120">Time</td></tr>';
while ($r = mysql_fetch_array($reports)) {
    # Symbol
    if (check_scouts($r['attunits'],$r['attrace'])) $symbol = '14';  # Is a scout
    elseif (check_cats($r['attunits'])) $symbol = '18';
    elseif (check_fake($r['attunits'],$r['attrace'])) $symbol = 'def2';
    else $symbol = 'att_all';
    $symbol .= ($_GET['p']==$r['defplayer']) ? '.gif' : 'r.gif';
    
    echo '<tr><td><img src="img/'.$symbol.'" border="0"></td>';
    echo '<td class="s7">';
    # Players -> Target village
    echo '<a href="/?h=7&p='.$r['attplayer'].'">'.$r['attplayer'].'</a> -> ';
    echo '<a href="/?h=8&k='.$r["defkarte"].'">'.where($r['defender']).'</a></td>';
    # Bounty, Efficiency
    if ($r["total"] != "") echo '<td>'.$r["total"].'</td>';
    else echo '<td> - </td>';
    # Kills
    echo '<td>'.($r["defcasualties"]+$r["rein1casualties"]+$r['rein2casualties']).'</td>';
    # Losses
    echo '<td>'.food($r["attcasualties"],$r["attrace"]).'</td>';
    # Report
    echo '<td><a href="/?h=5&i='.$r['id'].'">'.$r['id'].'</a></td>';
    # Time (NOTE: using 't' because preformatted
    echo '<td>'.str_replace("-","/",$r["t"]).'</td>';
    echo "</tr>\n";
}
echo '</table>';

echo '</table>';


?>
</body>
</html>

