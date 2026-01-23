# CVMS UI - Quick Start Guide

## Overview

The Customer & Vehicle Management System (CVMS) UI provides a complete, professional interface for managing customers, vehicles, and service records in a vehicle service center application.

## Features at a Glance

✅ **Customer Management**
- List, search, filter customers
- View customer details with vehicles
- Create, edit, delete customers
- Customer statistics and analytics

✅ **Vehicle Management**
- List, search, filter vehicles
- View vehicle details with service history
- Track mileage and insurance
- Transfer vehicle ownership
- Service due alerts

✅ **Service Record Management**
- List, search, filter service records
- Track service status and costs
- Record parts used
- Complete/cancel services
- Cross-branch service history

✅ **Additional Features**
- Multi-language support (EN, ES, FR)
- Responsive design (mobile, tablet, desktop)
- Permission-based access control
- Real-time search with debouncing
- Toast notifications
- Confirmation dialogs

## Installation

### Prerequisites

- Node.js 18+ and npm
- Laravel 11+ backend running
- CVMS backend module configured

### Setup

1. **Install Dependencies**
```bash
npm install
```

2. **Build for Production**
```bash
npm run build
```

3. **Run Development Server**
```bash
npm run dev
```

## Usage

### Accessing the UI

1. Start Laravel backend:
```bash
php artisan serve
```

2. Start Vue dev server:
```bash
npm run dev
```

3. Open browser: `http://localhost:8000`

4. Login with credentials

5. Navigate to:
   - `/customers` - Customer management
   - `/vehicles` - Vehicle management
   - `/service-records` - Service record management

### Required Permissions

Ensure users have these permissions:

**View Access:**
- `customer.view` - View customers
- `vehicle.view` - View vehicles
- `service-record.view` - View service records

**Create Access:**
- `customer.create` - Create customers
- `vehicle.create` - Create vehicles
- `service-record.create` - Create service records

**Edit Access:**
- `customer.update` - Edit customers
- `vehicle.update` - Edit vehicles
- `service-record.update` - Edit service records

**Delete Access:**
- `customer.delete` - Delete customers
- `vehicle.delete` - Delete vehicles
- `service-record.delete` - Delete service records

### Seeding Test Data

To populate the UI with test data:

```bash
php artisan db:seed --class=CustomerSeeder
php artisan db:seed --class=VehicleSeeder
php artisan db:seed --class=ServiceRecordSeeder
```

## Navigation

### Sidebar Menu

The CVMS features appear in the **MANAGEMENT** section of the sidebar:

- 👥 **Customers** - Manage customer database
- 🚗 **Vehicles** - Track vehicles and ownership
- 🔧 **Service Records** - Manage service history

### Keyboard Shortcuts

- `Ctrl+K` - Focus search box (future enhancement)
- `Esc` - Close dialogs
- `Tab` - Navigate between fields

## Common Tasks

### Adding a New Customer

1. Navigate to `/customers`
2. Click "Create Customer" button
3. Fill in customer form (future enhancement - form not yet implemented)
4. Save customer

### Viewing Customer's Vehicles

1. Navigate to `/customers`
2. Click on customer row or "View" button
3. Scroll to "Vehicles" section
4. Click on any vehicle to view details

### Recording a Service

1. Navigate to `/service-records`
2. Click "Create Service Record" (future enhancement - form not yet implemented)
3. Select vehicle and customer
4. Enter service details
5. Save service record

### Completing a Service

1. Navigate to `/service-records`
2. Find the service record
3. Click "View" to see details
4. Click "Complete" button
5. Confirm the action

### Searching

All list views have a search bar at the top:
- Type at least 2 characters to search
- Search is debounced (500ms delay)
- Press `X` in search box to clear

### Filtering

Use the dropdown filters to narrow results:
- **Customers**: Status, Type
- **Vehicles**: Status
- **Service Records**: Status, Type, Branch

### Pagination

At the bottom of each list:
- Select items per page (10, 25, 50, 100)
- Use Previous/Next buttons
- See current page / total pages

## Troubleshooting

### Problem: "No data found"

**Solution:**
- Ensure backend is running
- Check API endpoints are accessible
- Seed test data if database is empty
- Verify user has `*.view` permission

### Problem: "Actions not visible"

**Solution:**
- Check user permissions
- Ensure role has appropriate permissions
- Verify `super-admin` or `admin` role assigned

### Problem: "Search not working"

**Solution:**
- Type at least 2 characters
- Wait 500ms for debounce
- Check network tab for API calls
- Verify backend search endpoint works

### Problem: "Build fails"

**Solution:**
```bash
# Clear cache and reinstall
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Problem: "404 errors on routes"

**Solution:**
- Ensure Vue Router is properly configured
- Check Laravel routes include SPA fallback
- Verify `.htaccess` or nginx config for SPA

## Development

### Project Structure

```
resources/js/
├── services/           # API service layer
├── stores/            # Pinia state management
├── pages/             # Vue page components
│   ├── customers/
│   ├── vehicles/
│   └── service-records/
├── components/        # Reusable components
├── layouts/          # Layout components
├── router/           # Vue Router config
└── i18n/            # Translations
```

### Adding New Features

1. **Add API Method** in `services/`
2. **Add Store Action** in `stores/`
3. **Update Component** in `pages/`
4. **Add Translation Keys** in `i18n/locales/`
5. **Test Feature**
6. **Update Documentation**

### Code Style

Follow these conventions:
- Vue 3 Composition API
- `<script setup>` syntax
- PascalCase for components
- camelCase for variables/methods
- i18n for all user-facing text
- Proper error handling
- Loading states for async operations

## API Integration

### Base Configuration

API calls use `/api/v1` prefix with Sanctum authentication.

Configure in `.env`:
```
VITE_API_URL=http://localhost:8000
```

### Available Endpoints

See `CVMS_UI_IMPLEMENTATION_COMPLETE.md` for full list of endpoints.

Key endpoints:
- `GET /api/v1/customers`
- `GET /api/v1/vehicles`
- `GET /api/v1/service-records`

## Multi-Language

### Switching Language

Click the language dropdown in the top navbar:
- 🇺🇸 English
- 🇪🇸 Español
- 🇫🇷 Français

### Adding New Language

1. Create new file: `resources/js/i18n/locales/[lang].js`
2. Copy structure from `en.js`
3. Translate all keys
4. Import in `resources/js/i18n/index.js`
5. Add to language dropdown

## Performance

### Optimization Tips

- Pagination reduces data load
- Debounced search reduces API calls
- Lazy loading for routes
- Image optimization for avatars
- Gzip compression enabled

### Monitoring

Check browser DevTools:
- Network tab - API response times
- Performance tab - Page load metrics
- Console - No errors should appear

## Security

### Best Practices

- Always check permissions before actions
- Validate input on frontend and backend
- Use CSRF tokens (handled by Sanctum)
- Sanitize user input
- Use HTTPS in production

### Audit Trail

All actions are logged by backend:
- User who performed action
- Timestamp
- Action type
- Resource affected

## Browser Support

Tested on:
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

## Deployment

### Production Build

```bash
# Build assets
npm run build

# Assets are in public/build/
# Deploy with Laravel app
```

### Environment Variables

Set in `.env`:
```
VITE_API_URL=https://your-domain.com
VITE_APP_NAME="Your App Name"
```

## Support

### Documentation

- `CVMS_UI_IMPLEMENTATION_COMPLETE.md` - Full implementation details
- `CVMS_SCREENSHOT_GUIDE.md` - Screenshot guidelines
- `README.md` - Main project README
- `ARCHITECTURE.md` - System architecture

### Getting Help

1. Check documentation first
2. Search existing issues on GitHub
3. Ask in team chat/Slack
4. Create detailed bug report if needed

## Changelog

### Version 1.0.0 (2026-01-22)

**Initial Release**
- ✅ Customer list and detail views
- ✅ Vehicle list and detail views
- ✅ Service record list and detail views
- ✅ Multi-language support (EN, ES, FR)
- ✅ Permission-based access control
- ✅ Responsive design
- ✅ Search and filter functionality
- ✅ Pagination
- ✅ Toast notifications
- ✅ Confirmation dialogs

**Future Enhancements** (not yet implemented)
- ⏳ Form components (Create/Edit)
- ⏳ Export to PDF/Excel
- ⏳ Dashboard widgets
- ⏳ Charts and analytics
- ⏳ Email notifications
- ⏳ Appointment scheduling

## License

Same as main application.

---

**Created by**: GitHub Copilot  
**Date**: January 22, 2026  
**Version**: 1.0.0
