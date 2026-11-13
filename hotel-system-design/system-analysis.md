# Hotel Booking and Room Management System

## Project context

This design extends the existing real-hotel website with an internal hotel operations system. The public website and its current booking presentation remain the customer-facing layer. The new system is an internal management layer for front desk, hotel management, and housekeeping.

The first release intentionally does **not** include online payment, payment gateway integration, accounting, channel-manager synchronization, or a full guest self-service portal.

## Objectives

- Record and manage reservations created by staff or imported from the public booking flow later.
- Prevent double-booking for the same room and overlapping stay dates.
- Manage rooms, room types, availability, maintenance blocks, and housekeeping status.
- Support check-in, room assignment, check-out, cancellation, and no-show handling.
- Give managers a clear operational dashboard for arrivals, departures, occupancy, and room readiness.

## Main actors

- **Front-desk staff**: create and modify reservations, assign rooms, check guests in and out, and record walk-ins.
- **Housekeeping staff**: view assigned rooms and update cleaning and inspection status.
- **Hotel manager**: manage room inventory, room types, maintenance blocks, staff access, and operational reports.
- **Guest**: represented as a customer record; direct guest self-service is deferred.
- **System administrator**: configures users, roles, and system settings.

## Functional scope

### Reservation management

A reservation contains one or more reserved room stays, a guest, stay dates, occupancy, status, source, and operational notes. The supported lifecycle is:

`Tentative -> Confirmed -> Checked-in -> Checked-out`

Alternative transitions are `Tentative/Confirmed -> Cancelled`, `Confirmed -> No-show`, and `Confirmed -> Checked-in` only after at least one room has been assigned.

The system must validate that a room is not already reserved, blocked for maintenance, or unavailable for the requested date range. A reservation may be changed only when the resulting dates and room assignments remain valid.

### Room management

A room belongs to a room type and has a physical number, floor, capacity, amenities, and operational status. Operational status is separate from housekeeping status. For example, a room can be operationally available but still dirty after a previous checkout.

Operational status: `Available`, `Occupied`, `Out-of-service`, `Under-maintenance`.

Housekeeping status: `Clean`, `Dirty`, `Cleaning`, `Inspected`.

### Front-desk operations

- Search availability by arrival date, departure date, room type, and occupancy.
- Create a reservation without taking payment.
- Assign or change rooms.
- Check in an arriving reservation.
- Record a walk-in reservation.
- Check out a guest and mark the room dirty.
- Cancel a reservation or mark it no-show.

### Housekeeping operations

- See rooms requiring cleaning after checkout.
- Claim a cleaning task.
- Mark cleaning complete.
- Submit a room for inspection.
- Mark a room inspected and ready, or return it to cleaning.
- Report maintenance issues that move a room out of service.

## Non-functional requirements

- Use role-based access control.
- Store an audit trail for reservation and room-status changes.
- Use date-range validation at the service/database boundary, not only in the UI.
- Keep reservation status and room operational status independent.
- Make room assignment and check-in operations transactional so two staff members cannot assign the same room concurrently.
- Design the domain so payment can be added later without changing core reservation and room entities.

## Suggested modules

1. Authentication and role management
2. Guest and customer records
3. Room types and room inventory
4. Availability and reservation management
5. Check-in/check-out operations
6. Housekeeping and maintenance
7. Dashboard and operational reporting
8. Audit logging

## Deferred decisions

- Whether public website bookings create reservations directly or enter a pending queue.
- Whether a reservation can contain multiple rooms in the first implementation.
- Exact hotel-specific room types, prices, taxes, policies, and legal guest fields.
- Payment, invoices, refunds, and accounting integration.
