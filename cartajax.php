<?php
if (empty($_GET['x'])||empty($_GET['y'])||empty($_GET['b'])) die;   # This can only be called with x,y params

include 'common.php';   # Load common functions

$wings = array(401,450);
$naps = array(108,194,224,225,531);

$cenx = $_GET['x'];
$ceny = $_GET['y'];
$bound = $_GET['b'];

$x1 = dec($cenx,$bound);
$x2 = inc($cenx,$bound);
$y1 = dec($ceny,$bound);
$y2 = inc($ceny,$bound);

# Construct sql boundary conditions
if ($x1>$x2 && $y1<$y2) $cond = '('.$x1.'<=x or x<='.$x2.') and '.$y1.'<=y and y<='.$y2;
elseif ($y1>$y2 and $x1<$x2) $cond = $x1.'<=x and x<='.$x2.' and ('.$y1.'<=y or y<='.$y2.')';
elseif ($x1>$x2 and $y1>$y2) $cond = '('.$x1.'<=x or x<='.$x2.') and ('.$y1.'<=y or y<='.$y2.')';
else $cond = $x1.'<=x and x<='.$x2.' and '.$y1.'<=y and y<='.$y2;

# Connect
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax') or die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn) or die('Database error: '.mysql_error());

# Get map
$map = array();
$kartes = array();
$result = mysql_query('select x, y, info, karte from s4_us_map where '.$cond.' order by x,y desc');
while ($r = mysql_fetch_array($result)) {
	$map[$r['x']][$r['y']] = $r['info'];
    $kartes[$r['x']][$r['y']] = $r['karte'];
}

# Get villages + info
$villages = array();
$alliances = array();
$attrs = array('uid','player','aid','pop','inactive','village');
$result = mysql_query('select u.uid,u.player,u.inactive,v.x,v.y,v.aid,v.alliance,v.pop,v.village from s4_us_inactives u join (select uid,x,y,aid,alliance,pop,village from s4_us_villages where '.$cond.') v on u.uid=v.uid');
while ($r = mysql_fetch_array($result)) {
    foreach ($attrs as $k) $villages[$r['x']][$r['y']][$k] = $r[$k];
    if (!array_key_exists($r['aid'],$alliances)) $alliances[$r['aid']] = $r['alliance'];
}

# Write alliance number dictionary
foreach ($alliances as $aid => $name) {
    echo 'anum['.$aid.']="'.$name.'";';
}

# Write map dictionary
$icon = array('wood'=>1,'clay'=>4,'iron'=>7,'25'=>10,'25wood'=>3,'25clay'=>6,'25iron'=>9,'50'=>12);
foreach (incrange($x1, $x2) as $x) {
    foreach (incrange($y1, $y2) as $y) {
        echo 'm["'.$x.','.$y.'"]=new Array("'.$map[$x][$y].'","';
        if (!isset($villages[$x][$y])) {
            if (isset($icon[$map[$x][$y]])) {
                $img = (in_array($icon[$map[$x][$y]],array(1,4,7,10))) ? 'o'.($icon[$map[$x][$y]]+rand(0,1)) : 'o'.$icon[$map[$x][$y]];
            } else { 
                $img = 't'.rand(0,9);
            }
            echo $img.'","'.$kartes[$x][$y].'");';
            continue;
        } else {
            $img = 'd';
            if ($villages[$x][$y]['pop'] < 100) $img .= '0';
            elseif ($villages[$x][$y]['pop'] < 200) $img .= '1';
            elseif ($villages[$x][$y]['pop'] < 500) $img .= '2';
            else $img .= '3';
            if (in_array($villages[$x][$y]['aid'],$const_wings)) $img.= '3';
            elseif (in_array($villages[$x][$y]['aid'],$const_naps)) $img.= '5';
            else $img .= '2';
            if (in_array($villages[$x][$y]['aid'],$const_hostiles)) $img .= 'h';
            elseif ($villages[$x][$y]['inactive']>=2) $img .= 'i';
            echo $img.'"';
        }
        # info, img, karte[, uid, player, aid, pop, inactive, village]
        echo ',"'.$kartes[$x][$y].'",';
        echo $villages[$x][$y]['uid'].',"'.$villages[$x][$y]['player'].'",'.$villages[$x][$y]['aid'];
        echo ','.$villages[$x][$y]['pop'].','.$villages[$x][$y]['inactive'].',"'.$villages[$x][$y]['village'].'");';
    }
}
?>
    