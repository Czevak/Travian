import os.path, urllib2, cookielib, urllib, re, sys
from urllib2 import urlopen, Request

# html stripper
HTMLtag = re.compile('<.*?>')       # Matches html tags
HTMLcom = re.compile('<!--.*?-->')  # Matches html comments
def striphtml(inp): return HTMLtag.sub('', HTMLcom.sub('', inp))

url = "http://s4.travian.us/"
login_dict = {"w":""}
txheaders = {'User-agent': 'Mozilla/4.0 (compatible; MSIE 5.5; Windows NT)'}

# Check what we're simming
attacker, defender, type = "", [], ""
form = cgi.FieldStorage()
if "a" in form.keys(): attacker = form["a"].value
if "dromans" in form.keys(): defender.append("r")
if "dteutons" in form.keys(): defender.append("t")
if "dgauls" in form.keys(): defender.append("g")
if "dnature" in form.keys(): defender.append("n")
if "type" in form.keys(): type = form["type"].value
# Exit if we don't have all parameters we need
if (not attacker or not defender or not type): sys.exit()

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
                login_dict[input[1].replace("name=","")] = "linc"
            else:
                login_dict[input[1].replace("name=","")] = "ulrezaj"
        elif "name=e" in input[1] and "type=password" in input[0]:  #password
            if "allianceonly" not in sys.argv:
                login_dict[input[1].replace("name=","")] = raw_input("Enter your password: ").strip()
            elif "secondary" in sys.argv:
                login_dict[input[1].replace("name=","")] = "warhammer40k"
            else:
                login_dict[input[1].replace("name=","")] = "warhammer40k"
        elif "name=e" in input[1]:  # any other e-value
            login_dict[input[1].replace("name=","")] = input[2].replace("value=","").replace(">","")
    handle.close()
    
    txdata = urllib.urlencode(login_dict)   # encode login variables
    req = Request(url+"dorf1.php", txdata, txheaders)
    handle = urlopen(req)
    cj.save(cookiefile, ignore_discard=True, ignore_expires=True)   # collect cookie
    handle.close()
    print "Cookie saved"

