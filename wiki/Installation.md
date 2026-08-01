# Installation

## From WordPress.org (Recommended)

1. In your WordPress dashboard, go to **Plugins → Add New**.
2. Search for **Intercessor**.
3. Click **Install Now**, then **Activate**.

## Manual Upload

1. Download the latest `.zip` from [GitHub Releases](https://github.com/victoraigbeghian/intercessor/releases) or [WordPress.org](https://wordpress.org/plugins/intercessor).
2. Go to **Plugins → Add New → Upload Plugin**.
3. Choose the `.zip` file and click **Install Now**.
4. Activate the plugin.

## From Source (Developers)

```bash
cd wp-content/plugins
git clone https://github.com/victoraigbeghian/intercessor.git
cd intercessor
npm install
npm run build
```

Then activate from **Plugins** in the dashboard.

## What Happens on Activation

When activated, Intercessor automatically:

- Creates six custom database tables (prefixed with your WordPress table prefix) for prayer requests, requesters, prayer history, prayed counts, and notes.
- Registers three custom roles: **Prayer Manager**, **Prayer Warrior**, and **Requester**.
- Grants the `administrator` role full Intercessor capabilities.
- Registers three Gutenberg blocks: **Prayer Form**, **Prayer Wall**, and **Prayer History**.

No pages are created automatically — you add blocks to your pages using the WordPress editor.

## Uninstallation

If the **Delete All Data on Uninstall** setting is enabled (under Settings → Data Management), deactivating and deleting the plugin will remove all Intercessor database tables, options, roles, and capabilities. If disabled (the default), your data is preserved and will be available if you reinstall later.
