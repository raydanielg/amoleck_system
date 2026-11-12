# Module Completion Report

| Module | Status | Tested |
|---|---|---|
| A. Authentication (login, logout, reset, rate-limit, deactivate, first-login change) | Completed | Yes |
| B. Dashboard (real metrics: patients, visits, expenses, by-category, recent) | Completed | Yes |
| C. Patient management (register, auto-number, search, profile, edit, archive/restore, duplicates, print) | Completed | Yes |
| D. Visits & treatment (create, edit, complete, void-admin, follow-up, print, multi-service, correction window) | Completed | Yes |
| E. Service management (catalogue, categories, prices, order, activate/deactivate) | Completed | Yes |
| F. Expenses (CRUD, categories, approval mode, receipts, void, filters) | Completed | Yes |
| G. Reports (register, registrations, visits, service usage, expenses, by-category; CSV+PDF) | Completed | Yes |
| H. User management (create staff, reset, activate/deactivate, last login) | Completed | Yes |
| I. System settings (all groups, DB-driven, audited) | Completed | Yes |
| J. Audit logs (append-only, filters, actor/entity/IP) | Completed | Yes |

## Fixed roles
`ADMIN` and `CLINIC_STAFF` are hardcoded (`App\Models\User` constants + `role:` middleware). No role designer exists.

## Stack deviations from spec (deliberate)
- Bootstrap/AdminLTE instead of Tailwind (per project owner's UI choice; PDF allows "Tailwind or Bootstrap").
- Hugeicons instead of Lucide (equivalent icon font).
- SQLite in dev; migrations are portable to MySQL/MariaDB for production.

## Known limitations / future work
- No patient payments/invoicing (explicitly out of V1 scope).
- No SMS/email notifications yet (settings placeholders only).
- No automated backup scheduler inside the app (server cron documented).
- Patient photo field not implemented.

## Admin Control Center (Phase 2 Extension)

Added a complete administration workspace on top of the original system:

- **Financial Management**: invoices (auto `INV-` refs, service price snapshots, discounts with mandatory reasons), payments (`PAY-`, partial + full, overpayment blocked, row-level locking), refunds (`RFD-`, capped at refundable amount), invoice voiding (blocked once paid).
- **Financial Overview**: gross billed, collected, outstanding receivables, discounts, refunds, net collected, approved expenses, operating surplus — all from real transactions, period-filtered, 12-month trend chart.
- **Expense Approval Center**: inbox (pending / returned / decided), approve / reject / return-for-correction / mark-as-paid, full decision log (`expense_approvals`), internal comments, separation of duties (submitter can never self-approve), configurable thresholds, auto-approve-below, staff limits, mandatory receipts, resubmission workflow.
- **Staff Management**: directory with per-staff counts, performance page (patients / visits / expenses per month, last activity).
- **Appointments & Follow-ups**: scheduling (`APT-` refs, status flow), follow-up registry auto-created from visit follow-up dates, overdue tracking, admin due-notifications.
- **Notification Center**: bell in header with unread count, notification history, mark-read flows, per-type preferences in settings.
- **Executive Dashboard**: role-aware KPIs, period filter (today → custom range), Chart.js revenue-vs-expense trend, visits trend, service popularity, revenue-by-service, expense categories.
- **Reports**: 8 new reports (revenue, outstanding invoices, refunds, discounts, revenue-vs-expenses, approval history, staff activity) — CSV + PDF, financial reports are admin-only.
- **Service Price History**: every price change is logged; historical invoices keep their price snapshots.
- **Settings Center**: billing, approval rules, appointments, payment methods and notification preferences — all database-driven.

UI pattern: right-side offcanvas drawers for quick create/edit actions, modals for destructive/sensitive decisions, inline dropdown status changes. Full pages remain for major workspaces.
