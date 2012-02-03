<?php

require 'auth_header.php';  # Require that user be authenticated

?>

<html>

<head>

<meta http-equiv="content-type" content="text/html; charset=UTF-8">

<link rel="stylesheet" type="text/css" href="global.css" />

<script type="text/javascript">

<?php

include 'common.php';   # Load common functions



# Alliance declarations. Change these in javascript and cartajax

$wings = array(401,450);

$naps = array(108,194,224,225,531);



$cenx = (isset($_GET['x'])) ? $_GET['x'] : 79;

$ceny = (isset($_GET['y'])) ? $_GET['y'] : 8;

$bound = 30;

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

$result = mysql_query('select x, y, info from s4_us_map where '.$cond.' order by x,y desc');

while ($r = mysql_fetch_array($result)) {

	$map[$r['x']][$r['y']] = $r['info'];

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

echo "var anum=new Array();\n";

foreach ($alliances as $aid => $name) {

    echo 'anum['.$aid.']="'.$name.'";';

}

echo 'anum[0] = "-";';

# Write map dictionary

$icon = array('wood'=>'o2', 'clay'=>'o4','iron'=>'o8','25wood'=>'o3','25clay'=>'o6','25iron'=>'o9','25'=>'o11','50'=>'o12');

echo "var m=new Array();\n";

foreach (incrange($x1, $x2) as $x) {

    foreach (incrange($y1, $y2) as $y) {

        echo 'm["'.$x.','.$y.'"]=new Array("'.$map[$x][$y].'","';

        if (!isset($villages[$x][$y])) {

            $img = (isset($icon[$map[$x][$y]])) ? $icon[$map[$x][$y]] : 't'.rand(0,9);

            echo $img.'");';

            continue;

        } else {

            $img = 'd';

            if ($villages[$x][$y]['pop'] < 100) $img .= '0';

            elseif ($villages[$x][$y]['pop'] < 200) $img .= '1';

            elseif ($villages[$x][$y]['pop'] < 500) $img .= '2';

            else $img .= '3';

            if (in_array($villages[$x][$y]['aid'],$wings)) $img.= '3';

            elseif (in_array($villages[$x][$y]['aid'],$naps)) $img.= '5';

            else $img .= '2';

            if ($villages[$x][$y]['inactive']>2) $img .= 'i';

            echo $img.'"';

        }

        # info, uid, player, aid, pop, inactive, img

        echo ','.$villages[$x][$y]['uid'].',"'.$villages[$x][$y]['player'].'",'.$villages[$x][$y]['aid'];

        echo ','.$villages[$x][$y]['pop'].','.$villages[$x][$y]['inactive'].',"'.$villages[$x][$y]['village'].'");';

    }

}



$xcoord = array(0,36,72,108,144,180,216,252,288,324,360,396,432,469,506,543,580,617,654,691,728,765,804,839,876);   

$leftpos = array(432,469,506,543,580,617,654,691,728,765,802,839,876,396,433,470,507,544,581,618,655,692,729,766,803,840,360,397,434,471,508,545,582,619,656,693,730,767,804,324,361,398,435,472,509,546,583,620,657,694,731,768,288,325,362,399,436,473,510,547,584,621,658,695,732,252,289,326,363,400,437,474,511,548,585,622,659,696,216,253,290,327,364,401,438,475,512,549,586,623,660,180,217,254,291,328,365,402,439,476,513,550,587,624,144,181,218,255,292,329,366,403,440,477,514,551,588,108,145,182,219,256,293,330,367,404,441,478,515,552,72,109,146,183,220,257,294,331,368,405,442,479,516,36,73,110,147,184,221,258,295,332,369,406,443,480,0,37,74,111,148,185,222,259,296,333,370,407,444);

$toppos = array(-20,0,20,40,60,80,100,120,140,160,180,200,220,0,20,40,60,80,100,120,140,160,180,200,220,240,20,40,60,80,100,120,140,160,180,200,220,240,260,40,60,80,100,120,140,160,180,200,220,240,260,280,60,80,100,120,140,160,180,200,220,240,260,280,300,80,100,120,140,160,180,200,220,240,260,280,300,320,100,120,140,160,180,200,220,240,260,280,300,320,340,120,140,160,180,200,220,240,260,280,300,320,340,360,140,160,180,200,220,240,260,280,300,320,340,360,380,160,180,200,220,240,260,280,300,320,340,360,380,400,180,200,220,240,260,280,300,320,340,360,380,400,420,200,220,240,260,280,300,320,340,360,380,400,420,440,220,240,260,280,300,320,340,360,380,400,420,440,460);



echo 'var cenx = '.$cenx.";\n";

echo 'var ceny = '.$ceny.";\n";

?>

    

var wings = new Array(401,450);

var naps = new Array(108,194,224,225,531);

function cartajax(nx,ny) {

  var xmlHttp;

  try { xmlHttp=new XMLHttpRequest(); }

  catch (e) {

    try { xmlHttp=new ActiveXObject("Msxml2.XMLHTTP"); }

    catch (e) {

      try { xmlHttp=new ActiveXObject("Microsoft.XMLHTTP"); }

      catch (e) { alert('Ajax error'); return false; }

    }

  }

  xmlHttp.onreadystatechange=function() {

    if(xmlHttp.readyState==4) { eval(xmlHttp.responseText); }

  }

  xmlHttp.open("GET","cartajax.php?x="+nx+"&y="+ny+"&b=30",true);

  xmlHttp.send(null);

}



function oc(arguments) {

  var o = {};

  for (var i=0; i<arguments.length; i++) o[arguments[i]]=null;

  return o;

}



function movr(obj) {

  var x = add(obj.id.substr(1).split(',')[0],cenx-6);

  var y = add(obj.id.substr(1).split(',')[1],ceny-6);

  document.getElementById('x').innerHTML = x;

  document.getElementById('y').innerHTML = y;

  if (m[x+","+y].length < 4) {

    mout(obj);

    return;

  }

  out = "<table cellspacing='1' cellpadding='2' class='tbg tbg2 f8'>";

  out += "<tr><td class='rbg f8' colspan='2'>"+m[x+","+y][7]+"</td></tr>";

  out += "<tr><td width='45%' class='s7 f8'>Player:</td><td class='s7 f8'>"+m[x+","+y][3]+"</td></tr>";

  out += "<tr><td class='s7 f8'>Population:</td><td class='s7 f8' id='ew'>"+m[x+","+y][5]+"</td></tr>";

  out += "<tr><td class='s7 f8'>Alliance:</td><td class='s7 f8'>"+anum[m[x+","+y][4]]+"</td></tr>";

  out += "<tr><td class='s7 f8'>Inactivity:</td><td class='s7 f8'>";

  if (m[x+","+y][6]==0) { out += " - </td></tr>"; }

  else if (m[x+","+y][6]==1) { out += m[x+","+y][6]+" day</td></tr>"; }

  else { out += m[x+","+y][6]+" days</td></tr>"; }

  out += "</table>";

  document.getElementById('tb').innerHTML = out;

}

function mout(obj) {

  out = "<table class='f8 map_infobox_grey' width='100%' cellspacing='1' cellpadding='2'>";

  out += "<tr><td class='c b' colspan='2' align='center'></a>Details:</td></tr>";

  out += "<tr><td width='45%' class='c s7'>Player:</td><td class='c s7'>-</td></tr>";

  out += "<tr><td class='c s7'>Population:</td><td class='c s7'>-</td></tr>";

  out += "<tr><td class='c s7'>Alliance:</td><td class='c s7'>-</td></tr>";

  out += "<tr><td class='c s7'>Inactivity:</td><td class='c s7'>-</td></tr>";

  out += "</table>";

  document.getElementById('tb').innerHTML = out;

}

function inc(a,n) { return (a+n<=400)?a+n:a+n-801; }

function dec(a,n) { return (a-n>=-400)?a-n:a-n+801; }

function add(a,n) { return (n>0)?inc(a*1,n):dec(a*1,-n); }



function shift(xs,ys) {

  cenx = add(cenx,xs);

  ceny = add(ceny,ys);

  document.getElementById('x').innerHTML = cenx;

  document.getElementById('y').innerHTML = ceny;

  for (var yc=12; yc>=0; yc--) {

    for (var xc=0; xc<=12; xc++) {

      obj = document.getElementById("i"+xc+","+yc); // img object ids are static 0,0->12,12

      x = inc(dec(cenx,6),xc);

      y = inc(dec(ceny,6),yc);

      obj.src="img/"+m[x+","+y][1]+".gif";

    }

  }

  if (!m[add(cenx,xs*28)+","+add(ceny,ys*28)]) cartajax(add(cenx,xs*57),add(ceny,ys*57));

  if (!m[add(cenx,xs*7)+","+add(ceny,ys*7)]) cartajax(add(cenx,xs*37),add(ceny,ys*37));

}

</script>

</head>

<body>



<div class="map_insert_xy_xxl">

<table align="center" cellspacing="0" cellpadding="3">

<form action="cartographer.php">

<tr><td><b>x</b></td><td>

<?php echo '<input name="x" value="'.$cenx.'" size="2" maxlength="4">' ?>

</td><td><b>y</b></td><td>

<?php echo '<input name="y" value="'.$ceny.'" size="2" maxlength="4">' ?>

</td><td></td><td>

<input type="image" value="ok" border="0" name="s1" src="img/ok1.gif" width="50" height="20" onMousedown="btm1('s1','','img/ok2.gif',1)" onMouseOver="btm1('s1','','img/ok3.gif',1)" onMouseUp="btm0()" onMouseOut="btm0()"></input></td>

</tr></form></table></div><div class="map_show_xy_xxl">

<table width="100%" cellspacing="0" cellpadding="0">

<tr>

<td width="30%"><h1>Map</h1></td>

<td width="33%" align="right"><h1><nobr>(<span id="x"><?php echo $cenx ?></span></nobr></h1></td>

<td width="4%" align="center"><h1>|</h1></td>

<td width="33%" align="left"><h1><nobr><span id="y"><?php echo $ceny ?></span></span>)</h1></td>



</tr>

</table>

</div>    



<div class="map_infobox_xxl" id="tb">

<table class='f8 map_infobox_grey' width='100%' cellspacing='1' cellpadding='2'>

<tr><td class='c b' colspan='2' align='center'></a>Details:</td></tr>

<tr><td width='45%' class='c s7'>Player:</td><td class='c s7'><span id="boxu">-</td></tr>

<tr><td class='c s7'>Population:</td><td class='c s7'><span id="boxp">-</td></tr>

<tr><td class='c s7'>Alliance:</td><td class='c s7'><span id="boxa">-</td></tr>

<tr><td class='c s7'>Inactivity:</td><td class='c s7'><span id="boxi">-</td></tr>

</table>

</div>

<div align="center" style="position:absolute; z-index:50; left:10px; top:0px;">

<?php

$i = 0;

for ($yc=12; $yc>=0; $yc--) {

    for ($xc=0; $xc<=12; $xc++) {

        $x = inc(dec($cenx,6),$xc);

        $y = inc(dec($ceny,6),$yc);

        if (isset($villages[$x][$y])) {

            $img = 'd';

            if ($villages[$x][$y]['pop'] < 100) $img .= '0';

            elseif ($villages[$x][$y]['pop'] < 200) $img .= '1';

            elseif ($villages[$x][$y]['pop'] < 500) $img .= '2';

            else $img .= '3';

            if (in_array($villages[$x][$y]['aid'],$wings)) $img.= '3';

            elseif (in_array($villages[$x][$y]['aid'],$naps)) $img.= '5';

            else $img .= '2';

            if ($villages[$x][$y]['inactive']>2) $img .= 'i';

        } else {

            $img = (isset($icon[$map[$x][$y]])) ? $icon[$map[$x][$y]] : 't'.rand(0,9);

        }

        echo '<img id="i'.$xc.','.$yc.'" style="position:absolute; left:'.$leftpos[$i].'px; top:';

        echo $toppos[$i].'px" src="img/'.$img.'.gif">';

        $i++;

    }

}

?>

</div>

<map name="map">

<area href="#" onclick="shift(0,1)" coords="762,115,30" shape="circle" title="North">

<area href="#" onclick="shift(1,0)" coords="770,430,30" shape="circle" title="East">

<area href="#" onclick="shift(0,-1)" coords="210,430,30" shape="circle" title="South">

<area href="#" onclick="shift(-1,0)" coords="200,115,30" shape="circle" title="West">



<?php

$areax = array(442,479,516,553,590,627,664,701,738,775,812,849,886,406,443,480,517,554,591,628,665,702,739,776,813,850,370,407,444,481,518,555,592,629,666,703,740,777,814,334,371,408,445,482,519,556,593,630,667,704,741,778,298,335,372,409,446,483,520,557,594,631,668,705,742,262,299,336,373,410,447,484,521,558,595,632,669,706,226,263,300,337,374,411,448,485,522,559,596,633,670,190,227,264,301,338,375,412,449,486,523,560,597,634,154,191,228,265,302,339,376,413,450,487,524,561,598,118,155,192,229,266,303,340,377,414,451,488,525,562,82,119,156,193,230,267,304,341,378,415,452,489,526,46,83,120,157,194,231,268,305,342,379,416,453,490,10,47,84,121,158,195,232,269,306,343,380,417,454);

$areay = array(33,53,73,93,113,133,153,173,193,213,233,253,273,53,73,93,113,133,153,173,193,213,233,253,273,293,73,93,113,133,153,173,193,213,233,253,273,293,313,93,113,133,153,173,193,213,233,253,273,293,313,333,113,133,153,173,193,213,233,253,273,293,313,333,353,133,153,173,193,213,233,253,273,293,313,333,353,373,153,173,193,213,233,253,273,293,313,333,353,373,393,173,193,213,233,253,273,293,313,333,353,373,393,413,193,213,233,253,273,293,313,333,353,373,393,413,433,213,233,253,273,293,313,333,353,373,393,413,433,453,233,253,273,293,313,333,353,373,393,413,433,453,473,253,273,293,313,333,353,373,393,413,433,453,473,493,273,293,313,333,353,373,393,413,433,453,473,493,513);

$areacoordsx = array(0,36,73,36);

$areacoordsy = array(0,-20,0,20);



$i = 0;

for ($yc=12; $yc>=0; $yc--) {

    for ($xc=0; $xc<=12; $xc++) {

        $x = inc(dec($cenx,6),$xc);

        $y = inc(dec($ceny,6),$yc);

        $coords = array();

        for ($k=0; $k<4; $k++) {

            $coords[] = $areax[$i] + $areacoordsx[$k];

            $coords[] = $areay[$i] + $areacoordsy[$k];

        }

        echo '<area href="#" onclick="" coords="'.implode(',',$coords).'" shape="poly" ';

        echo 'id="a'.$xc.','.$yc.'" ';

        echo 'onmouseover="movr(this)" onmouseout="mout(this)"/>';

        $i++;

    }

}

?>

</map>

<img style="position:absolute; width:975px; height:550px; z-index:400; left:0px; top:0px;" usemap="#map" src="img/bg_xxl.gif" border="0">

</body>

</html>

