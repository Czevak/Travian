<?php
require 'auth_header.php';  # Require that user be authenticated
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="global.css" />
<link rel="stylesheet" href="windowfiles/dhtmlwindow.css" type="text/css" />
<script type="text/javascript" src="windowfiles/dhtmlwindow.js"></script>
<link rel="stylesheet" href="windowfiles/modal.css" type="text/css" />
<script type="text/javascript" src="windowfiles/modal.js"></script>
<script src="windowfiles/sorttable.js"></script>
<style type="text/css">
#dhtmltooltip{
position: absolute;
width: 100px;
font: 10pt verdana;
border: 2px solid black;
padding: 2px;
background-color: lightyellow;
visibility: hidden;
z-index: 800;
filter: progid:DXImageTransform.Microsoft.Shadow(color=gray,direction=135);
}
</style>
<script type="text/javascript">
<?php
include 'common.php';   # Load common functions

# Get centered coords
if (isset($_GET['cx'])) {
    $cenx = $_GET['cx'];
    $ceny = $_GET['cy'];
} else {
    $cenx = (isset($hqprefs)) ? $hqprefs[1] : 0;
    $ceny = (isset($hqprefs)) ? $hqprefs[2] : 0;
}
$races = array('','Roman','Teuton','Gaul');     # map starts at 1
$racemap = array(-1,2,0,1);                     # hqprefs -> normal order (R,T,G -> T,G,R)

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
$kartes = array();
$result = mysql_query('select x, y, info, karte from s4_us_map where '.$cond.' order by x,y desc');
while ($r = mysql_fetch_array($result)) {
	$map[$r['x']][$r['y']] = $r['info'];
    $kartes[$r['x']][$r['y']] = $r['karte'];
}

# Get villages + info
$villages = array();
$alliances = array();
$attrs = array('uid','player','aid','pop','inactive','village','karte');
$result = mysql_query('select u.uid,u.player,u.inactive,v.x,v.y,v.aid,v.alliance,v.pop,v.village,v.karte from s4_us_inactives u join (select uid,x,y,aid,alliance,pop,village,karte from s4_us_villages where '.$cond.') v on u.uid=v.uid');
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
$icon = array('wood'=>1,'clay'=>4,'iron'=>7,'25'=>10,'25wood'=>3,'25clay'=>6,'25iron'=>9,'50'=>12);
echo "var m=new Array();";
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

$xcoord = array(0,36,72,108,144,180,216,252,288,324,360,396,432,469,506,543,580,617,654,691,728,765,804,839,876);   
$leftpos = array(432,469,506,543,580,617,654,691,728,765,802,839,876,396,433,470,507,544,581,618,655,692,729,766,803,840,360,397,434,471,508,545,582,619,656,693,730,767,804,324,361,398,435,472,509,546,583,620,657,694,731,768,288,325,362,399,436,473,510,547,584,621,658,695,732,252,289,326,363,400,437,474,511,548,585,622,659,696,216,253,290,327,364,401,438,475,512,549,586,623,660,180,217,254,291,328,365,402,439,476,513,550,587,624,144,181,218,255,292,329,366,403,440,477,514,551,588,108,145,182,219,256,293,330,367,404,441,478,515,552,72,109,146,183,220,257,294,331,368,405,442,479,516,36,73,110,147,184,221,258,295,332,369,406,443,480,0,37,74,111,148,185,222,259,296,333,370,407,444);
$toppos = array(-20,0,20,40,60,80,100,120,140,160,180,200,220,0,20,40,60,80,100,120,140,160,180,200,220,240,20,40,60,80,100,120,140,160,180,200,220,240,260,40,60,80,100,120,140,160,180,200,220,240,260,280,60,80,100,120,140,160,180,200,220,240,260,280,300,80,100,120,140,160,180,200,220,240,260,280,300,320,100,120,140,160,180,200,220,240,260,280,300,320,340,120,140,160,180,200,220,240,260,280,300,320,340,360,140,160,180,200,220,240,260,280,300,320,340,360,380,160,180,200,220,240,260,280,300,320,340,360,380,400,180,200,220,240,260,280,300,320,340,360,380,400,420,200,220,240,260,280,300,320,340,360,380,400,420,440,220,240,260,280,300,320,340,360,380,400,420,440,460);

echo 'var cenx = '.$cenx.";\n";
echo 'var ceny = '.$ceny.";\n";
echo 'var wings = new Array('.implode(',',$const_wings).');';
echo 'var naps = new Array('.implode(',',$const_naps).');';
echo 'var leftpos = new Array('.implode(',',$leftpos).');';
echo 'var toppos = new Array('.implode(',',$toppos).');';
if (!empty($_GET['sx'])&&!empty($_GET['sy'])) echo 'var srcflag = "'.$_GET['sx'].','.$_GET['sy'].'";';
else echo 'var srcflag;';
if (isset($hqprefs)&& strlen($hqprefs[3])==1) echo 'var race='.$racemap[$hqprefs[3]].';';
else echo 'var race=0;';
?>
var info;
var reverseracemap = new Array(1,2,0);

var tgtflag;
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
function timeformat(inp) {
  return pad(floor(inp/3600))+':'+pad(floor((inp%3600)/60))+':'+pad(floor(inp%60));
}
function pad(inp) {
  inp = inp.toString();
  if (inp.length == 1) return '0'+inp;
  return inp;
}
function floor(x) { return Math.floor(x); }
function isoasis(info) {
  return (info=='25'||info=='50'||info=='25clay'||info=='25wood'||info=='25iron'||info=='clay'||info=='wood'||info=='iron');
}

function movr(obj) {
  var x = add(obj.id.substr(1).split(',')[0],cenx-6);
  var y = add(obj.id.substr(1).split(',')[1],ceny-6);
  document.getElementById('x').innerHTML = x;
  document.getElementById('y').innerHTML = y;
  if (m[x+","+y].length<5 && !isoasis(m[x+","+y][0])) { // die if not oasis or village
    mout(obj);
    return;
  }
  if (srcflag) {
    times = new Array(new Array(7,7,6,9,10,9,4,3,4,5),new Array(7,6,17,19,16,13,4,3,5,5),new Array(6,5,7,16,14,10,4,3,4,5));
    x2 = srcflag.split(',')[0];
    y2 = srcflag.split(',')[1];
    var delta = Math.sqrt(Math.pow(x-x2,2)+Math.pow(y-y2,2));
    tiphtml = '<table class="tbg tbg2" cellspacing="1" cellpadding="2">';
    tiphtml += '<tr><td colspan="2" class="s7">'+Number(delta).toFixed(2)+' sq</td></tr>';
    for (var i=0;i<10;i++) {
      if (i==10) alert(delta+","+times[race][i-1]);
      tiphtml += '<tr><td class="s7"><img src="img/'+(i+1+reverseracemap[race]*10)+'.gif"></td><td class="s7">';
      tiphtml += timeformat(delta*3600/times[race][i]);
      tiphtml += '</td></tr>';
    }
    tiphtml += '</table>';
    ddrivetip(tiphtml);
  }
  if (m[x+","+y].length <5) return;
  out = "<table cellspacing='1' cellpadding='2' class='tbg tbg2 f8'>";
  out += "<tr><td class='rbg f8' colspan='2'>"+m[x+","+y][8]+"</td></tr>";
  out += "<tr><td width='45%' class='s7 f8'>Player:</td><td class='s7 f8'>"+m[x+","+y][4]+"</td></tr>";
  out += "<tr><td class='s7 f8'>Population:</td><td class='s7 f8' id='ew'>"+m[x+","+y][6]+"</td></tr>";
  out += "<tr><td class='s7 f8'>Alliance:</td><td class='s7 f8'>"+anum[m[x+","+y][5]]+"</td></tr>";
  out += "<tr><td class='s7 f8'>Inactivity:</td><td class='s7 f8'>";
  if (m[x+","+y][7]==0) { out += " - </td></tr>"; }
  else if (m[x+","+y][7]==1) { out += m[x+","+y][7]+" day</td></tr>"; }
  else { out += m[x+","+y][7]+" days</td></tr>"; }
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
  hideddrivetip();
}
function mclick(obj, event) {
  var x = add(obj.id.substr(1).split(',')[0],cenx-6);
  var y = add(obj.id.substr(1).split(',')[1],ceny-6);
  if (m[x+","+y][8]) infotype = 'village';
  else if (m[x+","+y][0].length<=2 && m[x+","+y][0]!='25' && m[x+","+y][0]!='50') infotype = 'valley';
  else infotype = 'oasis';
  infohtml = '<table class="tbg" style="width: 100%" cellspacing="1" cellpadding="2" align="center">';
  infohtml += '<tr><td class="s7"><a href="http://s4.travian.us/karte.php?d='+m[x+","+y][2]+'">Go to this '+infotype+' <img src="img/external.gif" /></a></td></tr>';
  infohtml += '<tr><td class="s7"><a href="http://s4.travian.us/a2b.php?z='+m[x+","+y][2].split('&c')[0]+'">Attack this '+infotype+' <img src="img/external.gif" /></a></td></tr>';
  if (infotype=='village') infohtml += '<tr><td class="s7"><a href="analyzer.php?k='+m[x+","+y][2]+'">Analyze village</a></td></tr>';
  infohtml += '<tr><td class="s7"><a href="#" onclick="mark('+x+','+y+',0)">Mark (source)</a></td></tr>';
  infohtml += '<tr><td class="s7"><a href="#" onclick="mark('+x+','+y+',1)">Mark (target)</a></td></tr>';
  infohtml += '</table>';
  var infotitle = (m[x+","+y][8])?m[x+","+y][8]:'Unoccupied';
  var xpos = (event.clientX+200<975)?event.clientX:event.clientX-200;
  var infoheight=(infotype=='village')?105:85;
  info = dhtmlwindow.open('info','inline',infohtml,infotitle,'width=160px,height='+infoheight+'px,left='+xpos+',top='+(event.clientY+20)+',resize=0,scrolling=0');
}

function mark(x,y,loc) {
  if (loc==1||!x||!y) return;
  (loc==0)?srcflag=x+","+y:tgtflag=x+","+y;
  var gx = add(x,7-cenx);
  var gy = add(y,7-ceny);
  if (Math.abs(gx)>13||Math.abs(gy)>13) return;
  document.getElementById("srcmarker").innerHTML = '<img onmouseover="movr(document.getElementById(\'a'+(gx-1)+','+(gy-1)+'\'))" onmousedown="mclick(document.getElementById(\'a'+(gx-1)+','+(gy-1)+'\',event))" src="img/marksrc.gif">';
  document.getElementById("srcmarker").style.visibility = 'visible';
  document.getElementById("srcmarker").style.left = leftpos[(13-gy)*13+gx-1]+22+'px';
  document.getElementById("srcmarker").style.top = toppos[(13-gy)*13+gx-1]+10+'px';
  if (info) info.close();
  document.getElementById("jump").sx.value = x;
  document.getElementById("jump").sy.value = y;
}
function racechange(obj) {
  //race = (obj.selectedIndex>1)?0:obj.selectedIndex+1;
  race = obj.selectedIndex;
}

function jumpto(obj) {
  if (obj.selectedIndex==0) return;
  var ty = obj.options[obj.selectedIndex].value.split(",");
  var tx = ty[0];
  ty = ty[1];
  if (!m[tx+","+ty]) cartajax(tx,ty);
  cenx = tx;
  ceny = ty;
  if (m[tx+","+ty]) shift(0,0);
  else { 
    timeoutwin = dhtmlwindow.open('timeout','inline','<p align="center>Please wait...</p>','Processing...',"width=100px,height=50px,resize=0,scrolling=0,top=300px,left=437px");
    timeoutwin.onclose = function(){shift(0,0); return true;}
    setTimeout(function(){timeoutwin.hide();shift(0,0);},3000);
  }
}

function inc(a,n) { return (a+n<=400)?a+n:a+n-801; }
function dec(a,n) { return (a-n>=-400)?a-n:a-n+801; }
function add(a,n) { return (n>0)?inc(a*1,n):dec(a*1,-n); }
function inrange(a,n,x) {
  for (var i=a;i!=add(a,n);i=add(i,1)) {
    if (i==x) return true;
  }
  return false;
}
function shift(xs,ys) {
  cenx = add(cenx,xs);
  ceny = add(ceny,ys);
  document.getElementById('x').innerHTML = cenx;
  document.getElementById('y').innerHTML = ceny;
  villagelist = '<tr class="rbg"><td style="cursor: pointer">Player</td><td style="cursor: pointer">Alliance</td>';
  villagelist += '<td style="cursor: pointer">Village</td><td style="cursor: pointer">Pop</td><td class="sorttable_nosort">Coords</td></tr>';
  for (var yc=12; yc>=0; yc--) {
    for (var xc=0; xc<=12; xc++) {
      obj = document.getElementById("i"+xc+","+yc); // img object ids are static 0,0->12,12
      x = inc(dec(cenx,6),xc);
      y = inc(dec(ceny,6),yc);
      obj.src="img/"+m[x+","+y][1]+".gif";
      if (m[x+","+y].length<5) continue;
      villagelist += '<tr><td>'+m[x+","+y][4]+'</td><td>'+anum[m[x+","+y][5]]+'</td>';
      villagelist += '<td><a href="analyzer.php?k='+m[x+","+y][2]+'">'+m[x+","+y][8]+'</a>';
      villagelist += '</td><td>'+m[x+","+y][6]+'</td><td><a href="#" onclick="mark('+x+','+y+',0)">'+x+","+y+'</a></td></tr>';
    }
  }
  document.getElementById("villagelist").innerHTML = villagelist;
  sorttable.makeSortable(document.getElementById("villagelist"));
  document.getElementById('maplink').value = 'http://travian.ulrezaj.com/h=3&cx='+cenx+'&cy='+ceny;
  if (!m[add(cenx,xs*28)+","+add(ceny,ys*28)]) cartajax(add(cenx,xs*57),add(ceny,ys*57));
  for (var i=-6;i<=6;i++) { // Scan coming edge for missing map links
    if (!m[add(add(cenx,xs*7),ys*i)+","+add(add(ceny,ys*7),xs*i)]) {
      cartajax(add(cenx,xs*36),add(ceny,ys*36));
      break;
    }
  }
  if (!srcflag) return;
  var srcx = srcflag.split(',')[0];
  var srcy = srcflag.split(',')[1];
  var gx = add(srcx,7-cenx);
  var gy = add(srcy,7-ceny);
  if (inrange(add(cenx,-6),13,srcx) && inrange(add(ceny,-6),13,srcy)) {
    document.getElementById("srcmarker").style.visibility = 'visible'; 
    document.getElementById("srcmarker").style.left = leftpos[(13-gy)*13+gx-1]+22+'px';
    document.getElementById("srcmarker").style.top = toppos[(13-gy)*13+gx-1]+10+'px';
  } else { document.getElementById("srcmarker").style.visibility = 'hidden'; }
}
function jumpsubmit() {
  var f = document.getElementById("jumpform");
  if (!f.sx.value) {
    f.sx.parentNode.removeChild(f.sx);
    f.sy.parentNode.removeChild(f.sy);
  }
  f.submit();
}
</script>
<script type="text/javascript" src="common.js"></script>
</head>
<body <?php if (!empty($_GET['sx'])) echo 'onload="mark('.$_GET['sx'].','.$_GET['sy'].',0)"'; ?>>
<div id="dhtmltooltip"></div>
<script type="text/javascript" src="windowfiles/ddrivetooltip.js"></script>
<div class="map_insert_xy_xxl">
<table align="center" cellspacing="0" cellpadding="3">
<form id="jumpform" action="/" onsubmit="jumpsubmit()">
<input type="hidden" name="h" value="3">
<input type="hidden" name="sx" value=""><input type="hidden" name="sy" value="">
<tr><td><b>x</b></td><td><?php echo '<input name="cx" value="'.$cenx.'" size="2" maxlength="4">' ?>
</td><td><b>y</b></td><td><?php echo '<input name="cy" value="'.$ceny.'" size="2" maxlength="4">' ?>
</td><td></td><td><input type="submit" style="position:absolute; top:-1000px">
<img src="img/ok1.gif" style="cursor: pointer" onmousedown="this.src='img/ok2.gif'" onmouseout="this.src='img/ok1.gif'" onmouseup="this.src='img/ok1.gif'; blur(); jumpsubmit()"></td>
</tr></form></table></div><div class="map_show_xy_xxl">
<table width="100%" cellspacing="0" cellpadding="0">
<tr>
<td width="30%"><h1>Cartographer&nbsp;</h1></td>
<td width="33%" align="right"> <h1><nobr>(<span id="x"><?php echo $cenx ?></span></nobr></h1></td>
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
            if (in_array($villages[$x][$y]['aid'],$const_wings)) $img.= '3';
            elseif (in_array($villages[$x][$y]['aid'],$const_naps)) $img.= '5';
            else $img .= '2';
            if (in_array($villages[$x][$y]['aid'],$const_hostiles)) $img .= 'h';
            elseif ($villages[$x][$y]['inactive']>2) $img .= 'i';
        } else {
            if (isset($icon[$map[$x][$y]])) {
                $img = (in_array($icon[$map[$x][$y]],array(1,4,7,10))) ? 'o'.($icon[$map[$x][$y]]+rand(0,1)) : 'o'.$icon[$map[$x][$y]];
            } else { 
                $img = 't'.rand(0,9);
            }
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
        echo 'onmouseover="movr(this)" onmouseout="mout(this)" onmousedown="mclick(this, event)"/>';
        $i++;
    }
}
?>
</map>
<img style="position:absolute; width:975px; height:550px; z-index:100; left:0px; top:0px;" usemap="#map" src="img/bg_xxl.gif" border="0"><br>
<div id="srcmarker" style="position:absolute; top:-1000px height:50px; width:50px; z-index:701"></div>
<table style="position:absolute; left:0px; top: 560px;" cellspacing="1" cellpadding="2" class="tbg">
<tr><td width="100">Current link</td><td class="s7"><input type="text" id="maplink" readonly class="fm fm250" value="http://travian.ulrezaj.com/?h=3&cx=<?php echo $cenx.'&cy='.$ceny ?>"></td>
<td>Race</td><td class="s7"><select class="fm" onchange="racechange(this)" id="race">
<?php
foreach (array('Teuton','Gaul','Roman') as $r) {
    echo '<option';
    if ($hqprefs && $r==$races[$hqprefs[3]]) echo ' selected';
    echo '>'.$r.'</option>';
}
?>
</select></td>
<td>Jump to:</td><td><select class="fm" onchange="jumpto(this)" id="jump"><option value="0">- Select village -</option>
<?php
if ($hqprefs) {
    $result = mysql_query('select village,x,y from s4_us_villages where player="'.$hqprefs[0].'" order by village');
    while ($r = mysql_fetch_assoc($result)) {
        echo '<option value="'.$r['x'].','.$r['y'].'">'.$r['village'].'</option>';
    }
}
?>
</select></td>
</tr>
<tr><td colspan="6">
<table cellspacing="1" cellpadding="2" align="center" class="tbg4 sortable" id="villagelist">
<script type="text/javascript">
document.write('<tr class="rbg"><td style="cursor: pointer">Player</td><td style="cursor: pointer">Alliance</td>');
document.write('<td style="cursor: pointer">Village</td><td style="cursor: pointer">Pop</td><td class="sorttable_nosort">Coords</td></tr>');
var sortpos = new Array();
for (var yc=12; yc>=0; yc--){
  for (var xc=0; xc<=12; xc++){
    x = add(cenx,xc-6);
    y = add(ceny,yc-6);
    if (m[x+","+y].length<5) continue;
    //sortpos[m[x+","+y][n]]
    // info, img, karte[, uid, player, aid, pop, inactive, village]
    
    document.write('<tr><td>'+m[x+","+y][4]+'</td><td>'+anum[m[x+","+y][5]]+'</td>');
    document.write('<td><a href="analyzer.php?k='+m[x+","+y][2]+'">'+m[x+","+y][8]+'</a>');
    document.write('</td><td>'+m[x+","+y][6]+'</td><td><a href="#" onclick="mark('+x+','+y+',0)">'+x+","+y+'</a></td></tr>')
  }
}
</script>
</table>
</td></tr></table><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>

</body>
</html>
