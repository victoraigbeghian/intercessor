# Building from Source

The Gutenberg block editor scripts are written in JSX and compiled with webpack. The PHP source requires no build step — it's loaded directly via the autoloader.

## Prerequisites

- [Node.js](https://nodejs.org/) 18 or later
- npm (included with Node.js)

## Setup

```bash
git clone https://github.com/victoraigbeghian/intercessor.git
cd intercessor
npm install
```

## Build Commands

| Command | Description |
|---------|-------------|
| `npm run build` | Compile block scripts for production (minified) |
| `npm run start` | Watch mode — recompiles on file changes during development |
| `npm run package` | Build + create a distribution `.zip` for WordPress.org |
| `npm run zip` | Create the `.zip` without rebuilding (uses existing compiled assets) |

## What Gets Built

The webpack build compiles three Gutenberg block entry points:

| Source | Output |
|--------|--------|
| `src/blocks/prayer-form/index.js` | `assets/js/blocks/prayer-form/index.js` |
| `src/blocks/prayer-wall/index.js` | `assets/js/blocks/prayer-wall/index.js` |
| `src/blocks/prayer-history/index.js` | `assets/js/blocks/prayer-history/index.js` |

Each output directory also receives a copy of its `block.json` (via `copy-webpack-plugin`) and an `index.asset.php` (via `@wordpress/dependency-extraction-webpack-plugin`) that declares WordPress script dependencies.

## Build Stack

| Tool | Purpose |
|------|---------|
| webpack | Module bundler |
| Babel | JSX/ES6+ transpilation |
| @wordpress/dependency-extraction-webpack-plugin | Externalizes `@wordpress/*` packages so they're loaded from WordPress core at runtime |
| copy-webpack-plugin | Copies `block.json` files into the output directories |
| cross-env | Cross-platform `NODE_ENV` setting |

## Packaging for Distribution

Running `npm run package` creates `dist/intercessor-{version}.zip` (version is read from `intercessor.php`). The distribution zip includes only runtime files:

**Included:** `intercessor.php`, `index.php`, `uninstall.php`, `readme.txt`, `CHANGELOG.md`, `LICENSE`, `src/` (PHP + block source), `assets/`, `templates/`, `languages/`

**Excluded:** `node_modules/`, `tests/`, `.github/`, build config files (`webpack.config.js`, `package.json`, `phpcs.xml`, `composer.json`, etc.)

## Linting

```bash
npm run lint:js     # ESLint on block source
```

For PHP coding standards, the project includes a `phpcs.xml` configuration for use with [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer) and the [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards).
