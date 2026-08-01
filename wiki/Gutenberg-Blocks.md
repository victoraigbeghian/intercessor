# Gutenberg Blocks

Intercessor provides three Gutenberg blocks. All three are found in the block inserter under the **Intercessor** category.

## Prayer Form

**Block name:** `intercessor/prayer-form`

The public submission form for prayer requests. Visitors fill in their name, email, and prayer request text, then submit. Depending on your settings, the form may also show:

- A terms and conditions checkbox and link
- A privacy policy checkbox and link
- Account registration fields (username/password) for new users
- A Google reCAPTCHA challenge

**Block settings (sidebar):**
- Requests per page (inherits global default)

**How it works:** On submission, the request enters the moderation queue (status: `pending`) unless auto-approve is enabled. The requester receives a confirmation email if notifications are turned on. If registration is enabled, a new WordPress user account is created and a confirmation email is sent.

## Prayer Wall

**Block name:** `intercessor/prayer-wall`

Displays approved prayer requests in a paginated wall. Each request card shows the prayer text and (optionally) the requester's name and submission date. Logged-in users see an "I prayed for this" button that increments a live counter via AJAX.

**Block settings (sidebar):**
- Requests per page
- Show submission date
- Show requester name

**CSS classes:**
- `.intercessor-prayer-wall` — outer container
- `.wp-block-intercessor-prayer-wall` — WordPress block wrapper
- `.intercessor-pray-btn` — the "I prayed" button
- `.intercessor-pray-count` — the prayer counter display

## Prayer History

**Block name:** `intercessor/prayer-history`

A logged-in user's personal dashboard showing all prayer requests they've submitted.

**Guest view:** A centred card with a praying hands icon, a welcoming title and message, and a fully styled login form. If user registration is enabled on the site, a "Register here" link appears below the form.

**Authenticated view:** Each prayer request is displayed as a card with a coloured left accent bar keyed to status, the subject and status badge in the header, a meta row showing the submission date and how many people have prayed, a content excerpt, and Edit / Delete action buttons. Clicking Edit opens an inline form inside the card where the user can update the subject and prayer text — the request is re-submitted for moderation on save. Deleted requests fade out and are removed without a page reload.

Users can:

- See the status of each request (pending, approved, rejected, archived, private)
- Edit pending requests (content is re-submitted for moderation)
- Delete their own requests

**How it works:** Uses AJAX calls to the Intercessor REST API (`/wp-json/intercessor/v1/`) for inline editing and deletion without page reloads.
