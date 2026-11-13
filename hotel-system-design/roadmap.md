# Implementation roadmap

## Phase 1: Core operations

Implement users and roles, guests, room types, rooms, reservations, room assignment, availability validation, check-in, check-out, cancellation, and audit logging. Use seeded hotel data that matches the real hotel's room inventory only after the owner confirms the room list.

## Phase 2: Housekeeping and maintenance

Implement the room board, housekeeping queue, cleaning/inspection workflow, maintenance blocks, and manager dashboard metrics.

## Phase 3: Website integration

Connect the existing public website booking form to a controlled reservation endpoint. The first integration can create a `Tentative` reservation for staff review rather than immediately confirming or collecting money.

## Phase 4: Deferred commercial features

Add payment, invoices, taxes, coupons, refunds, email/SMS notifications, online guest portal, and external channel-manager integrations only after the operational workflow is stable.
