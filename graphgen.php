<?php
# Check cache
$cachefile = 'cache/'.md5($_SERVER['REQUEST_URI']);
if (file_exists($cachefile) && (time()-24*60*60 < filemtime($cachefile))) {
    $pic = file_get_contents($cachefile);
    echo $pic;
    exit;
}
ob_start();

include ("jp/jpgraph.php");
include ("jp/jpgraph_bar.php");

# Connect
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax') or die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn);

$aid = (!empty($_GET['aid'])) ? $_GET['aid'] : 401;
$daysback = (!empty($_GET['d'])) ? $_GET['d'] : 14;
$distinct = (isset($_GET['distinct'])) ? 'distinct ' : '';
if (!empty($_GET['p'])) {
    $r = mysql_fetch_assoc(mysql_query('select distinct uid from s4_us_villages where player="'.$_GET['p'].'"'));
    $_GET['uid'] = $r['uid'];
}

for ($h=0;$h<24;$h++) {
    $offactivity[$h] = 0;
    $popactivity[$h] = 0;
    $attackactivity[$h] = 0;
}

$cond = (!empty($_GET['uid'])) ? 'uid='.$_GET['uid'] : 'uid in (select distinct uid from s4_us_villages where aid='.$aid.')';
$result = mysql_query('select hour(time) hr, count('.$distinct.'uid) num from s4_us_off where '.$cond.' and time > date(timestampadd(hour,-'.($daysback*24-3).',now())) group by hr');
while ($r = mysql_fetch_assoc($result)) $offactivity[$r['hr']] += $r['num']/$daysback;
$cond = (!empty($_GET['uid'])) ? 'uid='.$_GET['uid'] : 'aid='.$aid;
$result = mysql_query('select hour(time) hr, count('.$distinct.'uid) num from s4_us_pop where '.$cond.' and time > date(timestampadd(hour,-'.($daysback*24-3).',now())) group by hr');
while ($r = mysql_fetch_assoc($result)) $popactivity[$r['hr']] += $r['num']/$daysback;
$cond = (!empty($_GET['uid'])) ? 'attplayer=(select distinct player from s4_us_villages where uid='.$_GET['uid'].')' : 'attaid='.$aid;
$result = mysql_query('select hour(basesenttime) hr, count(attplayer) num from s4_us_reports where '.$cond.' and time > date(timestampadd(hour,-'.($daysback*24-3).',now())) and basesenttime is not null group by hr having hr >=0');
while ($r = mysql_fetch_assoc($result)) $attackactivity[$r['hr']] += $r['num']/$daysback;

for ($h=0;$h<24;$h++) $hrs[] = sprintf('%02d',$h).':00';

$graph = new Graph(600,250,"auto",60);
$graph->SetScale("textlin");
$graph->SetFrame(false);

$graph->img->SetMargin(40,30,40,50); 
$graph->SetMarginColor('white');

// Create the bar plots
$b1plot = new BarPlot($popactivity);
$b1plot->SetFillColor("gold");
$b1plot->SetLegend("Offense");
$b2plot = new BarPlot($offactivity);
$b2plot->SetFillColor("darkturquoise");
$b2plot->SetLegend("Population");
$b3plot = new BarPlot($attackactivity);
$b3plot->SetFillColor("darkolivegreen2");
$b3plot->SetLegend("Attacks");

// Create the grouped bar plot
$gbplot = new AccBarPlot(array($b1plot,$b2plot,$b3plot));
$gbplot->SetWidth(0.6);

$graph->Add($gbplot);

$graph->xaxis->SetTickLabels($hrs);
$graph->xaxis->SetTitle('Time','middle');
$graph->xaxis->SetLabelAlign('right','top');
$graph->xaxis->SetFont(FF_ARIAL,FS_NORMAL,8);
$graph->xaxis->SetLabelAngle(45);
$graph->xaxis->title->SetFont(FF_ARIAL,FS_NORMAL,10);
$graph->xaxis->SetTitleMargin(23); 

$graph->yaxis->title->Set("Activity count");
$graph->yaxis->SetFont(FF_ARIAL,FS_NORMAL,8);
$graph->yaxis->title->SetFont(FF_ARIAL,FS_NORMAL,10);
$graph->yaxis->SetTitleMargin(30);

$graph->legend->Pos(0.05,0.1,'right','top');
$graph->legend->SetShadow(false);
$graph->legend->SetFillColor('gray9');
$graph->legend->SetColumns(3);
$graph->legend->SetReverse();

$graph->title->Set('Average hourly activity count over the last '.$daysback.' days');
$graph->title->SetFont(FF_ARIAL,FS_BOLD,10);

# Display the graph
$graph->Stroke();

# Begin caching
$fp = fopen($cachefile, 'wb');
fwrite($fp, ob_get_contents());
fclose($fp);
ob_end_flush(); 
?>
