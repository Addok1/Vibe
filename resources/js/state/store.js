import Vuex from 'vuex';

import layout from './modules/layout';
import notification from './modules/notification';
import todo from './modules/todo';

/**
 * Central store for permissions and shared app state.
 * Use Ziggy's route() for URLs (injected globally via ZiggyVue).
 * For Composition API, use useAppGlobals() composable to access permissions and route in one place.
 */
const store = new Vuex.Store({
  state: {
    permissions: []
  },
  modules: {
    layout,
    notification,
    todo
  },
  mutations: {
    setPermissions(state, permissions) {
      state.permissions = permissions;
    }
  },
  actions: {
    fetchPermissions({ commit }) {
      return axios.get('/user/permissions')
        .then(response => {
          commit('setPermissions', response.data.data);
        })
        .catch(error => {
          console.error(error);
        });
    }
  },
  getters: {
    permissions: state => state.permissions
  }
});

export default store;

