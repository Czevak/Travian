<?php
#require 'auth_header.php';  # Require that user be authenticated
if (!empty($_GET['karte'])) {
    for($i=0;$i<11;$i++) $units[] = (empty($_GET['u'.$i])) ? '0' : $_GET['u'.$i];
    $conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax') or die('Could not connect: '.mysql_error());
    mysql_select_db('ulrezaj2_travian', $conn) or die('DB error: '.mysql_error());
    mysql_query('update s4_us_rally set units="'.implode(',',$units).'" where id="'.$_GET['id'].'" and count='.$_GET['count'].' and karte="'.str_replace("-","&",$_GET['karte']).'" and landtime="'.$_GET['landtime'].'"');
    echo '<meta http-equiv=refresh content="0;URL=/?h=12&id='.$_GET['id'].'">';
    exit;
}
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="global.css" />
<link rel="stylesheet" href="windowfiles/dhtmlwindow.css" type="text/css" />
<script type="text/javascript" src="windowfiles/dhtmlwindow.js"></script>
<script src="windowfiles/sorttable.js"></script>
<script type="text/javascript">
var inc = 0;
var timer = new Array();
<?php
include 'common.php';   # Common functions
include 'connect.php';  # Connect


# Move landed reins
mysql_query('update s4_us_rally set type=4 where timestampadd(hour,3,now())>landtime and type=1');

$server_offset = 1;    # Offset between server and travian in seconds 

function same($a,$b) {
    return ($a['count']==$b['count']&&$a['karte']==$b['karte']&&$a['landtime']==$b['landtime']);
}

if (!empty($_GET['id'])) {
    $reins = array();
    $attacks = array();
    $raids = array();
    $known = array();
    $result = mysql_query('select *, replace(date_format(landtime,"%m/%d %T"),date_format(landtime,"%m/%d"),"Today") t, time_to_sec(timediff(landtime,timestampadd(hour,3,now()))) tr, timediff(landtime,timestampadd(hour,3,now())) tf from s4_us_rally where id="'.$_GET['id'].'" and type=1 order by landtime');
    while ($r = mysql_fetch_assoc($result)) $reins[] = $r;
    $result = mysql_query('select *, replace(date_format(landtime,"%m/%d %T"),date_format(landtime,"%m/%d"),"Today") t, time_to_sec(timediff(landtime,timestampadd(hour,3,now()))) tr, timediff(landtime,timestampadd(hour,3,now())) tf from s4_us_rally where id="'.$_GET['id'].'" and type=2 order by landtime');
    while ($r = mysql_fetch_assoc($result)) $attacks[] = $r;
    $result = mysql_query('select *, replace(date_format(landtime,"%m/%d %T"),date_format(landtime,"%m/%d"),"Today") t, time_to_sec(timediff(landtime,timestampadd(hour,3,now()))) tr, timediff(landtime,timestampadd(hour,3,now())) tf from s4_us_rally where id="'.$_GET['id'].'" and type=3 order by landtime');
    while ($r = mysql_fetch_assoc($result)) $raids[] = $r;
    $result = mysql_query('select *, replace(date_format(landtime,"%m/%d %T"),date_format(landtime,"%m/%d"),"Today") t, time_to_sec(timediff(landtime,timestampadd(hour,3,now()))) tr, timediff(landtime,timestampadd(hour,3,now())) tf from s4_us_rally where id="'.$_GET['id'].'" and type=1 and units!="" order by race');
    while ($r = mysql_fetch_assoc($result)) $known[] = $r;
    $result = mysql_query('select * from s4_us_rally where id="'.$_GET['id'].'" and type=4 order by race');
    while ($r = mysql_fetch_assoc($result)) $landed[] = $r;
    $result = mysql_query('select * from s4_us_rally where id="'.$_GET['id'].'" and type<4 order by landtime');
    while ($r = mysql_fetch_assoc($result)) $landorder[] = $r;
    echo 'var inc='.(count($reins)+count($attacks)+count($raids)).';';
    
    $i = 1;
    foreach (array_merge($attacks,$raids,$reins,$known) as $r) {
        echo 'timer['.$i++.']='.($r['tr']-$server_offset).';';
    }

}

?>
function showunits(o) {
}

function editunits(info) {
  var i = info.split(",");
  rh=new Array();
  rh[2] = '<td><img src="img/11.gif" title="Maceman"></td><td><img src="img/12.gif" title="Spearman"></td><td><img src="img/13.gif" title="Axeman"></td><td><img src="img/14.gif" title="Scout"></td><td><img src="img/15.gif" title="Paladin"></td><td><img src="img/16.gif" title="Teutonic Knight"></td><td><img src="img/17.gif" title="Ram"></td><td><img src="img/18.gif" title="Catapult"></td><td><img src="img/19.gif" title="Chieftain"></td><td><img src="img/20.gif" title="Settler"></td><td><img src="img/hero.gif" title="Hero"></td>';
  rh[3] = '<td><img src="img/21.gif" title="Phalanx"></td><td><img src="img/22.gif" title="Swordsman"></td><td><img src="img/23.gif" title="Pathfinder"></td><td><img src="img/24.gif" title="Theutates Thunder"></td><td><img src="img/25.gif" title="Druidrider"></td><td><img src="img/26.gif" title="Haeduan"></td><td><img src="img/27.gif" title="Battering Ram"></td><td><img src="img/28.gif" title="Trebuchet"></td><td><img src="img/29.gif" title="Chief"></td><td><img src="img/30.gif" title="Settler"></td><td><img src="img/hero.gif" title="Hero"></td>';
  rh[1] = '<td><img src="img/1.gif" title="Legionnaire"></td><td><img src="img/2.gif" title="Praetorian"></td><td><img src="img/3.gif" title="Imperian"></td><td><img src="img/4.gif" title="Equites Legati"></td><td><img src="img/5.gif" title="Equites Imperatoris"></td><td><img src="img/6.gif" title="Equites Caesaris"></td><td><img src="img/7.gif" title="Ram"></td><td><img src="img/8.gif" title="Fire Catapult"></td><td><img src="img/9.gif" title="Senator"></td><td><img src="img/10.gif" title="Settler"></td><td><img src="img/hero.gif" title="Hero"></td>';

  updatehtml = '<form action="/" id="updateform"><input type="hidden" name="h" value="12">';
  updatehtml += '<input type="hidden" name="count" value="'+i[0]+'"><input type="hidden" name="karte" value="'+i[1].replace("&","-")+'">';
  updatehtml += '<input type="hidden" name="landtime" value="'+i[2]+'"><input type="hidden" name="id" value="<?php echo $_GET['id'] ?>">';
  updatehtml += '<table class="tbg tbg2" cellspacing="1" cellpadding="2" align="center">';
  updatehtml += '<tr>'+rh[parseInt(i[3])]+'</tr><tr>';
  for (var j=0;j<11;j++) updatehtml += '<td width="40"><input class="fm fm40" type="text" name="u'+j+'"></td>';
  updatehtml += '</tr><tr><td colspan="11" class="r7"><img border="0" style="cursor:pointer" src="img/ok1.gif" onmousedown="this.src=\'img/ok2.gif\'" onmouseout="this.src=\'img/ok1.gif\'" onmouseup="this.src=\'img/ok1.gif\'; blur(); document.getElementById(\'updateform\').submit()"></td></tr>'
  updatehtml += '</table></form>';
  var updatewin = dhtmlwindow.open('updatewin','inline',updatehtml,'Update Incoming Troops','width=500px,height=75px,top=200px,left=337px,resize=0,scrolling=0');
}

function timeformat(inp) {return (inp>0)?pad(floor(inp/3600))+':'+pad(floor((inp%3600)/60))+':'+pad(floor(inp%60)):'00:00:00';}
function pad(inp) {
  inp = inp.toString();
  if (inp.length == 1) return '0'+inp;
  return inp;
}
function floor(x) { return Math.floor(x); }

function tick() {
  for (var i=1;i<=inc;i++) {
    timer[i] -= 1;
    if (timer[i]==0) window.location.href=window.location.href;
    document.getElementById("timer"+i).innerHTML = timeformat(timer[i]);
  }
  setTimeout("tick()",1000);
}

function rein(o,set) {
  var n=o.id.replace("e","");
  var c=(set)?"#d6e8ff":"#ffffff";
  var c1=(set)?"#feeaea":"#ffffff";
  var c2=(set)?"#e1eed3":"#ffffff";
  if (document.getElementById("known"+n)) document.getElementById("known"+n).style.backgroundColor=c;
  o.style.backgroundColor=c;
  for (var i=1;i<=inc;i++) {
    if (i==n) continue;
    document.getElementById("e"+i).style.backgroundColor=(i<=n)?c1:c2;
  }
}
function attack(o,set) {
  var n=o.id.replace("e","");
  var c=(set)?"#feeaea":"#ffffff";
  
  if (!document.getElementById("known"+n)) return;
  o.style.backgroundColor=c;
  document.getElementById("known"+n).style.backgroundColor=c;
}

function known(o,set) {
  var n=o.id.replace("known","");
  var c=(set)?"#d6e8ff":"#ffffff";
  if (!document.getElementById("e"+n)) return;
  o.style.backgroundColor=c;
  document.getElementById("e"+n).style.backgroundColor=c;
}

window.onload = function() {
  setTimeout("tick()",0);
}
</script>
<script type="text/javascript" src="common.js"></script>
</head>
<body>

<?php
#################
# Default page  #
#################
if (empty($_GET['id'])) {
    echo '<table class="tbg tbg5" cellspacing="1" cellpadding="2" align="center">';
    # The Bulwark
    echo '<tr class="rbg"><td colspan="2">The Bulwark</td></tr>';
    echo '<tr><td colspan="2">Welcome to the Bulwark. This section of the Warroom is dedicated to defensive operations. ';
    echo 'Below you can see all current defensive ops, or create an op.</td></tr>';
    echo '<tr><td width="50%"  valign="top">';
    echo '<table class="tbg tbg2" cellspacing="1" cellpadding="2">';
    echo '<tr class="cbg1"><td colspan="5" class="b">Current operations</td></tr>';
    echo '<tr><td>Player</td><td>Village</td><td>Coords</td><td>First hit</td><td>Op link</td></tr>';
    $result = mysql_query('select u.id,u.karte,u.x,u.y,u.village,date_format(v.landtime,"%m/%d %T") t,w.player from (select * from s4_us_rally where player="Own troops" and type=4) u join (select min(landtime) landtime,id from s4_us_rally where type in (2,3) group by id) v on u.id=v.id left join (select distinct player,karte from s4_us_villages) w on u.karte=w.karte order by v.landtime');
    while ($r = mysql_fetch_assoc($result)) {
        echo '<tr><td>'.$r['player'].'</td><td><a href="http://s4.travian.us/karte.php?d='.$r['karte'].'">'.$r['village'].'</a></td>';
        echo '<td>'.$r['x'].','.$r['y'].'</td><td>'.$r['t'].'</td><td><a href="/?h=12&id='.$r['id'].'">'.$r['id'].'</a></td></tr>';
    }
    echo '</table></td><td>';
    echo '<table class="tbg tbg2" cellspacing="1" cellpadding="2">';
    echo '<tr class="cbg1"><td class="b">Create New Operation</td></tr>';
    echo '<tr><td>Copy and paste the <b>source code</b> of your rally point to the box below to create a new defense operation.';
    echo '<form action="rally.cgi" method="post">';
    echo '<textarea name="bin" cols="40", style="overflow:hidden" rows="3">';
    echo '</textarea><br>';

    echo '<input type="submit" value="go">';
    echo '</form></td></tr></table>';
    
    echo '<tr><td colspan="2"><br><table class="tbg" cellspacing="1" cellpadding="2" align="center">';
    echo '<tr><td colspan="2" class="cbg1"><b>How do I get the source code?</b></td></tr>';
    echo '<tr><td class="s7" valign="top"><b>Firefox:</b><br>First, go to your rally point<br><img src="img/source1.jpg"><br>';
    echo 'Then right-click on the window and select "View page source" in the dropdown menu that appears.<br><img src="img/source4.jpg"><br>';
    echo 'A window will pop up with lots of code. Copy <b>everything</b> in that window and paste it into the box above.<br><img src="img/source5.jpg"></td>';
    echo '<td class="s7" valign="top"><b>IE:</b><br>First, go to your rally point<br><img src="img/source1.jpg"><br>';
    echo 'Then right-click on the window and select "View source" in the dropdown menu that appears.<br><img src="img/source2.jpg"><br>';
    echo 'A window will pop up with lots of code. Copy <b>everything</b> in that window and paste it into the box above.<br><img src="img/source3.jpg"></td></tr>';
    echo '</table>';
    
    echo '</table></body></html>';
    exit;
}

#################
# Specific ID   #
#################
echo '<table class="tbg tbg5" cellspacing="1" cellpadding="2" align="center">';
echo '<tr class="rbg"><td colspan="2">The Bulwark</td></tr>';
$r = mysql_fetch_assoc(mysql_query('select karte,x,y,village from s4_us_rally where player="Own Troops" and type=4 and id="'.$_GET['id'].'"'));
$p = mysql_fetch_assoc(mysql_query('select player from s4_us_villages where karte="'.$r['karte'].'"'));
echo '<tr><td width="120">Player</td><td class="s7">'.$p['player'].'</td></tr>';
echo '<tr><td width="120">Village</td><td class="s7"><a href="/?h=8&k='.$r['karte'].'">'.$r['village'];
echo ' ['.$r['x'].','.$r['y'].']</a> ';
echo '[<a href="http://s4.travian.us/a2b.php?z='.karte2k($r['karte']).'"><img height="12" width="12" src="img/def1.gif"> Reinforce</a>] ';
echo '[<a href="http://s4.travian.us/build.php?z='.karte2k($r['karte']).'&gid=17"><img src="img/r4.gif"> Send resources</a>] </td></tr>';
echo '<tr><td colspan="2">';

# Attacks, Raids, Reins
$ti = 1;
$ui = 1;
$ri = 1;
foreach (array($attacks, $raids, $reins) as $sec) {
    if (!$sec) continue;
    if ($sec==$reins) $secname = "Reinforcements";
    if ($sec==$attacks) $secname = "Attacks";
    if ($sec==$raids) $secname = "Raids";
    echo '<br><table class="tbg" cellspacing="1" cellpadding="2" align="center">';
    echo '<tr class="cbg1"><td>Incoming '.$secname.'</td></tr></table>';
    echo '<table class="tbg sortable" cellspacing="1" cellpadding="2" id="'.$secname.'" align="center">';
    echo '<tr><td width="130" style="cursor: pointer">Player</td><td width="90" style="cursor: pointer">Village</td>';
    echo '<td style="cursor: pointer">Arrival</td><td style="cursor: pointer">Remaining</td>';
    echo '<td class="sorttable_nosort">Troops</td><td style="cursor: pointer">Dist</td>';
    echo '<td style="cursor: pointer">Speed</td></tr>';
    foreach ($sec as $r) {
        for ($i=0;$i<count($landorder);$i++) {
            $secfunc = ($sec==$reins) ? 'rein' : 'rein';
            if (same($landorder[$i],$r)) echo '<tr onmouseover="'.$secfunc.'(this,1)" onmouseout="'.$secfunc.'(this,0)" id="e'.($i+1).'">';
        }
        echo '<td class="s7"><a href="/?h=10&p='.$r['player'].'">'.$r['player'].'</a></td>';
        echo '<td><a href="http://s4.travian.us/karte.php?d='.$r['karte'].'">';
        echo '['.$r['x'].','.$r['y'].']</a></td><td>'.$r['t'].'</td>';
        echo '<td><span id="timer'.$ti++.'">'.$r['tf'].'</span></td><td>';
        if ($r['units']) echo 'Show<span class="link" id="units'.$ui++.'" onclick="editunits(\''.$r['count'].','.$r['karte'].','.$r['landtime'].','.$r['race'].'\')"> [edit]</span>';
        else echo 'Unknown<span class="link" id="units'.$ui++.'" onclick="editunits(\''.$r['count'].','.$r['karte'].','.$r['landtime'].','.$r['race'].'\')"> [edit]</span>';
        echo '</td><td>'.sprintf('%.02f',$r['distance']).'</td><td>'.sprintf('%.02f',$r['speed']).'</td></tr>';
    }
    echo '</table>';

}

# Registered reins
if ($known) {
    echo '<br><table class="tbg tbg5" cellspacing="1" cellpadding="2" align="center">';
    echo '<tr class="cbg1"><td>Known Reinforcements</td></tr></table>';
    echo '<table class="tbg tbg5" cellspacing="1" cellpadding="2" align="center">';

    $raceseen = -1;
    foreach ($known as $r) {
        if ($raceseen != $r['race']) {
            echo '<tr><td width="120">Player</td><td width="120">Arrival</td><td width="100">Remaining</td>';
            echo $raceheader[$const_racemap[$r['race']]].'<td><img src="img/r5.gif"></td></tr>';
            $raceseen = $r['race'];
        }
        if (count(unitct($r['units']))==10) $r['units'] .= ",0";
        for ($i=0;$i<count($landorder);$i++) {
            if (same($landorder[$i],$r)) echo '<tr onmouseover="known(this,1)" onmouseout="known(this,0)" id="known'.($i+1).'">';
        }
        echo '<td><a href="/?h=10&p='.$r['player'].'">'.$r['player'].'</a></td><td>'.$r['t'].'</td>';
        echo '<td><span id="timer'.$ti++.'">'.$r['tf'].'</span></td>';
        echo unitclr(unitct($r['units'])).'<td>'.food($r['units'],$const_racemap[$r['race']]).'</td>';
        echo '</tr>';
    }
    echo '</table>';
}


# Landed
echo '<br><table class="tbg tbg5" cellspacing="1" cellpadding="2" align="center">';
echo '<tr class="cbg1"><td colspan="13">Troops in the village</td></tr>';
$raceseen = -1;
foreach ($landed as $r) {
    if ($raceseen != $r['race']) {
        echo '<tr><td></td>'.$raceheader[$const_racemap[$r['race']]].'<td><img src="img/r5.gif"></td></tr>';
        $raceseen = $r['race'];
    }
    if (count(unitct($r['units']))==10) $r['units'] .= ",0";
    echo '<tr><td><a href="/?h=10&p='.$r['player'].'">'.$r['player'].'</a></td>';
    if (!$r['units']) $r['units']='?,?,?,?,?,?,?,?,?,?,?';
    echo unitclr(unitct($r['units'])).'<td>'.food($r['units'],$const_racemap[$r['race']]).'</td>';
    echo '</tr>';
}
echo '</table>';

?>
</td></tr></table>
</body></html>
