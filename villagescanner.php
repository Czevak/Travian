<?php
#############################################################################
# villagescanner.php                                                        #
#   Updates inactivity list. (run daily)                                    #
#############################################################################

# Connect
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax') or die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn) or die('Database error: '.mysql_error());

# Get all uids
$result = mysql_query('select uid, sum(pop) pop from s4_us_villages group by uid');
mysql_query('truncate s4_us_inactives');
# For each player
while ($r = mysql_fetch_assoc($result)) {
    # Get the number of days player has been inactive
    $inactive = mysql_fetch_assoc(mysql_query('select datediff(date(timestampadd(HOUR,3,now())),day) inactive from s4_us_villages_snap where uid='.$r['uid'].' and day!=date(timestampadd(HOUR,3,now())) group by day having sum(pop)<'.$r['pop'].' order by day desc limit 1'));
    $inactive = max($inactive['inactive']-1,0);
    mysql_query('replace s4_us_inactives select v.player,'.$r['uid'].','.$inactive.','.$r['pop'].',v.aid,v.alliance from (select player, aid, alliance from s4_us_villages where uid='.$r['uid'].') v');
}

# Get rid of defense ops that are expired
$result = mysql_query('select id from s4_us_rally where type in (2,3) group by id having max(landtime)<timestampadd(hour,3,now())');
while ($r = mysql_fetch_assoc($result)) mysql_query('delete from s4_us_rally where id="'.$r['id'].'"');

?>