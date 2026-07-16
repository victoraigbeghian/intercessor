# Architecture

Intercessor is organized as a modern WordPress plugin using PHP namespaces, a custom PSR-4 autoloader, and the BerlinDB library for custom database tables.

## Directory Structure

```
intercessor/
├── intercessor.php          Main plugin file (bootstrap)
├── index.php                Silence-is-golden security file
├── uninstall.php            Cleanup on deletion
├── readme.txt               WordPress.org readme
├── assets/
│   ├── css/                 Stylesheets (3 files)
│   │   ├── admin.css        Admin dashboard styles
│   │   ├── iconfont.css     Icon font definitions
│   │   └── public.css       Front-end block styles
│   ├── fonts/               Intercessor icon font (eot/svg/ttf/woff)
│   └── js/
│       ├── admin/
│       │   └── admin.js     Admin dashboard script
│       ├── blocks/          Webpack-built Gutenberg blocks
│       │   ├── prayer-form/
│       │   ├── prayer-history/
│       │   └── prayer-wall/
│       └── public/          Front-end scripts (unminified)
│           ├── prayer-form.js
│           ├── prayer-history.js
│           └── prayer-wall.js
├── src/                     PHP source (autoloaded)
│   ├── Plugin.php           Central controller
│   ├── Loader.php           Hook registration
│   ├── Activator.php        Activation routines
│   ├── Roles.php            Roles and capabilities
│   ├── Requirements.php     PHP/WP version checks
│   ├── Admin/               Dashboard pages, settings, list tables
│   ├── Block/               Gutenberg block render callbacks
│   ├── Database/            BerlinDB tables, schemas, queries, rows
│   ├── Http/                Request handling, REST API, submissions
│   ├── Public/              Front-end asset loading
│   ├── Reports/             Reporting views and stats
│   ├── Tools/               Import/export handlers
│   ├── Util/                Cron, rate limiter, profanity filter, etc.
│   ├── blocks/              Gutenberg block source (JSX/JS)
│   └── includes/berlindb/   Bundled BerlinDB library
├── templates/               PHP templates
│   ├── admin/               Dashboard page templates
│   └── blocks/              Block render templates
├── languages/               Translation files (.pot, .po, .mo)
├── scripts/                 Build/packaging scripts
└── tests/                   PHPUnit test suite
```

## Autoloading

A custom PSR-4 autoloader (`Util\Autoloader`) maps the `Intercessor\` namespace to the `src/` directory. Class names map directly to filenames: `Intercessor\Block\Prayer_Wall_Block` loads from `src/Block/Prayer_Wall_Block.php`.

## Plugin Lifecycle

1. `intercessor.php` defines constants (`INTERCESSOR_VERSION`, `INTERCESSOR_DIR`, `INTERCESSOR_URL`, `INTERCESSOR_FILE`), registers the autoloader, and calls `Plugin::instance()`.
2. `Plugin` is a singleton that wires up the `Loader`, `Admin_Loader`, `Public_Loader`, `Block_Loader`, `Rest_Api`, `Cron_Handler`, and other subsystems via WordPress hooks.
3. `Activator` runs on plugin activation to create database tables, register roles, and store the plugin version.
4. `uninstall.php` runs on deletion and optionally drops all data based on the `delete_data_on_uninstall` setting.

## Database Layer

Intercessor uses [BerlinDB](https://github.com/berlindb/core), a lightweight ORM-like library for WordPress custom tables. Each entity has four classes:

- **Table** — defines the table name, version, and schema SQL
- **Schema** — declares columns and their types
- **Query** — provides CRUD operations and query building
- **Row** — represents a single record as a typed object

See [[Database Schema]] for the full table definitions.

## Block Registration

Blocks are registered via `Block_Loader`, which maps each block slug to a render-callback class and registers them using `register_block_type()` from the built `assets/js/blocks/{slug}/` directory (which contains `block.json`, compiled `index.js`, and `index.asset.php`).

The block source (JSX) lives in `src/blocks/` and is compiled with webpack. See [[Building from Source]] for build instructions.
