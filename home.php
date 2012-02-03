<?php
#require 'auth_header.php';  # Require that user be authenticated

# Connect
$conn = mysql_connect('localhost', 'ulrezaj2_admin', 'tyrax') or die('Could not connect: '.mysql_error());
mysql_select_db('ulrezaj2_travian', $conn);

?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="global.css" />
<link rel="stylesheet" href="windowfiles/dhtmlwindow.css" type="text/css" />
<script type="text/javascript" src="windowfiles/dhtmlwindow.js"></script>
<link rel="stylesheet" href="windowfiles/modal.css" type="text/css" />
<script type="text/javascript" src="windowfiles/modal.js"></script>

<script type="text/javascript">
// Autocomplete function for form
function autoC(field, select, property, forcematch) {
  var found = false;
  for (var i = 0; i < select.options.length; i++) {
    if (select.options[i][property].toUpperCase().indexOf(field.value.toUpperCase()) == 0) {
      found=true;
      break;
    }
  }
  if (found) { select.selectedIndex = i; }
  else { select.selectedIndex = -1; }
  if (field.createTextRange) {
    if (forcematch && !found) {
      field.value=field.value.substring(0,field.value.length-1); 
      return;
    }
    var cursorKeys ="8;46;37;38;39;40;33;34;35;36;45;";
    if (cursorKeys.indexOf(event.keyCode+";") == -1) {
      var r1 = field.createTextRange();
      var oldValue = r1.text;
      var newValue = found ? select.options[i][property] : oldValue;
      if (newValue != field.value) {
        field.value = newValue;
        var rNew = field.createTextRange();
        rNew.moveStart('character', oldValue.length) ;
        rNew.select();
      }
    }
  }
}
// Extract report id from report link
function report() {
  obj = document.getElementById("reportform");
  if (!obj.i.value) return false;
  if (isNaN(obj.i.value)) obj.i.value = obj.i.value.split("=")[1];
  obj.submit();
}
// Player submit hook
function player() {
  obj = document.getElementById("playerform");
  if (obj.p.value=="- Select -") return false;
  obj.inp.parentNode.removeChild(obj.inp);
  obj.submit();
}
// Extract karte from village link
function village() {
  obj = document.getElementById("villageform");
  if (obj.k.value==""&&obj.x.value=="") return false;
  if (obj.k.value !="") {
    obj.k.value = obj.k.value.split("d=")[1];
    obj.c.value = obj.k.value.split("&c=")[1];
    obj.k.value = obj.k.value.split("&c=")[0];
    obj.x.parentNode.removeChild(obj.x);
    obj.y.parentNode.removeChild(obj.y);
  } else obj.k.parentNode.removeChild(obj.k);
  obj.submit();
}

// CP calculator
function cp() {
  obj = document.getElementById("cp");
  if (!obj.c1.value || !obj.c2.value || isNaN(Number(obj.c1.value)) || isNaN(Number(obj.c2.value))) {
    alert('Enter valid current CP and CP/day values');
    return;
  }
  exp = new Array(0,2,8,20,39,65,99,141,191,251,319,397,486,584,692,811,941,1082,1234,1397,1572,1759,1957,2168,2391,2627,2874,3135,3409,3695,3995,4308,4634,4974,5347,5695,6076,6471,6881,7304,7742,8195,8662,9143,9640,10151,10677,11219,11775,12347);
  for (var i=0; exp[i]*1000<obj.c1.value; i++){}    // Find the next expansion target
  var current = obj.c1.value*1 + obj.c3.value*500 + obj.c4.value*2000;    // Current CP with parties
  var day = new Date();
  // InnerHTML for popup
  cphtml = '<table class="tbg" style="width: 496px" cellspacing="1" cellpadding="2" align="center">';
  cphtml += '<tr class="rbg"><td rowspan="2" width="50">Village</td><td colspan="2">Culture points</td><td colspan="2"><img src="img/clock2.gif" /></td></tr>';
  cphtml += '<tr class="rbg"><td>Total</td><td>Needed</td><td>Expansion date</td><td><img src="img/clock2.gif" /></td></tr>';
  for (var j=0; j<4; j++) {
    cphtml += '<tr><td>'+(i+1+j)+'</td><td>'+exp[i+j]*1000+'</td><td>'+(exp[i+j]*1000-current)+'</td><td>';
    var diff = ((exp[i+j]*1000-current)/obj.c2.value)*24*3600000;   // Number of millisec till expansion
    day.setTime(day.getTime()+diff);                                // Time of expansion
    cphtml += pad(day.getMonth()+1)+'/'+pad(day.getDate())+' at ';
    cphtml += pad(day.getHours())+':'+pad(day.getMinutes())+'</td><td>';
    var dd = floor(diff/86400000);               // Get # days in diff
    var hh = floor((diff%86400000)/3600000);     // Get # hrs in diff
    var mm = floor((diff%3600000)/60000);        // Get # mins in diff
    cphtml += dd+' days, '+pad(hh)+':'+pad(mm)+'</td></tr>';
  }
  cphtml += '</table>';
  var cpwin = dhtmlwindow.open('cpwin','inline',cphtml,'Expansion Schedule','width=500px,height=134px,top=200px,left=237px,resize=0,scrolling=0');
  return false;
}
// Distance calculator
function dist() {
  obj = document.getElementById("dist");
  var speed = document.getElementById("dist2").speed.value;
  var target = document.getElementById("dist2").target.value;
  if (target == "00:00:00") target = "24:00:00";
  if (target) target = target.split(":")[0]*3600+target.split(":")[1]*60+target.split(":")[2]*1;
  var delta = Math.sqrt(Math.pow(obj.x1.value-obj.x2.value,2)+Math.pow(obj.y1.value-obj.y2.value,2));
  disthtml = '<table class="tbg" style="width: 296px" cellspacing="1" cellpadding="2" align="center">';
  if (speed < 0) {  // Merchant not affected by TS
    speed = -speed;
    disthtml += '<tr class="cbg1"><td colspan="2">Distance: '+Number(delta).toFixed(2)+' squares</td></tr>';
    disthtml += '<tr class="rbg"><td>Race</td><td><img src="img/clock2.gif" /> Travel</td></tr>';
    disthtml += '<tr><td>Teuton - 12/hr</td><td>'+timeformat(delta*3600/12)+'</td></tr>';
    disthtml += '<tr><td>Roman - 16/hr</td><td>'+timeformat(delta*3600/16)+'</td></tr>';
    disthtml += '<tr><td>Gaul - 24/hr</td><td>'+timeformat(delta*3600/24)+'</td></tr>';
    var wheight = "106";
  } else {
    disthtml += '<tr class="cbg1"><td colspan="3">Distance: '+Number(delta).toFixed(2)+' squares</td></tr>';
    disthtml += '<tr class="rbg"><td width="50">TS level</td><td><img src="img/clock2.gif" /> Travel</td><td><img src="img/att4.gif" /> Launch</td></tr>';
    for (var i=0; i<21; i++) {
      disthtml += '<tr><td>'+i+'</td><td>';
      if (delta <= 30) {
        var time = delta*3600/speed;
      } else {
        var time = 30*3600/speed + (delta-30)*3600/(speed*(1+0.1*i))
      }
      disthtml += timeformat(time)+'</td><td>';
      if (target != "") {
        if (target*1 >= time*1) {
          disthtml += timeformat(target-time)+' same day</td></tr>';
        } else {
          disthtml += timeformat((target-time)%86400+86400)+' ';
          days = -floor((target-time)/86400);
          disthtml += days+' day';
          if (days > 1) disthtml += 's';
          disthtml += ' before</td></tr>';
        }
      } else {
        disthtml += ' - </td></tr>';
      }
    }
    var wheight = "500";
  }
  disthtml += '</table>';
  var distwin = dhtmlwindow.open('distwin','inline',disthtml,'Distance Calculator','width=300px,height='+wheight+'px,top=200px,left=337px,resize=0,scrolling=0');
}
function timeformat(inp) { return pad(floor(inp/3600))+':'+pad(floor((inp%3600)/60))+':'+pad(floor(inp%60)); }
function pad(inp) {
  inp = inp.toString();
  if (inp.length == 1) return '0'+inp;
  return inp;
}
function floor(x) { return Math.floor(x); }
function sendprefs(obj) {
  obj.form.submit();
}
function init() {
<?php
# Show welcome window or set cookie if needed
if (!isset($_COOKIE['hqprefs'])) {
    $result = mysql_query('select * from s4_us_hqprefs where login="'.$_SESSION['pa_user'].'"');
    if (!mysql_fetch_assoc($result)) echo "welcomewin = dhtmlmodal.open('welcomewin','iframe','welcomeframe.php','Getting Started','width=550px,height=428px,top=100,left=212,resize=0,scrolling=0');";
}
?>
}
window.onload = init;
</script>
<script type="text/javascript" src="common.js"></script>
</head>
<?php

?>
<body>
<table class="tbg" cellspacing="1" cellpadding="2" align="center">
<tr class="rbg"><td>HQ Central Home</td></tr>
<tr class="cbg1"><td>Welcome to HQ Central, a tool created by Ulrezaj to facilitate EHJ dominance in the S4 server of Travian. 
The links on the left are self-explanatory. If there are any issues, please PM Ulrezaj on the forums.</td></tr>

<tr><td>
<br>

<!-- Analyzers -->
<table cellspacing="1" cellpadding="2" class="tbg">
<tr class="cbg1"><td colspan="4" class="b">Analyzers</td></tr>

<!-- Players -->
<tr><td><b>Players</b></td><td class="s7"><form action="/" onsubmit="player()" id="playerform"><input type="hidden" name="h" value="7">
<input class="fm fm110" type="text" name="inp" value="" onkeyup="autoC(this,this.form.p,'value',false)">
<select class="fm" name="p" onChange="this.form.inp.value=this.options[this.selectedIndex].value" id="players">
<option>- Select -</option>
<?php
$players = mysql_query("select distinct player from s4_us_villages where aid in (401,450,1230) order by player");
while ($r = mysql_fetch_assoc($players)) echo '<option>'.$r['player'].'</option>';
?>
</select></td><td><input type="submit" style="position:absolute; top:-1000px">
<img border="0" src="img/ok1.gif" onmousedown="this.src='img/ok2.gif'" onmouseout="this.src='img/ok1.gif'" onmouseup="this.src='img/ok1.gif'; blur(); player()">
</form></td><td width="180">Search for a player</td></tr>

<!-- Villages -->
<tr><td rowspan="2"><b>Villages</b></td><td class="s7">
<form action="/" onsubmit="village()" id="villageform"><input type="hidden" name="h" value="8">
<input class="fm fm220" type="text" name="k" value=""><input type="hidden" name="c" value="">
<td rowspan="2"><input type="submit" style="position:absolute; top:-1000px">
<img border="0" src="img/ok1.gif" onmousedown="this.src='img/ok2.gif'" onmouseout="this.src='img/ok1.gif'" onmouseup="this.src='img/ok1.gif'; blur(); village()">
</td><td rowspan="2">Paste a village link from the game, or enter coordinates</td></tr>
<tr><td class="s7"><b>x</b> <input class="fm fm25" name="x" value="" size="2" maxlength="4" />
<b>y</b> <input class="fm fm25" name="y" value="" size="2" maxlength="4" /></form></td></tr>

<!-- Battle reports -->
<tr><td><b>Battle reports</b></td><td class="s7">
<form action="/" onsubmit="report()" id="reportform"><input type="hidden" name="h" value="5"><input class="fm fm220" type="text" name="i" value=""></td><td>
<input type="submit" style="position:absolute; top:-1000px">
<img border="0" src="img/ok1.gif" onmousedown="this.src='img/ok2.gif'" onmouseout="this.src='img/ok1.gif'" onmouseup="this.src='img/ok1.gif'; blur(); report()">
</form></td><td>Paste a battle report link from the game</td>
</tr>

</table>
<br><br>

<!-- Calculators -->
<table class="tbg" cellspacing="1" cellpadding="2" align="center">
<tr class="cbg1"><td colspan="4" class="b">Calculators</td></tr>

<!-- CP calculator -->
<tr><td width="50"><b>CP</b></td><td width="400" class="s7"><form id="cp">
Current <input class="fm fm60" type="text" name="c1" value=""> 
CP/Day <input class="fm fm40" type="text" name="c2" value=""> 
Parties: <b>S</b> <input class="fm fm40" type="text" name="c3" value="0"> 
<b>L</b> <input class="fm fm40" type="text" name="c4" value="0"></form></td>
<td><input type="image" border="0" src="img/ok1.gif" onmousedown="this.src='img/ok2.gif'" onmouseout="this.src='img/ok1.gif'" onmouseup="this.src='img/ok1.gif'; blur(); cp()"></td>
<td>Calculate time till next expansion</td></tr>
</tr>

<!-- Distance calculator -->
<tr><td rowspan="3"><b>Travel</b></td><td class="s7"><form id="dist">
Village 1: <b>x</b> <input class="fm fm25" name="x1" value="" size="2" maxlength="4" />
<b>y</b> <input class="fm fm25" name="y1" value="" size="2" maxlength="4" />
Village 2: <b>x</b> <input class="fm fm25" name="x2" value="" size="2" maxlength="4" />
<b>y</b> <input class="fm fm25" name="y2" value="" size="2" maxlength="4" /> </form>
</td>
<td rowspan="3"><input type="image" border="0" src="img/ok1.gif" onmousedown="this.src='img/ok2.gif'" onmouseout="this.src='img/ok1.gif'" onmouseup="this.src='img/ok1.gif'; blur(); dist()"></td>
<td rowspan="3">Calculate travel time<br>with optional target time</td></tr>
<tr><td class="s7"><form id="dist2">
Speed <select class="fm" name="speed">
<option value="3">3 - Catapults</option>
<option value="4">4 - Rams, Chieftains</option>
<option value="5">5 - Praetorians, Settlers</option>
<option value="6">6 - Axemen, Swordsmen, Legionnaires</option>
<option value="7">7 - Macemen, Spearmen, Phalanx, Imperians</option>
<option value="9">9 - Teuton Scouts, Teutonic Knights</option>
<option value="10">10 - Paladins, Equites Caesaris</option>
<option value="13">13 - Haeduans</option>
<option value="14">14 - Equites Imperatoris</option>
<option value="16">16 - Druidriders, Equites Legati</option>
<option value="17">17 - Pathfinders</option>
<option value="19">19 - Theutates Thunders</option>
<option value="-1">Merchants</option>
</select></td></tr>
<tr><td class="s7">Target time: <input class="fm fm60" type="text" name="target" value="">
<i> format: hh:mm:ss (24-hr)</i></form></td></tr>

</table>
<br><br>

<table class="tbg" cellspacing="1" cellpadding="2" align="center">
<tr class="cbg1"><td colspan="2" class="b">News</td></tr>
<?php
# TO DO
# - last seen res/wall level
# - Warroom: largest army
# - add building destruction value to reports
# - wheat summary like beyond
# - Oases don't show up in raids
# - Rally point parser
# - Sandbox
# - Alliance analyzer
# - Hostiles analyzer
# - Army analyzer (part of hostiles?)
# - Improve cartographer - oases %, croppers, beginner's protection

# <tr><td valign="top">06 May 09, 09:37</td><td class="s7">Text</td></tr>
?>
<tr><td valign="top">08 May 09, 12:12</td><td class="s7">HQ is back... for now. They've changed their code twice in the last 24 hours so there's no knowing whether they'll do it again and break the HQ again. Stay tuned!</td></tr>
<tr><td valign="top">07 May 09, 11:02</td><td class="s7">The Travian site layout changes that took place around 10:45 today have broken the HQ. Am working on fixing it, but for now, new BRs will not show up.</td></tr>
<tr><td valign="top">31 Mar 09, 15:30</td><td class="s7">Fixed a bug that was causing AFNE reports not to show up for the last few days.</td>
<tr><td valign="top">30 Mar 09, 16:14</td><td class="s7">Caching implemented! You should notice significant speed increases across the HQ.</td></tr>
<tr><td valign="top">29 Mar 09, 15:48</td><td class="s7">EHJ (the holding alliance) reports will now show up in the HQ starting today.</td></tr>
<tr><td valign="top">06 Mar 09, 16:34</td><td class="s7">Ah, it was nice having a life for the last few weeks. But I guess all good things come to an end, and it's time to get back to coding for you buggers :)<br>
Anyway without further ado, the <a href="/?h=12">Bulwark</a> is open for beta testing. Why beta? Cause you guys never get hit enough to do real QA, so we'll find bugs as we go with this module.</td></tr>
<tr><td valign="top">20 Feb 09, 16:12</td><td class="s7">For various reasons, pink reports are gone. Talk to Ulrezaj if you have any questions.</td></tr>
<tr><td valign="top">19 Feb 09, 13:03</td><td class="s7"><i>Deprecated</i></td></tr>
<tr><td valign="top">18 Feb 09, 23:28</td><td class="s7">Can <b>you</b> figure out what the pink reports are?</td></tr>
<tr><td valign="top">18 Feb 09, 21:14</td><td class="s7">Sorry about the missing scout reports! They're back now.</td></tr>
<tr><td valign="top">17 Feb 09, 10:00</td><td class="s7">After the previous change I realized the icons were really not intuitive at all so I overhauled them. Now attack types share the same icon for both incoming and outgoing, but incomings have a red halo.
<br><br>The updated legend is now:<br>
<img src="img/att_all.gif">/<img src="img/att_allr.gif">: Outbound/Inbound attack <br><img src="img/14.gif">/<img src="img/14r.gif">: Outbound/Inbound scout<br><img src="img/18.gif">/<img src="img/18r.gif">: Outbound/Inbound catapult<br><img src="img/def2.gif">/<img src="img/def2r.gif">: Outbound/Inbound fake</td></tr>
<tr><td valign="top">16 Feb 09, 23:19</td><td class="s7">Fake icon added to reports pages (incl. the Warroom). For those keeping track this brings the icons to 5:
<br><i>Edited out to avoid confusion; see above post</i></td></tr>
<tr><td valign="top">16 Feb 09, 12:01</td><td class="s7">Army tracking has been added to hostile player analysis.</td></tr>
<tr><td valign="top">15 Feb 09, 13:37</td><td class="s7">The <a href="/?h=11">Warroom</a> is up! I didn't have time to complete all the modules I wanted for it this weekend, so for now only the alliance and hostiles analyses are functional.<br><br>
Additionally, the <a href="/?h=3">Cartographer</a> has been modified to mark *13* as hostile reds now.</td></tr>
<tr><td valign="top">13 Feb 09, 17:14</td><td class="s7">The mostly useless Daily troop activity graph has been replaced by a player activity graph (which happens to be a preview of what's going to be in the upcoming Warroom) ;)</td></tr>
<tr><td>10 Feb 09, 16:00</td><td class="s7">Village analysis pages now link to full raid reports for that village.</td></tr>
<tr><td valign="top">09 Feb 09, 11:04</td><td class="s7">The <a href="/?h=2">Stats page</a> is now cached => muchos faster loading. Additionally, the population chart is now <b>realtime</b>.</td></tr>
<tr><td valign="top">09 Feb 09, 00:12</td><td class="s7">The first week of top 10 stats is over! Punch and pie for the winners.<br><br><img src="img/top1.png"></td></tr>
<tr><td valign="top">08 Feb 09, 21:12</td><td class="s7">Added the "Skirmishes" filter to the raid listings. This will filter for reports that have troops losses on either side.</td></tr>
<tr><td valign="top">08 Feb 09, 16:01</td><td class="s7">Changed around all the URLs, meaning, it's time to reset all your HQ bookmarks. All the links should be frame-friendly (ie: transparent) now.<br>
With these changes, you can use the url in your address bar as normal now for links. There's no more need to use the "This report link", even though that remains valid.</td></tr>
<tr><td valign="top">07 Feb 09, 01:24</td><td class="s7">The weird stuff the Cartographer was doing is fixed. The village jump is now fully operational.</td></tr>
<tr><td valign="top">06 Feb 09, 14:23</td><td class="s7">Server went down for 2 hours last night so there's a chunk of data missing. Am working on setting up a backup server.<br><b>If you have any reports you really want up, pm me their links.</b></td></tr>
<tr><td>05 Feb 09, 16:15</td><td class="s7">More Cartographer tweaks. You can now jump to any of your villages.</td></tr>
<tr><td>05 Feb 09, 01:58</td><td class="s7">Villages view added to the <a href="/?h=3">Cartographer</a>. (Click columns to sort)</td></tr>
<tr><td valign="top">04 Feb 09, 14:40</td><td class="s7">Population Growth, Largest Army, and Most Kills metrics added to the <a href="/?h=2">Stats and Rankings</a> page.</td></tr>
<tr><td valign="top">03 Feb 09, 16:36</td><td class="s7">Fixed Razed top 10 stats. Previously it was showing ranking from the entire history instead of just this week's.<br><span class="f8 i">(How did no one notice this before now?)</span></td></tr>
<tr><td valign="top">03 Feb 09, 00:13</td><td class="s7">Weekly rankings added to the alliance stats page (which has been renamed the <a href="/?h=2">Stats and Rankings</a> page). Have fun comparing e-peens!<br><br>The backend reengineering I mentioned earlier allows for far more robust reporting than before. 
Several changes have taken effect in terms of stat calculation, the most important being that fakes no longer count towards any of your ratings, including attack count and efficiency. Other minor tweaks may have changed your stats slightly so do not be surprised if the numbers are a bit different than you remember.</td></tr>
<tr><td>02 Feb 09, 17:47</td><td class="s7">Everything back to normal!</td></tr>
<tr><td valign="top">02 Feb 09, 10:20</td><td class="s7">You may notice some functions are broken while I revamp some backend. They should be back to normal sometime this afternoon.</td></tr>
<tr><td valign="top">01 Feb 09, 17:32</td><td class="s7">The old croplist has been removed and a brand spanking new <a href="/?h=4">Cropfinder</a> has taken its place. Most people probably won't need to use it much, but searching may still come in handy.</td></tr>
<tr><td>31 Jan 09, 20:09</td><td class="s7">Authentication has been activated. Access is now restricted by your forum login.</td></tr>
<tr><td valign="top">30 Jan 09, 02:35</td><td class="s7"><a href="/?h=3">Cartographer</a> enhanced with distance calculators. Click on a square for options. Use the third option (source mark) on a village to set it as the anchor.</td></tr>
<tr><td valign="top">30 Jan 09, 10:31</td><td class="s7">Opera issues fixed. Because giving IE compatability but dropping Opera would be shame to the open community. </td></tr>
<tr><td valign="top">29 Jan 09, 21:49</td><td class="s7">What changed? Most of you won't notice but the people who can magically see this site now would do well to read my <a href="http://www.ituroncavalry.com/forums/index.php?showtopic=33498&view=findpost&p=451827">post on the matter</a>.</td></tr>
<tr><td>29 Jan 09, 11:25</td><td class="s7">Hostile markers are in place in the <a href="/?h=3">Cartographer</a> for weekend preparation</td></tr>
<tr><td valign="top">28 Jan 09, 17:21</td><td class="s7"><a href="/?h=1">Battle reports</a> have been enhanced with a stats page. Now with more details than you can shake a stick at.<br>Additionally, inactivity marker set down to 2 days from 3 in the <a href="/?h=3">Cartographer</a>.</td></tr>
<tr><td>28 Jan 09, 11:38</td><td class="s7">Minor enhancements made to catapult reports</td></tr>
<tr><td valign="top">28 Jan 09, 00:01</td><td class="s7">Due to someone <a href="http://www.ituroncavalry.com/forums/index.php?showtopic=34191&view=findpost&p=451156">throwing down the gauntlet</a>, catapult reporting has been added. Check out any report that is part of a cat wave for a new link!</td></tr>
<tr><td valign="top">27 Jan 09, 09:37</td><td class="s7">If you aren't blind, you probably figured out the new toy is the <a href="/?h=3">Cartographer</a>. Navigation is complete, with delicious AJAX making it one big smooth world. More analysis info to be added soon. Enjoy!</td></tr>
<tr><td>27 Jan 09, 01:49</td><td class="s7">Can YOU spot the awesome new tool that isn't quite finished yet?</td></tr>
<tr><td valign="top">24 Jan 09, 00:36</td><td class="s7">So apparently there are some very, very sad people. (Defends filter added to <a href="/?h=1">alliance reports</a>.)<br>Some of you may have notice the HQ being unavailable today - I completed testing on the authentication module and it will be active soon. Logins coming!</td></tr>
<tr><td valign="top">24 Jan 09, 00:36</td><td class="s7">Filters for <a href="/?h=1">alliance reports</a> are complete. You can filter by player, alliance, date, type, and even geolocation. You are a sad, sad person if you need finer control than this.</td></tr>
<tr><td>23 Jan 09, 02:11</td><td class="s7">Added alliance filter to the <a href="/?h=1">alliance reports</a> page (finally!)</td></tr>
<tr><td>23 Jan 09, 09:37</td><td class="s7">Added scout reports to village analysis page.</td></tr>
<tr><td valign="top">21 Jan 09, 10:24</td><td class="s7">We're missing a chunk of data from EHJ between ~9pm last night and 10am this morning so the data for both days will be a bit skewed. Sorry!</td></tr>
<tr><td valign="top">20 Jan 09, 20:01</td><td class="s7">Fixed an error in alliance efficiency calculation.<br>We're actually more efficient than was displayed by a factor of 2 :X</td></tr>
<tr><td>19 Jan 09, 13:10</td><td class="s7">Added catapult and chief symbols to raid overview</td></tr>
<tr><td valign="top">17 Jan 09, 00:54</td><td class="s7">Ridiculously awesome graphs added to player overviews.<br>You really need to go see them right now.</td></tr>
<tr><td>16 Jan 09, 20:25</td><td class="s7">Optimized stats loading; added more detail</td></tr>
<tr><td>15 Jan 09, 23:08</td><td class="s7">Scout reports are now indicated with a different symbol</td></tr>
<tr><td valign="top">15 Jan 09, 19:42</td><td class="s7">Player filter added to alliance raids overview, and more detail added. More coming!</td></tr>
<tr><td>15 Jan 09, 17:29</td><td class="s7">Added travel time calculator</td></tr>
<tr><td>15 Jan 09, 13:11</td><td class="s7">Added CP calculator</td></tr>
<tr><td>15 Jan 09, 00:55</td><td class="s7">Losses and profit added to player daily summaries</td></tr>
<tr><td>14 Jan 09, 23:32</td><td class="s7">Losses added to player raid summaries</td></tr>
<tr><td>14 Jan 09, 21:52</td><td class="s7">Village and report search added.</td></tr>
<tr><td>14 Jan 09, 16:40</td><td class="s7">Added losses, profit, and efficiency to indiviual raid reports.<br>Links to each report and corresponding travian pages also added.</td></tr>
<tr><td width="120">13 Jan 09, 11:32</td><td class="s7">Home page created, search & navigational tools coming!</td></tr>
</table>
</td></tr>
</table>

</body>
</html>