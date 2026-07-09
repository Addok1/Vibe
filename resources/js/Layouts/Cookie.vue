<template>
    <div class="layout-wrapper landing">
      <slot />
      <CookieConsent />
    </div>
  </template>
  
  <script setup>
  import CookieConsent from '@/Components/CookieConsent.vue'
  </script>
  