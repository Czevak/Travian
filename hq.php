<?php
#require 'auth_header.php';  # Require that user be authenticated
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>EHJ HQ Central</title>

<style type="text/css">
body { background-color: #e1eed3; }
td { vertical-align: top; font: 8pt verdana; }
html, body, #wrapper { height:100%; margin: 0; padding: 0; border: none; }
</style>
<link rel="stylesheet" type="text/css" href="windowfiles/sdmenu.css" />
<script type="text/javascript" src="windowfiles/sdmenu.js"></script>
<script type="text/javascript">
if (top.location!=location) {top.location = location;}

function autoIframe(frameId){
try{
frame = document.getElementById(frameId);
innerDoc = (frame.contentDocument) ? frame.contentDocument : frame.contentWindow.document;
objToResize = (frame.style) ? frame.style : frame;
objToResize.height = innerDoc.body.scrollHeight + 10;
}
catch(err){
window.status = err.message;
}
}


function open(url) {
    document.getElementById('content').src = url;
}
var menu;
function init() {
    menu = new SDMenu("menu");
    menu.init();
    menu.speed = 3;
    menu.remember = true;
    menu.oneSmOnly = false;
    menu.markCurrent = true;
    <?php
    #if (!empty($_GET['h']) && $_GET['h']==3) echo 'open("cartographer.php");'
    ?>
}

window.onload = init;
</script>
<script type="text/javascript" src="common.js"></script>
</head>
<body>
<table id="wrapper">
    <tr><td colspan="2"></td></tr>
    <tr><td>
    <div style="float: left" id="menu" class="sdmenu">
      <div>
        <span>Tools</span>
        <a href="/">HQ Central Home</a>
        <a href="/?h=1">Alliance reports</a>
        <a href="/?h=2">Stats &amp; Rankings</a>
        <a href="/?h=3">Cartographer</a>
        <a href="/?h=4">Cropfinder</a>
        <a href="/?h=11" style="color:red">Warroom</a>
        <a href="/?h=12" style="color:red">Bulwark</a>
        <a href="login.php?logout">Logoff HQ Central</a>
      </div>

      <div>
        <span>EHJ</span>
        <a href="http://www.ituroncavalry.com/forums/index.php?showforum=143">Forums</a>
        <a href="http://ituroncavalry.com/wiki/travian/index.php/Main_Page">Wiki</a>
        <a href="irc://irc.gamesurge.net/#ehj">IRC</a>
      </div>
      
      <div>
        <span>External</span>
        <a href="http://travilog.org.ua/us/">Travilog</a>
        <a href="http://travianutility.netsons.org/index_en.php?body=calcolotempi">Travian Utility</a>
        <a href="http://www.travian.ws/analyser.pl?s=us4">World Anaylzer</a>
      </div>
      
    </div>&nbsp;<br> 
    </td>
    <td>
    <?php
    $pages = array('home.php','raids.php','rankings.php','cartographer.php','crop.php','report.php','catreport.php','players.php','analyzer.php','alliances.php','hostiles.php','warroom.php','rally.php');
    if (!empty($_GET['h'])) {
        $page = $pages[$_GET['h']].'?';
        unset($_GET['h']);
        foreach ($_GET as $k=>$v) $page .= $k.'='.$v.'&';
    }
    else $page='home.php';
    ?>
    <iframe id="content" src="<?php echo $page ?>" scrolling="no" frameborder="0"  onload="if (window.parent && window.parent.autoIframe) {window.parent.autoIframe('content');}" style="overflow:visible; border: none; width:975px;"></iframe>
    <td></tr>
</table>
</body>
</html>