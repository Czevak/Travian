<?php
#############################################################################
# updatemap.php                                                             #
#   Downloads map.sql from travian server and modifies to fit HQ database.  #
#   (Run daily)                                                             #
#############################################################################

$url = "http://s4.travian.us/map.sql";
$page = fopen($url, "r");           # Need to change to curl at some point
$content = "";
while (!feof($page)) {              # Buffer line read because file is huge (several MB)
	$buffer = fgets($page,4096);
	$content .= $buffer;
}
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax') or die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn);

$lines = explode("\n",trim($content));
foreach ($lines as $i) {
	$i = substr($i,0,-1);
	$i = str_replace(";",'\;',$i);
	$sql1 = str_replace("INSERT INTO `x_world`","replace s4_us_villages",$i.";");
	$sql2 = str_replace("INSERT INTO `x_world`","replace s4_us_villages_snap",$i.";");
	$sql2 = str_replace(");",",date(timestampadd(HOUR,3,now())))",$sql2);
    # Update s4_us_villages
	mysql_query($sql1) or die ("Query failed: ".$sql1.mysql_error());
    # Update s4_us_villages_snap
	mysql_query($sql2) or die ("Query2 failed: ".$sql2.mysql_error());
    # Delete villages that were removed since yesterday 
    mysql_query('delete from s4_us_villages where uid not in (select uid from s4_us_villages_snap where day=date(timestampadd(HOUR,3,now())))');
}
foreach (array("army","attacks","kills","pop","razed","stolen") as $tb) mysql_query('truncate w_'.$tb);
?>