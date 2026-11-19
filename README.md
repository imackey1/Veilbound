# Grunt

A choose-your-path noir game in PHP, HTML and CSS (no JavaScript, no database) for CSC 4370/6370 Project 2, Topic 05. Written and built by Ibrahim Mackey (solo team, Section 002, CRN 82835).

*Mature themes: violence and crime.*

Live: https://codd.cs.gsu.edu/~imackey1/web/PW/Veilbound 

## How to play

You are a nobody in a district that feeds on the weak. You set out to rob a gambling den and change your life. Register, log in, start a game, and pick one of four choices on each of seven pages. Health, Perspective, score and carried items are tracked. The items you carry always appear as emojis in the bottom-right corner (🔫 revolver, 🔪 knife, ⛓️ gold chain, 🪙 chips, 💵 cash). Progress saves automatically, so Continue resumes where you stopped, even after logging out.

The same seven pages play differently depending on earlier choices: arriving wet gets you patted down and disarmed, arriving with the rich man skips the search, an empty revolver reads as empty, and page text changes with what you carry.

## Endings

| Ending | How | Bonus |
| --- | --- | --- |
| **Enough** (good) | Reach the vault by the gun route (force an attendant at gunpoint) with Perspective 75+, gained by watching the people the casino has ruined, then open the vault | 300 |
| **The Assistant** | Arrive with the rich man, spend time listening to him, then accept his job | 200 |
| **The Cycle** (bad) | Rob a winner, take the money without understanding, trip the alarm, get cut, or run out of health. Every bad ending shows the same message | 0 |

Final score = points from items (revolver 10, knife 10, gold chain 50, chips 20, cash 40, awarded when obtained) + ending bonus.

## Requirements covered

- Sessions: scene, health, Perspective, inventory, flags, score and history live in `$_SESSION`.
- POST forms for every choice, register, login and logout, with CSRF tokens and Post/Redirect/Get.
- Registration and login with `password_hash()` / `password_verify()`; passwords never stored in plain text.
- Game logic in PHP: item requirements, locked choices, deterministic seeded luck at the roulette table, health loss, pat-down on entry, flag-based text changes.
- Leaderboard: top 10 kept in `$_SESSION['leaderboard']` and mirrored to `data/scores.txt`.
- Three distinct endings with a dynamic summary built from items, flags, Perspective and choice history.
- Four choices per page, an image per page, responsive layout, keyboard focus styles, alt text.

## In-app AI feature (undergraduate baseline)

- **Consequence preview:** under every choice the app shows a rule-based hint computed from the choice data and your history (for example "Costs health", "Gains an item", "+25 perspective", "Already done", or a luck warning). Final choices say "Final choice: no turning back" and never reveal which ending they lead to.
- **Dynamic ending summary:** the ending page lists what you carried, what you did and what you understood, generated from your session data.

## Test notes

`php -S` plus the Python script `tests/e2e.py` (urllib and cookies) drove the game end to end. 26 checks, all passing:
good route (score 310), assistant route (220), bad route by robbery, pat-down disarming, unarmed robbery (-25 health), snatch without a knife (-20), health reaching 0 ending the game, locked and forged choices rejected, stale scene and bad CSRF rejected, save restore after logout/login, tampered save file handled safely, leaderboard ordering, empty and filled item panel. Layout was checked at 375 px and 1100 px with no horizontal overflow.

**Manual checks to repeat on CODD:** register, log in, play one full route, log out and back in to confirm Continue, check the leaderboard, and confirm `data/users.txt` cannot be opened from the browser.

## Setup

### Local (XAMPP or PHP built-in server)
1. Copy the project folder into `htdocs` (XAMPP) or any folder.
2. From the project folder run `php -S localhost:8000` and open http://localhost:8000.
3. The `data/` and `data/saves/` folders must be writable by PHP.

### CODD
1. Upload the folder to `~/public_html/web/PW/Veilbound` (adjust to your path).
2. Make the data folders writable: `chmod 777 data data/saves` (or `775` if PHP runs as your user).
3. Open https://codd.cs.gsu.edu/~imackey1/web/PW/Veilbound.

**Troubleshooting:** if registering says "data folder is not writable", both `data` and `data/saves` must be writable by PHP: run `chmod 777 data data/saves` and check with `ls -ld data data/saves` (both should show `drwxrwxrwx`).

Written for PHP 7.4 or newer (developed and tested on PHP 8.3).

## Files

| Path | Purpose |
| --- | --- |
| `index.php`, `register.php`, `login.php`, `logout.php`, `menu.php` | Entry, accounts, and menu |
| `scene.php` | Story page: image, text, four choices with previews, bottom-right emoji item panel |
| `actions.php` | Controller (no HTML): validates every choice, applies rules, saves, redirects (Post/Redirect/Get) |
| `inventory.php`, `ending.php`, `leaderboard.php` | Items and stats, ending and summary, top 10 |
| `inc/` | `bootstrap.php`, `helpers.php`, `auth.php`, `game.php`, `scenes.php` (story data), `leaderboard.php`, `layout.php` |
| `css/style.css` | Responsive layout, focus styles |
| `images/` | Ten original SVG illustrations (7 pages + 3 endings) |
| `data/` | `users.txt`, `scores.txt`, `saves/` (created at run time; web access blocked by `.htaccess`) |

## Security notes

- Passwords: `password_hash()` and `password_verify()`; the hash file is blocked from web access.
- All user-controlled output is escaped with `htmlspecialchars()`.
- CSRF token on every form; session ID regenerated at login.
- Choices are re-validated on the server: the submitted scene must match the session scene, and item requirements are re-checked.
- Anything restored from a save file is validated (scene, stat ranges, known items and flags) before use. The only cookie remembers the username; credentials and scores are never stored in cookies.

## Credits and AI-use disclosure

- **Story:** the opening premise, the apartment, alley, gambling den, tables, and vault drafts, the choice labels, and all endings were written by Ibrahim Mackey and are kept in his wording. 
- **Code:** all PHP, CSS and the test scripts were built off of Ibrahim's initial code with Claude then run and tested locally.
- **Artwork:** all ten images are original SVG files generated by a script written for this project. No third-party images are used.
- **External code or libraries:** none.
- **Changes from the proposal:** the proposal described a modern-sorcerer concept with 9 pages. The final game is the noir story above with 7 pages (28 choices), because the story was rewritten after the proposal.
