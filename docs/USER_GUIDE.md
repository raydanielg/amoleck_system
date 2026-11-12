# User Guide

## Administrator
- **Dashboard**: patient/visit/expense totals for today and this month.
- **Patients**: register, search, view profile + visit history, archive (with reason), restore, print.
- **Visits**: any visit can be edited by admin (corrections are audit-logged); void with a mandatory reason.
- **Services** (Admin > Services): create/edit/deactivate services and categories, set price, duration, display order and note requirements. Changes never rewrite historical visits (snapshots are stored).
- **Expenses**: in approval mode (Settings > Expenses), approve or reject submitted expenses; void with reason. Download receipts.
- **Users** (Admin > Users): create Clinic Staff accounts (role is fixed — no new role types), reset passwords (forces first-login change), activate/deactivate.
- **Settings**: clinic profile, currency/timezone/date format, patient numbering & required fields, visit numbering & rules, expense prefixes/methods/approval mode.
- **Audit Log**: read-only chronological record of all sensitive actions.

## Clinic Staff
- **Register a patient**: Patients > Add Patient. Duplicate warning appears if a similar record exists — tick "Register anyway" only if it is genuinely a different person.
- **Record a visit**: open the patient profile > New Visit > fill complaint/assessment > select services > treatment notes > Save as Draft or Save & Complete.
- **Edit a draft visit**: Visits > open > Edit. Completed visits are read-only unless the Administrator sets a correction window in Settings.
- **Record an expense**: Expenses > Add Expense > title, category, amount, date > optional receipt > Save. You can edit your own expense while it is still Submitted.
- **Reports**: Reports > choose type > set date range > Run > export CSV/PDF.

## Notes for everyone
- All buttons perform real actions; toasts confirm success/failure.
- You will be logged out automatically after the configured inactivity period.
