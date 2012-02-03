<?php
include ("jp/jpgraph.php");
include ("jp/jpgraph_bar.php");

# Connect
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax') or die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn);

# Get params
$interval = (isset($_GET['daily'])) ? 'dayofweek' : 'hour';
$imax = (isset($_GET['daily'])) ? 7 : 24;
$aid = (!empty($_GET['aid'])) ? $_GET['aid'] : 401;
$daysback = (!empty($_GET['d'])) ? $_GET['d'] : 14;
$distinct = (isset($_GET['distinct'])) ? 'distinct ' : '';
if (!empty($_GET['p'])) {
    $r = mysql_fetch_assoc(mysql_query('select distinct uid from s4_us_villages where player="'.$_GET['p'].'"'));
    $_GET['uid'] = $r['uid'];
}

# takes 1-7 or 0-24
function xfilter($x) {
    return (isset($_GET['daily'])) ? $x-1 : $x;
}

# Initialize y-values
$weekdays = array("Sun","Mon","Tue","Wed","Thu","Fri","Sat");
for ($i=0;$i<$imax;$i++) {
    $ints[] = (isset($_GET['daily'])) ? $weekdays[$i] : sprintf('%02d',$i).':00';
    $index = (isset($_GET['daily'])) ? $i+1 : $i;
    $offactivity[xfilter($index)] = 0;
    $popactivity[xfilter($index)] = 0;
    $attackactivity[xfilter($index)] = 0;
}

# Get y-values
$cond = (!empty($_GET['uid'])) ? 'uid='.$_GET['uid'] : 'aid='.$aid;
$result = mysql_query('select '.$interval.'(time) it, count('.$distinct.'uid) num from s4_us_pop where '.$cond.' and time > date(timestampadd(hour,-'.($daysback*24-3).',now())) group by it');
while ($r = mysql_fetch_assoc($result)) $popactivity[xfilter($r['it'])] += $r['num']/$daysback;

$cond = (!empty($_GET['uid'])) ? 'uid='.$_GET['uid'] : 'uid in (select distinct uid from s4_us_villages where aid='.$aid.')';
$result = mysql_query('select '.$interval.'(time) it, count('.$distinct.'uid) num from s4_us_off where '.$cond.' and time > date(timestampadd(hour,-'.($daysback*24-3).',now())) group by it');
while ($r = mysql_fetch_assoc($result)) $offactivity[xfilter($r['it'])] += $r['num']/$daysback;

$cond = (!empty($_GET['uid'])) ? 'attplayer=(select distinct player from s4_us_villages where uid='.$_GET['uid'].')' : 'attaid='.$aid;
$result = mysql_query('select '.$interval.'(basesenttime) it, count(attplayer) num from s4_us_reports where '.$cond.' and time > date(timestampadd(hour,-'.($daysback*24-3).',now())) and basesenttime is not null group by it having it >=0');
while ($r = mysql_fetch_assoc($result)) $attackactivity[xfilter($r['it'])] += $r['num']/$daysback;

# Create graph
$graph = new Graph(600,260,"auto",60);
$graph->SetScale("textlin");
$graph->SetFrame(false);
$graph->img->SetMargin(40,30,50,50); 
$graph->SetMarginColor('white');

# Create bar plots
$b1plot = new BarPlot($popactivity);
$b1plot->SetFillColor("gold");
$b1plot->SetLegend("Pop increase");

$b2plot = new BarPlot($offactivity);
$b2plot->SetFillColor("darkturquoise");
$b2plot->SetLegend("Off ranking");

$b3plot = new BarPlot($attackactivity);
$b3plot->SetFillColor("darkolivegreen2");
$b3plot->SetLegend("Attacks sent");

# Create grouped bar plot
$gbplot = new AccBarPlot(array($b1plot,$b2plot,$b3plot));
$gbplot->SetWidth(0.6);

$graph->Add($gbplot);

# Set axes
$graph->xaxis->SetTickLabels($ints);
$xlabel = (isset($_GET['daily'])) ? 'Day' : 'Time';
$graph->xaxis->SetTitle($xlabel,'middle');
$graph->xaxis->SetLabelAlign('right','top');
$graph->xaxis->SetFont(FF_ARIAL,FS_NORMAL,8);
$graph->xaxis->SetLabelAngle(45);
$graph->xaxis->title->SetFont(FF_ARIAL,FS_NORMAL,10);
$graph->xaxis->SetTitleMargin(23); 

$graph->yaxis->title->Set("Activity count");
$graph->yaxis->SetFont(FF_ARIAL,FS_NORMAL,8);
$graph->yaxis->title->SetFont(FF_ARIAL,FS_NORMAL,10);
$graph->yaxis->SetTitleMargin(30);

# Set legend
$graph->legend->Pos(0.05,0.075,'right','top');
$graph->legend->SetShadow(false);
$graph->legend->SetFillColor('gray9');
$graph->legend->SetColumns(3);
$graph->legend->SetReverse();

# Set title
if (isset($_GET['daily'])) $graph->title->Set('Average daily activity count over the last '.$daysback.' days');
else $graph->title->Set('Average hourly activity count over the last '.$daysback.' days');
$graph->title->SetFont(FF_ARIAL,FS_BOLD,10);
$graph->title->SetAlign('left');

# Display graph
$graph->Stroke();
?>
