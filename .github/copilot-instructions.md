# Pub Quiz Score Manager - AI Coding Guide

## Project Overview
This is a PHP-based score management system for hosting pub quizzes. It provides two main interfaces:
- **Admin/Backend** (`_old/backend.php`): Team management and score editing with password authentication
- **Public Scoreboard** (`_old/scoreboard.php` & `dritterplatz.php`, etc.): Display team rankings with auto-refresh

## Architecture & Data Flow

### Core Configuration
- **Single file storage**: All data persists via JSON files (`game.json`, `clients.json`)
- **Fixed categories**: Number of scoring categories is hardcoded at initialization (`$numCategories = 3`)
- **No database**: Pure file-based system - important when making schema changes

### Authentication Model
- Password-based entry point (`$authPassword = 'QWer1234'`)
- Client tracking via hashed cookies (`pq_auth` cookie with 30-day expiry)
- Authenticated clients stored in `clients.json` as hex hashes

### Score Structure
Game state in `game.json` uses nested array structure:
```
Teams[] → [id] → {
  name: string,
  categories[]: [{ score: int, joker: boolean }, ...]
}
```
**Joker multiplier**: Joker flag doubles a category score (used during calculation/display)

### Score Calculation
Implemented in both backend and scoreboard displays:
1. Sum scores per team across all categories
2. Apply 2x multiplier if `joker === true` for any category
3. Sort teams by total (descending) for ranking display

## Key Development Patterns

### Request Handling Pattern
Backend uses request isolation checks to prevent form conflicts:
```php
// Prevent action if delete is being processed
if (!isset($_POST['delete_team']) && isset($_POST['rename_team_submit'])) { ... }
```
**Critical**: When adding new actions, check for conflicts with existing POST handlers

### HTML Generation
All UI is generated via `echo` statements - no templating engine. Structure:
1. Authentication check exits early if not authenticated
2. Load/validate game.json state
3. Determine which view to show (manage_teams, modify_score, etc.)
4. Generate appropriate HTML section with inline styles

### Form Navigation Pattern
Multi-step processes (e.g., score modification) use GET/POST parameters to track state:
- Step 1: Select category (`?edit_category=1`)
- Step 2: Show form with pre-filled values
- Step 3: Process POST save and redirect back

## Display Conventions

### Scoreboard Display Files
- `dritterplatz.php` - 3rd place display (team layout appears specific)
- `ersterplatz.php` - 1st place display
- `zweiterplatz.php` - 2nd place display
- All use `styles.css` with responsive font sizing based on team name length

### Font Sizing Strategy (in styles.css)
Backend dynamically selects CSS classes based on team name length:
- `scorebox_headline_large` (3.5em): name < 10 chars
- `scorebox_headline_normal` (2.0em): name < 20 chars  
- `scorebox_headline_small` (1.4em): longer names

## Critical Implementation Details

### JSON File Management
- Always use `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE` when writing
- After adding/deleting teams, file_put_contents must be called with updated array
- Team indices are NOT preserved on deletion - `array_splice()` reindexes

### Security Notes
- **Plain text password**: Auth password hardcoded (`$authPassword`)
- **Session tracking**: Via cookies + server-side hash list (no session library)
- **XSS prevention**: Use `htmlspecialchars(..., ENT_QUOTES | ENT_HTML5, 'UTF-8')`

### Common Issues to Avoid
1. **Joker calculation**: Applied at sum-time, not stored as doubled value
2. **Team/category sync**: If `$numCategories` changes, old `game.json` breaks - must be deleted
3. **Redirect loops**: Always use full redirect after form submit
4. **Form field indexing**: Use team index (0, 1, 2...) not team name in form fields

## File Organization
- **Root**: Main entry points (backend.php is empty - use `_old/backend.php`)
- **_old/**: All working source code (legacy structure preserved)
  - `backend.php` - Admin interface (auth, team management, score entry)
  - `scoreboard.php` - Generic scoreboard logic
  - `dritterplatz.php`, `ersterplatz.php`, `zweiterplatz.php` - Ranked displays
  - `styles.css` - Responsive typography for displays
  - `clients.json` - Authenticated client hashes
  - `game.json` - Team and score state

## Testing & Validation
- Manual testing via browser (no automated tests in repo)
- Clear browser cache when schema changes (due to JSON refresh logic)
- Frontend uses `<meta http-equiv="refresh" content="5">` for live score updates
