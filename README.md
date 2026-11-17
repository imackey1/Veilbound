# Veilbound

A modern-sorcerer **Choose Your Path** game built with PHP, HTML5, and CSS3.
**CSC 4370 Web Programming, Fall 2026, Georgia State University: Project 2 (Topic 05)**

- **Author (solo team):** Ibrahim Mackey, Section 002, CRN 82835
- **GitHub:** https://github.com/imackey1/Veilbound
- **CODD:** https://codd.cs.gsu.edu/~imackey1/web/PW/Veilbound

You are a rookie sorcerer of the Lantern Order. The seal on the Meridian Arcade has broken and Hollows are leaking into the city. Explore nine illustrated pages, collect items, and choose one of three endings. Items you collect are kept by PHP and unlock new choices on later pages.

## How to play

1. Register an account, then log in.
2. Choose **New Game**. Each page shows one image, a story passage, and **four choices**.
3. Under every choice, a **consequence preview** tells you what it will do (items gained, stat changes, odds of success).
4. Locked choices show what you need (for example, *Requires Veil Key*). Find the item elsewhere, then come back.
5. Reach an ending to get a score on the leaderboard.

Endings: **The Veil Holds** (true), **Hollow Crown** (dark), **The Lantern Goes Dark** (defeat).

## Requirements covered

| Rubric requirement | Where |
| --- | --- |
| Sessions and leaderboard | `$_SESSION` holds all game state (`inc/game.php`); runs are ranked in `$_SESSION['leaderboard']` and shown by `leaderboard.php` (also mirrored to `data/scores.txt` so other players' scores appear) |
| Form processing | Register, login, new game, and every choice are POST forms validated server-side (`register.php`, `login.php`, `actions.php`) with success and error messages |
| Registration and login | `password_hash()` / `password_verify()`, session guard on every protected page, `logout.php` destroys the session |
| PHP game logic | Scene array, item gates, fight checks, stats, scoring, and endings in `inc/scenes.php` and `inc/game.php` |
| Choose Your Path flow | Story-node array, session-tracked node and stats (aether, resolve), conditional choices, terminal endings, final score on the leaderboard |

No JavaScript and no database are used.

## In-app AI feature (undergraduate baseline)

**Consequence preview and dynamic ending summary** (rule-based PHP, no external service).

- **Data it uses:** choice metadata in `inc/scenes.php` (item gain, stat effects, fight power), the player's aether, resolve and inventory, and the session history log (`$_SESSION['history']`, one entry per choice with outcome `ok`, `item`, or `fail`).
- **Preview rule (`choice_preview()` in `inc/game.php`):**
  - Fight power = aether + the power bonus of every owned item (Warding Salt +15, Silver Lantern +20, Mentor's Ledger +10).
  - Margin = fight power minus the fight's required power. Margin >= 15 is **Safe**, 0 to 14 is **Even odds**, below 0 is **Risky**. A fight succeeds when margin >= 0.
  - Adds item gain, stat changes, failure cost, whether the next page was already explored, and a warning after two failed fights in a row (history).
  - The preview never makes the choice for the player and never reveals hidden items.
- **Ending summary rule (`ending_summary()`):** counts items collected, fights attempted and lost, moves, and locations visited from the history log, then selects matching sentences.
- **Limitation:** previews come from fixed metadata, so they explain the rules but do not predict story consequences beyond stats and items. The summary uses simple thresholds, not language understanding.

## Test notes

Automated end-to-end checks were run against a local PHP server (`php -S`) and all passed (53 checks). Manual cases to repeat on CODD:

| ID | Case | Input | Expected |
| --- | --- | --- | --- |
| TC-1 | Login failure | Right username, wrong password | Error shown, username kept, no session |
| TC-2 | Session guard | Open `scene.php` logged out | Redirect to `login.php` |
| TC-3 | Item persistence | Take Silver Lantern, log out, log in | Lantern still in inventory, game continues |
| TC-4 | Locked choice | Lobby without Veil Key | Stairs choice disabled, shows "Requires Veil Key" |
| TC-5 | Forged POST | Submit locked choice manually | Rejected on the server, scene unchanged |
| TC-6 | Page shape | Open every page | One image and exactly four choices |
| TC-7 | Preview (normal) | Food Court with aether 40, no items | Fight shows Risky (power 40 vs 60) |
| TC-7b | Tampered save | Edit save file to aether 999 and an unknown item | Save discarded, message shown |
| TC-8 | Preview/fight boundary | Aether 40 + Silver Lantern = power 60 vs 60 | "Even odds"; the fight succeeds. With Salt only (55 vs 60): "Risky"; the fight fails |
| TC-9 | Resolve reaches 0 | Lose fights repeatedly | Ending C (The Lantern Goes Dark) |
| TC-10 | All endings | Seal (Rune + Lantern), take power, flee or lose | Endings A, B, C each reachable once with a score |
| TC-11 | Responsive layout | View at 360 px width | No horizontal page scroll; labeled fields; visible focus |
| TC-12 | Leaderboard | Finish two runs | Both ranked high to low; reloading ending page adds no duplicate |
| TC-13 | Safe output | Register as `<b>x</b>` | Rejected by validation; any echoed text is escaped |

## Setup

### Local (XAMPP or PHP built-in server)
1. Copy the project folder into `htdocs` (XAMPP) or any folder.
2. From the project folder run `php -S localhost:8000` and open http://localhost:8000.
3. The `data/` and `data/saves/` folders must be writable by PHP.

### CODD
1. Upload the folder to `~/public_html/web/PW/Veilbound` (adjust to your path).
2. Make the data folders writable: `chmod 777 data data/saves` (or `775` if PHP runs as your user).
3. Open https://codd.cs.gsu.edu/~imackey1/web/PW/Veilbound.

Written for PHP 7.4 or newer (developed and tested on PHP 8.3).

## Files

| Path | Purpose |
| --- | --- |
| `index.php`, `register.php`, `login.php`, `logout.php`, `menu.php` | Entry, accounts, and menu |
| `scene.php` | Story page: image, text, four choices with previews |
| `actions.php` | Controller (no HTML): validates every choice, applies rules, saves, redirects (Post/Redirect/Get) |
| `inventory.php`, `ending.php`, `leaderboard.php` | Items and stats, ending and summary, top 10 |
| `inc/` | `bootstrap.php`, `helpers.php`, `auth.php`, `game.php`, `scenes.php` (story data), `leaderboard.php`, `layout.php` |
| `css/style.css` | Responsive layout, focus styles |
| `images/` | Twelve original SVG illustrations |
| `data/` | `users.txt`, `scores.txt`, `saves/` (created at run time; web access blocked by `.htaccess`) |

## Security notes

- Passwords: `password_hash()` and `password_verify()`; the hash file is blocked from web access.
- All user-controlled output is escaped with `htmlspecialchars()`.
- CSRF token on every form; session ID regenerated at login.
- Choices are re-validated on the server: the submitted scene must match the session scene, and item requirements are re-checked.
- Anything restored from a save file is validated (scene, stat ranges, known items and flags) before use. The only cookie remembers the username; credentials and scores are never stored in cookies.

## Credits and AI-use disclosure

- **Artwork:** all twelve images are original SVG files generated by a script written for this project. No third-party images are used.
- **External code or libraries:** none.
- **AI tools:** The application code (PHP), CSS, SVG artwork script, test script, and drafts of this README and the development journal were generated with Claude (Anthropic) at the direction of Ibrahim Mackey, then run and tested locally. The game concept, story, scope, and requirements decisions are Ibrahim's. *Ibrahim: edit this section so it states exactly what you wrote, changed, or reviewed yourself.*
