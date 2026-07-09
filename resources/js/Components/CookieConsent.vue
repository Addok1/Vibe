<template>
    <BModal
      v-model="modalShow25"
      hide-footer
      dialog-class="modal-dialog-bottom-right"
      class="v-modal-custom"
      hide-header-close
      :no-close-on-backdrop="true"
      :no-close-on-esc="true"
    >
      <div class="modal-body text-center">
        <h4 class="mb-3">We use cookies</h4>
        <p class="text-muted mb-4">
          We use cookies to improve your experience. By continuing, you agree to our cookie policy.
        </p>
  
        <div class="hstack gap-2 justify-content-center">
          <BButton variant="secondary" @click="rejectCookies">
            Reject
          </BButton>
  
          <BButton variant="success" @click="acceptCookies">
            Accept
          </BButton>
        </div>
      </div>
    </BModal>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue'
  
  const modalShow25 = ref(false)
  
  onMounted(() => {
    // auto-open ONLY on landing page
    if (window.location.pathname !== '/') return
  
    if (!getCookie('cookie_consent')) {
      modalShow25.value = true
    }
  })
  
  const acceptCookies = () => {
    setCookie('cookie_consent', 'accepted', 365)
    modalShow25.value = false
    loadAnalytics()
  }
  
  const rejectCookies = () => {
    setCookie('cookie_consent', 'rejected', 365)
    modalShow25.value = false
  }
  
  function setCookie(name, value, days) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString()
    document.cookie =
      `${name}=${value}; expires=${expires}; path=/; SameSite=Lax; Secure`
  }
  
  function getCookie(name) {
    return document.cookie
      .split('; ')
      .find(row => row.startsWith(name + '='))
  }
  
  function loadAnalytics() {
    console.log('Analytics loaded')
  }
  </script>
  