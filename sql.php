<?php
#require 'auth_header.php';  # Require that user be authenticated
?>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>SQL</title>
<script src="windowfiles/sorttable.js"></script>
<style type="text/css">
.tbg {background-color: #C0C0C0; text-align:left; font-size:8pt;}
table.tbg tr {background-color: #FFFFFF;}
</style>
</head>
<body>
<form action="sql.php" method="post">
<textarea name="query" cols="100", rows="15">
<?php if (isset($_POST["query"])) echo stripslashes($_POST["query"]); ?>
</textarea><br>
<input type="submit" value="go">
</form><br><hr>

<?php
function specialexplode($str) {
    $key = md5(rand());
    $str = str_replace('\;',$key,$str);
    $arr = explode(';',$str);
    foreach ($arr as &$arrelem) {
        $arrelem = str_replace($key,'\;',$arrelem);
    }
    return $arr;
}
if (isset($_POST['query'])) {
    $query = specialexplode($_POST['query']);
    # Connect
    $conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax');
    if (!$conn) die('Could not connect: '.mysql_error());
    mysql_select_db('ulrezaj2_travian', $conn);
    foreach ($query as $sql) {
        echo '<table cellspacing="1" cellpadding="2" class="tbg sortable"><tr>';
        $sql = trim($sql);
        if (!$sql) continue;
        $result = mysql_query(stripslashes($sql));
        echo mysql_error();
        if (!$result) continue;
        
        for ($i=0; $i<mysql_num_fields($result) ; $i++ )
           echo '<th style="cursor:pointer">'.mysql_field_name($result, $i)."</th>";
        echo "</tr>";
        while ($row = mysql_fetch_assoc($result)) {
            echo "<tr>";
            foreach ($row as $f) {
                echo "<td>".$f."</td>";
            }
            echo "</tr>\n";
        }
        echo "</table>\n\n";
    }
}
?>
</body></html>

