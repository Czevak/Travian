#! /usr/local/bin/python

#############################################################################
# rally.py                                                                  #
#   Parses rally point source for the Bulwark.                              #
#############################################################################

import cgitb; cgitb.enable();
print "Content-type: text/html"
print

import os.path, urllib2, cgi, urllib, re, sys, datetime
from urllib2 import urlopen, Request

outdict = {}    # Final dict we'll send to the agent

form = cgi.FieldStorage()
id, bin = "", ""
if "id" in form.keys():
    id = form["id"].value
    outdict = {"rallyupdate":"1"}
if "bin" in form.keys(): bin = form["bin"].value
if not bin: sys.exit()

# html stripper
HTMLtag = re.compile('<.*?>')       # Matches html tags
HTMLcom = re.compile('<!--.*?-->')  # Matches html comments
def striphtml(inp): return HTMLtag.sub('', HTMLcom.sub('', inp))
def hourdiff(a,b):
    if abs(a-b) > 12: return 24-abs(a-b)
    return abs(a-b)


bin = [k.decode('utf-8').strip() for k in bin.split("\n") if k.decode('utf-8').strip()]
sec = '';
i = 0
reins, reined = [], []
while i<len(bin):
    line = bin[i]
    if "warsim.php" in line:
        sec="start"     # flag us as being in section we want
    if not sec:         # if section flag is not up, keep looking
        i += 1
        continue
    if "Incoming troops (" in line: sec = "inc"     # Incoming troops section
    if ">Troops in the village" in bin[i]:          # Reined troops section
            sec = "reined"
            bin[i] = bin[i].replace("Troops in the village","")
    if sec=="inc" and "karte.php?d=" in line:
        reinvillage, line = line.split('</a>',1)
        reinkarte = reinvillage.split("karte.php?d=")[1]
        reinkarte = reinkarte[:reinkarte.find('"')]         # karte of the inc village
        reinvillage = striphtml(reinvillage).strip()        # name of the inc village
        type = line.split('</a>',1)[0]
        landkarte = type.split("karte.php?d=")[1]
        landkarte = landkarte[:landkarte.find('"')]         # karte of the landing village
        if "Reinforcement" in type or "Return from" in type: type = "Reinforcement"
        elif "Attack" in type: type = "Attack"
        elif "Raid" in type: type = "Raid"
        if "id=timer" in bin[i]:
            travland = striphtml(bin[i].split('<div class="in">in')[1].replace("&nbsp; in ","")).replace(" Hrs","")
        i += 1
        if ">Troops in the village" in bin[i]:
            sec = "reined"
            bin[i] = bin[i].replace("Troops in the village","")
        duration = striphtml(bin[i]).replace("at","").strip()
        reins.append([reinkarte, type, duration, travland, landkarte])
    elif sec=="reined" and "karte.php?d=" in line:
        reinvillage, line = line.split('</a>',1)
        reinkarte = reinvillage.split("karte.php?d=")[1]
        reinkarte = reinkarte[:reinkarte.find('"')]
        reinvillage = striphtml(reinvillage).strip()
        player, line = line.split('</a>',1)
        player = striphtml(player).replace("'s troops","")
        line = line.split('Units',1)[1]
        units = striphtml(line.replace("</td>"," ").split('Upkeep')[0]).strip().replace(" ",",")
        reined.append([reinkarte,"Landed",units,player])
    if "Troops moving" in line or "Troops in other villages" in line: break
    i += 1

if not reins and not reined: sys.exit()
rally = []
landkarte = [k for k,t,u,p in reined if p=="Own troops"][0]
count = {}
now = datetime.datetime.now()
curday = now.date()
curtime = now.time()
timestamp = str(now+datetime.timedelta(hours=3)).split(".")[0]
typedict = {"Reinforcement":1,"Attack":2,"Raid":3,"Landed":4}
for r in reins:
    out = {"karte":r[0],"type":str(typedict[r[1]]),"travland":r[3],"landkarte":landkarte}
    h,m,s = [int(k) for k in r[2].split()[0].split(":")]
    p = r[2].split()[1]
    if p=="am" and h==12: h = 0
    elif p=="pm" and h!=12: h += 12
    
    t = [int(k) for k in r[3].split()[0].split(":")]
    projected = now + datetime.timedelta(0, t[0]*3600+t[1]*60+t[2])
    lands = {}
    for i in range(5):
        travtime = datetime.timedelta(days=i) + datetime.datetime(curday.year,curday.month,curday.day,h,m,s)
        lands[abs(projected-travtime)] = travtime

    out["landtime"] = lands[min(lands)]
    out["time"] = timestamp
    if not id: id = hex(hash(timestamp)).split("x")[1]
    out["id"] = str(id)
    if (out["karte"],out["landtime"]) in count: count[(out["karte"],out["landtime"])] += 1
    else: count[(out["karte"],out["landtime"])] = 1
    out["count"] = count[(out["karte"],out["landtime"])]
    rally.append(out)
    
for r in reined:
    out = {"karte":r[0],"type":str(typedict[r[1]]),"units":r[2],"player":r[3]}
    out["time"] = timestamp
    if not id: id = hex(hash(timestamp)).split("x")[1]
    out["id"] = id
    rally.append(out)
    
# Check if this is an update to an existing operation
target = [k for k in rally if "player" in k and k["player"]=="Own troops"][0]
handle = urlopen("http://travian.ulrezaj.com/agent.php?rkarte="+target["karte"])
exists = handle.read().strip()
if exists != "0":
    id = exists
    for r in rally: r["id"] = id
handle.close()

    
fields = ["id","count","time","type","karte","player","landplayer","units","travland","landtime"]
sql = ""
for r in rally:   # construct sql statements
    stmt = "insert ignore s4_us_rally set "
    stmt += ",".join([f+"='"+r[f].encode('utf-8')+"'" for f in fields if f in r])
    sql += stmt+";"

outdict["sql"] = urllib.quote(sql.encode('utf-8'))
outdict["id"] = id


txdata = urllib.urlencode(outdict)    # stick into post variables
txheaders = {'User-agent': 'Mozilla/4.0 (compatible; MSIE 5.5; Windows NT)'}
req = Request("http://travian.ulrezaj.com/agent.php", txdata, txheaders)        # give statements to agent.php
handle = urlopen(req)
handle.close()


#print '<meta http-equiv=refresh content="0;URL=/rally.php?id='+id+'">';