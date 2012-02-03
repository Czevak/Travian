<?php
include 'common.php';   # Load common functions

# Connect
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax') or die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn);

# Call to update weekly rankings
if (isset($_GET['rankings'])) {
    mysql_query('replace w_attacks select @row:=@row+1 rank, u.* from (select attplayer,count(id) from s4_us_reports where type in (1,8,10,18) and attaid in ('.implode(',',$const_wings).') and week(date(time),3) = week(date(timestampadd(hour,3,now())),3) and attplayer in (select player from v_s4_us_alliance) and attplayer!=defplayer and id>0 group by attplayer order by count(id) desc) u, (select @row:=0) r');
    mysql_query('replace w_stolen select @row:=@row+1 rank, u.* from (select attplayer,sum(total) from s4_us_reports where type in (1,8,10,18) and attaid in ('.implode(',',$const_wings).') and week(date(time),3) = week(date(timestampadd(hour,3,now())),3) and attplayer in (select player from v_s4_us_alliance) and attplayer!=defplayer and id>0 group by attplayer order by sum(total) desc) u, (select @row:=0) r');
    mysql_query('replace w_razed select @row:=@row+1 rank, u.*  from (select s.attplayer,s.count+ifnull(t.count,0) razed from (select count(id) count, attplayer from s4_us_reports where (info like "%been destroyed%" or info like "%been destroyed%completely%") and attaid in ('.implode(',',$const_wings).') and week(date(time),3) = week(date(timestampadd(hour,3,now())),3) and attplayer in (select player from v_s4_us_alliance) and id>0 group by attplayer) s left join (select count(id) count, attplayer from s4_us_reports where info like "%been destroyed%,%been destroyed%" and week(date(time),3) = week(curdate(),3) and attplayer in (select player from v_s4_us_alliance) group by attplayer) t on s.attplayer=t.attplayer order by razed desc) u, (select @row:=0) r');
    mysql_query('replace w_pop select @row:=@row+1 rank, u.*  from (select w.player, v.pop-w.pop growth from (select player, sum(pop) pop from s4_us_villages_snap where day=date_sub(curdate(), interval replace(dayofweek(date(timestampadd(hour,3,now())))-2,"-1","6") day) and aid in (401,450,1230) group by player) w join (select y.player, sum(x.pop) pop from (select uid, max(pop) pop from s4_us_pop where aid in (401,450,1230) group by karte) x join (select * from v_s4_us_alliance) y on x.uid=y.uid group by x.uid order by pop desc) v on w.player=v.player order by growth desc) u, (select @row:=0) r');
    mysql_query('replace w_army select @row:=@row+1 rank, u.*  from (select attplayer, max(attunitsfood) army from s4_us_reports where attunitsfood is not null and attaid in ('.implode(',',$const_wings).') and week(date(time),3)=week(date(timestampadd(hour,3,now())),3) group by attplayer order by army desc) u, (select @row:=0) r');
    mysql_query('replace w_kills select @row:=@row+1 rank, u.*  from (select attplayer, sum(defcasualtiesfood)+sum(rein1casualtiesfood)+sum(rein2casualtiesfood) kills from s4_us_reports where attaid in ('.implode(',',$const_wings).') and week(date(time),3)=week(date(timestampadd(hour,3,now())),3) and attplayer!=defplayer and id>0 group by attplayer order by kills desc) u, (select @row:=0) r');
    file("http://travian.ulrezaj.com/?h=2");
    echo "Rankings updated";
    exit;
}

if (isset($_GET['p'])) {    # Request was made for a single player for today
    # Single player
    if ($_GET['p'] != "0") $players = mysql_query('select "'.$_GET['p'].'" player, date(timestampadd(HOUR,3,now())) day');
} else {                    # Normal call; get all players for yesterday
    $players = mysql_query('select distinct(player) player, date(timestampadd(HOUR,-21,now())) day from s4_us_villages where aid in (401,450,1230) order by player');
}
while ($p = mysql_fetch_assoc($players)) {
    $day = $p['day'];
    $p = $p['player'];
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
    $stats = mysql_query('select * from s4_us_reports where (attplayer="'.$p.'" or defplayer="'.$p.'") and date(time)="'.$day.'"');
    while ($r = mysql_fetch_assoc($stats)) {
        if (strpos($r["attacker"], $p." from") === 0) {     # Player is attacker
            $attacks++;
            $rdeaths += costr($r["attcasualties"],$r["attrace"]);
            $deaths += food($r["attcasualties"],$r["attrace"]);
            if (check_scouts($r['attunits'],$r['attrace'])) {   # Scout report
                $scouts++;
                continue;
            }
            $b = explode(',',$r['bounty']);                 # Bounty
            for ($i=0; $i<4; $i++) $bounty[$i] += $b[$i];
            if (carry($r["attunits"],$r["attrace"])!=0) $efficiency += sprintf('%01.2f',array_sum($b)*100/carry($r["attunits"],$r["attrace"]));
            $rkills += costr($r["defcasualties"],$r["defrace"])+costr($r["rein1casualties"],$r["rein1race"])+costr($r["rein2casualties"],$r["rein2race"]);
            $kills += food($r["defcasualties"],$r["defrace"])+food($r["rein1casualties"],$r["rein1race"])+food($r["rein2casualties"],$r["rein2race"]);
        } else {                                            # Player is defender
            $defends++;
            $rkills += costr($r["attcasualties"],$r["attrace"]);
            $kills += food($r["attcasualties"],$r["attrace"]);
            if (!$r["defcasualties"]) continue;             # Scouted
            $losses += array_sum(explode(',',$r['bounty']));    # Resources given up
            $rdeaths += costr($r["defcasualties"],$r["defrace"])+costr($r["rein1casualties"],$r["rein1race"])+costr($r["rein2casualties"],$r["rein2race"]);
            $deaths += food($r["defcasualties"],$r["defrace"])+food($r["rein1casualties"],$r["rein1race"])+food($r["rein2casualties"],$r["rein2race"]);
        }
    }
    $total = array_sum($bounty);
    if ($efficiency > 0) $efficiency /= $attacks-$scouts;
    $bounty = implode(',',$bounty);
    if (isset($_GET['p'])) $day = substr(str_replace('-','/',$day),5);
    $sql = '"'.$p.'","'.$day.'",'.$attacks.','.$scouts.','.$defends.',"'.$bounty.'",'.$total.',';
    $sql .= $losses.',"'.sprintf('%01.2f',$efficiency).'",'.$rkills.','.$rdeaths.','.$kills.','.$deaths.',"';
    if ($total > 0) $sql .= sprintf('%01.2f',($total-$rdeaths)*100/$total);
    else $sql .= '-';
    $sql .= '"';
    if (isset($_GET['p'])) {
        echo $sql;
    } else {
        echo $sql.'<br>';
        mysql_query('replace s4_us_stats values ('.$sql.')');
    }
}
?>