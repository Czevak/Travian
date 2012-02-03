<?php
#############
# CONSTANTS #
#############

# Cartographer alliance IDs
$const_wings = array(401,450,1230);
$const_naps = array(225);
$const_spy = array(73,281);
# Old squigglies $const_hostiles = array(103,214,584);
$const_hostiles = array(281,774,143,73,196,386,1348,337,130,214,284,224); 
$cookieurl = 'travian.ulrezaj.com';
$const_watching = array(71,73,85,108,143,194,196,217,224,225,248,281,386,401,450,531,615,627,774,838,867,924,1230);
$const_racemap = array(0,'Roman','Teuton','Gaul','Nature','Natar');
if (isset($_COOKIE['hqprefs'])) $hqprefs = explode(',',$_COOKIE['hqprefs']);

$raceheader["Teuton"] = '<td><img src="img/11.gif" title="Maceman"></td><td><img src="img/12.gif" title="Spearman"></td><td><img src="img/13.gif" title="Axeman"></td><td><img src="img/14.gif" title="Scout"></td><td><img src="img/15.gif" title="Paladin"></td><td><img src="img/16.gif" title="Teutonic Knight"></td><td><img src="img/17.gif" title="Ram"></td><td><img src="img/18.gif" title="Catapult"></td><td><img src="img/19.gif" title="Chieftain"></td><td><img src="img/20.gif" title="Settler"></td><td><img src="img/hero.gif" title="Hero"></td>';
$raceheader["Gaul"] = '<td><img src="img/21.gif" title="Phalanx"></td><td><img src="img/22.gif" title="Swordsman"></td><td><img src="img/23.gif" title="Pathfinder"></td><td><img src="img/24.gif" title="Theutates Thunder"></td><td><img src="img/25.gif" title="Druidrider"></td><td><img src="img/26.gif" title="Haeduan"></td><td><img src="img/27.gif" title="Battering Ram"></td><td><img src="img/28.gif" title="Trebuchet"></td><td><img src="img/29.gif" title="Chief"></td><td><img src="img/30.gif" title="Settler"></td><td><img src="img/hero.gif" title="Hero"></td>';
$raceheader["Roman"] = '<td><img src="img/1.gif" title="Legionnaire"></td><td><img src="img/2.gif" title="Praetorian"></td><td><img src="img/3.gif" title="Imperian"></td><td><img src="img/4.gif" title="Equites Legati"></td><td><img src="img/5.gif" title="Equites Imperatoris"></td><td><img src="img/6.gif" title="Equites Caesaris"></td><td><img src="img/7.gif" title="Ram"></td><td><img src="img/8.gif" title="Fire Catapult"></td><td><img src="img/9.gif" title="Senator"></td><td><img src="img/10.gif" title="Settler"></td><td><img src="img/hero.gif" title="Hero"></td>';
$raceheader["Nature"] = '<td><img src="img/31.gif" title="Rat"></td><td><img src="img/32.gif" title="Spider"></td><td><img src="img/33.gif" title="Snake"></td><td><img src="img/34.gif" title="Bat"></td><td><img src="img/35.gif" title="Wild Boar"></td><td><img src="img/36.gif" title="Wolf"></td><td><img src="img/37.gif" title="Bear"></td><td><img src="img/38.gif" title="Crocodile"></td><td><img src="img/39.gif" title="Tiger"></td><td><img src="img/40.gif" title="Elephant"></td>';
$raceheader["Natar"] = '<td><img src="img/41.gif" title="Pikeman"></td><td><img src="img/42.gif" title="Thorned Warrior"></td><td><img src="img/43.gif" title="Guardsman"></td><td><img src="img/44.gif" title="Birds of Prey"></td><td><img src="img/45.gif" title="Axerider"></td><td><img src="img/46.gif" title="Natarian Knight"></td><td><img src="img/47.gif" title="War Elephant"></td><td><img src="img/48.gif" title="Ballista"></td><td><img src="img/49.gif" title="Natarian Emperor"></td><td><img src="img/50.gif" title="Settler"></td>';

#############
# CONSTANTS #
#############

# Print fail page
function failpage($msg) {
    $out = '<br><br><br><br>';
    $out .= '<table class="tbg" style="width: 496px" cellspacing="1" cellpadding="2" align="center">';
    $out .= '<tr><td><br><br><br><br>'.$msg.'<br><br><br><br><br></td></tr></table></body></html>';
    echo $out;
    die;
}

# map wraparound functions
function inc($a,$n) { return ($a+$n <= 400) ? $a+$n : $a+$n-801; }
function dec($a,$n) { return ($a-$n >= -400) ? $a-$n : $a-$n+801; }
function add($a,$n) { return ($n > 0) ? inc($a,$n) : dec($a,-$n); }
function incrange($a,$b) {
    $i = $a;
	$j = inc($b,1);
    while ($i != $j) {
        $out[] = $i;
        $i = inc($i,1);
	}
    return $out;
}

# Returns whether two opposing aids constitute a spy report or not
function spyreport($attaid,$defaid) {
    global $const_wings, $const_naps, $const_spy;
    if ($attaid=="" or $defaid=="") return false;
    if (in_array($attaid,$const_spy) && !in_array($defaid,$const_wings)) return true;
    if (in_array($defaid,$const_spy) && !in_array($attaid,$const_wings)) return true;
    return false;
}

# separates "player from the village wherever" -> wherever
function where($inp) {
    $out = explode(" from the village ", $inp);
    return $out[1];
}

# explodes units into array
function unitct($inp) {
    if (strpos($inp,",")) return explode(",",$inp);
    else return array($inp);
}

# Colours a unit string appropriately and adds <td> tags
function unitclr($inp) {
    $out = '';
    foreach ($inp as $i) $out .= ($i=='0'||$i=='?') ? '<td class="c">'.$i.'</td>' : '<td>'.$i.'</td>';
    return $out;
}

# returns food consumption of a unit string
function food($units,$race) {
    if (!$units || !$race) return 0;
    $units = unitct($units);
    $food = array(array(1,1,1,1,2,3,3,6,4,1,5),array(1,1,2,2,2,3,3,6,4,1,5),array(1,1,1,2,3,4,3,6,5,1,5),array(1,1,1,1,2,2,3,3,3,5));
    $racemap = array("Teuton"=>0,"Gaul"=>1,"Roman"=>2,"Nature"=>3,"Natar"=>1);
    $total = 0;
    for ($i=0; $i<count($units); $i++)
        $total += $units[$i] * $food[$racemap[$race]][$i];
    return $total;
}

# returns carry capacity of a unit string (assumed mace, TT, imp heroes)
function carry($units,$race) {
    if (!$units || !$race) return 0;
    $units = unitct($units);
    $food = array(array(60,40,50,0,110,80,0,0,0,3000,80),array(30,45,0,75,35,65,0,0,0,3000,75),array(40,20,50,0,100,70,0,0,0,3000,70));
    $racemap = array("Teuton"=>0,"Gaul"=>1,"Roman"=>2);
    $total = 0;
    for ($i=0; $i<count($units); $i++)
        $total += $units[$i] * $food[$racemap[$race]][$i];
    return $total;
}

# returns resource cost of a unit string as an array
function cost($units,$race) {
    if (!$units || !$race || $race=="Nature" || $race=="Natar") return array(0,0,0,0);
    $units = unitct($units);
    # Teuton, Gaul, Roman costs
    $cost[] = array(array(95,75,40,40),array(145,70,85,40),array(130,120,170,70),array(160,100,50,50),array(370,270,290,75),array(450,515,480,80),array(1000,300,350,70),array(900,1200,600,60),array(35500,26600,25000,27200),array(7200,5500,5800,6500),array(450,515,480,80));
    $cost[] = array(array(100,130,55,30),array(140,150,185,60),array(170,150,20,40),array(350,450,230,60),array(350,330,280,120),array(500,620,675,170),array(950,555,330,75),array(960,1450,630,90),array(30750,45400,31000,37500),array(5500,7000,5300,4900),array(350,450,230,60));
    $cost[] = array(array(120,100,180,40),array(100,130,160,70),array(150,160,210,80),array(140,160,20,40),array(550,440,320,100),array(550,640,800,180),array(900,360,500,70),array(950,1350,600,90),array(30750,27200,45000,37500),array(5800,5300,7200,5500),array(550,440,320,100));
    $racemap = array("Teuton"=>0,"Gaul"=>1,"Roman"=>2);
    $total = array(0,0,0,0);
    for ($i=0; $i<count($units); $i++) {
        for ($k=0; $k<4; $k++)
            $total[$k] += $units[$i] * $cost[$racemap[$race]][$i][$k];
    }
    return $total;
}

# returns resource cost of a unit string as a total
function costr($units,$race) {
    if (!$units || !$race || $race=="Nature" || $race=="Natar") return 0;
    return array_sum(cost($units,$race));
}

# returns speed of a unit string (ie: slowest unit)
function speed($units,$race) {
    if (!$units || !$race) return 0;
    $units = unitct($units);
    $velocity = array(array(7,7,6,9,10,9,4,3,4,5,9),array(7,6,17,19,16,13,4,3,5,5,19),array(6,5,7,16,14,10,4,3,4,5,10));
    $racemap = array("Teuton"=>0,"Gaul"=>1,"Roman"=>2);
    for ($i=0; $i<count($units); $i++) $velocity[$racemap[$race]][$i] += ($units[$i]) ? 1000 : 0;
    return min($velocity[$racemap[$race]]);
}

# Returns infantry and cavalry attack totals from a unit string
function attpoints($units,$race) {
    if (!$units || !$race || $race=="Nature" || $race=="Natar") return array(0,0);
    $units = unitct($units);
    # Teuton, Gaul, Roman attack
    $pts[] = array(40,10,60,0,55,150,65,50,40,10,40);   # cav 4,5
    $pts[] = array(15,65,0,90,45,140,50,70,40,0,90);    # cav 3,4,5
    $pts[] = array(40,30,70,0,120,180,60,75,50,0,70);   # cav 4,5
    $racemap = array("Teuton"=>0,"Gaul"=>1,"Roman"=>2);
    $infatt = 0;
    $cavatt = 0;
    for ($i=0; $i<count($units); $i++) {
        if ($i==4 || $i==5 || ($i==3&&$race=="Gaul")) $cavatt += $units[$i]*$pts[$racemap[$race]][$i];
        else $infatt += $units[$i]*$pts[$racemap[$race]][$i];
    }
    return array($infatt,$cavatt);
}

# Returns infantry and cavalry defense totals from a unit string
function defpoints($units,$race) {
    if (!$units || !$race || $race=="Nature" || $race=="Natar") return array(0,0);
    $units = unitct($units);
    # Teuton, Gaul, Roman infantry defense
    $infpts[] = array(20,35,30,10,100,50,30,60,60,80,20);
    $infpts[] = array(40,35,20,25,115,50,30,45,50,80,25);
    $infpts[] = array(35,65,40,20,65,80,30,60,40,80,40);
    # Teuton, Gaul, Roman cavalry defense
    $cavpts[] = array(5,60,30,5,45,75,80,10,40,80,5);
    $cavpts[] = array(50,20,10,40,55,165,105,10,50,80,40);
    $cavpts[] = array(50,35,25,10,50,105,75,10,30,80,25);
    $racemap = array("Teuton"=>0,"Gaul"=>1,"Roman"=>2);
    $infdef = 0;
    $cavdef = 0;
    for ($i=0; $i<count($units); $i++) {
        $infdef += $units[$i]*$infpts[$racemap[$race]][$i];
        $cavdef += $units[$i]*$cavpts[$racemap[$race]][$i];
    }
    return array($infdef,$cavdef);
}

# Returns total build times @ lvl 20 barracks, stables, workshop
function buildtime($units,$race) {
    if (!$units || !$race || $race=="Nature" || $race=="Natar") return array(0,0);
    $units = unitct($units);
    $build[] = array(122,189,203,189,405,500,567,1216,70500,31000,122);
    $build[] = array(176,243,230,419,432,527,644,1216,90700,26300,419);
    $build[] = array(270,297,324,230,446,594,644,1216,90700,26900,324);
    $racemap = array("Teuton"=>0,"Gaul"=>1,"Roman"=>2);
    $typemap = array(array(0,0,0,0,1,1,2,2,3,3,0),array(0,0,0,1,1,1,2,2,3,3,1),array(0,0,0,1,1,1,2,2,3,3,0));
    $times = array(0,0,0,0);
    for ($i=0; $i<count($units); $i++) {
        $times[$typemap[$racemap[$race]][$i]] += $units[$i]*$build[$racemap[$race]][$i];
    }
    return $times;
}

# Formats seconds into day(s) hh:mm:ss string
function timeformat($s) {
    $out = floor($s/86400).'d ';
    $out .= sprintf('%02d',floor(($s % 86400)/3600)).':';
    $out .= sprintf('%02d',floor(($s % 3600)/60)).':';
    $out .= sprintf('%02d',floor($s % 60));
    return $out;
}


# Determines if an attunits is only scouts
function check_scouts($attunits,$attrace) {
    $units = explode(',',$attunits);
    $racemap = array("Teuton"=>3,"Gaul"=>2,"Roman"=>3,"Natar"=>3);  # Note: array indexes, not unit's position
    for ($i=0; $i<count($units); $i++) {
        if ($i != $racemap[$attrace] && $units[$i] != "0") return false;
    }
    return true;
}

# Determines if an attunits has cats
function check_cats($attunits) {
    $units = explode(',',$attunits);
    if ($units[7] != "0") return true;
    return false;
}

# Determines if an attunits has chiefs
function check_chiefs($attunits) {
    $units = explode(',',$attunits);
    if ($units[8] != "0") return true;
    return false;
}

# Determines if an attunits is a fake
function check_fake($units,$race) {
    if ($units=="0,0,0,0,0,0,0,0,0,1") return false;    # Not fake if settler
    if ($units=="0,0,0,0,0,0,0,0,1,0") return false;    # Not fake if chief
    if ($units=="0,0,0,0,0,0,0,0,0,0,1") return false;  # Not fake if hero
    $units = explode(',',$units);
    if (array_sum($units) > 1) return false;        # Not fake if more than 1 unit of any kind
    if (check_scouts($units,$race)) return false;   # Not fake if it's a scout
    return true;                                    # Otherwise, valid fake
}

# Adds two arrays by element
function array_add($arr1,$arr2) {
    for ($i=0; $i<count($arr2); $i++) {
        $arr1[$i] += $arr2[$i];
    }
    return $arr1;
}
function array_sub($arr1,$arr2) {
    for ($i=0; $i<count($arr2); $i++) {
        $arr1[$i] = max($arr1[$i],$arr2[$i],0);
    }
    return $arr1;
}

# Converts a full karte to a partial one
function karte2k($karte) {
    return (strpos($karte,"&c=")!==false) ? substr($karte,0,-5) : $karte;
}
?>