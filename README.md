# Bidscape

Bidscape is a Laravel Cloud-ready sales workflow app for contractors. V1 focuses on lead intake, estimate PDF/email delivery, public customer approval/signature or decline, deposit recording, sold-project registration, and immutable job packet handoff.

The app is intentionally sales-only. Do not add scheduling, field execution, payroll, inventory consumption, actual-cost tracking, or production job statuses to V1.

## Stack

- Laravel 13, Sail, MySQL, Redis
- Breeze auth, Inertia, React, TypeScript
- Tailwind CSS v4, Vite, Ziggy
- PHPUnit, ESLint, Prettier, Pint

## Local Setup

```bash
cd /Users/tsmacbookpro13/Sites/bidscape
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm run dev -- --host=0.0.0.0
```

Open [http://localhost:8092](http://localhost:8092).

Demo login:

- Email: `nick@desertridge.test`
- Password: `password`

Configured local ports:

- App: `http://localhost:8092`
- Vite: `5178`
- MySQL forward: `3312`
- Redis forward: `6384`

## Verification

```bash
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail artisan test
./vendor/bin/sail npm run build
./vendor/bin/sail npm run lint
```

If Vite reports a missing Rolldown native binding inside Sail, run:

```bash
./vendor/bin/sail npm install
```

That installs the Linux container optional dependency in the shared `node_modules` directory.

## Main Routes

- `/dashboard`
- `/leads`
- `/customers`
- `/estimates`
- `/estimates/{estimate}/builder`
- `/jobs`
- `/jobs/{job}/packet`
- `/assemblies`
- `/assemblies/{assembly}/formula`
- `/materials`
- `/reports`
- `/settings`

## Domain Notes

All user-facing business data is company-scoped. Money is stored as integer cents, quantities and unit values use decimal columns, and percentages use basis points.

Laravel already owns the queue `jobs` table, so Bidscape sold-project records use the `project_jobs` table through `App\Models\Job`.

Job handoff statuses are limited to:

- `sold`
- `contract_pending`
- `packet_ready`
- `handed_off`
- `archived`

## Laravel Cloud Defaults

The project is Cloud-ready but not deployed in this pass.

- Build command target: `composer install --no-dev && npm run build`
- Deploy command target: `php artisan migrate --force`
- Production file uploads should use Laravel Cloud object storage through Laravel's storage abstraction.
- Queue code should stay on Laravel's queue abstraction so local database queues can map cleanly to managed queues later.

Reference screenshots and the build prompt are copied into `/Users/tsmacbookpro13/Sites/bidscape/docs/reference`.
