# Settings

All settings are stored under a single WordPress option: `intercessor_settings`. Access them at **Intercessor → Settings** in the admin sidebar.

## General Tab

### Approval Rules
| Setting | Description | Default |
|---------|-------------|---------|
| Auto-Approve Requests | Skip moderation and publish requests immediately | Off |
| Require Login to Submit | Only logged-in users can submit requests | Off |
| Enable User Registration | Allow new users to create an account during submission | Off |
| Generate Username for New Users | Auto-generate a username from the email address | Off |
| Generate Password for New Users | Auto-generate a password and email it to the user | Off |
| Allow Anonymous Requests | Let visitors submit without providing a name | Off |
| Allow Private Requests | Show a "Keep my prayer request private" checkbox on the form. Private requests are seen only by admins and will never appear on the Prayer Wall | Off |
| Max Requests Per Email / Day | Rate limit per email address (0 = unlimited) | 0 |

### Site Terms
| Setting | Description |
|---------|-------------|
| Show Site Terms | Display a terms link on the submission form |
| Terms Label | The link text displayed to users |
| Terms URL | URL to your terms page |
| Show Privacy Policy | Display a privacy policy link |
| Privacy Policy Label | The link text displayed |
| Privacy Policy URL | URL to your privacy policy page |
| Require Acceptance of Terms | Users must check a box to submit |

### Moderation Options
| Setting | Description |
|---------|-------------|
| Enable Profanity Filter | Automatically reject requests containing prohibited words |
| Prohibited Words | Comma-separated list of words to filter |
| Moderator Role | Which WordPress role can moderate requests |

## Notifications Tab

### Email Notifications
| Setting | Description | Default |
|---------|-------------|---------|
| Notify Admin on New Request | Send an email when a new request is submitted | Off |
| Notify Requester on Receipt | Send a confirmation email to the submitter | Off |
| Notify Requester on Status Change | Email the requester when their request is approved/rejected | Off |
| Admin Notification Email | The email address that receives admin notifications | Site admin email |
| From Name | The sender name on outgoing emails | Site name |
| From Email Address | The sender address on outgoing emails | Site admin email |

### Prayer-Count Notifications (Scheduled)
| Setting | Description | Default |
|---------|-------------|---------|
| Enable Prayer-Count Notifications | Send digest emails to requesters with their prayer counts | Off |
| Send Frequency | How often to send (daily, weekly, monthly) | Weekly |
| Send Hour (UTC) | Hour of day to send (0–23) | 9 |
| Send Minute | Minute of hour (0–59) | 0 |

## Display Tab

| Setting | Description | Default |
|---------|-------------|---------|
| Requests Per Page | Default number of requests shown in the Prayer Wall block | 10 |
| Show Submission Date | Display the date each request was submitted | On |
| Show Requester Name | Display the requester's name alongside their request | On |
| Date Format | PHP date format string for displayed dates | WordPress default |

## reCAPTCHA Tab

| Setting | Description |
|---------|-------------|
| Site Key (public) | Your Google reCAPTCHA site key |
| Secret Key (private) | Your Google reCAPTCHA secret key |
| reCAPTCHA Version | Choose between v2 (checkbox) or v3 (invisible score-based) |
| v3 Score Threshold | Minimum score to accept (0.0–1.0, v3 only) |
| Prayer Request Form | Enable reCAPTCHA on the submission form |
| Prayer History Page | Enable reCAPTCHA on the user history page |

To obtain reCAPTCHA keys, visit the [Google reCAPTCHA admin console](https://www.google.com/recaptcha/admin).

## Export Tab

| Setting | Description |
|---------|-------------|
| Include Prayer Content in Requests Export | Include the full prayer text in CSV exports |
| Filter Requests Export by Status | Export only requests matching a specific status |
| Prayed Counts Export Mode | Export raw counts or aggregated totals |

## Data Tab

| Setting | Description | Default |
|---------|-------------|---------|
| Delete All Data on Uninstall | Remove all Intercessor tables, options, roles, and capabilities when the plugin is deleted | Off |
