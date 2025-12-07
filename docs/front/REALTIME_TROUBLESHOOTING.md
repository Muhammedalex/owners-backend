# Real-time Notifications Troubleshooting Guide

## Common Issues and Solutions

### Issue 1: Notifications not received in real-time (need to refresh)

**Problem:** Notifications are created but not received in real-time. User needs to refresh the page.

**Solutions:**

1. **Check Broadcasting Route Path**
   - Backend route: `/api/v1/broadcasting/auth`
   - Frontend Echo config must use: `/api/v1/broadcasting/auth`
   
   Update your `echo.js`:
   ```javascript
   authorizer: (channel) => {
     return {
       authorize: (socketId, callback) => {
         apiClient
           .post('/api/v1/broadcasting/auth', {  // ✅ Correct path
             socket_id: socketId,
             channel_name: channel.name,
           })
           .then((response) => {
             callback(null, response.data);
           })
           .catch((error) => {
             callback(error);
           });
       },
     };
   },
   ```

2. **Check Event Broadcasting**
   - Event must implement `ShouldBroadcastNow` (not `ShouldBroadcast`) for immediate broadcasting
   - If using `ShouldBroadcast`, make sure queue worker is running: `php artisan queue:work`

3. **Check Channel Name Matching**
   - Backend Event: `user.{user_id}` (integer ID)
   - Frontend Hook: `user.${userId}` - must use integer ID, not UUID
   
   Update your React hook:
   ```javascript
   // ✅ Use user.id (integer), not user.uuid
   const userId = user.id; // Not user.uuid
   const channelName = `user.${userId}`;
   ```

4. **Check Reverb Server**
   - Make sure Reverb server is running: `php artisan reverb:start`
   - Check Reverb logs for connection errors

5. **Check Browser Console**
   - Open browser DevTools → Console
   - Look for Echo connection messages
   - Check for WebSocket connection errors

### Issue 2: Broadcasting Auth Works but No Events Received

**Problem:** Channel subscription succeeds but events are not received.

**Solutions:**

1. **Verify Event is Broadcasting**
   - Check Laravel logs: `storage/logs/laravel.log`
   - Look for broadcasting errors

2. **Check Event Name**
   - Backend: `broadcastAs()` returns `'notification.created'`
   - Frontend: Listen for `.notification.created` (with dot prefix)
   
   ```javascript
   channel.listen('.notification.created', (data) => {
     // ✅ Correct - dot prefix is required
   });
   ```

3. **Verify Channel Authorization**
   - Check `routes/channels.php` authorization logic
   - Ensure user ID matches channel parameter

### Issue 3: WebSocket Connection Fails

**Problem:** Cannot connect to Reverb server.

**Solutions:**

1. **Check Reverb Server Status**
   ```bash
   php artisan reverb:start
   ```
   - Server should be running on configured port (default: 8080)

2. **Check Environment Variables**
   ```env
   VITE_REVERB_APP_KEY=your-key
   VITE_REVERB_HOST=localhost
   VITE_REVERB_PORT=8080
   VITE_REVERB_SCHEME=http
   ```

3. **Check CORS Configuration**
   - Ensure Reverb allows your frontend origin
   - Check `config/reverb.php` → `allowed_origins`

4. **Check Firewall/Network**
   - Ensure port 8080 is not blocked
   - Check if WebSocket connections are allowed

### Issue 4: Channel Subscription Fails

**Problem:** Cannot subscribe to private channel.

**Solutions:**

1. **Check Authentication**
   - Ensure access token is included in API requests
   - Verify token is valid and not expired

2. **Check Broadcasting Auth Endpoint**
   - Test manually: `POST /api/v1/broadcasting/auth`
   - Should return 200 with auth data

3. **Check Channel Authorization**
   - Verify `routes/channels.php` logic
   - Ensure user has permission to access channel

### Debugging Steps

1. **Enable Echo Debugging**
   ```javascript
   // In echo.js
   const echo = new Echo({
     // ... config
     enableLogging: true, // Add this
   });
   ```

2. **Check Browser Network Tab**
   - Look for WebSocket connection (ws:// or wss://)
   - Check for `/broadcasting/auth` requests
   - Verify responses

3. **Check Laravel Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Test Broadcasting Manually**
   ```php
   // In tinker or controller
   event(new \App\Events\V1\Notification\NotificationCreated($notification));
   ```

5. **Verify Event is Dispatched**
   - Add logging in Event constructor
   - Check if event is being fired

### Quick Checklist

- [ ] Reverb server is running (`php artisan reverb:start`)
- [ ] Queue worker is running (if using `ShouldBroadcast`)
- [ ] Broadcasting route is correct (`/api/v1/broadcasting/auth`)
- [ ] Channel name matches (integer ID, not UUID)
- [ ] Event implements `ShouldBroadcastNow` for immediate broadcast
- [ ] Frontend listens for `.notification.created` (with dot)
- [ ] Access token is valid and included in requests
- [ ] Browser console shows Echo connection messages
- [ ] WebSocket connection is established
- [ ] Channel subscription succeeds

### Testing Real-time Notifications

1. **Open two browser windows**
   - Login as the same user in both
   - Open browser console in both

2. **Create a notification via API**
   ```bash
   POST /api/v1/notifications
   {
     "user_id": 1,
     "type": "info",
     "title": "Test Notification",
     "message": "This is a test"
   }
   ```

3. **Check both windows**
   - Should receive notification immediately
   - Console should show event received
   - Unread count should update

### Common Mistakes

1. ❌ Using UUID instead of integer ID for channel name
2. ❌ Wrong broadcasting auth path (`/broadcasting/auth` instead of `/api/v1/broadcasting/auth`)
3. ❌ Missing dot prefix in event name (`.notification.created`)
4. ❌ Using `ShouldBroadcast` without queue worker running
5. ❌ Reverb server not running
6. ❌ Wrong Reverb configuration (host, port, scheme)

