<?php
require 'auth_header.php';  # Require that user be authenticated
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="global.css" />
<script type="text/javascript">
// Filter submission hook
function filter() {
  var f = document.getElementById("filterform");
  if (!f.x.value) {
    f.x.parentNode.removeChild(f.x);
    f.y.parentNode.removeChild(f.y);
    f.r.parentNode.removeChild(f.r);
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
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax') or die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn);

if (!empty($_GET['x'])) {
    $bound = $_GET['r'];
    $x1 = add($_GET['x'],-$bound);
    $x2 = add($_GET['x'],$bound);
    $y1 = add($_GET['y'],-$bound);
    $y2 = add($_GET['y'],$bound);

    # Construct sql boundary conditions
    if ($x1>$x2 && $y1<$y2) $bcond = '('.$x1.'<=x or x<='.$x2.') and '.$y1.'<=y and y<='.$y2;
    elseif ($y1>$y2 and $x1<$x2) $bcond = $x1.'<=x and x<='.$x2.' and ('.$y1.'<=y or y<='.$y2.')';
    elseif ($x1>$x2 and $y1>$y2) $bcond = '('.$x1.'<=x or x<='.$x2.') and ('.$y1.'<=y or y<='.$y2.')';
    else $bcond = $x1.'<=x and x<='.$x2.' and '.$y1.'<=y and y<='.$y2;
    $conds[] = '('.$bcond.')';   # Add to sql conditions
}

if (isset($_GET['ne'])) $quads[] = '(x>=0 and y>=0)';
else $_GET['ne'] = 1;
if (isset($_GET['se'])) $quads[] = '(x>=0 and y<=0)';
if (isset($_GET['sw'])) $quads[] = '(x<=0 and y<=0)';
if (isset($_GET['nw'])) $quads[] = '(x<=0 and y>=0)';

if (empty($_GET['grade'])) $_GET['grade'] = 2;

if (count($quads)==0) $quads[] = '(x>=0 and y>=0)';   # NE by default if nothing specified

if (!isset($_GET['15c'])&&!isset($_GET['9c'])) {
    $_GET['15c'] = 1;
    $_GET['9c'] = 1;
}

for ($i=0;$i<=9;$i++) $gradeconds[] = 'type="f'.$i.'"'; # grade conditions

$sql = 'select * from s4_us_map';
if (!empty($_GET['15c'])&&!empty($_GET['9c'])) $conds[] = 'info in ("15c","9c")';
elseif (!empty($_GET['15c'])) $conds[] = 'info="15c"';
elseif (!empty($_GET['9c'])) $conds[] = 'info="9c"';
if (!isset($_GET['all'])) $conds[] = 'karte not in (select karte from s4_us_villages)';
if (isset($_GET['grade'])) $conds[] = '('.implode(' or ',array_slice($gradeconds,0,$_GET['grade']+1)).')';
if (count($quads)!=4) $conds[] = '('.implode(' or ',$quads).')';

$sql .= (count($conds) > 0) ? ' where '.implode(' and ',$conds) : '';
$sql .= ' order by info,type,x,y';

$result = mysql_query($sql);
# crop [15c/9c] : [0-9] : [x,y] : karte
while ($r=mysql_fetch_assoc($result)) $crop[$r['info']][substr($r['type'],1)][$r['x'].','.$r['y']] = $r['karte'];

$ranks[] = 'Rank 1<br><img src="img/w12s.gif"><img src="img/w12s.gif"><img src="img/w12s.gif">';
$ranks[] = 'Rank 2<br><img src="img/w12s.gif"><img src="img/w12s.gif"><img src="img/w10s.gif">';
$ranks[] = 'Rank 3<br><img src="img/w12s.gif"><img src="img/w12s.gif">';
$ranks[] = 'Rank 4<br><img src="img/w12s.gif"><img src="img/w10s.gif"><img src="img/w10s.gif">';
$ranks[] = 'Rank 5<br><img src="img/w12s.gif"><img src="img/w10s.gif">';
$ranks[] = 'Rank 6<br><img src="img/w10s.gif"><img src="img/w10s.gif"><img src="img/w10s.gif">';
$ranks[] = 'Rank 7<br><img src="img/w12s.gif">';
$ranks[] = 'Rank 8<br><img src="img/w10s.gif"><img src="img/w10s.gif">';
$ranks[] = 'Rank 9<br><img src="img/w10s.gif">';
$ranks[] = 'Rank 10';

echo '<table class="tbg tbg2" cellspacing="1" cellpadding="2" align="center">';
# HQ Cropfinder
echo '<tr class="rbg"><td colspan="12">HQ Cropfinder</td></tr>';

#################
# Filters       #
#################

# Type
echo '<form action="/" onsubmit="filter()" id="filterform"><input type="submit" style="position:absolute; top:-1000px">';
echo '<input type="hidden" name="h" value="4"><tr><td width="150px"><b>Type:</b> ';
echo '15c <input type="checkbox" name="15c" value="1"';
echo (!empty($_GET['15c'])) ? ' checked> ' : '> ';
echo '9c <input type="checkbox" name="9c" value="1"';
echo (!empty($_GET['9c'])) ? ' checked>' : '>';
echo '</td>';

# Grade
echo '<td width="150px"><b>Minimum Grade:</b><br><select class="fm" name="grade">';
$gradedesc = array("150% - 3x50","125% - 2x50 + 25","100% - 2x50","100% - 50 + 2x25","75% - 50+25","75% - 3x25","50% - 50","50% - 2x25","25%","none");
for ($i=0;$i<=9;$i++) {
    echo '<option value="'.$i.'"';
    if (isset($_GET['grade']) && $_GET['grade']==$i) echo ' selected';
    echo '>'.($i+1).' - '.$gradedesc[$i].'</option>';
}
echo '</select></td>';

# Quadrant
echo '<td width="200px"><b>Quadrant:</b><br>';
echo 'NE <input type="checkbox" name="ne" value="1"';
echo (!empty($_GET['ne'])) ? ' checked> ' : '> ';
echo 'SE <input type="checkbox" name="se" value="1"';
echo (!empty($_GET['se'])) ? ' checked>' : '>';
echo 'SW <input type="checkbox" name="sw" value="1"';
echo (!empty($_GET['sw'])) ? ' checked> ' : '> ';
echo 'NW <input type="checkbox" name="nw" value="1"';
echo (!empty($_GET['nw'])) ? ' checked>' : '>';
echo '</td>';

# Geofilter
echo '<td class="s7" width="190px"><b>Geo:</b> ';
echo 'x <input class="fm fm25" name="x" value="'.$_GET['x'].'" size="2" maxlength="4" /> ';
echo 'y <input class="fm fm25" name="y" value="'.$_GET['y'].'" size="2" maxlength="4" /> ';
echo 'Range <input class="fm fm20" type="text" name="r" value="'.$_GET['r'].'"></td>';
echo '</td>';

# All
echo '<td><b>Include occupied:</b> ';
echo '<input type="checkbox" name="all" value="1"';
echo (!empty($_GET['all'])) ? ' checked></td>' : '></td>';

# Controls
echo '<td class="r7">';
echo '<input type="image" border="0" src="img/ok1.gif" onmousedown="this.src=\'img/ok2.gif\'" onmouseout="this.src=\'img/ok1.gif\'" onmouseup="this.src=\'img/ok1.gif\'; filter()">';
echo '</td></tr></form>';


#################
# Cropper list  #
#################

echo '<tr><td colspan=12><br>';
# Check for results
if (count($crop)==0) {
    echo 'No selection</td></tr></table></body></html>';
    exit;
}
foreach ($crop as $wheat=>$croppers) {
    echo '<table class="tbg tbg2" cellspacing="1" cellpadding="2" align="center">';
    echo '<tr class="cbg1"><td colspan="2" class="c2 b">'.$wheat.' Croppers</td></tr>';
    foreach ($crop[$wheat] as $g=>$grades){
        echo '<tr><td class="s7">'.$ranks[$g].'</td><td class="s7">';
        foreach ($crop[$wheat][$g] as $coords=>$karte) {
            $c = explode(',',$coords);
            echo '<a href="/?h=3&cx='.$c[0].'&cy='.$c[1].'&sx='.$c[0].'&sy='.$c[1].'">['.$coords.']</a> ';
            
        }
        echo '</td></tr>';
    }
    echo '</table><br>';
}
echo '</td></tr>';
echo '</table>';
?>
</body>
</html>
