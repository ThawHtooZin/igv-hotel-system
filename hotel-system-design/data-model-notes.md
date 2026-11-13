# Data-model notes

## Important invariants

1. `Reservation.arrivalDate < Reservation.departureDate`.
2. A room assignment overlaps another assignment when `newArrival < existingDeparture` and `newDeparture > existingArrival`.
3. A room cannot be assigned if an overlapping active reservation assignment exists.
4. A room cannot be assigned if an overlapping active maintenance block exists.
5. Check-in requires a confirmed reservation, an assigned room, and a room that is operationally available and housekeeping-ready.
6. Check-out sets the reservation to `Checked-out`, the room to `Available`, and the housekeeping status to `Dirty` in one transaction.
7. Cancellation and no-show must release future room assignments.
8. Every state-changing command creates an audit log entry with actor, timestamp, entity, old value, and new value.

## Suggested tables

- `users`
- `roles`
- `guests`
- `room_types`
- `rooms`
- `reservations`
- `reservation_rooms`
- `housekeeping_tasks`
- `maintenance_blocks`
- `audit_logs`

## Suggested service boundaries

- `AvailabilityService`: date-range queries and conflict detection.
- `ReservationService`: reservation lifecycle and room assignment.
- `StayService`: check-in and check-out transactions.
- `HousekeepingService`: cleaning, inspection, and readiness updates.
- `RoomService`: room, room type, and maintenance management.
- `AuditService`: immutable operational history.

## Recommended first screens

1. Operations dashboard: arrivals, departures, occupied rooms, dirty rooms, rooms under maintenance.
2. Reservation list and reservation detail.
3. Availability search and room assignment.
4. Room board grouped by floor with operational and housekeeping status.
5. Housekeeping queue.
6. Room and room-type administration.
7. Staff and role administration.
