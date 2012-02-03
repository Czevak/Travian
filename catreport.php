<?php
require 'auth_header.php';  # Require that user be authenticated

if (!isset($_GET['i'])) die();  # require an id parameter (i) for now

# Check cache
$cachefile = 'cache/'.md5($_SERVER['REQUEST_URI']);
if (file_exists($cachefile)) {
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

# Connect
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax');
if (!$conn) die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn);
$result = mysql_query("select time, attkarte, defkarte from s4_us_reports where id=".$_GET['i']) or failpage('Error: Invalid report id');
$r = mysql_fetch_assoc($result) or failpage('Error: Invalid report id');

# Get first report in wave
$result = mysql_query('select *, abs(timestampdiff(SECOND,time,"'.$r['time'].'")) elapsed from s4_us_reports where abs(timestampdiff(MINUTE,time,"'.$r['time'].'")) < 60 and attkarte="'.$r['attkarte'].'" and defkarte="'.$r['defkarte'].'" and type in (8,18) and id>0 order by id');
while ($r = mysql_fetch_assoc($result)) $rows[] = $r;   # Get all reports into an array
if (count($rows)<2) failpage('Error: This is not a valid cat report id');
    
# Pad unit lists to 11 length
foreach ($rows as &$r) {
    foreach (array("att","def","rein1","rein2") as $sec) {
        if ($r[$sec."race"]=="Natar" || $r[$sec."race"]=="Nature") continue;
        if (count(unitct($r[$sec."units"]))==10) $r[$sec."units"] .= ",0";
        if (count(unitct($r[$sec."casualties"]))==10) $r[$sec."casualties"] .= ",0";
    }
    if (count(unitct($r['prisoners']))==10) $r['prisoners'] .= ",0";
    if (strpos($r['defunits'],"?") !== false) $r["defunits"] = str_replace("0","?",$r["defunits"]);
}
unset($r);
$f = $rows[0];

# Extract attacker and defender names
$attvil = mysql_fetch_assoc(mysql_query('select x,y from s4_us_map where karte="'.$f['attkarte'].'"'));
$defvil = mysql_fetch_assoc(mysql_query('select x,y from s4_us_map where karte="'.$f['defkarte'].'"'));
$attalliance = mysql_fetch_assoc(mysql_query('select alliance from s4_us_villages_snap where karte="'.$f['attkarte'].'" and day=date("'.$f['time'].'")'));
$attalliance = $attalliance['alliance'];
$defalliance = mysql_fetch_assoc(mysql_query('select alliance from s4_us_villages_snap where karte="'.$f['defkarte'].'" and day=date("'.$f['time'].'")'));
$defalliance = $defalliance['alliance'];

echo '<table class="tbg" cellspacing="1" cellpadding="2" align="center">';
# Attack report
echo '<tr class="rbg"><td colspan="2">Catapult report</td></tr>';
# Attacker
echo '<tr class="cbg1"><td>Attacker</td><td class="s7"><a href="/?h=7&p='.$f['attplayer'].'">'.$f['attplayer'].'</a>';
echo ' from the village <a href="http://s4.travian.us/karte.php?d='.$f["attkarte"].'">'.where($f['attacker']).' ['.$attvil['x'].','.$attvil['y'].']</a>';
echo ($attalliance) ? ' - <b>'.$attalliance.'</b></td></tr>' : ' - unallied</td></tr>';
# Defender
echo '<tr class="cbg1"><td>Defender</td><td class="s7"><a href="/?h=10&p='.$f['defplayer'].'">'.$f['defplayer'].'</a>';
echo ' from the village <a href="/?h=8&k='.$f["defkarte"].'">'.where($f['defender']).' ['.$defvil['x'].','.$defvil['y'].']</a>';
echo ($defalliance) ? ' - <b>'.$defalliance.'</b></td></tr>' : ' - unallied</td></tr>';
# Timestamp
echo '<tr class="cbg1"><td>Timestamp</td><td class="s7">'.$f['time'].' till '.$rows[count($rows)-1]['time'].' EST</td></tr>';
# Link to this report
echo '<tr class="cbg1"><td>This report link</td><td class="s7">';
echo '<input type="text" readonly class="fm fm250" onclick="this.select()" value="http://travian.ulrezaj.com/?h=6&i='.$f["id"].'">';
echo '<a href="/?h=6&i='.$f['id'].'"> <img src="img/external.gif" /> </a></td></tr>';

echo '<tr><td colspan="2">';
$r = $rows[0];

# Start units section
foreach (array("att","def","rein1","rein2") as $sec) {

    if (!$r[$sec."race"]) continue;
    $cols = count(unitct($r[$sec."units"]));
    echo '<br>';
    echo '<table cellspacing="1" cellpadding="2" class="tbg"><tr class="cbg1">';
    # Attacker/Defender + name from village
    
    if ($sec == "att") echo '<td width="21%" class="c2 b">Attacker</td>';
    else echo '<td width="21%" class="c1 b">Defender</td>';
    if ($sec == "att") echo '<td colspan="'.$cols.'">'.$r["attacker"].'</td></tr>'."\n";
    elseif ($sec == "def") echo '<td colspan="'.$cols.'">'.$r["defender"].'</td></tr>'."\n";
    else echo '<td colspan="'.$cols.'">Reinforcements</td></tr>'."\n";
    # Troop icons
    echo '<tr class="unit"><td>&nbsp;</td>'.$raceheader[$r[$sec."race"]].'</tr>';
    # Units
    echo '<tr><td>';
    if ($sec=="att") echo '<a href="/?h=5&i='.$f['id'].'">Clear</a> ';
    echo 'Units</td>'.unitclr(unitct($r[$sec."units"])).'</tr>';
    # Casualties
    if ($r[$sec."casualties"] && !($sec=="att" && $r['prisoners'] && !food($r["attcasualties"],$r['attrace']))) {
        echo ($sec=="att") ? '<tr><td class="c5">Clear ' : '<tr><td>';
        echo 'Casualties</td>'.unitclr(unitct($r[$sec."casualties"])).'</tr>'."\n";
    }
    # Waves
    if ($sec=="att") {
        for ($i=1; $i<count($rows); $i++) {
            echo '<tr><td><a href="/?h=5&i='.$rows[$i]['id'].'">Wave '.$i.'</a>: +';
            # Time difference is from perspective of original report so adjust
            if ($rows[$i]['id'] > $_GET['i']) echo abs($rows[$i]['elapsed']*1+$r['elapsed']);
            elseif ($rows[$i]['id'] < $_GET['i']) echo abs($rows[$i]['elapsed']-$r['elapsed']);
            else echo $r['elapsed'];
            echo ' sec</td>'.unitclr(unitct($rows[$i][$sec."units"])).'</tr>';
        }
    }
    # Wave casualties
    if ($sec=="att") {
        for ($i=1; $i<count($rows); $i++) {
            $wavecasualties = array_add($wavecasualties,unitct($rows[$i]['attcasualties']));
        }
        echo '<tr><td class="c5">Wave Casualties</td>'.unitclr($wavecasualties).'</tr>'."\n";
    }
    # Prisoners
    if ($sec=="att" && $r['prisoners'])
        echo '<tr><td>Prisoners</td>'.unitclr(unitct($r["prisoners"])).'</tr>'."\n";
    # Info
    if ($sec=="att" && $r['info']){
        foreach ($rows as &$k) {
            foreach (unitct($k['info']) as $info) { 
                if (($info == 'You freed 0 of your own units' && $k!=$rows[0])||!$info) continue;
                echo '<tr class="cbg1"><td>Info</td><td class="s7" colspan="'.$cols.'">'.$info.'</td></tr>'."\n";
            }
        }
        unset($k);
    }
    # Bounty
    if ($sec=="att" && $r['bounty']) {
        echo '<tr class="cbg1"><td>Bounty';
        echo '</td><td class="s7" colspan="'.$cols.'">';
        foreach ($rows as $k) $bounty[] = explode(",",$k['bounty']);
        for ($i=0; $i<4; $i++) {
            foreach ($bounty as $b) $total[$i] += $b[$i]*1;
        }
        for ($i=0; $i<4; $i++) echo '<img class="res" src="img/r'.($i+1).'.gif" /> '.$total[$i].' ';
        echo ' &raquo; <span class="c3 b">'.array_sum($total).'</span> resources</td></tr>'."\n";
    }
    # Attacker loss
    if ($sec=="att") {
        echo '<tr class="cbg1"><td>Loss</td><td class="s7" colspan="'.$cols.'">';
        foreach ($rows as $k) $loss[] = cost($k[$sec.'casualties'],$k[$sec.'race']);
        for ($i=0; $i<4; $i++) {
            foreach ($loss as $l) $tloss[$i] += $l[$i];
        }
        for ($i=0; $i<4; $i++) echo '<img class="res" src="img/r'.($i+1).'.gif" /> '.$tloss[$i].' ';
        echo ' &raquo; <span class="c5 b">'.array_sum($tloss).'</span> resources</td></tr>'."\n";
    }
    # Loss
    if ($sec!="att" && $r[$sec.'casualties']) {
        echo '<tr class="cbg1"><td>Loss</td><td class="s7" colspan="'.$cols.'">';
        $loss = cost($r[$sec.'casualties'],$r[$sec.'race']);
        for ($i=0; $i<4; $i++) 
            echo '<img class="res" src="img/r'.($i+1).'.gif" /> '.$loss[$i].' ';
        echo ' &raquo; '.array_sum($loss).' resources</td></tr>'."\n";
    }
    # Profit
    if ($sec=="att" && $loss && $r['bounty'] && array_sum($total)!=0) {
        $bounty = array_sum($total);
        $loss = array_sum($loss);
        echo '<tr class="cbg1"><td>Profit</td><td class="s7" colspan="'.$cols.'">';
        echo sprintf('%01.2f',($bounty-$loss)*100/$bounty).'&#37; &raquo; ';
        echo ($bounty-$loss).' resources</td></tr>'."\n";
    }
    # Efficiency
    if ($sec=="att" && $r['efficiency'] && !check_scouts($r['attunits'],$r['attrace'])) {
        echo '<tr class="cbg1"><td>Efficiency</td><td class="s7" colspan="'.$cols.'">';
        echo $r['efficiency'].' &#37;</td></tr>'."\n";
    }
    echo '</table>';
}

echo '<br>';
# Begin stats section
foreach ($rows as $r) {
    $attloss += food($r['attcasualties'],$r['attrace']);
    $attunits += food($r['attunits'],$r['attrace']);
    $attpts = array_add($attpts,attpoints($r['attunits'],$r['attrace']));
    $attbuild[0] = array_add($attbuild[0],buildtime($r['attcasualties'],$r['attrace']));
    $attbuild[1] = array_add($attbuild[1],buildtime($r['attunits'],$r['attrace']));
    $attresloss += costr($r['attcasualties'],$r['attrace']);
    $attres += costr($r['units'],$r['attrace']);
    foreach (array('def','rein1','rein2') as $s) {
        $defunits += food($r[$s.'units'],$r[$s.'race']);
        $defloss += food($r[$s.'casualties'],$r[$s.'race']);
        $defpts = array_add($defpts,defpoints($r[$s.'units'],$r[$s.'race']));
        $defcost = array_add($defcost,cost($r[$s.'units'],$r[$s.'race']));
        $defbuild[0] = array_add($defbuild[0],buildtime($r[$s.'casualties'],$r[$s.'race']));
        $defbuild[1] = array_add($defbuild[0],buildtime($r[$s.'units'],$r[$s.'race']));
        $defresloss += costr($r[$s.'casualties'],$r[$s.'race']);
        $defres += costr($r[$s.'units'],$r[$s.'race']);
    }
    
}
$attper += $attloss*100/$attunits;
$defper = ($defunits>0) ? $defloss*100/$defunits : 0;
echo '<table cellspacing="1" cellpadding="2" class="tbg">';
# Battle stats
echo '<tr class="cbg1"><td colspan="5" class="b">Battle Stats</td></tr>';
echo '<tr><td width="20%">&nbsp;</td><td colspan="2" width="40%" class="c2 b">Attacker</td>';
echo '<td colspan="2" width="40%" class="c1 b">Defender</td></tr>';
# Percentage outcome
echo '<tr><td>Outcome</td><td colspan="2" class=';
echo ($attper<=$defper) ? '"c3 f16 b">' : '"c5 f16 b">';
echo sprintf('%01.2f',$attper).'&#37;</td><td colspan="2" class=';
echo ($attper<=$defper) ? '"c5 f16 b">' : '"c3 f16 b">';
echo sprintf('%01.2f',$defper).'&#37;</td></tr>';
# Off/def points
echo '<tr><td><img src="img/att2.gif"> <img src="img/def1.gif"> Points</td>';
echo '<td colspan="2"><img src="img/def_i.gif"> '.$attpts[0].' + <img src="img/def_c.gif"> '.$attpts[1].' = ';
echo '<img src="img/att2.gif"> '.array_sum($attpts).'</td>';
echo '<td colspan="2"><img src="img/def_i.gif"> '.$defpts[0].' + <img src="img/def_c.gif"> '.$defpts[1].' = ';
echo '<img src="img/def1.gif"> '.array_sum($defpts).'</td></tr>';
# Battle costs
echo'<tr><td></td><td colspan="2"></td><td colspan="2"></td></tr>';
echo '<tr class="cbg1"><td class="b">Battle Costs</td><td>Lost</td><td>Total</td><td>Lost</td><td>Total</td></tr>';
# Troop loss food cost
echo '<tr><td><img src="img/r5.gif"> Troop Losses</td>';
echo '<td class="c5 b">'.$attloss.'</td><td>'.$attunits.'</td><td class="c5 b">'.$defloss.'</td><td>'.$defunits.'</td></tr>';
# Troop loss resource cost
echo '<tr><td><img src="img/r6.gif"> Troop Losses</td>';
echo '<td class="c5 b">'.$attresloss.'</td><td>'.$attres.'</td><td class="c5 b">'.$defresloss.'</td><td>'.$defres.'</td></tr>';
# Build times (Lost, Total)
echo '<tr><td>Build times</td>';
foreach (array($attbuild,$defbuild) as $p) {
    foreach (array($p[0],$p[1]) as $b) {
        echo '<td class="r7">';
        if ($b[0]) echo '&nbsp;&nbsp;<img src="img/g19s.gif"> '.timeformat($b[0]);
        if ($b[0]&&$b[1]) echo '<br>';
        if ($b[1]) echo '<img src="img/g20s.gif"> '.timeformat($b[1]);
        if (($b[0]||$b[1])&&$b[2]) echo '<br>';
        if ($b[2]) echo '<img src="img/g21s.gif"> '.timeformat($b[2]);
        if (($b[0]||$b[1]||$b[2])&&$b[3]) echo '<br>';
        if ($b[3]) echo '<img src="img/g25s.gif"> '.timeformat($b[3]);
        echo '<br><img src="img/clock3.gif"> <span class="f10 b">'.timeformat(array_sum($b)).'</span></td>';
    }
}
echo '</table>';
echo '</td></tr>';

echo '</table></body></html>';

# Begin caching
$fp = fopen($cachefile, 'w');
fwrite($fp, ob_get_contents());
fclose($fp);
ob_end_flush(); 
?>

