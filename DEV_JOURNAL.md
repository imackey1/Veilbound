# Development Journal: Grunt

Solo project by Ibrahim Mackey (CSC 4370, Section 002, CRN 82835).
Entries are dated for the day the work happened. Add a new entry for each working day.

## November 8, 2026: Proposal
- **Done:** chose Topic 05 (Choose Your Path). Wrote and submitted the proposal: modern-sorcerer concept, item-gated choices, three endings, 9 pages with 4 choices each, 6-sprint plan, wireframes, and test cases. Created the public repo and the GitHub Projects sprint board.
- **Decisions:** no JavaScript or database, so progress is kept in `$_SESSION` and a validated save file.
- **Blockers:** none.

## November 17, 2026: Build session (compressed Sprints 2 to 4)
- **Done:** built the full PHP app in one session: file structure and shared includes, register/login/logout with hashed passwords, session guard and CSRF tokens, 9-page story with 4 choices each, item pickups with gated choices, fight checks, three endings, scoring, session leaderboard, responsive CSS, 12 SVG illustrations. Ran 53 automated end-to-end checks against a local PHP server; all pass.
- **Decisions:**
  - Replaced the proposal's "Spirit Sense" hints with the rubric's undergraduate baseline: consequence preview from choice metadata and session history, plus a dynamic ending summary.
  - Session is the authoritative game state; the save file is only a backup and every value is validated before use.
  - Fight outcomes are deterministic (aether + item power vs. required power) so the previews are honest and testable.
  - Kept the code compatible with PHP 7.4+ because the CODD PHP version is not known.
- **Blockers:** the app has not been tested on CODD yet.
- **Next:** push to GitHub, upload to CODD and set `data/` permissions, run the manual test table in the README on CODD, record the presentation, submit the links file.

## November 18, 2026: Story replacement (Grunt)
- **Done:** replaced the sorcerer story with my own noir story, Grunt. Rewrote the story data and game engine (flags, pat-down on entry, health, Perspective, seeded roulette luck, item points), added the bottom-right emoji item panel, re-themed the CSS, redrew all ten images, and rewrote the end-to-end tests (26 checks passing).
- **Decisions:** 7 pages with 4 choices each, three endings (Enough, The Assistant, The Cycle). The good ending needs the gun route plus Perspective 75; the assistant ending needs time with the rich man; all bad endings share one message. Items score when obtained, with an ending bonus.
- **Blockers:** not yet tested on CODD.
- **Next:** push, upload to CODD, set `data/` permissions, test live, record the presentation, submit links.

<!-- Add entries below for each additional working day (done / next / blockers). -->
