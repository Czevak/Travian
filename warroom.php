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
  if (f.all && f.all.checked) {
    f.all.parentNode.removeChild(f.all);
    f.scouts.parentNode.removeChild(f.scouts);
    f.cats.parentNode.removeChild(f.cats);
    f.defs.parentNode.removeChild(f.defs);
    f.skirmishes.parentNode.removeChild(f.skirmishes);
  }
  document.getElementById("filterform").submit()
}
function alliance() {
  o = document.getElementById("allianceform");
  if (o.aid.value!="0") o.submit();
}
</script>
<script type="text/javascript" src="common.js"></script>
</head>
<body>
<?php
include 'common.php';   # Load common functions
include 'connect.php';  # Connect

# Warroom
echo '<table class="tbg" cellspacing="1" cellpadding="2" align="center">';
echo '<tr class="rbg"><td colspan="4">Warroom</td></tr>';
echo '<tr><td colspan="2">Welcome to the warroom. This page has not been fully completed mostly due to me being away most of the weekend but I\'ve put up the Alliance and Player Analysis pages for now.<br><br>';
echo 'Select an alliance below to be taken to the alliance summary page, then click on a player for detailed analysis.</td></tr>';
echo '<form action="/" id="allianceform"><input type="hidden" name="h" value="9">';
echo '<tr><td width="20%">Select alliance:</td><td class="s7"><select class="fm" onchange="alliance()" name="aid"><option value="0">- Select -</option>';
$result = mysql_query('select distinct alliance,aid from s4_us_villages where aid in ('.implode(',',$const_hostiles).') order by alliance');
while ($r = mysql_fetch_assoc($result)) {
    echo '<option value="'.$r['aid'].'">'.$r['alliance'].'</option>';
}
echo '</select></td></tr></form>';


echo '<tr><td colspan="2">';
echo '<br><table class="tbg" cellspacing="1" cellpadding="2" align="center">';
# Reports
echo '<tr class="rbg"><td colspan="4">Reports</td></tr>';

#################
# Filters       #
#################
$gets = array('p','aid','x','y','r','d');
foreach ($gets as $k) {
    if (isset($_GET[$k]) && (!$_GET[$k] || $_GET[$k]=='- Show All -')) unset($_GET[$k]);
}

# Construct filter for navigation
$nav = '';
$params = array('p','d','aid','x','y','r');
foreach ($params as $k) $nav .= (isset($_GET[$k])) ? '&'.$k.'='.$_GET[$k] : '';
if (isset($_GET['scouts'])) $nav .= '&scouts';
if (isset($_GET['cats'])) $nav .= '&cats';
if (isset($_GET['defs'])) $nav .= '&defs';
if (isset($_GET['skirmishes'])) $nav .= '&skirmishes';

# Player filter
echo '<form action="/" onsubmit="filter()" id="filterform"><input type="hidden" name="h" value="11"><tr><td class="s7" width="90">Player:</td>';
echo '<td class="s7" width="240"><input class="fm fm90" type="text" name="ip" value="" onkeyup="autoC(this,this.form.p,\'value\',false)">';
echo ' <select class="fm" name="p" onChange="this.form.ip.value=this.options[this.selectedIndex].value">'."\n";
$result = mysql_query("select distinct player from s4_us_villages where aid in (401,450) order by player");
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

# Type filter
echo '<tr><td class="s7">Report type: </td>';
echo '<td class="s7" colspan="2" width="370">All <input type="checkbox" name="all" onclick="filter()"> ';
echo 'Scouts <input type="checkbox" name="scouts" value="" onclick="filter()"';
echo (isset($_GET['scouts'])) ? ' checked> ' : '> ';
echo 'Cats <input type="checkbox" name="cats" value="" onclick="filter()"';
echo (isset($_GET['cats'])) ? ' checked> ' : '> ';
echo 'Defends <input type="checkbox" name="defs" value="" onclick="filter()"';
echo (isset($_GET['defs'])) ? ' checked> ' : '> ';
echo 'Skirmishes <input type="checkbox" name="skirmishes" value="" onclick="filter()"';
echo (isset($_GET['skirmishes'])) ? ' checked> ' : '> ';
echo '</td>';

# Controls
echo '<td class="r7"><input type="submit" style="position:absolute; top:-1000px">';
echo '<img style="cursor:pointer" border="0" src="img/ok1.gif" onmousedown="this.src=\'img/ok2.gif\'" onmouseout="this.src=\'img/ok1.gif\'" onmouseup="this.src=\'img/ok1.gif\'; blur(); filter()">';
echo '</td></tr></form>';
echo '</table>';
echo '<br>';

#################
# Report list   #
#################
echo '<table class="tbg" cellspacing="1" cellpadding="2" align="center">';
# Column headers
echo '<tr class="cbg1"><td width="18">&nbsp;</td><td>Players</td><td>Resources</td><td>Efficiency</td><td>Losses</td><td>Report</td><td width="120">Time</td></tr>';
$n = 35;    # number of reports per page
$page = (isset($_GET['page'])) ? $_GET['page']*$n : 0;

$seen = array();
$conditions = array();
$sql = 'select id, date_format(time,"%m/%d %T") t, attacker, defender, attplayer, defplayer, attkarte, defkarte, attunits, attcasualties, attrace, attaid, defaid, total, efficiency from s4_us_reports';
# Get filter conditions
if (isset($_GET['p'])) $conds[] = (isset($_GET['defs']))? 'defplayer="'.$_GET['p'].'"' : '(attplayer="'.$_GET['p'].'" or defplayer="'.$_GET['p'].'")';
if (isset($_GET['d'])) $conds[] = 'date(time)="'.$_GET['d'].'"';
if (isset($_GET['scouts'])) $conds[] = 'type=4';
elseif (isset($_GET['cats'])) $conds[] = 'type in (8,18)';
if (isset($_GET['defs'])) $conds[] = 'defkarte in (select karte from s4_us_villages where aid in (401,450,1230))';
if (isset($_GET['skirmishes'])) $conds[] = '(type in (1,8,10,18) and (0<attcasualtiesfood+defcasualtiesfood+rein1casualtiesfood+rein2casualtiesfood))';
$conds[] = '(defkarte in (select karte from s4_us_villages where aid in ('.implode(',',$const_hostiles).')) or attkarte in (select karte from s4_us_villages where aid in ('.implode(',',$const_hostiles).')))';
$conds[] = 'id>0';
$sql .= (count($conds) > 0) ? ' where '.implode(' and ',$conds) : '';
# Order and page limits
$sql .= ' order by id desc limit '.$page.','.$n;
$result = mysql_query($sql);

while ($r = mysql_fetch_array($result)) {
    if (array_key_exists($r["attkarte"],$seen)) {   # Check if we've seen the player already
        $attalliance = $seen[$r["attkarte"]];
    } else {                                        # New player, get their alliance
        $attalliance = explode("&",$r['attkarte']);
        $ra = mysql_query('select aid, alliance from s4_us_villages_snap where karte='.$attalliance[0].' order by day desc limit 1');
        $attalliance = ($ra) ? mysql_fetch_assoc($ra) : 0;
        if (!$attalliance) $attalliance = "";       # Set to blank if none, otherwise get alliance
        else $attalliance = $attalliance["aid"];
        $seen[$r["attkarte"]] = $attalliance;       # Add to seen
    }
    # Symbol
    if (check_scouts($r['attunits'],$r['attrace'])) $symbol = '14';  # Is a scout
    elseif (check_cats($r['attunits'])) $symbol = '18';
    elseif (check_fake($r['attunits'],$r['attrace'])) $symbol = 'def2';
    else $symbol = 'att_all';
    $symbol .= (in_array($r['attaid'],$const_wings)||spyreport($r['attaid'],$r['defaid'])) ? '.gif' : 'r.gif';

    echo '<tr><td><img src="img/'.$symbol.'" border="0"></td>';
    echo '<td class="s7';
    echo (spyreport($r['attaid'],$r['defaid'])) ? ' spy">' : '">';
    $att = explode(" from the village",$r['attacker']);     # Extract attacker and defender names
    $def = explode(" from the village",$r['defender']);
    # Players -> Target village
    echo '<a href="/?h=';
    echo (spyreport($r['attaid'],$r['defaid'])||in_array($r['defaid'],$const_wings)) ? 10 : 7;
    echo '&p='.$r['attplayer'].'">'.$r['attplayer'].'</a> -> ';
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
    echo '<a href="/?h=11&page='.($page/$n-1).$nav.'">&laquo;</a>';
}
echo '<a href="/?h=11&page='.($page/$n+1).$nav.'">&raquo;</a></td>';
echo '</tr>';

echo '</table>';

?>
</table>
</body>
</html>