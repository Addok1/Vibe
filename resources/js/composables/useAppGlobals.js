import { computed } from 'vue';
import { useStore } from 'vuex';

/**
 * Single place for shared app state and routing in Composition API.
 * - permissions: from Vuex (use store.dispatch('fetchPermissions') when needed).
 * - route(): use the global route() from Ziggy (injected via ZiggyVue in app.js).
 * Use this composable so permissions and app globals stay consistent; keep using
 * Ziggy's route() for all URL generation.
 */
export function useAppGlobals() {
  const store = useStore();
  const permissions = computed(() => store.getters.permissions);
  return {
    permissions,
    /** For route names/URLs use the global route() from Ziggy (e.g. route('dashboard')). */
  };
}
