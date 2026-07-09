# Hostel Management System — Laravel + Vue + Inertia + Breeze

This is the converted version of the original Kimi-built app (React + tRPC + Drizzle/MySQL),
rebuilt on **Laravel 11 + Vue 3 + Inertia.js**, with **Breeze**-style authentication (hand-written,
plain `.vue`/`.js` files — no TypeScript/TSX).

## What was converted

| Area | Source (Kimi) | Converted to |
|---|---|---|
| Database schema | `app/db/schema.ts` (Drizzle, 24 tables) | `database/migrations/*` (24 migrations) + `app/Models/*` (24 Eloquent models) |
| Auth | Kimi platform SSO (`unionId`) | Laravel Breeze-style email/password auth (login, register, forgot/reset password, profile) |
| tRPC routers → Controllers | `api/routers/building.ts` | `app/Http/Controllers/BuildingController.php` |
| | `api/routers/floor.ts` | `app/Http/Controllers/FloorController.php` |
| | `api/routers/room.ts` | `app/Http/Controllers/RoomController.php` |
| | `api/routers/resident.ts` | `app/Http/Controllers/ResidentController.php` |
| | `api/routers/dashboard.ts` | `app/Http/Controllers/DashboardController.php` |
| | WhatsApp send (simulated) | `app/Http/Controllers/WhatsappController.php` |
| React pages (.tsx) | `src/pages/**/*.tsx` | Inertia Vue pages `resources/js/Pages/**/*.vue` |
| Sidebar layout | `src/components/layout/AppLayout.tsx` | `resources/js/Layouts/AuthenticatedLayout.vue` |

### Fully working modules (UI + backend + DB)
- **Auth**: Login, Register, Forgot/Reset Password, Profile (update info, change password, delete account)
- **Dashboard**: KPI cards, occupancy trend chart, recent activity feed
- **Infrastructure**: Buildings (CRUD), Floors (CRUD), Rooms (CRUD + auto bed generation), Inventory
- **Residents**: List with filters/search/pagination, Add (with **mandatory photograph upload** and optional
  room/bed allotment at admission time), View, Delete — resident list now shows the **currently assigned room**
  and flags active residents with no room assigned
- **Check-In / Check-Out**: allot a bed to any resident without one, or check an active resident out
  (frees the bed, updates room/building occupancy automatically)
- **Billing**: fee invoices, payment recording, outstanding/paid tracking
- **WhatsApp**: Send message (individual / all residents / all parents) with templates, message log
- **Student Support**: Complaints (log/track/resolve), Leaves (request/approve/reject with auto gate-pass code),
  Emergency Alerts (raise/resolve)
- **Admin**: Staff/user management (create accounts, change role, activate/deactivate, delete) plus a
  **dynamic per-user permission matrix** — see below
- **Gate Management**: gate passes (day out, night pass, visitor pass, late entry) with approval flow
- **Disciplinary Action**: incident logging with warning levels and resolution tracking
- **Hostel Mess**: weekly menu planner (breakfast/lunch/snacks/dinner per day)
- **Student Tracking**: manual entry/exit logging
- **Analytics**: a full 3-tab dashboard —
  - *Occupancy*: gender/course/institute/batch/year filters, capacity/filled/vacant summary cards
    with a donut, Building-Wise ⇄ Room-Wise bar chart toggle, Hostel-Wise ⇄ Unit-Wise occupancy
    cards (click a building to expand its detail — **Bed-Wise** shows bed-level counts/Total Beds,
    **Room-Wise** shows room-level counts/Total Room, **Full building detail** shows an inline
    floor-by-floor heat map across every bed type in that building at once), an info icon per row
    opening a single-unit-type **occupancy heat-map modal** (colour-coded occupied/partially
    filled/vacant/no-capacity, filterable), and real **.xlsx exports** that match exactly what's on
    screen (Hostel Summary, Bed Wise, Room Wise, Full Building Detail, Unit Summary) via SheetJS
  - *Leaves*: date-range presets (Today/Current Week/Last 7 days/.../Custom), total requests +
    students-on-leave cards, hostel-wise leave cards, and a Monday–Sunday leave-frequency chart
  - *Complains*: date-range presets, raised/resolved/pending/rejected cards, hostel-wise breakdown
    with success-rate donut (All Buildings or filtered to one), and a "Type of Complaints" donut by
    priority (urgent/high/medium/low)
- **Reports**: quick tabular reports (admissions, payments, outstanding fees, active check-ins)

Every module in the original 14-module proposal now has at least a working first version. Some
(Billing, Mess, Reports, Analytics) are intentionally simple first passes — the patterns are consistent
so extending them (e.g. adding PDF invoice generation, richer charts) is straightforward from here.

### Bug fixes / gaps closed in this pass
1. **Resident photograph was missing and not enforced** — a photo is now a **required** field when
   adding a resident (`ResidentController@baseRules`), stored via Laravel's public disk, shown as an
   avatar throughout the UI.
2. **No link between a resident and their room** — added `Resident::currentStay()` (latest active
   `ResidentStay`), eager-loaded on the Residents index and shown as a "Room" column; active residents
   with no room assigned are now flagged with a warning icon instead of silently showing nothing.
3. **No actual room/bed allotment workflow existed** — added `RoomAllotmentService` (used by both the
   Residents "add" form and the new Check-In/Check-Out module) that creates a `ResidentStay`, marks the
   bed occupied, and keeps `Room.occupied_beds`/`status` and `Building.occupied` in sync in one DB
   transaction; checkout reverses all of that.

## All 24 database tables (migrations + models already created)

`users, buildings, floors, rooms, beds, residents, resident_stays, documents, vehicles,
fee_invoices, payments, attendance, complaints, leaves (→ LeaveRequest model), emergency_alerts,
mess_menu, inventory, room_inventory, gate_passes, disciplinary_actions, whatsapp_messages,
activity_log, entry_exit_logs, whatsapp_settings`

## Dynamic permission system (Admin > Users > Manage Permissions)

Roles alone weren't enough — you asked for the ability to pick, per user, exactly which modules
they can view/create/edit/delete in. Here's how it works:

- **`config/modules.php`** is the single source of truth: every permission-able module in the app
  (Buildings, Rooms, Residents, Billing, Complaints, ... 18 in total) with the actions that make
  sense for it (most get `view/create/edit/delete`, read-only screens like Reports/Analytics only
  get `view`). Add a module here and it automatically shows up in the permission matrix — no
  frontend changes needed.
- **`users.permissions`** is a JSON column (`{"residents": ["view","create"], "billing": ["view"]}`)
  storing each non-super-admin user's explicit grants. `super_admin` always bypasses this and gets
  full access everywhere — permissions can't be restricted for that role.
- **`User::hasPermission($module, $action)`** is the single check used everywhere (model method,
  not scattered `if` statements).
- **`app/Http/Middleware/CheckPermission.php`** (aliased as `permission`) is attached to every
  module route in `routes/web.php`, e.g. `->middleware('permission:residents,create')`. A 403 is
  thrown server-side even if someone bypasses the UI — permissions are enforced on the backend,
  not just hidden in the frontend.
- **The sidebar** (`AuthenticatedLayout.vue`) hides any module/menu item the logged-in user has no
  `view` permission for, computed from `auth.permissions` shared on every Inertia request via
  `HandleInertiaRequests`.
- **New accounts start view-only** (`User::defaultPermissions()`) except the Admin/User-management
  module itself, which is never granted by default — only a super admin can opt someone into it.
  The very first account created on a fresh install automatically becomes `super_admin` (see
  `RegisteredUserController`) so there's always someone who can grant permissions to everyone else.
- **The UI**: on `Admin > Users`, every non-super-admin row has a "Manage Permissions" button that
  opens a checkbox matrix (modules × view/create/edit/delete, with "All"/"None" quick-set per row),
  saved via `PUT /admin/users/{user}/permissions`.



This project's PHP dependencies could not be installed in the environment that generated it
(no access to packagist.org), so **you need to run composer install yourself** the first time.

```bash
# 1. Install PHP dependencies
composer install

# 2. Install JS dependencies
npm install

# 3. Environment
cp .env.example .env   # already present, just edit DB credentials
php artisan key:generate

# 4. Configure your database in .env (DB_DATABASE, DB_USERNAME, DB_PASSWORD)

# 5. Link storage so uploaded resident photos are publicly viewable
php artisan storage:link

# 6. Run migrations + demo seed data (1 admin user, 1 building, floors, rooms, 1 resident)
php artisan migrate --seed

# 7. Run the dev servers (two terminals)
php artisan serve
npm run dev
```

Demo login after seeding:
- Email: `admin@hostel.test`
- Password: `password`

## Notes on the conversion

- **No TypeScript/TSX** — all frontend files are plain `.vue` and `.js`, as requested.
- **No tRPC / no separate API layer** — Inertia passes data directly as page props, and forms
  post straight to Laravel controllers/routes (`resources/js/Pages/**` ↔ `routes/web.php`).
- **Drizzle enums → MySQL `enum` columns** via Laravel's `$table->enum()`, same values as the
  original schema.
- **Auto bed generation**: creating a Room auto-creates `Bed` rows for its capacity, mirroring
  the original tRPC `room.create` behavior.
- **Resident code generation** (`PP-YYYY-XXXX`) reproduced exactly as in the original resident
  router.
- The `leaves` table's Eloquent model is named `LeaveRequest` (PHP reserves `leave` less commonly,
  but to avoid confusion with soft "Leave" naming clashes it's called `LeaveRequest`); the actual
  table name is still `leaves`.
- Sidebar navigation mirrors the original 14-module structure 1:1, including collapsible
  sub-menus for Infrastructure, Residents, and Student Support.

## Continuing development

All 14 modules from the proposal now have a working first version (see list above). Good next
steps to make each module more production-ready:
- **Billing**: PDF invoice/receipt generation, recurring monthly invoice auto-generation
- **Reports**: CSV/PDF export instead of on-screen tables only
- **Analytics**: swap the hand-rolled bar/donut visuals for a charting library (e.g. Chart.js, already
  listed in `package.json`)
- **WhatsApp**: connect a real gateway (e.g. Baileys, Meta Cloud API) in `WhatsappController` instead
  of just logging to `whatsapp_messages`
- **Role-based access**: routes currently only require `auth`; add a `role` middleware/policy so e.g.
  only `super_admin`/`hostel_admin` can reach `/admin/users`
- **Documents & Vehicles**: `documents` and `vehicles` tables/models exist but have no controller/UI
  yet — same recipe as everything else (controller + Vue page + route)

The pattern is consistent throughout: Controller (validate → Eloquent → `Inertia::render`/`back()`) +
Vue page (`useForm` + `Modal` + table) + route in `routes/web.php`, so extending any module follows
the same recipe used for Buildings/Rooms/Residents/Billing/etc.