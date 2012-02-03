<?php
# Check cache
$cachefile = 'cache/'.md5($_SERVER['REQUEST_URI']);
if (file_exists($cachefile) && (time()-24*60*60 < filemtime($cachefile))) {
    $pic = file_get_contents($cachefile);
    echo $pic;
    exit;
}
ob_start();

include 'jp/jpgraph.php';
include 'jp/jpgraph_line.php';
$gx = explode(',',$_GET['gx']);
$gy1 = explode(',',$_GET['gy1']);
$gy2 = explode(',',$_GET['gy2']);
$gy1lbl = $_GET['gy1lbl'];
$gy2lbl = $_GET['gy2lbl'];
$gyax = $_GET['gyax'];
$graph = new Graph(600,200,"auto",60);
$graph->SetMarginColor('white');
$graph->img->SetMargin(50,20,20,60); 
$graph->SetScale("textlin");
$graph->SetBox();
$graph->SetFrame(false);

$graph->legend->Pos(0.1,0.125,'left','top');
$graph->legend->SetShadow(false);
$graph->legend->SetFillColor('gray9');

$graph->xaxis->SetTickLabels($gx);
$graph->xaxis->SetLabelAlign('right','top');
$graph->xaxis->SetFont(FF_ARIAL,FS_NORMAL,8);
$graph->xaxis->SetLabelAngle(45);
$graph->xaxis->SetTextLabelInterval(2);

$graph->yaxis->SetFont(FF_ARIAL,FS_NORMAL,8);
$graph->yaxis->title->SetFont(FF_ARIAL,FS_NORMAL,10);
$graph->yaxis->title->Set($gyax);
$graph->yaxis->SetTitleMargin(40); 
function yformat($inp) {
    if (strpos($inp,'000')!==false) return substr($inp,0,-3).'k';
    return $inp;
}
$graph->yaxis->SetLabelFormatCallback('yformat'); 


$p1c = 'darkolivegreen2';
$p2c = 'indianred3';

$p1 = new LinePlot($gy1);
$p1->SetColor($p1c);
$p1->SetWeight(3);
$p1->SetLegend($gy1lbl);

$p2 = new LinePlot($gy2);
$p2->SetColor($p2c);
$p2->SetWeight(3);
$p2->SetLegend($gy2lbl);

$graph->Add($p1);
$graph->Add($p2);
$graph->Stroke();

# Begin caching
$fp = fopen($cachefile, 'wb');
fwrite($fp, ob_get_contents());
fclose($fp);
ob_end_flush(); 
?>