# Employee Wellness Check-in Shortcodes Documentation

This document provides detailed information about the shortcodes available in the Employee Wellness Check-in plugin.

## Available Shortcodes

### 1. Employee Wellness Check-in Form

**Shortcode:** `[employee_wellness_checkin_form]`

**Description:** Displays the mood submission form for logged-in users. This form allows employees to report their daily well-being by selecting a mood icon.

**Usage Example:**
```
[employee_wellness_checkin_form]
```

**Notes:**
- This shortcode only works for logged-in users. Non-logged-in users will see a message asking them to log in.
- Users can submit their mood once per day or update their previous submission.
- The form displays visual mood icons with labels: Happy, Okay, Tired, Stressed, and Anxious.

### 2. Employee Wellness Report Dashboard

**Shortcode:** `[employee_wellness_report_dashboard]`

**Description:** Displays the wellness report dashboard for administrators. This dashboard provides insights into employee well-being data.

**Usage Example:**
```
[employee_wellness_report_dashboard]
```

**Notes:**
- This shortcode is intended for administrators and HR personnel.
- It displays aggregated mood data and trends.
- Access to this dashboard should be restricted to appropriate personnel.

## Implementation Examples

### Adding the Check-in Form to a Page

1. Create a new page in WordPress (e.g., "Daily Wellness Check-in")
2. Add the following shortcode to the page content:
   ```
   [employee_wellness_checkin_form]
   ```
3. Publish the page

### Adding the Dashboard to a Protected Page

1. Create a new page in WordPress (e.g., "Wellness Reports")
2. Add the following shortcode to the page content:
   ```
   [employee_wellness_report_dashboard]
   ```
3. Use a membership or page restriction plugin to ensure only administrators can access this page
4. Publish the page

## Troubleshooting

If the shortcodes are not working properly, check the following:

1. Ensure the plugin is activated
2. Verify that users have the appropriate permissions
3. Check for JavaScript errors in the browser console
4. Ensure the database tables were created correctly during plugin activation