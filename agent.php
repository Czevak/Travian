<?php
#############################################################################
# agent.php                                                                 #
#   Common agent for remote queries and updates for various programs.       #
#   Portal to the HQ for external applications                              #
#   Runs with:                                                              #
#       sql     generic request to execute an sql query against the db      #
#       b       raidanalyzer request for existing report IDs to prevent     #
#               duplicate downloads                                         #
#       m, a    raidanalyzer request to upload report IDs to be mined       #
#       c, a    raidanalyzer request to download reports for mining         #
#       mget    stats request to get an alliance's populations              #
#       hname, hget     returns uid, playername pairs                       #
#############################################################################

include 'common.php';

# Explodes sql statements while keeping in mind escaped semicolons
function specialexplode($str) {
    $key = md5(rand());
    $str = str_replace('\;',$key,$str);
    $arr = explode(';',$str);
    foreach ($arr as &$arrelem) {
        $arrelem = str_replace($key,'\;',$arrelem);
    }
    return $arr;
}

# Connect
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax') or die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn);

if (isset($_GET['b'])) {            # request for existing report ids (raidanalyzer)
    # Get list of IDs that already exist; raidanalyzer will diff the lists
    $result = mysql_query("select id from s4_us_reports where id in (".$_GET['b'].")");
    while ($r = mysql_fetch_array($result)) {
        echo $r["id"]."\n";
    }
} elseif (isset($_POST['sql'])) {   # write an sql statement with optional prefix & suffix (general)
    if (isset($_POST['rallyupdate'])) mysql_query('delete from s4_us_rally where (type=4 and id="'.$_POST['id'].'") or (timestampadd(hour,3,now())>landtime and type=1)');
    if (strpos($_POST['sql'],'s4_us_rally')!==false) {
        mysql_query('delete from s4_us_rally where troops="Own troops" and id="'.$_GET['id'].'"');
    }
    $pre = (isset($_POST['pre'])) ? urldecode($_POST['pre']) : "";
    $suf = (isset($_POST['suf'])) ? urldecode($_POST['suf']) : "";
    foreach (specialexplode(urldecode($_POST['sql'])) as $sql) {
        if ($sql) {
            $r = mysql_query(stripslashes($pre." ".$sql." ".$suf.";"));
            if (!$r) echo mysql_error();
        }
    }
    # If this is a report insert, update basesenttime
    if (strpos($_POST['sql'],'s4_us_reports')!==false) {
        $missing = mysql_query('select id,time,attkarte,attunits,attrace,defkarte from s4_us_reports where basesenttime is null and attkarte is not null and defkarte is not null');
        while ($m = mysql_fetch_assoc($missing)){
            $r = mysql_fetch_assoc(mysql_query('select sqrt(pow(v1.x-v2.x,2)+pow(v1.y-v2.y,2)) dist from (select x,y from s4_us_villages where karte="'.$m['attkarte'].'") v1 join (select x,y from s4_us_villages where karte="'.$m['defkarte'].'") v2'));
            if (speed($m['attunits'],$m['attrace'])==0) continue;
            mysql_query('update s4_us_reports set basesenttime=timestampadd(second,-'.($r['dist']*3600/speed($m['attunits'],$m['attrace'])).',time) where id='.$m['id']);
        }
        foreach (array("att","def") as $sec) mysql_query('update s4_us_reports r set '.$sec.'aid = (select aid from s4_us_villages where player=r.'.$sec.'player limit 1) where '.$sec.'player is not null and '.$sec.'aid is null');
    }
    
    # If this is a rally point update, insert additional info
    if (strpos($_POST['sql'],'s4_us_rally')!==false) {
        mysql_query('update s4_us_rally r set player=(select player from s4_us_villages where karte=r.karte) where player is null');
        mysql_query('update s4_us_rally r set race=(select tribe from s4_us_villages where karte=r.karte), x=(select x from s4_us_villages where karte=r.karte), y=(select y from s4_us_villages where karte=r.karte), village=(select village from s4_us_villages where karte=r.karte) where race is null');
        mysql_query('update s4_us_rally set duration=timediff(landtime,time) where (duration is null or duration=0) and type<4');
        mysql_query('update s4_us_rally m set speed=(select sqrt(pow(r.x-v.x,2)+pow(r.y-v.y,2))*3600/time_to_sec(r.duration) from (select x,y,karte,landtime,duration from s4_us_rally where type<4 and id="'.$_POST['id'].'") r join (select x,y,karte,landtime from s4_us_rally where player="Own troops" and id="'.$_POST['id'].'") v where r.karte=m.karte and r.landtime=m.landtime limit 1) where speed is null');
        mysql_query('update s4_us_rally m set distance=(select sqrt(pow(r.x-v.x,2)+pow(r.y-v.y,2)) from (select x,y,karte,landtime,duration from s4_us_rally where type<4 and id="'.$_POST['id'].'") r join (select x,y,karte,landtime from s4_us_rally where player="Own troops" and id="'.$_POST['id'].'") v where r.karte=m.karte and r.landtime=m.landtime limit 1)');
    }
} elseif ($_GET['rkarte']) {        # Find out if an id exists for bulwark and return if so
    $result = mysql_query('select id from s4_us_rally where player="Own troops" and karte="'.$_GET['rkarte'].'&c='.$_GET['c'].'"');
    if ($result && $r=mysql_fetch_assoc($result) && $r['id']) {
        echo $r['id'];
    } else {
        echo '0';
    }
} elseif (isset($_POST['m'])) {     # berichte upload only for single player scans (raidanalyzer)
    foreach (explode(",",$_POST['m']) as $id) {
        mysql_query("replace s4_mining set id=".$id.", alliance=".$_POST['a']);
    }
} elseif (isset($_GET['c'])) {      # collect mined ids to prepare for download  (raidanalyzer)
    $result = mysql_query("select id from s4_mining where alliance=".$_GET['a']);
    while ($r = mysql_fetch_array($result)) {
        echo $r["id"].",";
    }
    mysql_query("delete from s4_mining where alliance=".$_GET['a']);
} elseif ($_GET['hget']) {          # Get a list of pops for an alliance (stats)
    $result = mysql_query('select sum(a.pop) pop, a.uid from s4_us_pop a join (select max(time) time, karte from s4_us_pop where aid in ('.$_GET['hget'].') group by karte) v on v.karte=a.karte and v.time=a.time group by a.uid');
    while ($r = mysql_fetch_array($result)) {
        echo $r['uid'].",".$r['pop'].";";
    }
} elseif ($_GET['hname']) {         # Get a list of names for uids
    $result = mysql_query('select sum(v.pop) pop, v.uid from (select max(time), uid, pop from s4_us_pop where aid='.$_GET['hget'].' group by karte) v group by v.uid');
    while ($r = mysql_fetch_array($result)) {
        echo $r['uid'].",".$r['pop'].";";
    }
}
?>