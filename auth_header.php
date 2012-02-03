<?php
session_start();
# Check authentication
if (!isset($_SESSION['authenticated'])||($_SESSION['pa_mgroup']!='30'&&$_SESSION['pa_mgroup']!='34')) {
    $auth_redirect = $_SERVER['SCRIPT_NAME'];
    if (!empty($_SERVER['QUERY_STRING'])) $auth_redirect .= '?'.$_SERVER['QUERY_STRING'];
    echo '<meta http-equiv=refresh content="0;URL=http://travian.ulrezaj.com/login.php?redirect='.urlencode($auth_redirect).'">';
    exit;
}
# Fix broken prefs cookie
if (isset($_COOKIE['hqprefs'])) {
    $auth_prefs = explode(',',$_COOKIE['hqprefs']);
    if ($auth_prefs[3]!=1 && $auth_prefs[3]!=2 && $auth_prefs[3]!=3) setcookie ("hqprefs", '', time()-42000, "/",".travian.ulrezaj.com");
}

# Check for prefs cookie
if (!isset($_COOKIE['hqprefs'])) {
    $auth_conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax') or die('Could not connect: '.mysql_error());
    mysql_select_db('ulrezaj2_travian', $auth_conn) or die('Database error: '.mysql_error());
    $auth_result = mysql_query('select * from s4_us_hqprefs where login="'.$_SESSION['pa_user'].'"');
    if ($auth_result) {
        if ($auth_r=mysql_fetch_assoc($auth_result))
            setcookie ("hqprefs", $auth_r['ign'].','.$auth_r['x'].','.$auth_r['y'].','.$auth_r['race'], time()+60*60*24*365, "/",".travian.ulrezaj.com");
    }
    mysql_close($auth_conn);
}
?>