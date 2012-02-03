<?php
require 'auth_header.php';  # Require that user be authenticated
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="global.css" />
<script type="text/javascript">
// Autocomplete function for form
function autoC(field, select, property, forcematch) {
  var found = false;
  for (var i = 0; i < select.options.length; i++) {
    if (select.options[i][property].toUpperCase().indexOf(field.value.toUpperCase()) == 0) {
      found=true;
      break;
    }
  }
  if (found) { select.selectedIndex = i; }
  else { select.selectedIndex = -1; }
  if (field.createTextRange) {
    if (forcematch && !found) {
      field.value=field.value.substring(0,field.value.length-1); 
      return;
    }
    var cursorKeys ="8;46;37;38;39;40;33;34;35;36;45;";
    if (cursorKeys.indexOf(event.keyCode+";") == -1) {
      var r1 = field.createTextRange();
      var oldValue = r1.text;
      var newValue = found ? select.options[i][property] : oldValue;
      if (newValue != field.value) {
        field.value = newValue;
        var rNew = field.createTextRange();
        rNew.moveStart('character', oldValue.length) ;
        rNew.select();
      }
    }
  }
}
// Clear search fields
function clear(obj) {
    document.getElementById("pl").value="";
    alert( "work?");  
}

// Filter submission hook
function filter() {
  var f = document.getElementById("filterform");
  f.ip.parentNode.removeChild(f.ip);
  if (f.p.value=="- Show All -") f.p.parentNode.removeChild(f.p);
  if (f.d.value=="- Show All -") f.d.parentNode.removeChild(f.d);
  f.ia.parentNode.removeChild(f.ia);
  if (f.aid.value=="- Show All -") f.aid.parentNode.removeChild(f.aid);
  if (!f.x.value) {
    f.x.parentNode.removeChild(f.x);
    f.y.parentNode.removeChild(f.y);
    f.r.parentNode.removeChild(f.r);
  }
  if (f.all && f.all.checked) {
    f.all.parentNode.removeChild(f.all);
    f.scouts.parentNode.removeChild(f.scouts);
    f.cats.parentNode.removeChild(f.cats);
    f.defs.parentNode.removeChild(f.defs);
    f.skirmishes.parentNode.removeChild(f.skirmishes);
  }
  document.getElementById("filterform").submit()
}

</script>
<script type="text/javascript" src="common.js"></script>
</head>
<body>
<?php
include 'common.php';   # Load common functions

# Connect
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax');
if (!$conn) die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn);

echo '<form action="/" id="filterform" onsubmit="filter()"><table class="tbg" cellspacing="1" cellpadding="2" align="center">';
# Alliance reports
echo '<tr class="rbg"><td colspan="4">Alliance reports</td></tr>';

#################
# Filters       #
#################
$gets = array('p','aid','x','y','r','d');
foreach ($gets as $k) {
    if (isset($_GET[$k]) && ($_GET[$k]=="" || $_GET[$k]=='- Show All -')) unset($_GET[$k]);
}

# If bounds specified, get and construct conditions
$xp = (isset($_GET['x'])) ? $_GET['x']*1 : '';
$yp = (isset($_GET['y'])) ? $_GET['y']*1 : '';
$range = (isset($_GET['r'])) ? $_GET['r']*1 : 10;
$x1 = add($xp,-$range);
$x2 = add($xp,$range);
$y1 = add($yp,-$range);
$y2 = add($yp,$range);
if ($x1>$x2 && $y1<$y2) $bound = '('.$x1.'<=x or x<='.$x2.') and '.$y1.'<=y and y<='.$y2;
elseif ($y1>$y2 and $x1<$x2) $bound = $x1.'<=x and x<='.$x2.' and ('.$y1.'<=y or y<='.$y2.')';
elseif ($x1>$x2 and $y1>$y2) $bound = '('.$x1.'<=x or x<='.$x2.') and ('.$y1.'<=y or y<='.$y2.')';
else $bound = $x1.'<=x and x<='.$x2.' and '.$y1.'<=y and y<='.$y2;

# Construct filter for navigation
$nav = '';
$params = array('p','d','aid','x','y','r');
foreach ($params as $k) $nav .= (isset($_GET[$k])) ? '&'.$k.'='.$_GET[$k] : '';
foreach (array('scouts','cats','defs','skirmishes','spy') as $t)
    if (isset($_GET[$t])) $nav .= '&'.$t;


# Player filter
echo '<input type="hidden" name="h" value="1"><tr><td class="s7" width="90">Player:</td>';
echo '<td class="s7" width="240"><input class="fm fm90" type="text" id="ip" name="ip" value="" onkeyup="autoC(this,this.form.p,\'value\',false)">';
echo ' <select class="fm" name="p" onChange="this.form.ip.value=this.options[this.selectedIndex].value">'."\n";
$result = mysql_query('select distinct player from s4_us_villages where aid in ('.implode(',',$const_wings).') order by player');
while ($r = mysql_fetch_assoc($result)) $players[] = $r['player'];
echo '<script type="text/javascript">';
echo 'var players = new Array("'.implode('","',$players).'");';
echo 'document.write("<option>- Show All -</option>");';
echo 'for (var i=0;i<players.length;i++) {';
echo '  document.write("<option");';
if (isset($_GET['p']))
    echo 'if (players[i] == "'.$_GET['p'].'") document.write(" selected=\'selected\'");';
echo '  document.write(">"+players[i]+"</option>")}';
echo '</script>';
echo '</select></td>';

# Date filter
echo '<td class="s7" width="90">Date: </td><td class="s7"><select class="fm" name="d">';
$result = mysql_query('select distinct date_format(time,"%y/%m/%d") day from s4_us_reports order by time desc');
while ($r = mysql_fetch_assoc($result)) $days[] = $r['day'];
echo '<script type="text/javascript">';
echo 'var days = new Array("'.implode('","',$days).'");';
echo 'document.write("<option>- Show All -</option>");';
echo 'for (var i=0;i<days.length;i++) {';
echo '  document.write("<option");';
if (isset($_GET['d']))
    echo 'if (days[i] == "'.$_GET['d'].'") document.write(" selected=\'selected\'");';
echo '  document.write(">"+days[i]+"</option>")}';
echo '</script>';
echo '</select>';
echo '</td>';
echo '</tr>';

# Alliance filter
echo '<tr><td class="s7">Alliance:</td>';
echo '<td class="s7"><input class="fm fm90" type="text" name="ia" value="" onkeyup="autoC(this,this.form.aid,\'text\',false)">';
echo ' <select class="fm" name="aid" onChange="this.form.ia.value=this.options[this.selectedIndex].text">'."\n";
$result = mysql_query('select distinct aid,alliance from s4_us_villages where aid!=0 order by alliance');
while ($r = mysql_fetch_assoc($result)) {
    $aids[] = $r['aid'];
    $alliances[] = htmlentities($r['alliance'], ENT_COMPAT, 'UTF-8');
}
echo '<script type="text/javascript">';
echo 'var aids = new Array('.implode(',',$aids).');';
echo 'var alliances = new Array("'.implode('","',$alliances).'");';
echo 'document.write("<option>- Show All -</option>");';
echo 'for (var i=0;i<aids.length;i++) {';
echo '  document.write("<option value=\'"+aids[i]+"\' ");';
if (isset($_GET['aid']))
    echo 'if (aids[i] == '.$_GET['aid'].') document.write("selected=\'selected\'");';
echo '  document.write(">"+alliances[i]+"</option>")}';
echo '</script>';
echo '</select></td>';

# Geofilter
echo '<td class="s7">Geography:</td>';
echo '<td class="s7"><b>x</b> <input class="fm fm25" name="x" value="'.$xp.'" size="2" maxlength="4" /> ';
echo '<b>y</b> <input class="fm fm25" name="y" value="'.$yp.'" size="2" maxlength="4" /> ';
echo 'Range <input class="fm fm40" type="text" name="r" value="'.$range.'"></td>';
echo '</td></tr>';

# Type filter
echo '<tr><td class="s7" valign="top">Report type: </td>';
echo '<td class="s7" colspan="2" width="370">All <input type="checkbox" name="all" onclick="filter()"> ';
echo 'Scouts <input type="checkbox" name="scouts" value="" onclick="filter()"';
echo (isset($_GET['scouts'])) ? ' checked> ' : '> ';
echo 'Cats <input type="checkbox" name="cats" value="" onclick="filter()"';
echo (isset($_GET['cats'])) ? ' checked> ' : '> ';
echo 'Defends <input type="checkbox" name="defs" value="" onclick="filter()"';
echo (isset($_GET['defs'])) ? ' checked> ' : '> ';
echo 'Skirmishes <input type="checkbox" name="skirmishes" value="" onclick="filter()"';
echo (isset($_GET['skirmishes'])) ? ' checked> ' : '> ';
echo 'Chiefs <input type="checkbox" name="chiefs" value="" onclick="filter()"';
echo (isset($_GET['chiefs'])) ? ' checked> ' : '> ';
echo '</td>';

# Controls
echo '<td class="r7" valign="bottom"><input type="submit" style="position:absolute; top:-1000px;">';
echo '<img src="img/ok1.gif" style="cursor: pointer" onmousedown="this.src=\'img/ok2.gif\'" onmouseout="this.src=\'img/ok1.gif\'" onmouseup="this.src=\'img/ok1.gif\'; blur(); filter()">';
echo '</td></tr>';

#################
# Report list   #
#################
echo '<tr><td colspan="5"><br>';
echo '<table class="tbg" cellspacing="1" cellpadding="2" align="center">';
# Column headers
echo '<tr class="cbg1"><td width="18">&nbsp;</td><td>Players</td><td>Resources</td><td>Efficiency</td><td>Losses</td><td>Report</td><td width="120">Time</td></tr>';
$n = 35;    # number of reports per page
$page = (isset($_GET['page'])) ? $_GET['page']*$n : 0;

$seen = array();
$sql = 'select id, date_format(time,"%m/%d %T") t, attacker, defender, attplayer, defplayer, attkarte, defkarte, attunits, attcasualties, attrace, attaid, defaid, total, efficiency, type from s4_us_reports';
# Get filter conditions
if (isset($_GET['p'])) $conds[] = (isset($_GET['defs']))? 'defplayer="'.$_GET['p'].'"' : '(attplayer="'.$_GET['p'].'" or defplayer="'.$_GET['p'].'")';
if (isset($_GET['d'])) $conds[] = 'date(time)="'.$_GET['d'].'"';
if (isset($_GET['aid'])) $conds[] = 'defkarte in (select karte from s4_us_villages where aid='.$_GET['aid'].')';
if (isset($_GET['x']) && isset($_GET['y']) && isset($_GET['r'])) $conds[] = 'defkarte in (select karte from s4_us_villages where '.$bound.')';
if (isset($_GET['scouts'])) $conds[] = 'type=4';
elseif (isset($_GET['cats'])) $conds[] = 'type in (8,18)';
if (isset($_GET['defs'])) $conds[] = 'defkarte in (select karte from s4_us_villages where aid in (401,450,1230))';
if (isset($_GET['skirmishes'])) $conds[] = '(type in (1,8,10,18) and (0<attcasualtiesfood+defcasualtiesfood+rein1casualtiesfood+rein2casualtiesfood))';
if (isset($_GET['chiefs'])) $conds[] = 'type in (10,18)';
if (isset($_GET['spy'])) $conds[] = '(attaid in ('.implode(',',$const_spy).') or defaid in ('.implode(',',$const_spy).')) and attaid not in ('.implode(',',$const_wings).') and defaid not in ('.implode(',',$const_wings).')';
$conds[] = 'id>0';$sql .= (count($conds) > 0) ? ' where '.implode(' and ',$conds) : '';
# Order and page limits
$sql .= ' order by id desc limit '.$page.','.$n;
$result = mysql_query($sql);

while ($r = mysql_fetch_array($result)) {
    # Symbol
    if ($r['type']==4) $symbol = '14';                          # Is a scout
    elseif ($r['type']==8 || $r['type']==18) $symbol = '18';    # Cat
    elseif ($r['type']==3) $symbol = 'def2';                    # Fake
    else $symbol = 'att_all';
    $symbol .= (in_array($r['attaid'],$const_wings)||spyreport($r['attaid'],$r['defaid'])) ? '.gif' : 'r.gif';
    
    echo '<tr><td><img src="img/'.$symbol.'" border="0"></td>';
    echo '<td class="s7';
    echo (spyreport($r['attaid'],$r['defaid'])) ? ' spy">' : '">';
    $att = explode(" from the village",$r['attacker']);     # Extract attacker and defender names
    $def = explode(" from the village",$r['defender']);
    # Players -> Target village
    echo '<a href="/?h=7&p='.$r['attplayer'].'">'.$r['attplayer'].'</a> -> ';
    echo '<a href="/?h=8&k='.$r["defkarte"].'">'.where($r['defender']).'</a></td>';
    # Bounty, Efficiency
    if ($r["total"] != "") echo '<td>'.$r["total"].'</td><td>'.$r["efficiency"].'&#37;</td>';
    else echo '<td> - </td><td> - </td>';
    # Losses
    echo '<td>'.food($r["attcasualties"],$r["attrace"]).'</td>';
    # Report
    echo '<td><a href="/?h=5&i='.$r['id'].'">'.$r['id'].'</a></td>';
    # Time (NOTE: using 't' because preformatted
    echo '<td>'.str_replace("-","/",$r["t"]).'</td>';
    echo "</tr>\n";
}
echo '</tr><tr class="rbg"><td class="s7" colspan="5">Page '.(($page/$n)+1).'</td>';
echo '<td></td><td class="r7">';
if ($page==0) {
    echo '<span class="c"><b>&laquo;</b></span>';
} else {
    echo '<a href="?h=1&page='.($page/$n-1).$nav.'">&laquo;</a>';
}
echo '<a href="?h=1&page='.($page/$n+1).$nav.'">&raquo;</a></td>';
echo '</tr>';

echo '</table></form>';
echo '</table></body></html>';
?>
