#############################################################################
# stats.py                                                                  #
#   Mines player's population, offensive ranking, and defensive ranking     #
#   changes at 10 minute intervals, then creates sql query and passes it    #
#   to the agent.                                                           #
#   Runs with:                                                              #
#       no args     goes through 'watching' alliance list and logs changes  #
#       -off        goes through first 300 pages of offense rankings        #
#       -def        goes through first 300 pages of defense rankings        #
#############################################################################


import os.path, urllib2, cookielib, urllib, re, sys, datetime
from urllib2 import urlopen, Request

# html stripper
HTMLtag = re.compile('<.*?>')       # Matches html tags
HTMLcom = re.compile('<!--.*?-->')  # Matches html comments
def striphtml(inp): return HTMLtag.sub('', HTMLcom.sub('', inp))

watching = [71,73,85,108,143,194,196,217,224,225,248,281,386,401,450,531,615,627,774,838,867,924,1230,1348,337,130,214,284]
# Watched alliances
watching = [str(k) for k in watching]

home = "http://s4.travian.us/"
login_dict = {"w":""}
txheaders = {'User-agent': 'Mozilla/4.0 (compatible; MSIE 5.5; Windows NT)'}

cookiefile = "cookie.lwp"
cj = cookielib.LWPCookieJar()
opener = urllib2.build_opener(urllib2.HTTPCookieProcessor(cj))
urllib2.install_opener(opener)
if os.path.isfile(cookiefile):      # Cookie exists, check if it's still valid
    cj.load(cookiefile, ignore_discard=True, ignore_expires=True)
    print "Cookie loaded"
    handle = urlopen(Request(home+"dorf1.php", None, txheaders))
    for line in handle.readlines():
        if '"password"' in line:    # cookie has expired; get new one
            print "Cookie expired, renewing..."
            os.remove(cookiefile)   # delete old one
            break
    handle.close()
    
if not os.path.isfile(cookiefile):  # If no cookie (or expired), load login page variables
    handle = urlopen(home)
    for line in handle.readlines():
        line = line.strip()[:-1]
        if "type=" not in line or "name=" not in line or "value=" not in line: continue # don't care about this line
        input = [k.replace('"','') for k in line.strip().split() if "type=" in k or "name=" in k or "value=" in k]
        if len(input) <3: continue
        if "login" in input[1] and "login" not in login_dict:       # login serial
            login_dict["login"] = input[2].replace("value=","")
        elif "name=e" in input[1] and "type=text" in input[0]:      # username
            login_dict[input[1].replace("name=","")] = "ulrezaj"
        elif "name=e" in input[1] and "type=password" in input[0]:  #password
            login_dict[input[1].replace("name=","")] = "warhammer40k"
        elif "name=e" in input[1]:  # any other e-value
            login_dict[input[1].replace("name=","")] = input[2].replace("value=","").replace(">","")
    handle.close()
    
    txdata = urllib.urlencode(login_dict)   # encode login variables
    handle = urlopen(Request(home+"dorf1.php", txdata, txheaders))
    cj.save(cookiefile, ignore_discard=True, ignore_expires=True)   # collect cookie
    handle.close()
    print "Cookie saved"

print datetime.datetime.now()
# Collecting off/def stats
sql = ""
cur, count = "", 0
if "off" in sys.argv or "def" in sys.argv:
    type = {"off":"31", "def":"32"}
    for i in range(300):
        sys.stdout.write(".")
        sys.stdout.flush()
        handle = urlopen(home+"statistiken.php?id="+type[sys.argv[1]]+"&rang="+str(20*i+1))
        for line in handle.readlines():
            if "spieler.php?uid=" in line and 'ss="s7' in line:
                cur = [k.replace("spieler.php?uid=","") for k in line.split('"') if "spieler" in k][0]
            elif cur and count==0: count = 1
            elif cur and count==1: count = 2
            elif cur and count==2:
                sql +=  "uid="+cur+",points="+striphtml(line.strip())+",;"
                cur, count = "", 0

    sql_dict = {'pre': 'insert ignore into s4_us_'+sys.argv[1]+' set',
                'suf': 'time=timestampadd(HOUR,3,now())',
                'sql': sql}
    txdata = urllib.urlencode(sql_dict)     # stick into post variables
    req = Request("http://travian.ulrezaj.com/agent.php", txdata, txheaders)    # give statements to agent.php
    handle = urlopen(req)
    handle.close()
    print
    print datetime.datetime.now()
    sys.exit()

    
# Collecting pop
players = {}    # uid: pop
alliances = {}  # uid: aid
villages = {}   # karte: pop

# Get each watched alliance's current pops
for aid in watching:
    handle = urlopen(Request(home+"allianz.php?aid="+aid, None, txheaders))
    for line in handle.readlines():
        if "spieler.php?uid=" in line and ".</td" in line:
            players[[k.replace("spieler.php?uid=","") for k in line.split('"') if "spieler" in k][0]] = striphtml(line[line.rfind("<td>"):].strip())
            alliances[[k.replace("spieler.php?uid=","") for k in line.split('"') if "spieler" in k][0]] = aid
    handle.close()

# Get latest recorded pops for each alliance and prune players list
handle = urlopen("http://travian.ulrezaj.com/agent.php?hget="+",".join(watching))
latest = [k.split(",") for k in handle.read().split(";") if k]
for uid,pop in latest:
    if uid in players and players[uid]==pop: players.pop(uid)
handle.close()

# Get pop details for players whose pop changed
sql, cur = "", ""
for uid in players:
    sys.stdout.write(".")
    sys.stdout.flush()
    handle = urlopen(Request(home+"spieler.php?uid="+uid, None, txheaders))
    for line in handle.readlines():
        # Karte and pop are on adjacent lines - check for karte then immediately get pop
        if "karte.php?d=" in line:
            cur = [k.replace("karte.php?d=","") for k in line.split('"') if "karte" in k][0]
            sql += 'uid='+uid+',aid='+alliances[uid]+',karte="'+cur+'",'
        elif cur:
            sql += 'pop='+striphtml(line.strip())+',;'
            cur = ""
    handle.close()
print
print datetime.datetime.now()
print len(players),"records updated"        

# Upload
sql_dict = {'pre': 'insert ignore into s4_us_pop set',
            'suf': 'time=timestampadd(HOUR,3,now())',
            'sql': sql}
txdata = urllib.urlencode(sql_dict)     # stick into post variables
req = Request("http://travian.ulrezaj.com/agent.php", txdata, txheaders)    # give statements to agent.php
handle = urlopen(req)
print handle.read()
handle.close()
