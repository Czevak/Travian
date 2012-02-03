<?php
require 'auth_header.php';  # Require that user be authenticated

# Connect
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax') or die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn);

if (isset($_GET['ign'])) {
    $racemap = array("Teuton"=>2,"Gaul"=>3,"Roman"=>1);
    $x = (!empty($_GET['x'])) ? $_GET['x'] : 79;
    $y = (!empty($_GET['y'])) ? $_GET['y'] : 8;
    $sql = 'replace s4_us_hqprefs set x='.$x.',y='.$y.',race="'.$racemap[$_GET['race']].'",ign="';
    $sql .= $_GET['ign'].'",login="'.$_SESSION['pa_user'].'"';
    mysql_query($sql);
    setcookie ("hqprefs", $_GET['ign'].','.$x.','.$y.','.$racemap[$_GET['race']], time()+60*60*24*365, "/",$cookieurl); 
    echo '<html><head><script type="text/javascript">window.parent.welcomewin.hide()</script></head></html>';
    die;
}
?>
<html><head><link rel="stylesheet" type="text/css" href="global.css" /></head><body><form action="welcomeframe.php" method="get" id="prefs">
<table class="tbg tbg2" cellspacing="1" cellpadding="2" align="center">
<tr><td class="s7" colspan="2">Welcome to the EHJ Travian HQ!<br><br>
This site houses a number of analysis and reporting tools designed to aid EHJ members in dominating s4. The panel on the left contains links to the various tools.<br><br>
A good place to start would be the Alliance Reports page to see what the latest raid activity has been, or the Cartographer to get a better view of your surrounding area.<br><br>
The homepage has several handy calculators available, including CP and launch times, and also houses the News section which lists all the updates and improvements to the site as they go live.<br><br>
If there's anything you don't understand or notice that's broken, feel free to give Ulrezaj a shout on the forums or IRC.<br><br>
</td></tr>
</td></tr>
<tr class="rbg"><td colspan="2">My preferences</td></tr>
<tr class="cbg1"><td colspan="2" class="s7">Before you begin, please fill out the form below to personalize your HQ portal.</td></tr>
<tr><td class="s7" width="200px">In game name:</td><td class="s7"><select name="ign" class="fm">
<script type="text/javascript">document.write(window.parent.document.getElementById("players").innerHTML)</script>
</select></td></tr>
<tr><td class="s7">Cartographer starting village</td><td class="s7">
<b>x</b> <input class="fm fm25" name="x" value="" size="2" maxlength="4" />
<b>y</b> <input class="fm fm25" name="y" value="" size="2" maxlength="4" /></td></tr>
<tr><td class="s7">Race</td><td class="s7"><select name="race" class="fm"><option>Teuton</option><option>Gaul</option><option>Roman</option></select></td></tr>
<tr><td colspan="2" class="r7"><input type="image" border="0" src="img/ok1.gif" onmousedown="this.src='img/ok2.gif'" onmouseout="this.src='img/ok1.gif'" onmouseup="this.src='img/ok1.gif'; document.getElementById('prefs').submit()"></td></tr>
</table></form></body></html>