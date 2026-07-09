<script>
import { Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import { ref } from "vue";
import axios from "axios";
import { useI18n } from 'vue-i18n';

export default {
  components: { Layout, PageHeader, Head },
  props: {
    successMessage: String,
    alertMessage: String,
    app_for: String,
    settings: {
      type: Object,
      required: false,
      default: () => ({}),
    },
  },
  setup(props) {
    const { t } = useI18n();
    const successMessage = ref(props.successMessage || '');
    const alertMessage = ref(props.alertMessage || '');

    const form = useForm({
      enable_google_social_login: props.settings.enable_google_social_login ?? '0',
      google_client_id: props.settings.google_client_id ?? '',
      google_client_secret: props.settings.google_client_secret ?? '',
      enable_facebook_social_login: props.settings.enable_facebook_social_login ?? '0',
      facebook_client_id: props.settings.facebook_client_id ?? '',
      facebook_client_secret: props.settings.facebook_client_secret ?? '',
      enable_apple_social_login: props.settings.enable_apple_social_login ?? '0',
      apple_client_id: props.settings.apple_client_id ?? '',
      apple_team_id: props.settings.apple_team_id ?? '',
      apple_key_id: props.settings.apple_key_id ?? '',
      apple_private_key: props.settings.apple_private_key ?? '',
    });

    const dismissMessage = () => {
      successMessage.value = '';
      alertMessage.value = '';
    };

    const handleSubmit = async () => {
      try {
        const formData = new FormData();
        Object.keys(form.data()).forEach((k) => formData.append(k, form[k]));

        const response = await axios.post('/social-auth-settings/update', formData);
        if (response.status === 201) {
          successMessage.value = t('updated_successfully') || 'Updated successfully';
          router.get('/social-auth-settings');
        } else {
          alertMessage.value = t('failed_to_update') || 'Failed to update';
        }
      } catch (e) {
        alertMessage.value = t('failed_to_update') || 'Failed to update';
      }
    };

    return { t, form, successMessage, alertMessage, dismissMessage, handleSubmit };
  },
  mounted() {
    if (this.app_for !== 'demo') {
      return;
    }

    const secretFieldIds = [
      'google_client_id',
      'google_client_secret',
      'facebook_client_id',
      'facebook_client_secret',
      'apple_client_id',
      'apple_team_id',
      'apple_key_id',
      'apple_private_key',
    ];

    secretFieldIds.forEach((fieldId) => {
      const field = document.getElementById(fieldId);

      if (!field) {
        return;
      }

      if (field.tagName === 'INPUT') {
        field.type = 'password';
      }

      const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          if (mutation.attributeName === 'type' && field.type !== 'password') {
            field.type = 'password';
          }
        });
      });

      observer.observe(field, { attributes: true });
    });
  }
};
</script>

<template>
  <Layout>
    <Head title="Social Auth Settings" />
    <PageHeader :title="$t('social-auth-settings') || 'Social Auth Settings'" :pageTitle="$t('social-auth-settings') || 'Social Auth Settings'" />

    <BRow>
      <BCard v-if="app_for === 'demo'" no-body>
        <BCardHeader class="border-0">
          <div class="alert bg-warning border-warning fs-18" role="alert">
            <strong>{{ $t('note') }} : <em>{{ $t('actions_restricted_due_to_demo_mode') }}</em></strong>
          </div>
        </BCardHeader>
      </BCard>

      <BCol lg="12">
        <BCard no-body>
          <BCardBody class="border border-dashed border-end-0 border-start-0">
            <form @submit.prevent="handleSubmit">
              <div class="row">
                <div class="col-12"><h5 class="mb-3">{{ $t('google') }}</h5></div>
                <div class="col-sm-4">
                  <div class="mb-3">
                    <label class="form-label">{{ $t('enable_google_login') }}</label>
                    <select :disabled="app_for === 'demo'" class="form-select" v-model="form.enable_google_social_login">
                      <option value="1">{{ $t('yes') }}</option>
                      <option value="0">{{ $t('no') }}</option>
                    </select>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="mb-3">
                    <label class="form-label">{{ $t('google_client_id') }}</label>
                    <input id="google_client_id" :readonly="app_for === 'demo'" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" v-model="form.google_client_id" :placeholder="$t('enter_google_client_id')" />
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="mb-3">
                    <label class="form-label">{{ $t('google_client_secret') }}</label>
                    <input id="google_client_secret" :readonly="app_for === 'demo'" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" v-model="form.google_client_secret" :placeholder="$t('enter_google_client_secret')" />
                  </div>
                </div>

                <div class="col-12 mt-2"><h5 class="mb-3">{{ $t('facebook') }}</h5></div>
                <div class="col-sm-4">
                  <div class="mb-3">
                    <label class="form-label">{{ $t('enable_facebook_login') }}</label>
                    <select :disabled="app_for === 'demo'" class="form-select" v-model="form.enable_facebook_social_login">
                      <option value="1">{{ $t('yes') }}</option>
                      <option value="0">{{ $t('no') }}</option>
                    </select>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="mb-3">
                    <label class="form-label">{{ $t('facebook_client_id') }}</label>
                    <input id="facebook_client_id" :readonly="app_for === 'demo'" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" v-model="form.facebook_client_id" :placeholder="$t('enter_facebook_client_id')" />
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="mb-3">
                    <label class="form-label">{{ $t('facebook_client_secret') }}</label>
                    <input id="facebook_client_secret" :readonly="app_for === 'demo'" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" v-model="form.facebook_client_secret" :placeholder="$t('enter_facebook_client_secret')" />
                  </div>
                </div>

                <div class="col-12 mt-2"><h5 class="mb-3">{{ $t('apple') || 'Apple' }}</h5></div>
                <div class="col-sm-4">
                  <div class="mb-3">
                    <label class="form-label">{{ $t('enable_apple_login') || 'Enable Apple Login' }}</label>
                    <select :disabled="app_for === 'demo'" class="form-select" v-model="form.enable_apple_social_login">
                      <option value="1">{{ $t('yes') }}</option>
                      <option value="0">{{ $t('no') }}</option>
                    </select>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="mb-3">
                    <label class="form-label">{{ $t('apple_client_id') || 'Apple Client ID' }}</label>
                    <input id="apple_client_id" :readonly="app_for === 'demo'" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" v-model="form.apple_client_id" placeholder="Services ID" />
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="mb-3">
                    <label class="form-label">{{ $t('apple_team_id') || 'Apple Team ID' }}</label>
                    <input id="apple_team_id" :readonly="app_for === 'demo'" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" v-model="form.apple_team_id" placeholder="Apple Developer Team ID" />
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="mb-3">
                    <label class="form-label">{{ $t('apple_key_id') || 'Apple Key ID' }}</label>
                    <input id="apple_key_id" :readonly="app_for === 'demo'" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" v-model="form.apple_key_id" placeholder="Apple Sign in Key ID" />
                  </div>
                </div>
                <div class="col-sm-8">
                  <div class="mb-3">
                    <label class="form-label">{{ $t('apple_private_key') || 'Apple Private Key (.p8)' }}</label>
                    <textarea
                      id="apple_private_key"
                      :readonly="app_for === 'demo'"
                      :style="app_for === 'demo' ? { '-webkit-text-security': 'disc' } : {}"
                      class="form-control font-monospace"
                      rows="6"
                      v-model="form.apple_private_key"
                      placeholder="-----BEGIN PRIVATE KEY-----"
                    ></textarea>
                    <small class="text-muted d-block mt-1">
                      {{ $t('paste_the_apple_private_key_content_from_your_p8_file') || 'Paste the private key content from your .p8 file.' }}
                    </small>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-end gap-2">
                <!-- <BButton type="button" variant="light" @click="dismissMessage">{{ $t('clear') || 'Clear' }}</BButton> -->
                <BButton type="submit" variant="primary" :disabled="app_for === 'demo'">{{ $t('save') || 'Save' }}</BButton>
              </div>
            </form>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </Layout>
</template>
