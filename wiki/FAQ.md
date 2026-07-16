# FAQ

## General

**Does Intercessor work with any WordPress theme?**
Yes. Intercessor uses standard Gutenberg blocks that work with any block-enabled theme. The blocks inherit your theme's typography and spacing, with minimal custom styling for the prayer-specific UI elements.

**Does the plugin create any pages automatically?**
No. You create pages and add Intercessor blocks to them using the WordPress editor. This gives you full control over where and how the prayer features appear on your site.

**Can visitors submit prayer requests without creating an account?**
Yes, by default. You can require login, enable optional registration during submission, or allow fully anonymous requests — all configurable under Settings → General.

**Is the plugin translatable?**
Yes. All user-facing strings use WordPress i18n functions. Translation files are included for German, Spanish, French, Italian, and Brazilian Portuguese. Additional translations can be contributed via the WordPress.org translation platform.

## Privacy & Security

**What personal data does Intercessor store?**
Intercessor stores the requester's name, email address, and IP address alongside their prayer request text. If user registration is enabled, a WordPress user account is also created. All data is stored in your WordPress database.

**Is the data shared with any external services?**
Only if you enable Google reCAPTCHA (optional, disabled by default). When enabled, form submissions are verified against Google's reCAPTCHA API. No other data is sent to external services. See the "External Services" section in `readme.txt` for the full disclosure.

**Can I delete all plugin data?**
Yes. Enable "Delete All Data on Uninstall" under Settings → Data Management, then deactivate and delete the plugin. This removes all six database tables, all plugin options, and all custom roles/capabilities.

## Moderation

**How do I approve prayer requests?**
Go to Intercessor → Prayer Requests in the admin sidebar. Pending requests appear at the top. You can approve, reject, or spam individual requests, or use bulk actions to moderate multiple requests at once.

**Can I auto-approve all requests?**
Yes. Enable "Auto-Approve Requests" under Settings → General. Submitted requests will be published immediately without moderation.

**What does the profanity filter do?**
When enabled, the filter checks submitted prayer requests against a configurable list of prohibited words. Requests containing any of those words are automatically rejected with a user-friendly error message.

## Blocks

**Can I use the same block on multiple pages?**
Yes. Each block instance is independent. You can have a Prayer Form on one page and a Prayer Wall on another, or put both on the same page.

**Why does the Prayer History block show a login prompt?**
The Prayer History block is only available to logged-in users, since it displays their personal submissions. Guests see a login/registration form instead.

**Can I customize the block appearance?**
The blocks use standard CSS classes that you can target in your theme's stylesheet or the WordPress Customizer's Additional CSS. See [[Gutenberg Blocks]] for the class names.

## Troubleshooting

**The "I prayed for this" button doesn't work.**
Check that the user is logged in (the button only works for authenticated users). Also verify that no JavaScript errors appear in the browser console — a conflict with another plugin's scripts is the most common cause.

**Email notifications aren't being sent.**
Intercessor uses WordPress's `wp_mail()` function. If emails aren't arriving, the issue is usually with your WordPress email configuration, not the plugin. Install an SMTP plugin (like WP Mail SMTP) to route emails through a proper mail server.

**The reCAPTCHA badge doesn't appear.**
Verify that you've entered both the site key and secret key correctly under Settings → reCAPTCHA, and that the keys match the version you selected (v2 vs v3). Also check that reCAPTCHA is enabled for the specific page (Prayer Request Form and/or Prayer History Page checkboxes).
