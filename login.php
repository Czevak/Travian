<?
error_reporting(E_ERROR);
# Checks against hqauthenticate - called by user, and sets session
$cookieurl = ".travian.ulrezaj.com";
$authenticationurl = "http://www.ituroncavalry.com/forums/hqauthenticate.php";
session_start();

function cipher($str) {
    $encryptedstr='';
    for ($i=0; $i<strlen($str); $i++){
        $key = (215+((strlen($str)+$i)+1)) % 255;
        $xored = ord(substr($str,$i,1)) ^ $key;
        $encryptedstr .= chr($xored);
       }
    return $encryptedstr;
}
function urldecrypt($str) {
    $str = pack('H*',$str);
    return cipher($str);
}
function urlencrypt($str) {
    return bin2hex(cipher($str));
}

# Logout
if (isset($_GET['logout'])) {
    $_SESSION = array();                                        # Destroy session variables
    setcookie (session_name(), '', time()-42000, '/');          # Destroy session id cookie
    setcookie ("member_id", '', time()-42000, '/',$cookieurl);  # Destroy member_id cookie
    setcookie ("pass_hash", '', time()-42000, '/',$cookieurl);  # Destroy pass_hash cookie
    setcookie ("hqprefs", '', time()-42000, '/',$cookieurl);    # Destroy hqprefs cookie
    session_destroy();                                          # Destroy session
    
    # Logout page html
    echo '<html><head><title>HQ logout successful</title><link rel="stylesheet" type="text/css" href="http://travian.ulrezaj.com/global.css" /></head>';
    echo '<body><br><br><br><br>';
    echo '<table class="tbg" style="width: 496px" cellspacing="1" cellpadding="2" align="center">';
    echo '<tr><td><br><br><br><br>You have successfully logged out of the HQ.<br>';
    echo 'It is recommended that you close your browser<br>to ensure maximum security.<br><br>';
    echo 'To return you must <a href="login.php">reauthenticate</a>.<br><br><br><br><br></td></tr></table></body></html>';
    exit;
}

# Authentication failed for whatever reason
function failexit(){
    $_SESSION = array();                                        # Destroy session variables
    setcookie (session_name(), '', time()-42000, '/');          # Destroy session id cookie
    setcookie ("member_id", '', time()-42000, '/',$cookieurl);  # Destroy member_id cookie
    setcookie ("pass_hash", '', time()-42000, '/',$cookieurl);  # Destroy pass_hash cookie
    setcookie ("hqprefs", '', time()-42000, '/',$cookieurl);    # Destroy hqprefs cookie
    session_destroy();                                          # Destroy session
    # HTTP 401 header
    Header("WWW-authenticate: basic realm=\"EHJ members only\"");
    Header("HTTP/1.0 401 Unauthorized");
    # Failure page html
    echo '<html><head><title>HQ login failure</title><link rel="stylesheet" type="text/css" href="http://travian.ulrezaj.com/global.css" /></head>';
    echo '<body><br><br><br><br>';
    echo '<table class="tbg" style="width: 496px" cellspacing="1" cellpadding="2" align="center">';
    echo '<tr><td><br><br><br><br>HQ login was unsuccessful.<br>';
    echo 'You may attempt to <a href="login.php">reauthenticate</a>.<br><br><br><br><br></td></tr></table></body></html>';
    exit;
}

# If no PHP_AUTH_USER and no cookie, fail
#if (!$_SERVER['PHP_AUTH_USER'] and !$_COOKIE['member_id']) failexit();

# If cookie exists, validate against IPB db
if ($_COOKIE['member_id']) {
    $url = $authenticationurl.'?member_id='.urlencrypt($_COOKIE['member_id']).'&pass_hash='.urlencrypt($_COOKIE['pass_hash']);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $response = unserialize(urldecrypt(curl_exec($ch))) or failexit();
    curl_close($ch);
    
    # Successful login
    
    # Set/overwrite session variables
    $_SESSION['pa_user'] = $response['name'];
    $_SESSION['pa_pass'] = $response['converge_pass_hash'];
    
# If we don't have any cookies, validate PHP_AUTH_USER
} else {
    # If user session variable is missing, generate it
    if (!isset($_SESSION['pa_user'])){
        $_SESSION['pa_user'] = str_replace("'","&#39;",$_SERVER['PHP_AUTH_USER']);
        $_SESSION['pa_user'] = mysql_escape_string($_SESSION['pa_user']);
    }
    $url = $authenticationurl.'?pa_user='.urlencrypt($_SESSION['pa_user']).'&PHP_AUTH_PW='.urlencrypt($_SERVER['PHP_AUTH_PW']);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $response = unserialize(urldecrypt(curl_exec($ch))) or failexit();
    curl_close($ch);

    # If pass session variable isn't set, generate it IPB-style from salt and PHP_AUTH_PW
    if (!isset($_SESSION['pa_pass'])) $_SESSION['pa_pass'] = $SESSION['pa_pass'];
    # Session pass and converge pass hashes should match now - generate cookies
    setcookie ("member_id", $response['id'], time()+60*60*24*365, "/", $cookieurl);
    setcookie ("pass_hash", $response['member_login_key'], time()+60*60*24*365, "/", $cookieurl);

    # Successful login
}

# Store user group in session
$_SESSION['pa_mgroup'] = $response['mgroup'];
# However, if group isn't 30, fail
if ($_SESSION['pa_mgroup']!='30' && $_SESSION['pa_mgroup']!='34') failexit();

# Successful authentication - proceed
$_SESSION['authenticated'] = 1;
$redirect = (isset($_GET['redirect'])) ? $_GET['redirect'] : '';
echo '<meta http-equiv=refresh content="0;URL=http://travian.ulrezaj.com'.str_replace('hq.php','',$redirect).'">';

$fp = fopen('txt314159','a');
fwrite($fp,date('y-m-d H:i:s ',time()).$_SESSION['pa_user'].','.$_SESSION['pa_mgroup'].",".$_SERVER['REMOTE_ADDR']."\n");
?>
