# ParkEase - System Architecture

This document provides a detailed technical overview of the ParkEase system, including diagrams and file structure.

## 1. Project Structure
```
/third-parkease
├── assets/                 # CSS, JS, and image resources
├── backend/                # Server-side logic
│   ├── config/             # Database configuration
│   ├── controllers/        # Business logic (Auth, Location, Booking, Admin)
│   ├── models/             # Database interaction classes
│   ├── router.php          # API Entry point
│   └── utils/              # Helper scripts
├── database.sql            # Database schema definition
├── index.html              # Landing page
├── login.html              # Authentication page
├── dashboard_user.html     # User Dashboard
├── dashboard_vendor.html   # Vendor Dashboard
└── dashboard_admin.html    # Admin Dashboard
```

## 2. Entity Relationship Diagram (ERD)

```mermaid
erDiagram
    USERS ||--o{ LOCATIONS : "owns"
    USERS ||--o{ BOOKINGS : "makes"
    LOCATIONS ||--o{ BOOKINGS : "has"

    USERS {
        int id PK
        string name
        string email
        string password
        string role "user, vendor, admin"
    }

    LOCATIONS {
        int id PK
        int vendor_id FK
        string name
        string address
        decimal price_per_hour
        int total_slots
        enum status "pending, approved, rejected"
    }

    BOOKINGS {
        int id PK
        int user_id FK
        int location_id FK
        datetime start_time
        datetime end_time
        decimal total_price
        enum status "pending, confirmed, cancelled"
    }
```

## 3. Data Flow Diagram (DFD) - Level 0

```mermaid
graph TD
    User[Driver] -->|Search & Book| System[ParkEase System]
    Vendor[Parking Owner] -->|Add Location| System
    Admin[Administrator] -->|Approve/Reject| System
    
    System -->|Booking Status| User
    System -->|Location Status| Vendor
    System -->|System Stats| Admin
```

## 4. Database Schema Structure
The system uses a relational MySQL database with the following table definitions used in `database.sql`:

### `users` table
- **Purpose**: Manages authentication and user roles.
- **Key Columns**: `id` (Primary Key), `email` (Unique), `role` (Enum: user/vendor/admin).

### `locations` table
- **Purpose**: Stores parking spot details managed by vendors.
- **Relationships**: Linked to `users` via `vendor_id`.
- **Status**: Columns `status` allows Admin moderation.

### `bookings` table
- **Purpose**: Records reservations made by drivers.
- **Relationships**: Linked to `users` (driver) and `locations`.
