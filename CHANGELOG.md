# Changelog
## v1.1.0 (2026)

* Added single prayer request detail page in admin with notes panel and live prayer count.
* Added prayer-level notes with optional private flag.
* Added anonymous prayer recording — non-logged-in visitors can now use the Prayer Wall button.
* Improved prayed-count tracking — repeat interactions increment a counter rather than inserting duplicate rows.
* Notification cron job reschedules automatically on settings change.
* Internal: centralised settings schema into a Registry class.
* Fixed missing class imports in Admin_Loader causing fatal errors on moderation and note actions.

## v1.0.1 (2026)

* Added private prayer request option on the submission form.
* Added duplicate request prevention (blocks resubmission of the same subject by the same requester).
* Added status filter bar to the admin Prayer Requests list (All / Pending / Approved / Rejected / Archived / Private).
* Fixed "Settings saved." notice not appearing after saving settings.
* Fixed double blank lines throughout PHP source files.
* Redesigned the Prayer History block — guest login prompt is now a styled card with icon; authenticated view uses a card-per-request layout.

## v1.0.0 (2026)

* Initial release.
