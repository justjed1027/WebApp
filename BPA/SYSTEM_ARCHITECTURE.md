# Notification System Architecture

## System Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        USER INTERFACE                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│   ┌──────────────┐                                              │
│   │ Notification │◄─────────────────────────────────────────┐   │
│   │    Badge     │                                          │   │
│   │   Shows: 0   │                                      Updates  │
│   │   Shows: 3   │                                       every   │
│   │   Shows: 99+ │                                      10 sec   │
│   └──────────────┘                                          │   │
│        │ Click                                               │   │
│        ▼                                                     │   │
│   ┌──────────────────────────────────┐                      │   │
│   │   Notification Modal              │                      │   │
│   ├──────────────────────────────────┤                      │   │
│   │ ┌──────────────────────────────┐ │                      │   │
│   │ │ John sent you a friend req   │ │                      │   │
│   │ │ 5m ago                       │ │                      │   │
│   │ │ [Delete button]              │ │                      │   │
│   │ └──────────────────────────────┘ │                      │   │
│   │ ┌──────────────────────────────┐ │                      │   │
│   │ │ Sarah accepted your request  │ │                      │   │
│   │ │ 2h ago                       │ │                      │   │
│   │ │ [Delete button]              │ │                      │   │
│   │ └──────────────────────────────┘ │                      │   │
│   │ ┌──────────────────────────────┐ │                      │   │
│   │ │ New Event: Web Dev Workshop  │ │                      │   │
│   │ │ 1d ago                       │ │                      │   │
│   │ │ [Delete button]              │ │                      │   │
│   │ └──────────────────────────────┘ │                      │   │
│   └──────────────────────────────────┘                      │   │
│                                                             │   │
└────────────────────────────────────────────────────────────┼───┘
                                                             │
                    ┌────────────────────────────────────────┘
                    │ AJAX Calls
                    ▼
        ┌─────────────────────────────┐
        │   post/get_notifications    │
        │         .php                │
        ├─────────────────────────────┤
        │ ?action=get_count           │
        │ ?action=get_recent          │
        │ ?action=mark_read (POST)    │
        │ ?action=mark_all_read (POST)│
        │ ?action=delete (POST)       │
        └────────┬────────────────────┘
                 │
                 │ Calls
                 ▼
        ┌─────────────────────────────┐
        │  database/Notification      │
        │        .php (Class)         │
        ├─────────────────────────────┤
        │ - createNotification()      │
        │ - getUnreadCount()          │
        │ - getRecentNotifications()  │
        │ - markAsRead()              │
        │ - markAllAsRead()           │
        │ - deleteNotification()      │
        │ - getCountDisplay()         │
        └────────┬────────────────────┘
                 │
                 │ Queries
                 ▼
        ┌─────────────────────────────┐
        │   MySQL Database            │
        ├─────────────────────────────┤
        │  notifications Table        │
        │  ├─ notification_id (PK)    │
        │  ├─ user_id (FK)            │
        │  ├─ type                    │
        │  ├─ actor_user_id (FK)      │
        │  ├─ title                   │
        │  ├─ description             │
        │  ├─ reference_id            │
        │  ├─ reference_type          │
        │  ├─ is_read                 │
        │  └─ created_at              │
        │  Indexes: user_id, is_read, │
        │          created_at         │
        └─────────────────────────────┘
```

## Notification Creation Flow

```
TRIGGER EVENT OCCURS
        │
        ├─────────────────────┬────────────────────────┐
        │                     │                        │
        ▼                     ▼                        ▼
   Friend Request         Friend Accepted          Event Created
   send_request.php      respond_request.php     create_event.php
        │                     │                        │
        │ Create             │ Create                 │ Query users with
        │ Notification()     │ Notification()         │ matching interests
        │                     │                        │
        └────────────────────┬─────────────────────┬──┘
                             │
                             ▼
                 INSERT INTO notifications
                      (user_id, type, ...)
                             │
                             ▼
                   ✓ Notification Stored
                             │
                             ▼
         User sees updated badge count
         User clicks notification bell
         Modal opens with new notification
```

## Component Interaction Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        post.php                                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌────────────────────────────────────────────────────────┐    │
│  │  PHP Server Side                                       │    │
│  ├────────────────────────────────────────────────────────┤    │
│  │                                                        │    │
│  │  require_once 'Notification.php'                      │    │
│  │                                                        │    │
│  │  $notif = new Notification($conn);                    │    │
│  │  $count = $notif->getUnreadCount($_SESSION['user_id'])│    │
│  │  $display = $notif->getCountDisplay(...)             │    │
│  │                                                        │    │
│  │  Output: $notificationCountDisplay = '3'             │    │
│  └────────────────────────────────────────────────────────┘    │
│                           │                                    │
│                           │ Renders HTML                      │
│                           ▼                                    │
│  ┌────────────────────────────────────────────────────────┐    │
│  │  HTML Markup                                           │    │
│  ├────────────────────────────────────────────────────────┤    │
│  │                                                        │    │
│  │  <button id="notificationBtn">                        │    │
│  │    <span id="notificationBadge">3</span>             │    │
│  │  </button>                                            │    │
│  │                                                        │    │
│  │  <div id="notificationModal">                        │    │
│  │    <div id="notificationList">...</div>             │    │
│  │  </div>                                              │    │
│  └────────────────────────────────────────────────────────┘    │
│                           │                                    │
│                           │ Loads                              │
│                           ▼                                    │
│  ┌────────────────────────────────────────────────────────┐    │
│  │  JavaScript (post.php)                                 │    │
│  ├────────────────────────────────────────────────────────┤    │
│  │                                                        │    │
│  │  const notificationBtn = ...;                         │    │
│  │                                                        │    │
│  │  notificationBtn.addEventListener('click', () => {   │    │
│  │    fetch('./get_notifications.php?action=get_recent')│    │
│  │      .then(response => response.json())              │    │
│  │      .then(data => displayNotifications(data))      │    │
│  │  });                                                  │    │
│  │                                                        │    │
│  │  setInterval(updateNotificationBadge, 10000);        │    │
│  │                                                        │    │
│  └────────────────────────────────────────────────────────┘    │
│                           │                                    │
│                           │ Communicates with                  │
│                           ▼                                    │
│  ┌────────────────────────────────────────────────────────┐    │
│  │  CSS (style.css)                                       │    │
│  ├────────────────────────────────────────────────────────┤    │
│  │                                                        │    │
│  │  .notification-modal-backdrop { display: none; }     │    │
│  │  .notification-modal-backdrop.active { display: flex; } │    │
│  │                                                        │    │
│  │  .notification-item-modal:hover { ... }              │    │
│  │  .notification-delete { opacity: 0; }                │    │
│  │  .notification-item-modal:hover .notification-delete │    │
│  │    { opacity: 1; }                                   │    │
│  │                                                        │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

```
USER ACTION                    BACKEND PROCESSING                DATABASE
    │                              │                               │
    ├─ Send Friend Request ──────► send_request.php              │
    │                              │                               │
    │                              ├─ Validate request            │
    │                              │                               │
    │                              ├─ Create connection           │
    │                              │                               │
    │                              ├─ Create notification  ──────►INSERT
    │                              │                               │
    │                              └─ Redirect to connections    │
    │                                                             │
    │                                                             │
    ├─ Accept Friend Request ───► respond_request.php            │
    │                              │                               │
    │                              ├─ Update connection status   │
    │                              │                               │
    │                              ├─ Create notification  ──────►INSERT
    │                              │                               │
    │                              └─ Redirect to connections    │
    │                                                             │
    │                                                             │
    ├─ Create Event ────────────► create_event.php              │
    │                              │                               │
    │                              ├─ Validate event data        │
    │                              │                               │
    │                              ├─ Insert event        ──────►INSERT
    │                              │                               │
    │                              ├─ Query matching users ──────►SELECT
    │                              │  (users with same interest)  │
    │                              │                               │
    │                              ├─ Create notifications ──────►INSERT
    │                              │  (for each matching user)    │
    │                              │                               │
    │                              └─ Return success JSON        │
    │                                                             │
    │                                                             │
    ├─ Click Notification Bell ─► Fetch request                  │
    │                              │                               │
    │                              ├─ get_notifications.php      │
    │                              │  ?action=get_count    ──────►SELECT
    │                              │                               │
    │                              ├─ Return count        ◄──────
    │                              │                               │
    │                              └─ Update badge               │
    │                                                             │
    │                                                             │
    ├─ Open Modal ──────────────► Fetch recent notifications     │
    │                              │                               │
    │                              ├─ get_notifications.php      │
    │                              │  ?action=get_recent   ──────►SELECT
    │                              │                               │
    │                              ├─ Return notification array  │
    │                              │                               │
    │                              ├─ Mark all as read    ──────►UPDATE
    │                              │                               │
    │                              └─ Display in modal          │
    │                                                             │
    │                                                             │
    ├─ Delete Notification ─────► Fetch request                  │
    │                              │                               │
    │                              ├─ get_notifications.php      │
    │                              │  ?action=delete (POST) ────►DELETE
    │                              │                               │
    │                              ├─ Return success      ◄──────
    │                              │                               │
    │                              └─ Remove from modal         │
    │                                                             │
```

## State Management

```
Client State (JavaScript):
├─ notificationBtn → DOM element
├─ notificationModal → DOM element  
├─ notificationList → DOM element
├─ currentNotifications → Array of notification objects
└─ isModalOpen → Boolean

Server State (PHP Session):
├─ $_SESSION['user_id'] → Current user ID
└─ $_SESSION (must be active for auth)

Database State (MySQL):
├─ notifications table
│  ├─ All notifications (read and unread)
│  ├─ Indexed by user_id for fast access
│  └─ Indexed by is_read for counting
└─ Foreign keys linking to:
   ├─ user table (user_id)
   └─ user table (actor_user_id)
```

## Security Layers

```
Request Flow:
    │
    ▼
┌─────────────────────────────────┐
│ Session Check                    │ ◄─ Must be logged in
│ if (empty($_SESSION['user_id'])) │
└─────────────────────────────────┘
    │
    ▼
┌─────────────────────────────────┐
│ Input Validation                 │ ◄─ Validate action & parameters
│ Whitelist allowed actions        │
└─────────────────────────────────┘
    │
    ▼
┌─────────────────────────────────┐
│ Prepared Statements              │ ◄─ Prevent SQL injection
│ Parameterized queries            │
└─────────────────────────────────┘
    │
    ▼
┌─────────────────────────────────┐
│ Data Access Control              │ ◄─ Only access own notifications
│ Filter by user_id from session   │
└─────────────────────────────────┘
    │
    ▼
┌─────────────────────────────────┐
│ Output Escaping                  │ ◄─ Prevent XSS
│ escapeHtml() function            │
└─────────────────────────────────┘
    │
    ▼
┌─────────────────────────────────┐
│ JSON Response                    │ ◄─ Type-safe delivery
│ Content-Type: application/json   │
└─────────────────────────────────┘
    │
    ▼
  Client
```

---

**This diagram shows how all components work together to deliver a secure, responsive notification system.**
