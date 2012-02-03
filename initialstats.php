<?php
#------------------------------------#
# Initial population of daily stats  #
# table from current s4 reports      #
#------------------------------------#
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="global.css" />
</head>
<body>
<?php
include 'common.php';   # Load common functions

# Connect
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax');
if (!$conn) die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn);

# For each player
$players = mysql_query('select distinct(player) player from s4_us_villages where aid in (401,450) order by player');
while ($p = mysql_fetch_assoc($players)) {
    $p = $p['player'];
    # For each day
    $dates = mysql_query('select date(time) day, count(id) reports from s4_us_reports where (attacker like "'.$p.' from the village%" or defender like "'.$p.' from the village%") and date(time)!=curdate() group by date(time)');
    while ($d = mysql_fetch_assoc($dates)) {
        $attacks = 0;
        $defends = 0;
        $scouts = 0;
        $bounty = Array(0,0,0,0);   # Bounty gained
        $losses = 0;                # Resources lost to enemies
        $efficiency = 0;
        $rkills = 0;                # Kills in resources
        $rdeaths = 0;               # Deaths in resources
        $kills = 0;                 # Kills in food
        $deaths = 0;                # Deaths in food
        # Get reports
        $stats = mysql_query('select * from s4_us_reports where (attacker like "'.$p.' from the village%" or defender like "'.$p.' from the village%") and date(time)="'.$d["day"].'"');
        while ($r = mysql_fetch_assoc($stats)) {
            if (strpos($r["attacker"],$p." from")!== false) {   # Player is attacker
                $attacks++;
                $rdeaths += costr($r["attcasualties"],$r["attrace"]);
                $deaths += food($r["attcasualties"],$r["attrace"]);
                if (!$r["defcasualties"] && strpos($r["info"],"soldiers")===false) {    # Scout report
                    $scouts++;
                    continue;
                }
                $b = explode(',',$r['bounty']);                 # Bounty
                for ($i=0; $i<4; $i++) $bounty[$i] += $b[$i];
                $efficiency += sprintf('%01.2f',array_sum($b)*100/carry($r["attunits"],$r["attrace"]));
                $rkills += costr($r["defcasualties"],$r["defrace"])+costr($r["rein1casualties"],$r["rein1race"])+costr($r["rein2casualties"],$r["rein2race"]);
                $kills += food($r["defcasualties"],$r["defrace"])+food($r["rein1casualties"],$r["rein1race"])+food($r["rein2casualties"],$r["rein2race"]);
            } else {                                            # Player is defender
                $defends++;
                $rkills += costr($r["attcasualties"],$r["attrace"]);
                $kills += food($r["attcasualties"],$r["attrace"]);
                if (!$r["defcasualties"]) continue;             # Scouted
                $losses += array_sum(explode(',',$r['bounty']));# Resources given up
                $rdeaths += costr($r["defcasualties"],$r["defrace"])+costr($r["rein1casualties"],$r["rein1race"])+costr($r["rein2casualties"],$r["rein2race"]);
                $deaths += food($r["defcasualties"],$r["defrace"])+food($r["rein1casualties"],$r["rein1race"])+food($r["rein2casualties"],$r["rein2race"]);
            }
        }
        $total = array_sum($bounty);
        if ($efficiency > 0) $efficiency /= $attacks-$scouts;
        $bounty = implode(',',$bounty);
        $sql = '("'.$p.'","'.$d["day"].'",'.$attacks.','.$scouts.','.$defends.',"'.$bounty.'",'.$total.',';
        $sql .= $losses.',"'.sprintf('%01.2f',$efficiency).'",'.$rkills.','.$rdeaths.','.$kills.','.$deaths.',"';
        if ($total > 0) $sql .= sprintf('%01.2f',($total-$rdeaths)*100/$total);
        else $sql .= '-';
        $sql .= '")';
        echo $sql.'<br>';
        mysql_query('replace s4_us_stats values '.$sql);
    }
}
?>

</body>
</html>
