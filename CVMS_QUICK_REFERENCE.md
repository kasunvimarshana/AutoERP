# CVMS UI Components - Quick Reference Guide

## Files Created

### Vue Components (5 files)
1. `resources/js/pages/customers/CustomerDetail.vue` - Customer detail view
2. `resources/js/pages/vehicles/VehicleList.vue` - Vehicles list with filters
3. `resources/js/pages/vehicles/VehicleDetail.vue` - Vehicle detail view
4. `resources/js/pages/service-records/ServiceRecordList.vue` - Service records list
5. `resources/js/pages/service-records/ServiceRecordDetail.vue` - Service record detail view

### Documentation
- `CVMS_UI_COMPONENTS_SUMMARY.md` - Comprehensive implementation guide

### Updated Files
- `resources/js/i18n/locales/en.js` - Added common.back, common.all, common.previous, common.next
- `resources/js/i18n/locales/es.js` - Added Spanish translations
- `resources/js/i18n/locales/fr.js` - Added French translations

---

## Component Features at a Glance

| Component | Search | Filters | Pagination | Actions | Statistics |
|-----------|--------|---------|------------|---------|------------|
| CustomerDetail | ❌ | ❌ | ❌ | Edit, Delete | ✅ |
| VehicleList | ✅ | Status | ✅ | View, Edit, Delete | ❌ |
| VehicleDetail | ❌ | ❌ | ❌ | Edit, Update Mileage, Transfer, Delete | ✅ |
| ServiceRecordList | ✅ | Status, Type, Branch | ✅ | View, Edit, Complete, Cancel, Delete | ❌ |
| ServiceRecordDetail | ❌ | ❌ | ❌ | Edit, Complete, Cancel, Delete | ❌ |

---

## Permissions Required

### Customer Module
- `customer.create` - Create customer button
- `customer.update` - Edit customer button
- `customer.delete` - Delete customer button

### Vehicle Module
- `vehicle.create` - Create vehicle button
- `vehicle.update` - Edit vehicle, update mileage, transfer ownership
- `vehicle.delete` - Delete vehicle button

### Service Record Module
- `service-record.create` - Create service record button
- `service-record.update` - Edit service record, complete, cancel
- `service-record.delete` - Delete service record button

**Note:** Super-admin and admin roles bypass all permission checks.

---

## Router Configuration Needed

Add these routes to your Vue Router:

```javascript
// Customer routes
{
  path: '/customers/:id',
  name: 'customer.show',
  component: () => import('@/pages/customers/CustomerDetail.vue'),
  meta: { requiresAuth: true }
},

// Vehicle routes
{
  path: '/vehicles',
  name: 'vehicle.index',
  component: () => import('@/pages/vehicles/VehicleList.vue'),
  meta: { requiresAuth: true, permission: 'vehicle.read' }
},
{
  path: '/vehicles/:id',
  name: 'vehicle.show',
  component: () => import('@/pages/vehicles/VehicleDetail.vue'),
  meta: { requiresAuth: true }
},

// Service record routes
{
  path: '/service-records',
  name: 'service-record.index',
  component: () => import('@/pages/service-records/ServiceRecordList.vue'),
  meta: { requiresAuth: true, permission: 'service-record.read' }
},
{
  path: '/service-records/:id',
  name: 'service-record.show',
  component: () => import('@/pages/service-records/ServiceRecordDetail.vue'),
  meta: { requiresAuth: true }
},
```

---

## Navigation Menu Items

Add to your sidebar navigation:

```javascript
{
  title: 'CVMS',
  icon: 'fas fa-car',
  children: [
    {
      title: 'Customers',
      path: '/customers',
      icon: 'fas fa-users',
      permission: 'customer.read'
    },
    {
      title: 'Vehicles',
      path: '/vehicles',
      icon: 'fas fa-car',
      permission: 'vehicle.read'
    },
    {
      title: 'Service Records',
      path: '/service-records',
      icon: 'fas fa-wrench',
      permission: 'service-record.read'
    }
  ]
}
```

---

## API Endpoints Used

### Customer Endpoints
- `GET /api/v1/customers/:id/with-vehicles` - Fetch customer with vehicles
- `GET /api/v1/customers/:id/statistics` - Fetch customer statistics
- `DELETE /api/v1/customers/:id` - Delete customer

### Vehicle Endpoints
- `GET /api/v1/vehicles` - List vehicles (with filters and pagination)
- `GET /api/v1/vehicles/search` - Search vehicles
- `GET /api/v1/vehicles/:id/with-relations` - Fetch vehicle with relations
- `GET /api/v1/vehicles/:id/statistics` - Fetch vehicle statistics
- `PUT /api/v1/vehicles/:id/mileage` - Update vehicle mileage
- `PUT /api/v1/vehicles/:id/transfer-ownership` - Transfer ownership
- `DELETE /api/v1/vehicles/:id` - Delete vehicle

### Service Record Endpoints
- `GET /api/v1/service-records` - List service records (with filters)
- `GET /api/v1/service-records/search` - Search service records
- `GET /api/v1/service-records/:id/with-relations` - Fetch with relations
- `GET /api/v1/service-records/by-vehicle/:vehicleId` - By vehicle
- `POST /api/v1/service-records/:id/complete` - Complete service
- `POST /api/v1/service-records/:id/cancel` - Cancel service
- `DELETE /api/v1/service-records/:id` - Delete service record

---

## Common Utility Functions

All components include these utility functions:

```javascript
// Format currency
const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

// Format numbers with thousand separators
const formatNumber = (value) => {
  return new Intl.NumberFormat('en-US').format(value);
};

// Format dates
const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString();
};
```

---

## Testing Checklist

### CustomerDetail.vue
- [ ] Customer info displays correctly
- [ ] Statistics cards show correct data
- [ ] Vehicles list displays with correct links
- [ ] Edit button works (with permission)
- [ ] Delete button works (with permission)
- [ ] Loading state shows during fetch
- [ ] Error state displays on failure
- [ ] Empty state shows when no vehicles

### VehicleList.vue
- [ ] Vehicles list displays correctly
- [ ] Search functionality works (debounced)
- [ ] Status filter works
- [ ] Pagination works correctly
- [ ] Per-page selector works
- [ ] Create button visible with permission
- [ ] Edit/Delete buttons work with permissions
- [ ] Links to customer pages work

### VehicleDetail.vue
- [ ] Vehicle info displays correctly
- [ ] Statistics cards show correct data
- [ ] Service history displays with links
- [ ] Update Mileage dialog works
- [ ] Transfer Ownership dialog works
- [ ] Edit/Delete buttons work with permissions
- [ ] Loading/error states work

### ServiceRecordList.vue
- [ ] Service records list displays correctly
- [ ] Search functionality works
- [ ] All filters work (status, type, branch)
- [ ] Pagination works correctly
- [ ] Complete/Cancel buttons work
- [ ] Conditional action buttons display correctly
- [ ] Links to vehicle/customer pages work

### ServiceRecordDetail.vue
- [ ] Service info displays correctly
- [ ] Vehicle info displays with link
- [ ] Customer info displays with link
- [ ] Parts used table displays correctly
- [ ] Complete/Cancel actions work
- [ ] Conditional buttons based on status
- [ ] Edit/Delete buttons work with permissions

---

## Common Issues & Solutions

### Issue: Components not loading
**Solution:** Ensure routes are properly configured in router and components are imported correctly.

### Issue: Permission checks not working
**Solution:** Verify auth store is properly initialized and user permissions are loaded.

### Issue: Search/filters not working
**Solution:** Check API endpoints return correct response format with pagination data.

### Issue: Toast notifications not showing
**Solution:** Ensure Toast component is properly imported and ref is correctly set.

### Issue: i18n keys not found
**Solution:** Verify all translation keys are present in en.js, es.js, and fr.js files.

---

## Next Development Steps

1. **Create Form Components:**
   - CustomerForm.vue (for create/edit)
   - VehicleForm.vue (for create/edit)
   - ServiceRecordForm.vue (for create/edit)

2. **Add Route Guards:**
   - Implement permission-based route guards
   - Add redirect for unauthorized access

3. **Enhance Features:**
   - Add export functionality (CSV/PDF)
   - Add bulk operations (bulk delete, bulk status update)
   - Add advanced filtering options
   - Add sorting by column headers

4. **Optimize Performance:**
   - Implement virtual scrolling for large lists
   - Add caching for frequently accessed data
   - Optimize API calls with batch requests

5. **Improve UX:**
   - Add keyboard shortcuts
   - Add tooltips for action buttons
   - Add confirmation before navigation with unsaved changes
   - Add breadcrumbs for better navigation

---

## Support & Documentation

For detailed information, refer to:
- `CVMS_UI_COMPONENTS_SUMMARY.md` - Complete implementation guide
- `CVMS_API_DOCUMENTATION.md` - API endpoints documentation
- `CVMS_IMPLEMENTATION_COMPLETE.md` - Backend implementation details

For questions or issues:
1. Check the comprehensive summary document
2. Review existing UserList.vue and CustomerList.vue patterns
3. Verify API endpoints are working correctly
4. Check browser console for errors
5. Verify permissions are correctly assigned

---

## Version Info

- **Created:** January 2025
- **Vue Version:** 3.x (Composition API)
- **UI Framework:** AdminLTE 4.0 + Bootstrap 5
- **Icons:** FontAwesome
- **i18n:** English, Spanish, French
