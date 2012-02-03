<?php
# variable setting
$loc = array ("speed.travian.us" => "speed_us_",
              "s3.travian.us"    => "s3_us_",
			  "s4.travian.us"	 => "s4_us_",
              "s6.travian.us"    => "s6_us_");

# Initialize database connection
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax');
if (!$conn) die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn);

# get form keys
if (isset($_GET['x'])) $x = $_GET['x'];
if (isset($_GET['y'])) $y = $_GET['y'];
if (isset($_GET['s'])) $server = $_GET['s'];

$sql = "select tribe, village, player, alliance, pop from ".$loc[$server]."villages ";
$sql = $sql."where x=".$x." and y=".$y;
$result = mysql_query($sql);

$tribes = array("", "Roman", "Teuton", "Gaul");
while ($row = mysql_fetch_array($result)) {
    echo "<table border='0' style='font: 10px verdana'>";
    echo "<tr><td colspan='2' style='font: bold 16px verdana'>".$row["village"]."</td></tr>";
    echo "<tr style='font: 12px verdana'><td>Player</td><td>".$row["player"]."</td></tr>";
    echo "<tr style='font: 12px verdana'><td>Alliance</td><td>".$row["alliance"]."</td></tr>";
    echo "<tr style='font: 12px verdana'><td>Tribe</td><td>".$tribes[$row["tribe"]]."</td></tr>";
    echo "<tr style='font: 12px verdana'><td>Population</td><td>".$row["pop"]."</td></tr>";
    echo "</table>";
}
mysql_close($conn);
