# Pub Quiz Score Manager

A lightweight PHP-based score management system for hosting pub quizzes. Provides password-protected admin interface for team and category management with persistent JSON file storage.

## Files Overview

### `backend.php`
**Main application file** containing the complete admin interface. Handles:
- **Authentication**: Password-based login with secure token validation
- **Team Management**: Add, rename, and delete teams with auto-incrementing IDs
- **Category Management**: Placeholder for managing quiz categories
- **Game Administration**: Create new game with automatic backup of previous game state
- **Client Tracking**: Records authenticated users with browser, IP, and timestamp information

**Key Features**:
- Token-based access control (GET/POST parameters)
- 30-day persistent authentication cookies
- Responsive mobile-first design
- State-based menu navigation

### `styles.css`
**Centralized styling** for the entire application. Includes:
- Button and form styling with responsive behavior
- Team card layouts
- Mobile optimization for devices ≤768px width
- Minimum 44px tap targets for touch devices
- Alert and warning styling
- Rename control toggles

**Mobile Features**:
- Full-width inputs on small screens
- Flex-based responsive layouts
- Touch-friendly button sizing

### `game.json`
**Game state persistence file** storing all quiz data:
```json
{
  "Teams": [
    {"id": 1, "name": "Team Name"}
  ]
}
```

- Auto-created on first run with empty Teams array
- Stores team information with unique IDs and names
- Will store Categories when category management is implemented
- Updated whenever teams or game settings change

### `clients.json`
**Authentication record file** tracking all authenticated admin sessions:
```json
[
  {
    "hash": "session_hash_here",
    "authenticated_at": "2026-01-28 14:30:45",
    "last_seen": "2026-01-28 14:35:12",
    "browser": "Chrome",
    "browser_version": "91.0.4472.124",
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0..."
  }
]
```

- Automatically created and managed by authentication system
- Records browser detection and IP information
- Updates last_seen timestamp on each request
- Validates stored hashes against login cookies

## Usage

1. **First Access**: Visit the application and log in with password `QWer1234`
2. **Team Management**: Add teams, rename them, or delete teams from "Manage Teams"
3. **Create New Game**: Start a fresh game (automatically backs up previous game state)
4. **Manage Categories**: Setup quiz categories (currently in development)

## Technical Details

- **Language**: PHP 7.4+
- **Database**: JSON files (no external database required)
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Security**: XSS prevention, token validation, secure password hashing
- **Responsive**: Mobile-first design with desktop support

## Data Storage

All application data is stored as JSON files in the root directory:
- `game.json` - Quiz game state (teams, categories)
- `clients.json` - Authenticated sessions and client metadata

No database or external services required. Delete `game.json` to reset the application.

## Authentication

- **Password**: QWer1234
- **Session Duration**: 30 days
- **Token**: Required with every request (GET/POST parameters)
- **Client Tracking**: Browser and IP information logged for all authenticated sessions

## File Organization

```
├── backend.php          # Main application
├── styles.css           # Responsive styling
├── game.json            # Game state (auto-created)
├── clients.json         # Auth records (auto-created)
└── README.md            # This file
```

The `_old/` directory contains legacy versions and alternative display files for public scoreboards (not part of current active application).
