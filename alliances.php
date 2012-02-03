<?php
require 'auth_header.php';  # Require that user be authenticated
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="global.css" />
<script src="windowfiles/sorttable.js"></script>
<script type="text/javascript" src="common.js"></script>
</head>
<body>
<?php
include 'common.php';   # Common functions
include 'connect.php';  # Connect

$aid = (!empty($_GET['aid'])) ? $_GET['aid'] : 401;
$daysback = (!empty($_GET['d'])) ? $_GET['d'] : 14;
$foodthreshold = (!empty($_GET['f'])) ? $_GET['f'] : 1;


$foodthreshold = 1;
# Fetch stats about players
$result = mysql_query('select max(attunitsfood-attcasualtiesfood) army, attplayer from s4_us_reports where type in (1,8,10,18) and attunitsfood-attcasualtiesfood>'.$foodthreshold.' and date(time)>date_sub(curdate(), interval '.($daysback*24-3).' hour) and attplayer in (select distinct player from s4_us_villages where aid='.$aid.') group by attplayer');
while ($r = mysql_fetch_assoc($result)) $armysent[$r['attplayer']] = $r['army'];
$result = mysql_query('select max(defunitsfood) army, defplayer from s4_us_reports where type=4 and defunitsfood>'.$foodthreshold.' and date(time)>date_sub(curdate(), interval '.($daysback*24-3).' hour) and defplayer in (select distinct player from s4_us_villages where aid='.$aid.') group by defplayer');
while ($r = mysql_fetch_assoc($result)) $armyscouted[$r['defplayer']] = $r['army'];
$result = mysql_query('select x.player,y.pts off,z.pts def from (select distinct uid, player from s4_us_villages where aid='.$aid.') x left join (select uid, max(points) pts from s4_us_off group by uid) y on x.uid=y.uid left join (select uid, max(points) pts from s4_us_def group by uid) z on x.uid=z.uid');
while ($r = mysql_fetch_assoc($result)) {
    $offs[$r['player']] = $r['off'];
    $defs[$r['player']] = $r['def'];
}
$result = mysql_query('select player, inactive from s4_us_inactives where aid='.$aid);
while ($r = mysql_fetch_assoc($result)) $inactivity[$r['player']] = $r['inactive'];

# Alliance summary
echo '<table class="tbg" cellspacing="1" cellpadding="2" align="center">';
echo '<tr class="rbg"><td colspan="2">Alliance summary</td></tr>';
echo '<tr><td>Click on a player name to go to their analysis page. Off and Def columns are the offensive and defensive rankings. Sent is the largest recent army we have on record, and scouted is the largest army we\'ve scouted.</td></tr>';

echo '<tr><td colspan="2">';

# Player list
echo '<table cellspacing="1" cellpadding="2" align="center" class="tbg4 sortable" id="playerlist">';
# Headers
echo '<tr class="rbg"><td style="cursor: pointer">Player</td><td width="10%" style="cursor: pointer">Pop</td>';
echo '<td width="10%" style="cursor: pointer">Villages</td>';
echo '<td width="10%" style="cursor: pointer">Off</td><td width="10%" style="cursor: pointer">Def</td>';
echo '<td width="10%" style="cursor: pointer">Sent</td><td width="10%" style="cursor: pointer">Scouted</td>';
echo '<td width="10%" style="cursor: pointer">Inactivity</td>';

$result = mysql_query('select y.player, sum(x.pop) pop, y.villages from (select uid, max(pop) pop from s4_us_pop where aid='.$aid.' group by karte) x join (select uid, player, count(karte) villages from s4_us_villages where aid='.$aid.' group by uid) y on x.uid=y.uid group by x.uid order by y.player');
while ($r = mysql_fetch_assoc($result)) {
    echo '<tr><td><a href="/?h=10&p='.$r['player'].'">'.$r['player'].'</a></td><td>'.$r['pop'].'</td><td>'.$r['villages'].'</td><td>';
    foreach (array($offs,$defs,$armysent,$armyscouted) as $field) {
        echo (isset($field[$r['player']])) ? $field[$r['player']] : '-';
        echo '</td><td>';
    }
    echo (!empty($inactivity[$r['player']])) ? $inactivity[$r['player']] : '-';
    echo '</td></tr>';
}
echo '</table>'


?>
</table>
</body>
</html>
