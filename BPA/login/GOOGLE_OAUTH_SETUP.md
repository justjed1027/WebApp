# Google OAuth 2.0 Setup Instructions

This guide will help you set up Google OAuth 2.0 for your SkillSwap login page.

## Step 1: Create a Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click "Select a project" → "New Project"
3. Enter your project name (e.g., "SkillSwap Login")
4. Click "Create"

## Step 2: Enable Google+ API

1. In your Google Cloud project, go to "APIs & Services" → "Library"
2. Search for "Google+ API"
3. Click on it and click "Enable"

## Step 3: Configure OAuth Consent Screen

1. Go to "APIs & Services" → "OAuth consent screen"
2. Select "External" (unless you have a Google Workspace account)
3. Click "Create"
4. Fill in the required information:
   - App name: SkillSwap
   - User support email: Your email
   - Developer contact email: Your email
5. Click "Save and Continue"
6. Skip the "Scopes" step (click "Save and Continue")
7. Add test users if needed (for development)
8. Click "Save and Continue"

## Step 4: Create OAuth 2.0 Credentials

1. Go to "APIs & Services" → "Credentials"
2. Click "Create Credentials" → "OAuth client ID"
3. Select "Web application"
4. Set the name (e.g., "SkillSwap Web Client")
5. Add Authorized JavaScript origins:
   ```
   http://localhost
   http://localhost:3000
   https://yourdomain.com (your production domain)
   ```
6. Add Authorized redirect URIs:
   ```
   http://localhost/WebApp/BPA/login/login.php
   http://localhost:3000/WebApp/BPA/login/login.php
   https://yourdomain.com/login/login.php (your production URL)
   ```
7. Click "Create"
8. **IMPORTANT**: Copy your Client ID - you'll need this!

## Step 5: Update Your Code

Replace `YOUR_GOOGLE_CLIENT_ID` with your actual Client ID in these files:

### 1. login.php (Line ~113)
```php
data-client_id="YOUR_ACTUAL_CLIENT_ID.apps.googleusercontent.com"
```

### 2. google-oauth.php (Line ~29)
```php
if (isset($data['aud']) && $data['aud'] === 'YOUR_ACTUAL_CLIENT_ID.apps.googleusercontent.com') {
```

### 3. login.js (Line ~112)
```javascript
client_id: 'YOUR_ACTUAL_CLIENT_ID.apps.googleusercontent.com',
```

## Step 6: Update Database Schema (If Needed)

Make sure your `users` table has these columns:

```sql
ALTER TABLE users 
ADD COLUMN google_id VARCHAR(255) NULL,
ADD COLUMN profile_picture VARCHAR(500) NULL,
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
```

If you don't have an `email` column, add it:

```sql
ALTER TABLE users 
ADD COLUMN email VARCHAR(255) NULL UNIQUE;
```

## Step 7: Test Your Integration

1. Start your local server (XAMPP)
2. Navigate to your login page
3. Click "Sign up with Google"
4. Sign in with your Google account
5. Check that you're redirected to the post page
6. Verify the session is created

## Troubleshooting

### "Error: invalid_client"
- Check that your Client ID is correct in all files
- Verify your authorized origins and redirect URIs match exactly

### "Error: redirect_uri_mismatch"
- The URL you're accessing must match one of your authorized redirect URIs
- Make sure to include the protocol (http/https)

### "Google library not loaded"
- Check your internet connection
- Verify the Google script is loading: `https://accounts.google.com/gsi/client`

### User not being created in database
- Check your database connection in `DatabaseConnection.php`
- Verify your table structure matches the schema above
- Check PHP error logs for details

## Security Notes

1. **Never commit your Client ID to public repositories** if it contains sensitive data
2. For production, always use HTTPS
3. Validate all Google tokens on the server side (already implemented)
4. Keep your OAuth consent screen up to date
5. Regularly review authorized domains

## Testing with Different Accounts

During development, you may need to add test users:
1. Go to OAuth consent screen
2. Scroll to "Test users"
3. Click "Add Users"
4. Add email addresses that can test your app

## Going to Production

Before launching:
1. Switch OAuth consent screen from "Testing" to "In production"
2. Complete the app verification process (if required)
3. Update all authorized origins to use your production domain
4. Use environment variables for sensitive data
5. Enable error logging and monitoring

## Additional Resources

- [Google Identity Documentation](https://developers.google.com/identity)
- [OAuth 2.0 Guide](https://developers.google.com/identity/protocols/oauth2)
- [Sign In With Google](https://developers.google.com/identity/gsi/web)

---

**Need Help?**
If you encounter issues, check the browser console for errors and review the PHP error logs in your XAMPP installation.
