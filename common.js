// Hook onload and set all anchors to parent frame
var oldonload=window.onload;
window.onload=function(){
  var anchors=document.getElementsByTagName("a");
  for (var i=0;i<anchors.length;i++) anchors[i].target="_parent";
  var forms=document.getElementsByTagName("form");
  for (var i=0;i<forms.length;i++) forms[i].target="_parent";
  if (oldonload) oldonload();
}
var redirects=new Array("home.php","raids.php","rankings.php","cartographer.php","crop.php","report.php","catreport.php","players.php","analyzer.php","alliances.php","hostiles.php","warroom.php","rally.php");
var toploc = top.location+"";
var frameloc = location+"";
for (var ri=0;ri<redirects.length;ri++){
  if (toploc.indexOf(redirects[ri])>=0) top.location = frameloc.replace("?","").replace(redirects[ri],"?h="+ri+"&");
}

