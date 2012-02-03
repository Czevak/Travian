<?php
# Common connect include for local database
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax') or die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn) or die('DB error: '.mysql_error());
?>
