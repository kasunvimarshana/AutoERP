# Vue.js Frontend Documentation

## Overview

This document provides comprehensive documentation for the Vue.js 3 frontend implementation of the ModularSaaS application. The frontend is built with modern best practices, focusing on clean architecture, maintainability, security, and accessibility.

## Technology Stack

- **Vue.js 3.5+** - Progressive JavaScript framework with Composition API
- **Vue Router 4.5+** - Official routing library for Vue.js
- **Pinia 2.2+** - Official state management library for Vue.js
- **Vue I18n 10+** - Internationalization plugin
- **Vite 6+** - Next-generation frontend build tool
- **Tailwind CSS 3.4+** - Utility-first CSS framework
- **Axios 1.7+** - Promise-based HTTP client
- **Headless UI** - Unstyled, accessible UI components
- **Heroicons** - Beautiful hand-crafted SVG icons

## Project Structure

```
resources/js/
├── components/           # Reusable UI components
│   ├── Alert.vue        # Alert/notification component
│   ├── AuthLayout.vue   # Layout for auth pages
│   ├── FormButton.vue   # Reusable button component
│   └── FormInput.vue    # Reusable input component
├── i18n/                # Internationalization
│   ├── locales/         # Translation files
│   │   ├── en.js       # English translations
│   │   ├── es.js       # Spanish translations
│   │   └── fr.js       # French translations
│   └── index.js        # i18n configuration
├── pages/               # Page components
│   ├── auth/           # Authentication pages
│   │   ├── Login.vue
│   │   ├── Register.vue
│   │   ├── ForgotPassword.vue
│   │   └── ResetPassword.vue
│   ├── Dashboard.vue   # Main dashboard
│   ├── Profile.vue     # User profile
│   ├── Home.vue        # Landing page
│   └── NotFound.vue    # 404 page
├── router/             # Vue Router configuration
│   └── index.js       # Routes and navigation guards
├── services/          # API services
│   ├── api.js        # Axios instance with interceptors
│   └── auth.js       # Authentication API calls
├── stores/           # Pinia stores
│   └── auth.js      # Authentication state management
├── App.vue          # Root component
└── app.js          # Application entry point
```

## Features

### 1. Authentication System

#### Login
- **Route**: `/login`
- **Features**:
  - Email and password authentication
  - "Remember me" functionality
  - Password reset link
  - Form validation
  - Error handling
  - Auto-redirect to intended page after login

#### Register
- **Route**: `/register`
- **Features**:
  - User registration with name, email, and password
  - Password confirmation validation
  - Password strength requirements
  - Form validation
  - Auto-login after successful registration

#### Forgot Password
- **Route**: `/forgot-password`
- **Features**:
  - Email-based password reset
  - Success confirmation
  - Link to return to login

#### Reset Password
- **Route**: `/reset-password`
- **Features**:
  - Token-based password reset
  - Password confirmation
  - Form validation
  - Auto-redirect to login after successful reset

### 2. Protected Pages

#### Dashboard
- **Route**: `/dashboard`
- **Authentication**: Required
- **Features**:
  - Welcome message with user name
  - Display user email
  - Show user roles with badges
  - Display permissions count
  - Navigation to profile
  - Logout functionality

#### Profile
- **Route**: `/profile`
- **Authentication**: Required
- **Features**:
  - Display user information
  - Show all assigned roles
  - List all user permissions
  - Navigation bar
  - Logout functionality

### 3. State Management (Pinia)

The authentication store (`stores/auth.js`) provides:

#### State
- `user` - Current user object
- `token` - Authentication token
- `loading` - Loading state
- `error` - Error messages

#### Getters
- `isAuthenticated` - Check if user is logged in
- `userName` - Get current user's name
- `userEmail` - Get current user's email
- `userRoles` - Get user's roles
- `userPermissions` - Get user's permissions

#### Actions
- `checkAuth()` - Verify authentication status
- `register(credentials)` - Register new user
- `login(credentials)` - Login user
- `logout()` - Logout from current device
- `logoutAll()` - Logout from all devices
- `refresh()` - Refresh authentication token
- `forgotPassword(email)` - Request password reset
- `resetPassword(data)` - Reset password with token
- `hasPermission(permission)` - Check if user has permission
- `hasRole(role)` - Check if user has role
- `clearAuth()` - Clear authentication state

### 4. Routing & Navigation Guards

The router (`router/index.js`) includes:

#### Public Routes
- `/` - Home/landing page
- `/login` - Login page (guest only)
- `/register` - Register page (guest only)
- `/forgot-password` - Forgot password page (guest only)
- `/reset-password` - Reset password page (guest only)

#### Protected Routes (Require Authentication)
- `/dashboard` - User dashboard
- `/profile` - User profile

#### Navigation Guards
- **requiresAuth**: Redirects to login if not authenticated
- **guestOnly**: Redirects to dashboard if already authenticated
- Preserves intended destination for post-login redirect

### 5. API Service Layer

#### Base API Configuration (`services/api.js`)
- Base URL: `/api/v1`
- Automatic token injection in headers
- Response interceptors for error handling
- 401 handling (auto-logout and redirect)

#### Authentication Service (`services/auth.js`)
Provides methods for all authentication endpoints:
- `register(credentials)`
- `login(credentials)`
- `logout()`
- `logoutAll()`
- `me()`
- `refresh()`
- `forgotPassword(email)`
- `resetPassword(data)`
- `resendVerification()`

### 6. Internationalization (i18n)

#### Supported Languages
- **English (en)** - Default
- **Spanish (es)**
- **French (fr)**

#### Usage in Components
```vue
<template>
  <h1>{{ $t('auth.login') }}</h1>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
console.log(t('auth.register'));
</script>
```

#### Switching Languages
```javascript
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();
locale.value = 'es'; // Switch to Spanish
```

### 7. Reusable Components

#### AuthLayout
Layout wrapper for authentication pages with centered card design.

**Props**:
- `title` (String) - Page title

**Slots**:
- `default` - Main content
- `header` - Custom header (optional)
- `footer` - Footer content (optional)

#### FormInput
Styled input field with label, validation, and error display.

**Props**:
- `id` (String, required) - Input ID
- `modelValue` (String/Number) - v-model value
- `type` (String) - Input type (default: 'text')
- `label` (String) - Input label
- `placeholder` (String) - Placeholder text
- `required` (Boolean) - Required field
- `disabled` (Boolean) - Disabled state
- `error` (String) - Error message
- `hint` (String) - Helper text
- `autocomplete` (String) - Autocomplete attribute

**Events**:
- `update:modelValue` - Value change
- `blur` - Input blur

#### FormButton
Styled button with loading state and variants.

**Props**:
- `type` (String) - Button type (default: 'button')
- `variant` (String) - Style variant: 'primary', 'secondary', 'danger'
- `disabled` (Boolean) - Disabled state
- `loading` (Boolean) - Loading state (shows spinner)

#### Alert
Notification/alert component with different types.

**Props**:
- `show` (Boolean) - Show/hide alert
- `type` (String) - Alert type: 'success', 'error', 'warning', 'info'
- `message` (String, required) - Alert message
- `dismissible` (Boolean) - Can be dismissed

**Events**:
- `dismiss` - Alert dismissed

## Security Best Practices

### 1. Token Management
- Tokens stored in localStorage
- Automatic token injection in API requests
- Token validation on every request
- Auto-logout on 401 responses

### 2. Input Validation
- Client-side validation before API calls
- Server-side validation errors displayed
- Password strength requirements enforced
- Email format validation

### 3. Route Protection
- Navigation guards prevent unauthorized access
- Authentication check on app mount
- Redirect to login for protected routes
- Preserve intended destination

### 4. CSRF Protection
- CSRF token meta tag in blade template
- Axios configured to use CSRF token
- Laravel backend validates CSRF tokens

### 5. XSS Prevention
- Vue.js auto-escapes content
- No `v-html` usage with user input
- Sanitized error messages

## Accessibility Features

### 1. Semantic HTML
- Proper heading hierarchy
- Semantic form elements
- ARIA labels where needed

### 2. Keyboard Navigation
- All interactive elements keyboard accessible
- Focus states visible
- Logical tab order

### 3. Screen Reader Support
- Descriptive labels
- Error announcements
- Loading state announcements

### 4. Color Contrast
- WCAG AA compliant colors
- Tailwind CSS color system
- Sufficient contrast ratios

## Build & Development

### Installation
```bash
# Install dependencies
npm install

# Install PHP dependencies
composer install
```

### Development
```bash
# Start Vite dev server
npm run dev

# Start Laravel server
php artisan serve

# Run both concurrently
composer dev
```

### Production Build
```bash
# Build for production
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Performance Optimization

### 1. Code Splitting
- Route-based code splitting
- Lazy-loaded page components
- Dynamic imports for heavy components

### 2. Asset Optimization
- Vite for fast builds
- Tree-shaking unused code
- CSS purging with Tailwind

### 3. Caching
- Browser caching for static assets
- Service worker (can be added)
- API response caching

### 4. Bundle Size
- Minimal dependencies
- No CDN usage (all via npm)
- Optimized production builds

## Testing (Future Enhancement)

### Recommended Tools
- **Vitest** - Unit testing framework
- **Vue Test Utils** - Vue component testing
- **Playwright** - E2E testing
- **Cypress** - E2E testing alternative

### Test Coverage Goals
- Component unit tests
- Store unit tests
- Integration tests for forms
- E2E tests for critical flows

## Browser Support

### Supported Browsers
- Chrome/Edge (last 2 versions)
- Firefox (last 2 versions)
- Safari (last 2 versions)
- Mobile browsers (iOS Safari, Chrome Mobile)

### Required Features
- ES6+ support
- CSS Grid and Flexbox
- Fetch API
- LocalStorage

## Deployment Checklist

### Frontend
- [ ] Run `npm run build`
- [ ] Verify build output in `public/build`
- [ ] Test production build locally
- [ ] Configure web server for SPA routing

### Laravel
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Run `php artisan optimize`
- [ ] Configure CORS if needed
- [ ] Set up SSL/HTTPS

### Environment Variables
```env
VITE_APP_NAME="${APP_NAME}"
VITE_API_URL="${APP_URL}/api/v1"
```

## Troubleshooting

### Common Issues

#### 1. "Module not found" errors
```bash
# Clear node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
```

#### 2. Vite not detecting changes
```bash
# Restart dev server
npm run dev
```

#### 3. 404 on refresh in SPA mode
Ensure web server is configured for SPA:

**Nginx**:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

**Apache** (.htaccess already configured in Laravel)

#### 4. CORS errors
- Ensure API routes are in `routes/api.php`
- Configure CORS in `config/cors.php`
- Add Sanctum configuration in `config/sanctum.php`

## Future Enhancements

### Planned Features
- [ ] Two-Factor Authentication (2FA) UI
- [ ] OAuth2 login (Google, GitHub)
- [ ] User preferences management
- [ ] Dark mode toggle
- [ ] Notifications center
- [ ] Real-time updates (WebSockets)
- [ ] File upload component
- [ ] Data tables with sorting/filtering
- [ ] Charts and analytics
- [ ] Progressive Web App (PWA)

### UI/UX Improvements
- [ ] Loading skeletons
- [ ] Transition animations
- [ ] Toast notifications
- [ ] Modal dialogs
- [ ] Confirmation prompts
- [ ] Tooltips
- [ ] Dropdown menus

## Contributing

When contributing to the frontend:

1. Follow Vue.js 3 Composition API style
2. Use TypeScript types (if adding TS)
3. Maintain component documentation
4. Write tests for new features
5. Follow Tailwind CSS utility classes
6. Ensure accessibility standards
7. Update this documentation

## Resources

### Documentation
- [Vue.js 3 Docs](https://vuejs.org/)
- [Pinia Docs](https://pinia.vuejs.org/)
- [Vue Router Docs](https://router.vuejs.org/)
- [Vue I18n Docs](https://vue-i18n.intlify.dev/)
- [Vite Docs](https://vitejs.dev/)
- [Tailwind CSS Docs](https://tailwindcss.com/)

### Tools
- [Vue DevTools](https://devtools.vuejs.org/)
- [Vite DevTools](https://github.com/webfansplz/vite-plugin-vue-devtools)

## Support

For issues, questions, or contributions:
- Check the main [README.md](../../README.md)
- Review [ARCHITECTURE.md](../../ARCHITECTURE.md)
- Consult [CONTRIBUTING.md](../../CONTRIBUTING.md)

---

**Last Updated**: January 2024
**Version**: 1.0.0
