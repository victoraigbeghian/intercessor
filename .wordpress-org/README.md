# WordPress.org Plugin Assets

Images in this directory are deployed to the WordPress.org SVN `assets/` folder
(separate from the plugin code) and appear on the plugin's directory page.

## Required files

| File | Size | Purpose |
|------|------|---------|
| `banner-772x250.jpg` or `.png` | 772×250 px | Plugin page header (low-DPI) |
| `banner-1544x500.jpg` or `.png` | 1544×500 px | Plugin page header (Retina) |
| `icon-128x128.jpg` or `.png` | 128×128 px | Plugin icon (low-DPI) |
| `icon-256x256.jpg` or `.png` | 256×256 px | Plugin icon (Retina) |

## Optional files

| File | Size | Purpose |
|------|------|---------|
| `screenshot-1.png` | Any | First screenshot shown in the Screenshots tab |
| `screenshot-2.png` | Any | Second screenshot, and so on |

Screenshots must be referenced in readme.txt under `== Screenshots ==`:

```
== Screenshots ==
1. The Prayer Form block on the front end.
2. The Prayer Wall block showing approved requests.
3. The admin Prayer Requests list with moderation actions.
```

## Notes

- Files here are NOT included in the plugin zip download — they live only on WordPress.org.
- The deploy workflow (deploy-to-wporg.yml) reads this directory via the ASSETS_DIR variable.
- Recommended format: PNG for icons, JPG for banners (smaller file size).
