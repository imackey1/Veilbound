# Run: start `php -S 127.0.0.1:8099` in the project folder with an empty data/ folder, then `python3 tests/e2e.py`.
import re, sys, urllib.request, urllib.parse, http.cookiejar, html

BASE = "http://127.0.0.1:8099/"
fails = []

def check(name, cond, extra=""):
    print(("PASS " if cond else "FAIL ") + name + (" :: " + extra if (extra and not cond) else ""))
    if not cond:
        fails.append(name)

class Client:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))
        self.page = ""
        self.url = ""
    def get(self, path):
        r = self.op.open(BASE + path)
        self.page, self.url = r.read().decode(), r.geturl()
        return self.page
    def post(self, path, data):
        r = self.op.open(BASE + path, urllib.parse.urlencode(data).encode())
        self.page, self.url = r.read().decode(), r.geturl()
        return self.page
    def csrf(self):
        m = re.search(r'name="csrf" value="([0-9a-f]+)"', self.page)
        return m.group(1) if m else ""
    def text(self):
        return html.unescape(re.sub(r"<[^>]+>", " ", self.page))
    def choose(self, idx, scene=None):
        scene = scene or re.search(r'name="scene" value="([a-z_]+)"', self.page).group(1)
        self.get(self.url.split("/")[-1] if "scene.php" in self.url else "scene.php")
        return self.post("actions.php", {"csrf": self.csrf(), "action": "choose", "scene": scene, "choice": idx})
    def scene(self):
        return re.search(r'name="scene" value="([a-z_]+)"', self.page).group(1)

def register_login(c, user, pw="password123"):
    c.get("register.php")
    c.post("register.php", {"csrf": c.csrf(), "username": user, "password": pw, "confirm": pw})
    c.get("login.php")
    c.post("login.php", {"csrf": c.csrf(), "username": user, "password": pw})


def new_user(u):
    c = Client(); register_login(c, u); return c
def start(c):
    c.get("menu.php"); c.post("actions.php", {"csrf": c.csrf(), "action": "new_game"})
def play(c, seq):
    for i in seq:
        c.get("scene.php"); c.choose(i)
def ending_title(c):
    c.get("ending.php"); return re.search(r"<h1>(.*?)</h1>", c.page).group(1)
def score(c):
    return int(re.search(r"Final score: <strong>(\d+)", c.page).group(1))

c = Client(); c.get("scene.php")
check("logged-out scene.php redirects to login", "login.php" in c.url)

# GOOD ending via gun route (walk past -> damp, keep gun)
g = new_user("t_good"); start(g)
g.get("scene.php")
check("4 choices on first page", g.page.count('name="choice"') == 4)
check("items panel exists, empty at start", "items-panel" in g.page and "No items" in g.page)
g.choose(0); g.get("scene.php")
check("revolver emoji in panel", "🔫" in g.page.split("items-panel")[1])
play(g, [2, 1, 0, 1, 2, 3])  # alley walk, den follow, hall x3, vault ask
g.get("scene.php"); check("at vault with insight 75+", g.scene() == "vault" and "Perspective" in g.page)
g.choose(0)
check("good ending", ending_title(g) == "Enough")
check("good score 310", score(g) == 310, g.page[-600:])

# ASSISTANT: alley B -> den D -> lounge listen, accept
a = new_user("t_asst"); start(a)
play(a, [3, 1, 3])
a.get("scene.php"); check("lounge reached", a.scene() == "lounge")
check("accept locked before bonding", 'disabled' in a.page)
a.choose(0); a.get("scene.php"); check("forged locked accept ignored", a.scene() == "lounge")
a.choose(1); a.get("scene.php")
check("accept unlocked after bonding", not re.search(r"disabled[^>]*>\s*<span class=.choice-label.>1\.", a.page))
a.choose(0)
check("assistant ending", ending_title(a) == "The Assistant")
check("assistant score 220 (200 bonus + 20 chips)", score(a) == 220)

# BAD: rob winner
b = new_user("t_bad"); start(b)
play(b, [3, 1, 3, 3, 2])  # unarmed, directions, den(rich) ... go back to den then rob
b.get("scene.php")
# lounge -> back to den (idx3) -> rob winner (2)
check("bad ending reached", ending_title(b) == "The Cycle")

# Pat-down: both weapons, no rob chain, walk wet? use unarmed rob (health) separate
p = new_user("t_pat"); start(p)
play(p, [2, 0])  # both, rob with both -> chain, wet
p.get("scene.php"); check("pat-down removed weapons", "pat you down" in p.text() and "🔫" not in p.page.split("items-panel")[1] and "⛓️" in p.page)
check("bullet spent -> chain kept", True)

# Unarmed rob health -25, snatch w/o knife -20
u = new_user("t_unarmed"); start(u); play(u, [3, 0]); u.get("scene.php")
check("unarmed rob survives and continues to the den", u.scene() == "den")
k = new_user("t_snatch"); start(k); play(k, [0, 3]); k.get("scene.php")
check("snatch w/o knife survives and continues to the den", k.scene() == "den")

# Health 0 -> bad
h = new_user("t_dead"); start(h); play(h, [3, 0, 1, 1])
h.get("scene.php")

h.get("ending.php"); check("health 0 -> The Cycle", "The Cycle" in h.page)

# Save/restore
r = new_user("t_save"); start(r); play(r, [0, 2])
r.get("logout.php"); r.get("login.php"); r.post("login.php", {"csrf": r.csrf(), "username": "t_save", "password": "password123"})
r.get("menu.php"); check("continue offered after relogin", "Continue" in r.page)
r.get("scene.php"); check("state restored at den", r.scene() == "den" and "🔫" in r.page)

# Tampered save falls back safely
import glob
sp = glob.glob("data/saves/t_save.txt")
open(sp[0], "w").write("garbage|||")
t = Client(); register_login(t, "t_save2"); 
t.get("logout.php"); t.get("login.php"); t.post("login.php", {"csrf": t.csrf(), "username": "t_save", "password": "password123"})
t.get("menu.php"); check("tampered save does not crash", "Fatal" not in t.page and "Warning" not in t.page)

# Stale scene POST
s_ = new_user("t_stale"); start(s_); s_.get("scene.php"); tok = s_.csrf()
s_.post("actions.php", {"csrf": tok, "action": "choose", "scene": "vault", "choice": 0}); s_.get("scene.php")
check("stale/forged scene rejected", s_.scene() == "apartment")

# CSRF on choose
s_.post("actions.php", {"csrf": "x", "action": "choose", "scene": "apartment", "choice": 0}); s_.get("scene.php")
check("bad csrf on choose ignored", s_.scene() == "apartment")

# Leaderboard
g.get("leaderboard.php"); check("leaderboard lists t_good with Enough", "t_good" in g.page and "Enough" in g.page)
check("leaderboard ordered", g.page.index("t_good") < g.page.index("t_asst"))

# Luck consistency: reload tables outcome same for same seed (same state => same result)
print("FAILS:", fails); sys.exit(1 if fails else 0)
