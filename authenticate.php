<?
$cookieurl = ".ulrezaj.com";
session_start();
function cleanexit(){
    session_unset();
    session_destroy();
    Header("WWW-authenticate: basic realm=\"Ulrezaj's Realm\"");
    Header("HTTP/1.0 401 Unauthorized");
    include "error.php";
    mysql_free_result($pa->db->result);
    mysql_close($pa->db->link);
    exit;
}
function normalexit() {
    #mysql_free_result($pa->db->result);
    #mysql_close($pa->db->link);
    echo 'Not so fail eh?';
    flush();
}

if(!$_SERVER['PHP_AUTH_USER'] and !$_COOKIE['member_id']) {
    Header("WWW-authenticate: basic realm=\"Ulrezaj's Realm\"");
    Header("HTTP/1.0 401 Unauthorized");
    # include "http://travian.ulrezaj.com/error.php";
    echo "YOU ERROR FAIL";
    exit;
} else {
    $pa->db->name = "ulrezaj2_jap";
    $pa->db->host = "localhost";
    $pa->db->user = "ulrezaj2_admin";
    $pa->db->password = "tyrax";
    $pa->db->link = mysql_connect($pa->db->host, $pa->db->user, $pa->db->password) or die("Database Error [ref=1]");
    mysql_select_db($pa->db->name) or die("Database Error [ref=2]");
    if ($_COOKIE['member_id']) {
        $pa->db->query = "SELECT * FROM `ibf_members` WHERE `id` = " . $_COOKIE['member_id'];
        $pa->db->result = mysql_query($pa->db->query) or die("Database Error [ref=3]");
        $pa->db->val = mysql_fetch_object($pa->db->result);
        $pa->db->query = "SELECT * FROM `ibf_members_converge` WHERE `converge_id` = " . $pa->db->val->id;
        $pa->db->result = mysql_query($pa->db->query) or die("Database Error [ref=4]");
        $pa->db->val2 = mysql_fetch_object($pa->db->result);
        if ($pa->db->val->member_login_key != $_COOKIE['pass_hash']){
            setcookie ("member_id", "0", time() - 3600, "/", "$cookieurl");
            setcookie ("pass_hash", "0", time() - 3600, "/", "$cookieurl");
            cleanexit();
        }
        if (!isset($_SESSION['pa_user'])){
            $_SESSION['pa_user'] = $pa->db->val->name;
        }
        if (!isset($_SESSION['pa_pass'])){
            $_SESSION['pa_pass'] = $pa->db->val2->converge_pass_hash;
        }
        if (!isset($_SESSION['pa_ip'])){
            //$_SESSION['pa_ip'] = md5($_SERVER['REMOTE_ADDR']);
        }
    } else {
        if (!isset($_SESSION['pa_user'])){
            $_SESSION['pa_user'] = str_replace("'","&#39;",$_SERVER['PHP_AUTH_USER']);
            $_SESSION['pa_user'] = mysql_escape_string($_SESSION['pa_user']);
        }
        if (!isset($_SESSION['pa_ip'])){
            //$_SESSION['pa_ip'] = md5($_SERVER['REMOTE_ADDR']);
        }
        $pa->db->query = "SELECT * FROM `ibf_members` WHERE `name` = '" . $_SESSION['pa_user'] . "';";
        $pa->db->result = mysql_query($pa->db->query) or die("Database Error [ref=4]");
        $pa->db->val = mysql_fetch_object($pa->db->result);
        $pa->db->query = "SELECT * FROM `ibf_members_converge` WHERE `converge_id` = " . $pa->db->val->id;
        $pa->db->result = mysql_query($pa->db->query) or cleanexit();
        $pa->db->val2 = mysql_fetch_object($pa->db->result);
        if (!isset($_SESSION['pa_pass'])){
            $_SESSION['pa_pass'] = md5(md5($pa->db->val2->converge_pass_salt).md5($_SERVER['PHP_AUTH_PW']));
        }
        if ($pa->db->val2->converge_pass_hash == $_SESSION['pa_pass']){
            setcookie ("member_id", $pa->db->val->id, time() + 60*60*24*365, "/", "$cookieurl");
            setcookie ("pass_hash", $pa->db->val->member_login_key, time() + 60*60*24*365, "/", "$cookieurl");
            include '../travian/hq.php';
            flush();
        }
    }
    
    //$pa_ip1 = md5($_SERVER['REMOTE_ADDR']);
    if($pa->db->val2->converge_pass_hash == $_SESSION['pa_pass']) {
    // and $pa_ip1 == $_SESSION['pa_ip']
        if ($pa->db->val->mgroup == 5){
            $_SESSION['pa_level'] = 1;
        }elseif($pa->db->val->mgroup == 6){
            $_SESSION['pa_level'] = 2;
        }elseif($pa->db->val->mgroup == 7 or $pa->db->val->mgroup == 4 or $pa->db->val->mgroup == 9 or $pa->db->val->mgroup == 11 or $pa->db->val->mgroup == 13){
            $_SESSION['pa_level'] = 3;
        }else{
            $_SESSION['pa_level'] = -1;
        }
    }else{
        cleanexit();
    }

    flush();
}
?>
