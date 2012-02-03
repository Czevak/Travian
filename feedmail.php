{\rtf1\ansi\ansicpg1252\deff0\deflang1033{\fonttbl{\f0\fswiss\fcharset0 Arial;}}
{\*\generator Msftedit 5.41.15.1515;}\viewkind4\uc1d\f0\fs20 <style type="text/css">
  .label{
    text-align:right;
  }
  #submit{
    text-align:center;
  }
</style>
<?php
  $to='webmaster@jensenresearch.com';
  $messageSubject='Jensen Research Feedback';
  $confirmationSubject='Jensen Research Feedback';
  $confirmationBody="Thank you for your interest.";
  $email='';
  $body='';
  $displayForm=true;
  if ($_POST){
    $email=stripslashes($_POST['email']);
    $body=stripslashes($_POST['body']);
    // validate e-mail address
    $valid=eregi('^([0-9a-z]+[-._+&])*[0-9a-z]+@([-0-9a-z]+[.])+[a-z]{2,6}$',$email);
    $crack=eregi("(\\r|\\n)(to:|from:|cc:|bcc:)",$body);
    if ($email && $body && $valid && !$crack){
      if (mail($to,$messageSubject,$body,'From: '.$email."\\r\\n")
          && mail($email,$confirmationSubject,$confirmationBody.$body,'From: '.$to."\\r\\n")){
        $displayForm=false;
?>
<p>
  Your message was successfully sent.
  Your message is shown below.
</p>
<?php
        echo '<p>'.htmlspecialchars($body).'</p>';
      }else{ // the messages could not be sent
?>
<p>
  Something went wrong when the server tried to send your message.
  This is usually due to a server error, and is probably not your fault.
  We apologise for any inconvenience caused.
</p>
<?php
      }
    }else if ($crack){ // cracking attempt
?>
<p><strong>
  Your message contained e-mail headers within the message body.
  This seems to be a cracking attempt and the message has not been sent.
</strong></p>
<?php
    }else{ // form not complete
?>
<p><strong>
  Your message could not be sent.
  You must include both a valid e-mail address and a message.
</strong></p>
<?php
    }
  }
  if ($displayForm){
?>
<?php
  }
?>
<script>
<!--
timeout = '5000'; // milliseconds/1000th of a sec
window.onload = setTimeout(myRedirect, timeout); // ensure we load the whole page

function myRedirect() {

window.location = "http://www.jensenresearch.com";
}
//-->
</script>
}
