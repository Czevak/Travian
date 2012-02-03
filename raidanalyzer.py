#############################################################################
# raidanalyzer.py                                                           #
#   Gatherer and parser for personal and alliance reports. Responsible for  #
#   formatting report page into a format that can be inserted into the db.  #
#   Runs with:                                                              #
#      no args        asks for login, goes through player's reports, mines  #
#                     report IDs then passes list to agent                  #
#      -allianceonly  Fetches alliance reports for Ulrezaj (1)              #
#      -secondary     Fetches alliance reports for Linc (2)                 #
#############################################################################

import os.path, urllib2, cookielib, urllib, re, sys, datetime
from urllib2 import urlopen, Request

# html stripper
HTMLtag = re.compile('<.*?>')       # Matches html tags
HTMLcom = re.compile('<!--.*?-->')  # Matches html comments
def striphtml(inp): return HTMLtag.sub('', HTMLcom.sub('', inp))
# Turns str into int and ? into -1
def num(inp):                       
    if inp == "?": return -1
    else: return int(inp)
# Finds race from line
def race(inp):
    if '"Maceman"' in inp: return "Teuton"
    elif '"Phalanx"' in inp: return "Gaul"
    elif '"Legionnaire"' in inp: return "Roman"
    elif '"Rat"' in inp: return "Nature"
    elif '"Pikeman"' in inp: return "Natar"
    else: return None
# Converts travian timestamp to SQL DATETIME format
def timeconv(d,t,ap):
    if ap=="Hrs":
        if "/" in d:
            return d.replace("/","-")+" "+t   # If UTC, time is already formatted
        else:
            d = d.split(".")
            return d[2]+"-"+d[1]+"-"+d[0]+" "+t
    d = d.split("/")
    out = d[2]+"-"+d[0]+"-"+d[1]+" "
    ap = {"am":0,"pm":12}[ap]
    t = t.split(":")
    if ap: return out + str(int(t[0])+ap).replace("24","12")+":"+t[1]+":"+t[2]
    else: return out + t[0].replace("12","00")+":"+t[1]+":"+t[2]
# Returns carrying capacity of unit string
def carry(units,race):
    c = {"Teuton":[60,40,50,0,110,80,0,0,0,3000],
         "Gaul":[30,45,0,75,35,65,0,0,0,3000],
         "Roman":[40,20,50,0,100,70,0,0,0,3000]}
    return sum([int(u)*r for u, r in zip(units.split(",")[:10], c[race])])
# sprintf %01.2f
def decformat(inp):
    inp = inp.split(".")
    if len(inp[1]) == 1: inp[1] += "0"
    return inp[0]+"."+inp[1][:2]
# Unit type checks for report type
def isscout(units,race):
    units = [int(k) for k in units.split(",")]
    if race in ["Teuton","Roman"] and units[:3]==[0]*3 and units[4:]==[0]*6: return True
    elif race == "Gaul" and units[:2]==[0]*2 and units[3:]==[0]*7: return True
    else: return False
def isfake(units,race):
    if isscout(units,race): return False
    if units == "0,0,0,0,0,0,0,0,0,0,1": return False
    return sum([int(k) for k in units.split(",")]) == 1
def iscat(units): return int(units.split(",")[7]) > 0
def ischief(units): return int(units.split(",")[8]) > 0
# Report type: fake = 3, scout = 4, cats = 8, chief = 10, cat+chief = 18, anything else = 1
def reporttype(units,race):
    if isfake(units,race): return 3
    if isscout(units,race): return 4
    if ischief(units) and iscat(units): return 18
    if iscat(units): return 8
    if ischief(units): return 10
    return 1
# Wheat upkeep of a unit string
def food(units,race):
    if not units: return 0
    units = [int(k.replace("?","0")) for k in units.split(",")]
    food = [[1,1,1,1,2,3,3,6,4,1,1],[1,1,2,2,2,3,3,6,4,1,1],[1,1,1,2,3,4,3,6,5,1,1],[1,1,1,1,2,2,3,3,3,5]]
    racemap = {"Teuton":0,"Gaul":1,"Roman":2,"Nature":3,"Natar":1}
    total = 0
    for i in range(len(units)): total += units[i]*food[racemap[race]][i]
    return total
# Resource cost of a unit string
def cost(units,race):
    if not units or race in ["Nature","Natar"]: return 0
    units = [int(k.replace("?","0")) for k in units.split(",")]
    res = []
    res.append([[95,75,40,40],[145,70,85,40],[130,120,170,70],[160,100,50,50],[370,270,290,75],[450,515,480,80],
                [1000,300,350,70],[900,1200,600,60],[35500,26600,25000,27200],[7200,5500,5800,6500],[450,515,480,80]])
    res.append([[100,130,55,30],[140,150,185,60],[170,150,20,40],[350,450,230,60],[350,330,280,120],[500,620,675,170],
                [950,555,330,75],[960,1450,630,90],[30750,45400,31000,37500],[5500,7000,5300,4900],[350,450,230,60]])
    res.append([[120,100,180,40],[100,130,160,70],[150,160,210,80],[140,160,20,40],[550,440,320,100],[550,640,800,180],
                [900,360,500,70],[950,1350,600,90],[30750,27200,45000,37500],[5800,5300,7200,5500],[550,440,320,100]])
    racemap = {"Teuton":0,"Gaul":1,"Roman":2}
    total = 0               # We're making this costr instead of cost for now
    for i in range(len(units)):     # Ignore hero
        total += units[i]*sum(res[racemap[race]][i])
    return total

# We are ALWAYS in alliancemode for now
if "allianceonly" not in sys.argv: sys.argv.append("allianceonly")

# Designate which alliance this is being run from (PACK=1, AFNE=2, EHJ=4)
if "secondary" in sys.argv: alliance = "2"
elif "tertiary" in sys.argv: alliance = "3"
elif "quarternary" in sys.argv: alliance = "4"
else: alliance = "1"
print alliance

# Directories for log files 
predir = {"1":"", "2":"", "3":"", "4":""}

url = "http://s4.travian.us/"
login_dict = {"w":""}
txheaders = {'User-agent': 'Mozilla/4.0 (compatible; MSIE 5.5; Windows NT)'}

# Use separate cookies for EHJ and AFNE so we don't have to keep logging in
cookiefile = predir[alliance]+"cookie"+alliance+".lwp"

# Build cookie jar
cj = cookielib.LWPCookieJar()
opener = urllib2.build_opener(urllib2.HTTPCookieProcessor(cj))
urllib2.install_opener(opener)

# Check if it's still valid
if os.path.isfile(cookiefile):
    cj.load(cookiefile, ignore_discard=True, ignore_expires=True)
    print "Cookie loaded"
    req = Request(url+"dorf1.php", None, txheaders)
    handle = urlopen(req)
    for line in handle.readlines():
        if '"password"' in line:    # cookie has expired; get new one
            print "Cookie expired, renewing..."
            os.remove(cookiefile)   # delete old one
            break
    handle.close()
    
# If no cookie (or expired), load login page variables
if not os.path.isfile(cookiefile):
    handle = urlopen(url)
    for line in handle.readlines():
        line = line.strip()[:-1]
        if "type=" not in line or "name=" not in line or "value=" not in line: continue # don't care about this line
        input = [k.replace('"','') for k in line.strip().split() if "type=" in k or "name=" in k or "value=" in k]
        if len(input) <3: continue
        if "login" in input[1] and "login" not in login_dict:   # login serial
            login_dict["login"] = input[2].replace("value=","")
        elif "name=e" in input[1] and "type=text" in input[0]:  # username
            if "allianceonly" not in sys.argv:
                login_dict[input[1].replace("name=","")] = raw_input("Enter your username: ").strip()
            elif "secondary" in sys.argv:
                login_dict[input[1].replace("name=","")] = "McDuck"
            elif "tertiary" in sys.argv:
                login_dict[input[1].replace("name=","")] = "Nimai"
            elif "quarternary" in sys.argv:
                login_dict[input[1].replace("name=","")] = "Hannibal"
            else:
                login_dict[input[1].replace("name=","")] = "ulrezaj"
        elif "name=e" in input[1] and "type=password" in input[0]:  #password
            if "allianceonly" not in sys.argv:
                login_dict[input[1].replace("name=","")] = raw_input("Enter your password: ").strip()
            elif "secondary" in sys.argv:
                login_dict[input[1].replace("name=","")] = "battle"
            elif "tertiary" in sys.argv:
                login_dict[input[1].replace("name=","")] = "ah4045eh"
            elif "quarternary" in sys.argv:
                login_dict[input[1].replace("name=","")] = "battle"
            else:
                login_dict[input[1].replace("name=","")] = "battle"
        elif "name=e" in input[1]:  # any other e-value
            login_dict[input[1].replace("name=","")] = input[2].replace("value=","").replace(">","")
    handle.close()
    
    txdata = urllib.urlencode(login_dict)   # encode login variables
    req = Request(url+"dorf1.php", txdata, txheaders)
    handle = urlopen(req)
    cj.save(cookiefile, ignore_discard=True, ignore_expires=True)   # collect cookie
    handle.close()
    print "Cookie saved"

berichte = [] # report urls

# Get alliance report links 
req = Request(url+"allianz.php?s=3", None, txheaders)
handle = urlopen(req)
for line in handle.readlines():
    if "berichte.php?id=" in line:
        berichte.append([k.replace("berichte.php?id=","") for k in line.split('"') if "berichte" in k][0])
handle.close()
# Check for existing reports if we're not refreshing
if "autistic" in sys.argv and os.path.isfile("ra_reportlog"+alliance):  # Running autistic
    br = open(predir[alliance]+"ra_reportlog"+alliance,"r")
    berichte = [k for k in berichte if k not in br.readlines()]
    br.close()
elif "refresh" not in sys.argv:                                         # Running normally
    handle = urlopen("http://travian.ulrezaj.com/agent.php?b="+",".join(berichte))
    exists = handle.read().split()
    berichte = [k for k in berichte if k not in exists]
    handle.close()
    
# Get mined reports links (IRRESPECTIVE of existing or not) unless we are in autistic mode
if "allianceonly" in sys.argv and "autistic" not in sys.argv: 
    handle = urlopen("http://travian.ulrezaj.com/agent.php?c&a="+alliance)
    berichte += handle.read().strip().split(",")[:-1]
    handle.close()
print

# Dump log of IDs
if berichte:
    ps = open(predir[alliance]+"ra_reportlog"+alliance,"a")
    ps.write("\n".join(berichte)+"\n")
    ps.close()

# If run with no args, upload just berichtes and exit (we never do this)
if len(sys.argv) == 1:
    txdata = urllib.urlencode({'m':",".join(berichte), 'a':alliance}) 
    req = Request("http://travian.ulrezaj.com/agent.php", txdata, txheaders)
    handle = urlopen(req)
    handle.close()
    print len(berichte), "reports compiled and uploaded."
    raw_input("Finished! Press enter to exit.")
    sys.exit(0)

# Get reports
reports = []
successful, unsuccessful = 0, 0
print "Fetching reports"
for page in berichte:
    try:
        sys.stdout.write(".")
        sys.stdout.flush()
        out = {"id":page, "info":[],"attrace":"","defrace":""}
        section = ""
        reporting = None
        req = Request(url+"berichte.php?id="+page, None, txheaders)
        handle = urlopen(req)
        for line in handle.readlines():
            if ">Subject:<" in line: reporting = 1      # ignore lines until we hit battle report
            if not reporting: continue
            
            line = line.strip()
            if ">Attacker<" in line: section = "att"    # set attack section
            elif ">Defender<" in line: section = "def"  # set defence section (note: def precedes every rein)
            elif ">Reinf.<" in line:
                if "rein1" not in out:
                    out["rein1"] = ""
                    section = "rein1"
                else:
                    section = "rein2"
                
            if "att" in section and not out["attrace"] and race(line):          # find out player's race
                out["attrace"] = race(line)
            elif "def" in section and not out["defrace"] and race(line):        # find out defender's race
                out["defrace"] = race(line)
            elif "rein1" in section and "rein1race" not in out and race(line):  # find out first rein race
                out["rein1race"] = race(line)
            elif "rein2" in section and "rein2race" not in out and race(line):  # find out second rein race
                out["rein2race"] = race(line)
            
            if 'class="goods"' in line and "carry" in line:                     # get bounty
                sub = line[:line.find("</div>")]
                out["bounty"] = [k for k in striphtml(sub).replace("|","").split()]
                
            if section and "Units" in line:                     # get units, prisoners, casualties
                sub = "<th><junk>Units"+line.split('Units')[1]
                sub = sub.split("<tr")
                for sec in sub:
                    sec = sec[sec.find(">")+1:]
                    if "Units" in sec:                          # Everyone has a units section regardless
                        sec = sec.replace("Units","")
                        out[section+"units"] = [k for k in [striphtml(k) for k in sec.split("</td>")] if k]
                    elif "Casualties" in sec:
                        sec = sec.replace("Casualties","")
                        out[section+"casualties"] = [k for k in [striphtml(k) for k in sec.split("</td>")] if k]
                    elif "Prisoners" in sec:                    # should only happen for att section
                        sec = sec.replace("Prisoners","")
                        out["prisoners"] = [k for k in [striphtml(k) for k in sec.split("</td>")] if k]
                    elif "Resources" in sec:                    # treat scout report same as bounty from raid
                        sec = sec.replace("Resources","").replace("|","")
                        out["bounty"] = [k for k in striphtml(sec).split()]
                    elif "Info" in sec:                         # should only happen for att section
                        sec = sec.replace("Info","")
                        out["info"].append(striphtml(sec).strip())
                    elif "Bounty" in sec:                       # Some weird formats will have bounty in here
                        sec = sec.replace("Bounty","").replace("|","")
                        out["bounty"] = [k for k in striphtml(sec).split()]
                        
            if "att" in section and "</a> from" in line:        # find attacker links
                out["attacker"] = striphtml(line)
                line = line.split("karte.php?d=")[1]
                out["attkarte"] = line[:line.find(">")]
            elif "def" in section and ("</a> from" in line or '">from' in line):    # find defender links
                out["defender"] = striphtml(line)
                if "karte.php?d=" in line:
                    line = line.split("karte.php?d=")[1]
                    out["defkarte"] = line[:line.find(">")]
            elif "def" in section and "village</span" in line:  # no report case with weird parsing
                out["defender"] = striphtml(line.split("</td>")[1])
                line = line.split("karte.php?d=")[1]
                out["defkarte"] = line[:line.find(">")]
            elif 'td>on ' in line:                                # get date & time of attack
                line = striphtml(line).split()
                out["time"] = timeconv(line[1],line[3],line[4])
        handle.close()

        # post-processing
        for k in ["attkarte","defkarte"]:
            if k in out: out[k] = out[k].replace('"','').replace("amp;","")
        if "bounty" in out: out["total"] = sum([int(k) for k in out["bounty"]])
        for k in out:
            if type(out[k]) is type([]):
                out[k] = ",".join([str(i) for i in out[k]])
        if "total" in out and "attunits" in out:
            capacity = carry(out["attunits"],out["attrace"])
            if "prisoners" in out: capacity -= carry(out["prisoners"],out["attrace"])
            if "attcasualties" in out: capacity -= carry(out["attcasualties"],out["attrace"])
            if capacity > 0: out["efficiency"] = decformat(str((out["total"]*100.0)/capacity))
            else: out["efficiency"] = "0.00";
        if "attunits" not in out and "defunits" not in out and "attkarte" not in out and "defkarte" not in out: continue
        if "attacker" in out: out["attplayer"] = out["attacker"].split(" from the village ")[0]
        if "defender" in out: out["defplayer"] = out["defender"].split(" from the village ")[0]
        out["type"] = reporttype(out["attunits"],out["attrace"])
        for sec in ["att","def","rein1","rein2"]:
            for c in ["units","casualties"]:
                if sec+c in out:
                    out[sec+c+"food"] = food(out[sec+c],out[sec+"race"])
                    out[sec+c+"cost"] = cost(out[sec+c],out[sec+"race"])
        for k in ["attacker","defender"]:
            if k in out: out[k] = out[k].replace(";","\\;").replace("'","\\'").replace('"','\\"').replace("\n","").replace("\r","")
        if "tertiary" in sys.argv: out["id"] = "-"+out["id"]
        if "info" in out: out["info"] = out["info"].replace("'","\\'")
        reports.append(out)
        successful += 1
    except:
        sys.stdout.write("e")
        sys.stdout.flush()
        # Dump error log but carry on
        ps = open(predir[alliance]+"ra_errorlog","a")
        ps.write(str(datetime.datetime.now())+"  "+page+"  "+str(sys.exc_info())+"\n")
        ps.close()
        unsuccessful += 1

print

fields = ["id","time","attacker","attplayer","defender","defplayer",
          "attunits","attcasualties","attunitsfood","attunitscost","attcasualtiesfood","attcasualtiescost",
          "prisoners","bounty","total","efficiency","info",
          "defunits","defcasualties","defunitsfood","defunitscost","defcasualtiesfood","defcasualtiescost",
          "rein1units","rein1casualties","rein1unitsfood","rein1unitscost","rein1casualtiesfood","rein1casualtiescost","rein1race",
          "rein2units","rein2casualties","rein2unitsfood","rein2unitscost","rein2casualtiesfood","rein2casualtiescost","rein2race",
          "attrace","defrace","attkarte","defkarte","type"]
sql = ""
for r in reports:   # construct sql statements
    stmt = "replace s4_us_reports set "
    stmt += ",".join([f+"='"+str(r[f]).decode('utf-8')+"'" for f in fields if f in r])
    sql += stmt+";"
    
# If running in autistic mode, dump sql and exit
if "autistic" in sys.argv:
    ps = open("ra_sqldump"+alliance,"a")
    ps.write(";\n".join([k.replace("@@314159@@","\\;").encode('utf-8') for k in sql.replace("\\;","@@314159@@").split(";")])+"\n")
    ps.close()
    sys.exit()
    

txdata = urllib.urlencode({'sql':urllib.quote(sql.encode('utf-8'))})    # stick into post variables
req = Request("http://travian.ulrezaj.com/agent.php", txdata, txheaders)   # give statements to agent.php
handle = urlopen(req)
handle.close()

print len(berichte), "reports compiled and uploaded."



# Log to messages
ps = open(predir[alliance]+"ra_messages","a")
ps.write(str(datetime.datetime.now())+"  "+alliance+": "+str(successful)+"/"+str(len(berichte)))
if unsuccessful==0: ps.write("\n")
else: ps.write(", "+str(unsuccessful)+"failed \n")
ps.close()

sys.exit()  # attack logger disabled

# If secondary, we're done
if "secondary" in sys.argv: sys.exit()
def jump(h,n):
    if n not in h: return False
    return h[h.find(n):]
def cut(h,n):
    if n not in h: return False
    return h[:h.find(n)]
    
# Rally point inc troops log
villages = ["83736","64555","98840"]
for v in villages:
    handle = urlopen(Request(url+"build.php?newdid="+v+"&gid=16&id=39", None, txheaders))
    bin = handle.read()
    bin = jump(bin,"Incoming troops")
    if bin:
        bin = cut(bin, "Troops in")
        bin = bin.replace("\r","").replace("\n","").replace("\t","").replace("</td>"," ")
        bin = striphtml(bin).replace("          ","").replace("&nbsp; ","").replace(" pm ","pm \n").replace(" am ","am \n")
        ps = open(predir[alliance]+"attacks.txt","a")
        ps.write(bin.decode('utf-8').encode('utf-8'))
        ps.close()
