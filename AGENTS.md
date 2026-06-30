# Bidscape Agent Notes

## Product Boundary

Bidscape V1 is a contractor sales workflow app. Keep the product focused on:

- Lead intake and conversion
- Estimate building
- Branded estimate PDF/email delivery
- Public estimate approval, typed signature, or decline
- Deposit recording
- Sold-project register
- Immutable job packet handoff

Do not add production operations features in V1:

- Scheduling
- Field execution
- Payroll
- Inventory consumption
- Actual-cost tracking
- Production job status tracking

## Technical Conventions

- Use Laravel, Inertia, React, TypeScript, Tailwind CSS v4, Ziggy, Sail, MySQL, and Redis.
- Keep all business records company-scoped.
- Store money as integer cents.
- Store percentages as basis points.
- Avoid float math for pricing. Use services such as `MoneyCalculator` and `EstimateCalculator`.
- Use `project_jobs` for Bidscape job records. Laravel's own queue table remains `jobs`.
- Use Laravel storage for attachments and generated packet PDFs.
- Keep formula execution behind `AssemblyFormulaEvaluator`; do not execute arbitrary PHP.

## UI Conventions

- Match the supplied screenshots: warm off-white background, white panels, subtle borders and shadows, deep navy text, forest green actions, pale sage active states, spacious tables, and Lucide icons.
- Reuse the shared Bidscape components in `/Users/tsmacbookpro13/Sites/bidscape/resources/js/Components/Bidscape`.
- Preserve responsive behavior: drawer navigation on mobile, wrapped KPI cards, stacked panels, horizontally scrollable wide tables, and reachable header actions.

## Verification

Run these before handing off meaningful changes:

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail npm run build
./vendor/bin/sail npm run lint
```

Use `/Users/tsmacbookpro13/Sites/bidscape/docs/reference/assets` as visual reference for browser checks.
