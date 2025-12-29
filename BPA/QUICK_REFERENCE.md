# Quick Reference Guide - Notifications

## 🚀 Quick Start (5 Minutes)

### Step 1: Create Database Table
```bash
# Copy the SQL from database/setup_notifications.sql
# And run it in your MySQL database

mysql -u your_user -p your_db < database/setup_notifications.sql
```

### Step 2: Done! ✓
The notification system is already integrated and ready to use.

---

## 📊 Checking Notification Count

**For End Users:**
- Look at the bell icon in the top right
- The badge shows how many unread notifications you have
- "0" = no notifications
- "3" = 3 unread notifications
- "99+" = 100+ unread notifications

**For Developers:**
```php
// Get count
$notif = new Notification($connection);
$count = $notif->getUnreadCount($userId);  // Returns: 0, 1, 2, etc.
$display = $notif->getCountDisplay($userId); // Returns: "0", "3", "99+"
```

---

## 🔔 Viewing Notifications

**For End Users:**
1. Click the bell 🔔 icon in top right
2. A modal opens showing recent notifications
3. Notifications automatically mark as read
4. Click X or outside modal to close

**For Developers:**
```php
$notif = new Notification($connection);
$result = $notif->getRecentNotifications($userId, $limit = 20);

// Returns MySQLi result with fields:
// - notification_id, type, title, description
// - is_read, created_at, actor_username, time_ago
```

---

## 📝 Creating Notifications

### Friend Request Notification
```php
require_once '../database/Notification.php';

$notif = new Notification($connection);
$notif->createNotification(
    $receiverUserId,              // Who gets the notification
    'friend_request',             // Type
    $senderUserId,                // Who triggered it
    'John sent you a friend request',  // Title
    'View their profile to accept or decline',  // Description
    $senderUserId,                // Reference ID
    'user'                        // Reference type
);
```

### Event Created Notification
```php
$notif = new Notification($connection);

// Get all users with matching interests
$query = $connection->prepare("
    SELECT DISTINCT us_user_id FROM user_skills 
    WHERE us_subject_id = ? AND us_user_id != ?
");
$query->bind_param("ii", $subjectId, $organizerId);
$query->execute();
$result = $query->get_result();

while ($row = $result->fetch_assoc()) {
    $notif->createNotification(
        $row['us_user_id'],
        'event_created',
        $organizerId,
        'New event: Web Development Workshop',
        'An event matching your interests was created',
        $eventId,
        'event'
    );
}
```

---

## 🗑️ Deleting Notifications

**For End Users:**
1. Open notification modal
2. Hover over a notification
3. Click the trash icon that appears

**For Developers:**
```php
$notif = new Notification($connection);
$success = $notif->deleteNotification($notificationId);
if ($success) {
    echo "Deleted!";
}
```

---

## ✅ Marking As Read

**Single Notification:**
```php
$notif = new Notification($connection);
$notif->markAsRead($notificationId);
```

**All Notifications:**
```php
$notif = new Notification($connection);
$notif->markAllAsRead($userId);
```

---

## 🌐 API Endpoints

All endpoints are in `post/get_notifications.php`

### Get Count
```
GET /post/get_notifications.php?action=get_count

Response:
{
  "success": true,
  "count": 3,
  "display": "3"
}
```

### Get Recent (Paginated)
```
GET /post/get_notifications.php?action=get_recent&limit=20

Response:
{
  "success": true,
  "notifications": [
    {
      "notification_id": 1,
      "type": "friend_request",
      "title": "John sent you a friend request",
      "description": "View their profile to accept or decline",
      "is_read": 0,
      "created_at": "2025-12-29 10:30:00",
      "actor_username": "john_doe",
      "time_ago": "5m ago"
    }
  ]
}
```

### Mark All Read
```
POST /post/get_notifications.php?action=mark_all_read

Response:
{
  "success": true
}
```

### Mark Single Read
```
POST /post/get_notifications.php?action=mark_read

Body:
notification_id=1

Response:
{
  "success": true
}
```

### Delete
```
POST /post/get_notifications.php?action=delete

Body:
notification_id=1

Response:
{
  "success": true
}
```

---

## 📋 Notification Types & Icons

| Type | Icon | Color | When Sent |
|------|------|-------|-----------|
| `friend_request` | 👤 | 🔵 Blue | Someone sends friend request |
| `friend_accepted` | ✓ | 🟢 Green | Someone accepts your request |
| `event_created` | 📅 | 🟠 Orange | Event created with your interests |
| `event_reminder` | ⏰ | 🟣 Purple | Event reminder |
| `post_like` | ❤️ | 🔴 Red | Someone likes your post |
| `post_comment` | 💬 | 🔵 Blue | Someone comments on your post |

---

## 🔧 Database Queries

### See All Notifications
```sql
SELECT * FROM notifications 
WHERE user_id = 1 
ORDER BY created_at DESC;
```

### Count Unread
```sql
SELECT COUNT(*) as unread 
FROM notifications 
WHERE user_id = 1 AND is_read = 0;
```

### Get Recent with User Info
```sql
SELECT 
    n.*,
    u.user_username,
    u.user_email
FROM notifications n
LEFT JOIN user u ON n.actor_user_id = u.user_id
WHERE n.user_id = 1
ORDER BY n.created_at DESC
LIMIT 20;
```

### Delete Old Notifications (Cleanup)
```sql
DELETE FROM notifications 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

## 🐛 Troubleshooting

### Badge not showing count
```php
// Check if Notification class is included
require_once '../database/Notification.php';

// Check if user is logged in
var_dump($_SESSION['user_id']);

// Check if table exists
SELECT * FROM notifications LIMIT 1;
```

### Modal not opening
```javascript
// Check console for errors
console.log('notificationBtn:', document.getElementById('notificationBtn'));
console.log('notificationModal:', document.getElementById('notificationModal'));

// Make sure CSS is loaded
document.querySelector('.notification-modal-backdrop');
```

### Notifications not being created
```php
// Check insert statement
$result = $notif->createNotification(...);
var_dump($result); // Should be true

// Check database for errors
// Enable SQL error logging in DatabaseConnection.php
```

---

## 📱 JavaScript Functions

### Load notifications
```javascript
loadNotifications() // Fetches and displays recent notifications
```

### Update badge
```javascript
updateNotificationBadge() // Gets new count and updates badge
```

### Delete notification
```javascript
deleteNotification(notifId, element) // Deletes and removes from list
```

### Open modal
```javascript
notificationBtn.click() // Opens notification modal
```

---

## 🎨 CSS Classes Reference

```css
/* Main elements */
.notification-modal-backdrop      /* Overlay when modal open */
.notification-modal-content       /* Modal container */
.notification-modal-header        /* Header with title */
.notification-modal-body          /* Content area */

/* Notification items */
.notification-item-modal          /* Single notification */
.notification-item-modal.unread   /* Unread notification */
.notification-icon                /* Icon container */
.notification-content-modal       /* Text content */
.notification-delete              /* Delete button */

/* Icon type variants */
.notification-friend-request      /* Blue icon */
.notification-friend-accepted     /* Green icon */
.notification-event               /* Orange icon */
.notification-like                /* Red icon */
.notification-comment             /* Blue icon */

/* States */
.notification-modal-backdrop.active  /* Modal visible */
.notification-item-modal:hover       /* Hover effect */
```

---

## 📦 Files Reference

| File | Purpose |
|------|---------|
| `database/Notification.php` | Core notification class |
| `post/get_notifications.php` | API endpoints |
| `post/post.php` | UI & JavaScript |
| `post/style.css` | Modal styling |
| `database/setup_notifications.sql` | Database schema |
| `connections/send_request.php` | Create friend_request notifications |
| `connections/respond_request.php` | Create friend_accepted notifications |
| `events/create_event.php` | Create event_created notifications |

---

## 🚦 Flow Examples

### Example 1: User Sends Friend Request

1. User A clicks "Add Friend" on User B's profile
2. `send_request.php` processes the request
3. Connection record created in database
4. **Notification created:**
   ```php
   $notif->createNotification(
       $userBId,
       'friend_request',
       $userAId,
       'Alice sent you a friend request',
       'View their profile to accept or decline',
       $userAId,
       'user'
   );
   ```
5. User B sees notification count update to "1"
6. User B clicks notification bell
7. Modal shows: "Alice sent you a friend request"

### Example 2: Event Created

1. User creates event about "JavaScript"
2. `create_event.php` processes creation
3. Event inserted into database
4. **Query for matching users:**
   ```sql
   SELECT us_user_id FROM user_skills 
   WHERE us_subject_id = 5 AND us_user_id != 1
   ```
5. **Notification created for each matching user:**
   ```php
   foreach ($matchingUsers as $user) {
       $notif->createNotification(
           $user['us_user_id'],
           'event_created',
           $creatorId,
           'New event: JavaScript Masterclass',
           'An event matching your interests was created',
           $eventId,
           'event'
       );
   }
   ```
6. All matching users see notification count increase
7. They can click to view the notification details

---

## 💡 Pro Tips

1. **Batch Create Notifications**: Loop through users for efficient bulk creates
2. **Use Indexes**: Queries on `user_id`, `is_read`, and `created_at` are fast
3. **Clean Old Data**: Periodically delete notifications older than 30 days
4. **Test with Multiple Users**: Use two browsers or incognito mode
5. **Monitor Performance**: Check response times on high-volume notifications
6. **Customize Icons**: Modify SVG icons in `createNotificationElement()`
7. **Adjust Poll Rate**: Change `setInterval(updateNotificationBadge, 10000)` to poll more/less frequently

---

## ✨ You're All Set!

Everything is installed and ready to use. Just:
1. Run the database migration
2. Send friend requests between users
3. Create events with interests
4. Watch notifications appear! 🎉

---

**Need help?** Check the detailed guides:
- `NOTIFICATION_SYSTEM_README.md` - Full documentation
- `SETUP_NOTIFICATIONS.md` - Setup & testing guide
- `SYSTEM_ARCHITECTURE.md` - System design diagrams
