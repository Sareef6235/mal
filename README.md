# MAL Premium LMS

A modular WordPress LMS and SaaS-style monthly plan/receipt management plugin.

## Features

- Admin, Ustad, and Student role capabilities.
- Activation-created MySQL tables for courses, plans, receipts, attendance, exams, results, and certificates.
- Premium glassmorphism WordPress admin dashboard with dark/light/neon theme switch.
- Monthly plan drag-and-drop board powered by SortableJS.
- Receipt approval/rejection, preview modal, bulk delete, print/share actions.
- Course management with video and PDF media attachment fields.
- Frontend shortcodes for student dashboard, courses, and receipts.
- REST API namespace for React, mobile, and PWA support: `mal-lms/v1`.
- Dynamic PWA manifest and service worker routes.

## Shortcodes

- `[mal_lms_dashboard]` renders the full student dashboard.
- `[mal_lms_courses]` renders visible courses.
- `[mal_lms_receipts]` renders receipt upload and history.
- `[mal_lms_certificate]` renders a printable/PDF-ready certificate shell.

## Developer Notes

All write operations validate WordPress nonces and capabilities. Database writes use sanitized values through `$wpdb->insert()`, `$wpdb->update()`, `$wpdb->delete()`, or `$wpdb->prepare()` for filtered reads.
