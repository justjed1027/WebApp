# Notification System Setup Guide

## Quick Start

### Step 1: Run Database Migration
Execute the SQL script to create the notifications table:

```bash
mysql -u your_username -p your_database < database/setup_notifications.sql
```

Or manually run this SQL in your database manager:

```sql
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    actor_user_id INT,
    reference_id INT,
    reference_type VARCHAR(50),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (actor_user_id) REFERENCES user(user_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);
```

### Step 2: Files Already Updated

The following files have been automatically updated with notification functionality:

✅ `post/post.php` - Notification button with count display and modal
✅ `post/style.css` - Notification modal styles
✅ `post/get_notifications.php` - New notification API
✅ `database/Notification.php` - New notification class
✅ `connections/send_request.php` - Friend request notification
✅ `connections/respond_request.php` - Friend acceptance notification
✅ `events/create_event.php` - Event creation notification

## What Now Works

### 1. Notification Badge
- The notification button in the top bar now displays the count of unread notifications
- Shows "0" when there are no notifications
- Shows "1", "2", "99+" etc. for unread counts
- Automatically updates every 10 seconds

### 2. Click Notification Button
- Opens a clean modal showing recent notifications (up to 30)
- Automatically marks all notifications as read when opened
- Shows notification type with color-coded icons
- Displays time ago ("5m ago", "2h ago", etc.)
- Includes delete button on hover for each notification

### 3. Automatic Notifications Sent For:

#### Friend Requests
- When User A sends a friend request to User B
- User B receives: "John sent you a friend request"
- User B can view and accept/decline

#### Friend Request Accepted
- When User B accepts User A's friend request
- User A receives: "John accepted your friend request"

#### Events with Matching Interests
- When a new event is created
- All users with skills matching the event's subject receive notification
- Message: "New event: [Event Title]"

## Testing the System

### Test 1: View Notification Count
1. Go to the Posts page
2. Look at the notification button in the top right
3. You should see either "0" or a number badge

### Test 2: Send Friend Request (Create a Test Notification)
1. Go to Connections
2. Find a user and send them a friend request
3. The notification count should increase for that user
4. They can click the notification button to see it

### Test 3: Create an Event
1. Go to Events
2. Create a new event and select a subject (e.g., "Mathematics")
3. All users with "Mathematics" as a skill will receive notifications
4. They'll see: "New event: [Your Event Title]"

### Test 4: Mark as Read
1. Click the notification button to open the modal
2. You should see your notifications
3. They should automatically mark as read
4. The count badge should update

### Test 5: Delete a Notification
1. Open the notification modal
2. Hover over a notification
3. Click the delete button (trash icon)
4. Notification is removed from list

## Technical Details

### Notification Types
```php
'friend_request'  - Someone sent you a friend request
'friend_accepted' - Someone accepted your friend request
'event_created'   - New event matching your interests
'event_reminder'  - Event reminder (future feature)
'post_like'       - Someone liked your post (future feature)
'post_comment'    - Someone commented on your post (future feature)
```

### API Endpoints
All endpoints return JSON and require authentication.

**GET** `post/get_notifications.php?action=get_count`
```json
{
  "success": true,
  "count": 3,
  "display": "3"
}
```

**GET** `post/get_notifications.php?action=get_recent&limit=20`
```json
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

**POST** `post/get_notifications.php?action=mark_read`
```
Body: notification_id=1
Response: {"success": true}
```

**POST** `post/get_notifications.php?action=mark_all_read`
```
Response: {"success": true}
```

**POST** `post/get_notifications.php?action=delete`
```
Body: notification_id=1
Response: {"success": true}
```

## Troubleshooting

### Notifications not showing count?
- Check that database table exists: `SHOW TABLES LIKE 'notifications';`
- Verify session is set up properly
- Check browser console for JavaScript errors (F12)

### Notifications not being created?
- Verify foreign keys are working correctly
- Check database logs for insert errors
- Ensure the Notification class is properly included in the PHP files

### Modal not opening?
- Check that `notificationBtn` and `notificationModal` elements exist
- Look for JavaScript errors in console
- Verify CSS animations are not broken

### Notifications showing but not deleting?
- Check POST request is being sent correctly
- Verify notification_id is being passed properly
- Look for database errors in PHP error logs

## Performance Notes

- Notifications are indexed on user_id, is_read, and created_at for fast queries
- Unread count is calculated dynamically for real-time accuracy
- Badge updates every 10 seconds (can be adjusted in script)
- Notifications are soft-deleted (marked as read) to maintain history

## Future Enhancements

Possible additions to the notification system:

1. **Email Notifications** - Send digest emails of notifications
2. **Push Notifications** - Browser push notifications for real-time alerts
3. **Notification Preferences** - Let users choose which notifications to receive
4. **Notification Center** - Separate dedicated page for all notifications
5. **Notification Groups** - Group similar notifications together
6. **Action Buttons** - Direct actions from notification modal (Accept/Decline friend requests)
7. **Read Receipts** - Track when notifications are opened
8. **Notification History** - Archive and search past notifications

## Security Notes

- All notifications are user-specific and securely linked via user_id
- Session authentication required for all notification endpoints
- SQL injection prevented through prepared statements
- XSS prevention through HTML escaping in frontend
- Only logged-in users can access notification endpoints
