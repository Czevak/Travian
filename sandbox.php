<?php
require 'auth_header.php';  # Require that user be authenticated
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="global.css" />

<style type="text/css">
<?php
$position = "absolute";

# Anchor points for all location ids
# For village view, 39 (rally point) first, then 19 through 38, 40
# 0 is village center on field view.
$coords = Array(Array(144,101,165,224,46,138,203,262,31,83,214,42,93,160,239,87,140,190,53,136,196,270,327,14,97,182,337,2,129,92,342,22,167,290,95,222,80,199,270,312,49,0), Array(131,33,32,46,63,74,94,86,117,110,142,171,164,184,199,217,231,232,91,66,56,69,117,129,139,119,156,199,164,189,216,238,232,251,273,284,306,316,158,338,338,144));
$wall[] = Array(Array(0,34,93,181,252,305,358,402,421,421,378,280,175,78,0), Array(0,-56,-105,-129,-129,-113,-81,-38,7,-51,-97,-144,-144,-116,-52));
$wall[] = Array(Array(0,35,65,94,109,109,84,48), Array(0,0,-18,-50,-76,-116,-63,-27));
$wall[] = Array(Array(0,-49,-49,-16,39), Array(0,-64,-98,-52,0));
$rally = Array(Array(0,33,46,48,34,18,-7,-20), Array(0,-23,-3,20,53,69,80,57));
# Everything else
$area = Array(Array(0,0,75,75,38), Array(0,-54,-54,0,21));

# Style sheet
echo "#f1,#f2,#f3,#f4,#f5,#f6,#f7,#f8,#f9,#f10 {position:".$position."; width:300px; height:264px; left:15px; top:75px; background-repeat:no-repeat; z-index:1;}\n";
for ($i=1; $i<=10; $i++) echo '#f'.$i.' {background-image:url(img/f'.$i.".jpg);}\n";
echo "#resfeld {position:absolute; width:300px; height:264px; left:15px; top:75px; z-index:3;}\n";

# Resource fields
echo ".rf1,.rf2,.rf3,.rf4,.rf5,.rf6,.rf7,.rf8,.rf9,.rf10,.rf11,.rf12,.rf13,.rf14,.rf15,.rf16,.rf17,.rf18 {position:".$position."; z-index:2;}\n";
$rfx = Array(93,156,216,38,130,195,253,23,74,205,260,33,84,151,230,79,132,182);
$rfy = Array(27,26,41,59,67,87,81,111,104,136,139,165,158,178,192,211,223,227);
for ($i=1; $i<=18; $i++) echo '.rf'.$i.' {left: '.$rfx[$i-1].'px; top:'.$rfy[$i-1]."px;}\n";

# 
echo ".d2_x {position:".$position."; width:540px; height:448px; z-index:1; left:5px; top:30px;}\n";
echo ".d2_0 {background-image:url(img/bg0.jpg);}\n";
echo ".d2_1 {background-image:url(img/bg1.jpg);}\n";
echo ".d2_11 {background-image:url(img/bg11.jpg);}\n";
echo ".d2_12 {background-image:url(img/bg12.jpg);}\n";
echo ".d2_2 {background-image:url(img/bg2.jpg);}\n";
echo ".d2_3 {background-image:url(img/bg3.jpg);}\n";

# Set positions of village building locations
$dposx = Array(121,204,264,338,394,86,167,253,401,72,198,161,408,90,233,360,164,292,150,266);
$dposy = Array(82,57,47,62,111,121,128,111,152,191,156,182,210,230,226,243,266,277,297,306);
$dposz = Array(6,9,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,);
for ($i=0; $i<=19; $i++) echo '.d'.($i+1).'{position:'.$position.'; z-index:'.$dposz[$i].'; left:'.($dposx[$i]+200).'px; top:'.$dposy[$i]."px;}\n";
# Rally point is special case
echo ".dx1 {position:".$position."; z-index:5; left:318px; top:232px;}\n";

#
echo ".dmap {position:".$position."; width:422px; height:339px; z-index:30; left:68px; top:70px;}\n";

?>
</style>

<script type="text/javascript">
function init() {
    
}
</script>
</head>
<body onload="init()">

<?php
include 'common.php';   # Load common functions



# Connect
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax') or die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn) or die('Database error: '.mysql_error());

?>

<table class="tbg" cellspacing="1" cellpadding="2" align="center">
<tr class="cbg1"><td colspan="9" class="b">Test</td></tr>
<tr><td>

<div class="dname"><h1>village name</h1></div>
<img class="d1" src="img/iso.gif" width="75" height="100">
<img class="d2" src="img/g5.gif">
<img class="d3" src="img/g6.gif">
<img class="d4" src="img/g7.gif">
<img class="d5" src="img/g8.gif">
<img class="d6" src="img/g11.gif">
<img class="d7" src="img/g18.gif">
<img class="d8" src="img/g15.gif">
<img class="d9" src="img/iso.gif" width="75" height="100">
<img class="d10" src="img/g10.gif">
<img class="d11" src="img/g12.gif">
<img class="d12" src="img/g17.gif">
<img class="d13" src="img/g25.gif">
<img class="d14" src="img/iso.gif" width="75" height="100">
<img class="d15" src="img/iso.gif" width="75" height="100">
<img class="d16" src="img/g22.gif">
<img class="d17" src="img/g24.gif">
<img class="d18" src="img/g19.gif">
<img class="d19" src="img/g37.gif">
<img class="d20" src="img/iso.gif" width="75" height="100">
<img class="dx1" src="img/g16.gif">
<div class="d2_x d2_0" style="position:absolute; left:200px">
<img usemap="#map2" src="img/x.gif" width="540" height="448" border="0">
</div>
<map name="map1">
<?php
for ($i=19; $i<=42; $i++) {
    echo '<area href="'.($i+1).'" title="Location '.($i+1).'" coords="';
    $out = Array();
    for ($a=0; $a<5; $a++) {
        $out[] = $area[0][$a]+$coords[0][$i];
        $out[] = $area[1][$a]+$coords[1][$i];
    }
    echo implode(',',$out).'" shape="poly">'."\n";
}

?>

</map>

<img class="dmap" usemap="#map1" src="img/x.gif" style="position:absolute; left:260px" width="422" height="339" border="0">

</td></tr>
</table>
</body>
</html>
