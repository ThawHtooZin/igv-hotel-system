# Hotel Booking and Room Management System
## Detailed implementation roadmap

**Technology baseline:** Laravel REST API, Laravel Sanctum for authentication, relational database, React with Vite, Tailwind CSS, and a small client-side data layer for API calls and cache management.

**Implementation rule:** Build the system in vertical slices. Each slice must have its database schema, Laravel model, API resource, request validation, controller/service logic, routes, automated tests, and the React page that consumes it. Do not build a page with fake data and postpone the API contract.

**Current product boundary:** This roadmap covers internal hotel operations. It does not implement online payment, invoices, refunds, public guest self-service, accounting, or channel-manager synchronization.

---

## Phase 0 — Confirm the project foundation

### Step 0.1 — Freeze the first-release scope

Read `system-analysis.md`, `data-model-notes.md`, `class-diagram.mmd`, `booking-sequence.mmd`, and `room-state-diagram.mmd`. Write a short project decision note confirming that the first release contains staff reservations, rooms, room types, availability, check-in, check-out, housekeeping, maintenance, users, roles, dashboards, and audit logs.

Explicitly record that payment and public website integration are deferred. Do not add payment columns or payment screens just because the public hotel website has booking-related UI.

**Deliverable:** approved scope note in the project repository.

### Step 0.2 — Create the two application workspaces

Create a Laravel API project and a React/Vite frontend. Establish separate environment files for API URL, database connection, and authentication settings. Add a README explaining how to start both applications.

Use a predictable structure:

```text
backend/
  app/Models
  app/Http/Controllers/Api/V1
  app/Http/Requests
  app/Http/Resources
  app/Services
  app/Policies
  app/Enums
  database/migrations
  database/seeders
  routes/api.php
frontend/
  src/pages
  src/components
  src/features
  src/lib/api
  src/lib/auth
  src/types
  src/hooks
```

### Step 0.3 — Establish coding rules before feature work

Define the API prefix as `/api/v1`. Use plural kebab-case resource names, JSON request and response bodies, HTTP status codes, Laravel Form Requests, API Resources, Policies, and service classes for business transactions.

Define the frontend rule that pages do not call `fetch` directly. All requests go through a shared API client that handles the base URL, authentication headers, JSON parsing, validation errors, and expired sessions.

**Exit criteria:** both applications start locally, the frontend can call a Laravel health endpoint, and the repository contains linting, formatting, and test commands.

---

## Phase 1 — Database and domain foundation

### Step 1.1 — Translate the class diagram into Laravel models

Read the class diagram and create the first Laravel models and factories for `User`, `Guest`, `RoomType`, `Room`, `Reservation`, `ReservationRoom`, `HousekeepingTask`, `MaintenanceBlock`, and `AuditLog`.

Create PHP enums for reservation status, reservation source, room operational status, housekeeping status, assignment status, housekeeping task type/status, and maintenance status. Keep enum values stable because they are stored in the database and returned by the API.

Add Eloquent relationships exactly as represented by the class diagram:

- `Guest hasMany Reservations`.
- `Reservation belongsTo Guest` and `hasMany ReservationRooms`.
- `ReservationRoom belongsTo Reservation and Room`.
- `Room belongsTo RoomType` and `hasMany ReservationRooms`, `HousekeepingTasks`, and `MaintenanceBlocks`.
- `HousekeepingTask belongsTo Room and User`.
- `AuditLog belongsTo User` through the acting user reference.

Do not put reservation workflow logic directly into a controller. The model may expose casts, relationships, scopes, and small domain helpers, while state-changing workflows belong in services.

### Step 1.2 — Create migrations in dependency order

Create migrations for users and roles first, then guests and room types, then rooms, then reservations and reservation-room assignments, then housekeeping tasks and maintenance blocks, and finally audit logs.

Add foreign keys, unique indexes, searchable indexes, and timestamps. Add a unique constraint for room numbers. Add indexes for reservation date ranges, reservation status, room status, housekeeping status, and maintenance dates.

Use a consistent date convention: arrival date is inclusive and departure date is exclusive. Store dates as database date values and return them as ISO date strings from the API.

### Step 1.3 — Seed safe development data

Create seeders for roles, an initial administrator, sample staff users, room types, rooms across multiple floors, guests, and a small set of reservations in different statuses. Use clearly fake development data unless the hotel owner has approved real operational data for the development environment.

**Exit criteria:** migrations run from an empty database, seeders produce a usable demo hotel, factories can create each entity, and model relationship tests pass.

---

## Phase 2 — Authentication, authorization, and API conventions

### Step 2.1 — Implement staff authentication

Configure Laravel Sanctum for staff login. Create endpoints for login, current-user retrieval, and logout. Return the authenticated user with role and permissions needed by the frontend navigation.

Implement middleware for authentication and a policy or permission layer for front-desk, housekeeping, manager, and administrator actions. Do not rely on hiding a React button as authorization; every protected operation must be checked by Laravel.

### Step 2.2 — Standardize API responses

Use Laravel API Resources for all entities. Return a consistent envelope such as:

```json
{
  "data": {},
  "meta": {},
  "links": {}
}
```

For validation failures return HTTP `422` with field-level messages. For unauthenticated requests return `401`. For unauthorized actions return `403`. For missing records return `404`. For conflict states such as a double-booking or invalid transition return `409` with a stable application error code.

Use pagination for collection endpoints. Accept `page`, `per_page`, `sort`, and filter parameters. Cap `per_page` on the server.

### Step 2.3 — Build the frontend shell

Create the React application shell with protected routes, login, authenticated layout, sidebar navigation, page title area, loading states, empty states, error states, toast notifications, and a reusable confirmation dialog.

Create shared Tailwind components for buttons, inputs, selects, date fields, badges, tables, modals, drawers, cards, and status indicators. Define visual tokens for room operational status and housekeeping status so the same status always has the same color and label.

**Exit criteria:** a staff member can log in, see role-based navigation, refresh the page without losing the authenticated session, and receive understandable API errors.

---

## Phase 3 — Room types and room inventory

### Step 3.1 — Build room-type backend resources

Read the `RoomType` class in the diagram and create its Laravel model, migration, factory, Form Requests, API Resource, resource controller, policy, and feature tests.

Open these REST endpoints:

```text
GET    /api/v1/room-types
POST   /api/v1/room-types
GET    /api/v1/room-types/{roomType}
PATCH  /api/v1/room-types/{roomType}
DELETE /api/v1/room-types/{roomType}
```

Use `POST` for creation and `PATCH` for partial updates. Prevent deletion when rooms or reservations depend on the room type; return `409` and instruct the client to deactivate or archive it instead.

### Step 3.2 — Build room backend resources

Read the `Room` class and create its model, migration, factory, Form Requests, API Resource, resource controller, policy, and tests.

Open these endpoints:

```text
GET    /api/v1/rooms
POST   /api/v1/rooms
GET    /api/v1/rooms/{room}
PATCH  /api/v1/rooms/{room}
DELETE /api/v1/rooms/{room}
GET    /api/v1/rooms/board
PATCH  /api/v1/rooms/{room}/operational-status
PATCH  /api/v1/rooms/{room}/housekeeping-status
```

Support filters such as `room_type_id`, `floor`, `operational_status`, and `housekeeping_status`. Do not allow a normal room update to silently change status without recording an audit event; route deliberate status changes through explicit action endpoints or service methods.

### Step 3.3 — Build room administration pages

Create a Room Types page with list, create, edit, and archive flows. Create a Rooms page with filters, room number, floor, type, capacity, operational status, and housekeeping status. Create a Room Detail page showing current status, upcoming assignments, maintenance blocks, and recent audit events.

Connect each page to the room-type and room endpoints. Add optimistic UI only for low-risk display changes; status changes must refresh from the server after success.

**Exit criteria:** a manager can create room types, add rooms, filter the inventory, update allowed room information, and see a clear refusal when a destructive operation is unsafe.

---

## Phase 4 — Guests and reservation read model

### Step 4.1 — Build guest resources

Read the `Guest` class and create the Laravel model, migration, factory, Form Requests, API Resource, policy, controller, and tests.

Open these endpoints:

```text
GET    /api/v1/guests
POST   /api/v1/guests
GET    /api/v1/guests/{guest}
PATCH  /api/v1/guests/{guest}
```

Support search by name, phone, email, and identity reference. Avoid returning unnecessary identity data in list responses. Use a detail resource for sensitive or less frequently needed fields.

### Step 4.2 — Build reservation read endpoints before reservation writes

Create the `Reservation` and `ReservationRoom` models and API Resources. Implement read-only endpoints first so the frontend can be built against real data:

```text
GET /api/v1/reservations
GET /api/v1/reservations/{reservation}
GET /api/v1/reservations/{reservation}/history
```

Support filters for `status`, `arrival_from`, `arrival_to`, `departure_from`, `departure_to`, `guest_id`, `confirmation_code`, and `room_id`. Return room assignments and guest summary in the detail resource.

Use pagination for the list. Keep list resources compact and detail resources complete. Do not duplicate unrelated dashboard data in every reservation response.

### Step 4.3 — Build reservation pages

Create a Reservations page with filters, pagination, status badges, arrival/departure dates, guest name, assigned rooms, and confirmation code. Create a Reservation Detail page with guest information, stay dates, occupancy, assigned rooms, notes, status history, and available actions based on the current status and user role.

Add a reusable reservation status transition component, but make the API the source of truth for whether an action is allowed.

**Exit criteria:** staff can search and inspect real reservations from the API, and the frontend handles loading, pagination, empty results, and authorization errors.

---

## Phase 5 — Availability and reservation creation

### Step 5.1 — Implement the availability service first

Read the booking sequence diagram and data-model invariants. Create `AvailabilityService` with one tested responsibility: return rooms that can accommodate the requested occupancy and are not occupied by an overlapping active assignment or blocked by an overlapping active maintenance block.

Use the overlap rule:

```text
new_arrival < existing_departure
AND new_departure > existing_arrival
```

Treat cancellation and no-show assignments as released. Keep availability logic in one service so reservation creation, room reassignment, walk-ins, and future public booking integration use the same rules.

Open:

```text
GET /api/v1/availability?arrival_date=YYYY-MM-DD&departure_date=YYYY-MM-DD&adults=2&children=0&room_type_id=...
```

Return available rooms or room-type availability summaries. Return `422` for invalid dates and `409` only when a requested operation conflicts with current inventory.

### Step 5.2 — Implement reservation creation

Create `ReservationService`. Add a `StoreReservationRequest` that validates guest data, date order, occupancy, source, notes, and optional room assignment.

Open:

```text
POST /api/v1/reservations
```

The service must create or reuse the guest, create the reservation, validate the requested room assignment again inside a database transaction, create the assignment, and write an audit record. The final conflict check must happen inside the transaction because the frontend availability result can become stale.

Return HTTP `201` with the reservation resource and confirmation code. Do not accept client-provided confirmation codes or status values.

### Step 5.3 — Implement room assignment and reassignment

Open these action endpoints:

```text
POST /api/v1/reservations/{reservation}/assignments
PATCH /api/v1/reservations/{reservation}/assignments/{assignment}
DELETE /api/v1/reservations/{reservation}/assignments/{assignment}
```

Use these for assigning, changing, and removing rooms. Validate that assignment dates remain inside the reservation dates and that the room is available for the entire assignment interval.

If a reservation can contain multiple rooms, enforce that the total assigned capacity meets the reservation occupancy requirement before check-in. If the first release supports only one room, document that limitation rather than hiding it in the UI.

### Step 5.4 — Build availability and reservation creation pages

Create an Availability Search page with arrival date, departure date, adults, children, and room type. Show available rooms with status, floor, capacity, and rate. From a selected result, open a Create Reservation flow with guest details, notes, and confirmation review.

Create a room-assignment panel inside Reservation Detail. After every assignment or reassignment, refresh the reservation and availability data from the API.

**Exit criteria:** front-desk staff can create a reservation, assign a room, receive a clear double-booking conflict, and see the reservation immediately in the list and detail pages.

---

## Phase 6 — Reservation lifecycle and stay operations

### Step 6.1 — Implement explicit lifecycle actions

Do not use a generic `PATCH /reservations/{id}` to change status. Expose explicit action endpoints so the API communicates business intent:

```text
POST /api/v1/reservations/{reservation}/confirm
POST /api/v1/reservations/{reservation}/cancel
POST /api/v1/reservations/{reservation}/no-show
POST /api/v1/reservations/{reservation}/check-in
POST /api/v1/reservations/{reservation}/check-out
```

Each action must use a Form Request if it accepts input, a policy check, a service method, a database transaction, a state-transition validation, and an audit record.

### Step 6.2 — Implement check-in

Create `StayService::checkIn`. Lock the reservation and assigned room rows during the transaction. Verify that the reservation is confirmed, has an assigned room, the room is not under maintenance or out of service, and the housekeeping state is ready according to the approved business rule.

On success, set the reservation to `Checked-in` and the room to `Occupied`. Return the updated reservation detail resource. On failure, return `409` with a specific code such as `ROOM_NOT_READY`, `NO_ROOM_ASSIGNED`, or `INVALID_RESERVATION_STATUS`.

### Step 6.3 — Implement check-out

Create `StayService::checkOut`. Lock the reservation and room rows. Set the reservation to `Checked-out`, set the room operational status to `Available`, set housekeeping status to `Dirty`, create a cleaning task, and write audit records in one transaction.

### Step 6.4 — Implement cancellation and no-show

On cancellation or no-show, release future room assignments according to the data-model notes. Prevent cancellation after check-out. Prevent no-show on a reservation that is already checked-in or checked-out.

### Step 6.5 — Build front-desk lifecycle UI

Add action buttons to Reservation Detail for confirm, cancel, no-show, check-in, and check-out. Show only actions permitted for the current status and role, but still handle server-side refusal.

Use confirmation dialogs for cancellation, no-show, check-in, and check-out. After an action succeeds, replace the cached reservation data with the response and refresh affected room and dashboard queries.

**Exit criteria:** every supported transition works through an explicit endpoint, invalid transitions are rejected consistently, and check-in/check-out cannot create inconsistent room and reservation states.

---

## Phase 7 — Housekeeping and maintenance

### Step 7.1 — Implement housekeeping tasks

Create the `HousekeepingTask` model, migration, factory, requests, resource, policy, service, controller, and tests.

Open these endpoints:

```text
GET  /api/v1/housekeeping/tasks
GET  /api/v1/housekeeping/tasks/{task}
POST /api/v1/housekeeping/tasks/{task}/claim
POST /api/v1/housekeeping/tasks/{task}/complete
POST /api/v1/housekeeping/tasks/{task}/submit-for-inspection
POST /api/v1/housekeeping/tasks/{task}/pass-inspection
POST /api/v1/housekeeping/tasks/{task}/fail-inspection
```

Ensure that task transitions update the room housekeeping status through `HousekeepingService`. Do not let the task status and room status drift apart.

### Step 7.2 — Implement maintenance blocks

Create the `MaintenanceBlock` model, migration, factory, requests, resource, policy, service, controller, and tests.

Open:

```text
GET    /api/v1/maintenance-blocks
POST   /api/v1/maintenance-blocks
GET    /api/v1/maintenance-blocks/{maintenanceBlock}
PATCH  /api/v1/maintenance-blocks/{maintenanceBlock}
DELETE /api/v1/maintenance-blocks/{maintenanceBlock}
```

When an active maintenance block overlaps a requested stay, the availability service must exclude the room. Prevent creating a block that conflicts with an occupied room unless the manager confirms the emergency workflow defined by the product owner.

### Step 7.3 — Build housekeeping and maintenance pages

Create a Housekeeping Queue page with filters for task status, floor, task type, and assigned staff. Create a Room Board grouped by floor, using separate visual labels for operational status and housekeeping status. Create a Maintenance page for active and historical blocks.

**Exit criteria:** checkout creates a cleaning task, housekeeping staff can claim and complete it, inspection can pass or fail, and maintenance blocks affect availability.

---

## Phase 8 — Dashboard and operations reporting

### Step 8.1 — Define dashboard read models

Do not make the React dashboard assemble dozens of unrelated API calls. Create dashboard endpoints that return purpose-built summaries:

```text
GET /api/v1/dashboard/summary
GET /api/v1/dashboard/arrivals?date=YYYY-MM-DD
GET /api/v1/dashboard/departures?date=YYYY-MM-DD
GET /api/v1/dashboard/room-status
GET /api/v1/dashboard/occupancy?from=YYYY-MM-DD&to=YYYY-MM-DD
```

Return counts and compact lists for arrivals, departures, occupied rooms, dirty rooms, rooms under maintenance, and rooms awaiting inspection. Apply role authorization and pagination to large lists.

### Step 8.2 — Build the operations dashboard

Create the dashboard as the first page after login. Add summary cards, today’s arrivals, today’s departures, room readiness, and urgent maintenance. Link every summary card to the relevant filtered page rather than creating dead-end metrics.

**Exit criteria:** the dashboard reflects the same reservation and room states as detail pages and remains useful when there are zero reservations or zero maintenance blocks.

---

## Phase 9 — Audit logs and operational safety

### Step 9.1 — Implement audit logging consistently

Create `AuditService` and call it from every state-changing service. Store actor, entity type, entity ID, action, timestamp, previous values, new values, and optional reason. Keep audit records append-only from the application API.

Open:

```text
GET /api/v1/audit-logs
GET /api/v1/audit-logs/{auditLog}
```

Restrict audit access to managers and administrators. Add history sections to reservation and room detail pages.

### Step 9.2 — Add concurrency and failure handling

Add transaction tests for assignment, check-in, and check-out. Test two simultaneous assignment attempts for the same room and overlapping dates. Confirm that one succeeds and the other receives a conflict response.

Add request id or correlation id support to logs so a failed staff operation can be traced across the frontend and API.

**Exit criteria:** important state changes are traceable and concurrent operations do not silently overwrite each other.

---

## Phase 10 — Frontend completion and UX consistency

### Step 10.1 — Complete the page map

Build these React pages in this order:

1. Login.
2. Operations Dashboard.
3. Reservations list.
4. Reservation detail.
5. Availability search and create reservation.
6. Guest search and guest detail.
7. Room board.
8. Rooms administration.
9. Room types administration.
10. Housekeeping queue.
11. Maintenance blocks.
12. Staff and role administration.
13. Audit log viewer.

Each page must define its loading, empty, error, unauthorized, and success states. Each page must identify the API endpoints it reads and the mutation endpoints it calls in a small developer note or feature README.

### Step 10.2 — Apply Tailwind design rules

Use consistent spacing, typography, table density, status badges, form controls, and responsive breakpoints. Keep operational screens desktop-first but usable on tablet widths. Avoid hiding important room or reservation status behind hover-only behavior.

Use reusable components rather than creating a unique table or modal for each page. Keep business labels in one place so status wording is consistent with Laravel enum values.

### Step 10.3 — Protect against stale data

After reservation mutations, refresh reservation detail, reservation list, availability, room board, and dashboard queries that are affected. After housekeeping mutations, refresh task queue, room board, and dashboard room-readiness data.

**Exit criteria:** users can complete the main operational workflows without manually refreshing the browser, and the UI remains understandable during slow or failed requests.

---

## Phase 11 — Testing and acceptance

### Step 11.1 — Backend tests

Write unit tests for date-overlap logic, availability filtering, state transitions, and permission rules. Write Laravel feature tests for every endpoint, including successful requests, validation failures, unauthenticated requests, unauthorized requests, missing records, and conflict responses.

Prioritize these end-to-end backend scenarios:

- Create reservation and assign an available room.
- Reject an overlapping room assignment.
- Confirm a reservation.
- Check in only when a room is assigned and ready.
- Check out and create a dirty-room housekeeping task.
- Complete cleaning and pass inspection.
- Block a room for maintenance and exclude it from availability.
- Cancel or mark no-show and release the future assignment.

### Step 11.2 — Frontend tests

Test API client error handling, protected routes, reservation forms, status-action visibility, room board status rendering, pagination, filters, and confirmation dialogs. Add browser-level tests for the most important staff workflows if the project setup supports them.

### Step 11.3 — API contract verification

Create an OpenAPI document or equivalent endpoint reference. Verify that method names, URL parameters, request bodies, response resources, status codes, and error codes match the frontend implementation.

**Exit criteria:** backend tests pass from a clean database, frontend checks pass, critical workflows work against the real API, and the API reference is current.

---

## Phase 12 — Deployment and handover

### Step 12.1 — Prepare environments

Create separate local, staging, and production configuration. Never use real guest identity data in local seeders. Configure database backups, application logging, queue strategy if notifications are added later, CORS, HTTPS, and Sanctum settings.

### Step 12.2 — Seed the real hotel inventory safely

Before importing real hotel data, confirm the room list, room numbers, room types, capacities, amenities, policies, and user roles with the hotel owner. Import inventory through a reviewed seeder or one-time command, not manual production edits.

### Step 12.3 — Release in stages

Release the API and frontend to staging first. Have front-desk and housekeeping users execute the acceptance scenarios using non-production data. Fix workflow issues before enabling production access.

For production, create administrator access, configure least-privilege staff roles, verify backups, and monitor reservation and room-status errors during the first operational period.

### Step 12.4 — Handover documentation

Deliver the API reference, database diagram, deployment instructions, role matrix, status transition rules, seed-data instructions, backup instructions, and a short operator guide for front desk and housekeeping staff.

**Final release condition:** staff can manage the complete operational lifecycle from reservation creation through check-in, check-out, cleaning, inspection, and room readiness without using payment or public booking features.

---

## REST endpoint checklist

### Authentication

```text
POST /api/v1/auth/login
GET  /api/v1/auth/me
POST /api/v1/auth/logout
```

### Guests

```text
GET   /api/v1/guests
POST  /api/v1/guests
GET   /api/v1/guests/{guest}
PATCH /api/v1/guests/{guest}
```

### Room types and rooms

```text
GET    /api/v1/room-types
POST   /api/v1/room-types
GET    /api/v1/room-types/{roomType}
PATCH  /api/v1/room-types/{roomType}
DELETE /api/v1/room-types/{roomType}
GET    /api/v1/rooms
POST   /api/v1/rooms
GET    /api/v1/rooms/{room}
PATCH  /api/v1/rooms/{room}
DELETE /api/v1/rooms/{room}
GET    /api/v1/rooms/board
```

### Availability and reservations

```text
GET  /api/v1/availability
GET  /api/v1/reservations
POST /api/v1/reservations
GET  /api/v1/reservations/{reservation}
PATCH /api/v1/reservations/{reservation}
GET  /api/v1/reservations/{reservation}/history
POST /api/v1/reservations/{reservation}/assignments
PATCH /api/v1/reservations/{reservation}/assignments/{assignment}
DELETE /api/v1/reservations/{reservation}/assignments/{assignment}
POST /api/v1/reservations/{reservation}/confirm
POST /api/v1/reservations/{reservation}/cancel
POST /api/v1/reservations/{reservation}/no-show
POST /api/v1/reservations/{reservation}/check-in
POST /api/v1/reservations/{reservation}/check-out
```

### Housekeeping and maintenance

```text
GET  /api/v1/housekeeping/tasks
GET  /api/v1/housekeeping/tasks/{task}
POST /api/v1/housekeeping/tasks/{task}/claim
POST /api/v1/housekeeping/tasks/{task}/complete
POST /api/v1/housekeeping/tasks/{task}/submit-for-inspection
POST /api/v1/housekeeping/tasks/{task}/pass-inspection
POST /api/v1/housekeeping/tasks/{task}/fail-inspection
GET    /api/v1/maintenance-blocks
POST   /api/v1/maintenance-blocks
GET    /api/v1/maintenance-blocks/{maintenanceBlock}
PATCH  /api/v1/maintenance-blocks/{maintenanceBlock}
DELETE /api/v1/maintenance-blocks/{maintenanceBlock}
```

### Dashboard and audit

```text
GET /api/v1/dashboard/summary
GET /api/v1/dashboard/arrivals
GET /api/v1/dashboard/departures
GET /api/v1/dashboard/room-status
GET /api/v1/dashboard/occupancy
GET /api/v1/audit-logs
GET /api/v1/audit-logs/{auditLog}
```

## REST rules to enforce throughout the project

Use nouns for resources and verbs only for explicit business commands such as `check-in`, `check-out`, `claim`, and `pass-inspection`. Use `GET` for reads, `POST` for creation and commands, `PATCH` for partial updates, and `DELETE` only when deletion is genuinely safe. Use plural resource names and stable `/api/v1` versioning.

Never trust IDs, status values, prices, room availability, or permissions supplied by the browser. Recalculate and authorize them on the server. Return resource representations after successful mutations so the React client can update from the server response. Use pagination, filtering, sorting, validation, consistent error shapes, idempotency protection where repeated commands could be harmful, and transactions around multi-record state changes.

Keep the API independent from React page names. Pages may change, but resource contracts should remain stable. If a future public booking flow is added, it should call the same availability and reservation services through a separately authorized endpoint rather than duplicating the business logic.
