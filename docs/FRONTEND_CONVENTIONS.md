# Frontend Conventions (Vue 3 / Inertia)

## 6.2 Composition API vs Options API

- **Prefer Composition API** for all **new** components. Use `<script setup>` and composables (e.g. `useForm`, `useI18n`, `useAppGlobals`) for a consistent, testable style.
- **When touching high-traffic or complex pages** (e.g. booking, login, dashboard), migrate them to Composition API instead of leaving Options API in place.
- **Standardize on one style per module**: if a module (e.g. landing/user-web) is being refactored, use Composition API for the whole module so the next developer doesn’t mix styles in the same area.
- Existing Options API components can stay as-is until they are modified; no need to migrate everything at once.

## 6.1 State and routing

- Use **Ziggy’s `route()`** for all named routes and URL generation (injected globally via ZiggyVue).
- **Permissions and shared app state** live in Vuex (`resources/js/state/store.js`). In Composition API, use the **`useAppGlobals()`** composable (`resources/js/composables/useAppGlobals.js`) to access permissions in one place.
- For new modules, Pinia can be considered if you prefer Composition-style stores; keep permissions in the existing Vuex store for consistency.

## 6.3 i18n and accessibility

- Use **vue-i18n** and **`$t()`** (or `useI18n().t`) for all user-facing strings; locale and direction come from the backend.
- **Audit translation keys**: ensure every `$t('key')` has a corresponding key in the locale files under `public/lang/{locale}/` (e.g. `view_pages_1.json`, `user_app.json`).
- **Accessibility**: add `aria-*` attributes and semantic roles for critical flows:
  - **Login**: `role="main"`, `aria-label` on form and inputs, `aria-describedby` for errors, `role="alert"` for error messages.
  - **Booking**: `role="form"` where appropriate, `aria-label` for pickup/drop sections and actions.
  - **Payment**: clear labels and `aria-label` on payment controls; announce errors with `role="alert"` or `aria-live`.
