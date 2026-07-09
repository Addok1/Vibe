<script>
import { Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import { ref } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import { useI18n } from 'vue-i18n';

export default {
  components: {
    Layout,
    PageHeader,
    Head,
  },
  props: {
    successMessage: String,
    alertMessage: String,
    app_for: String,
    settings: Object,
  },
  setup(props) {
    const { t } = useI18n();
    const form = useForm({
      map_show: props.settings ? props.settings.map_show || 'classic_layout' : 'classic_layout',
    });

    const errors = ref({});
    const successMessage = ref(props.successMessage || '');
    const alertMessage = ref(props.alertMessage || '');

    const dismissMessage = () => {
      successMessage.value = '';
      alertMessage.value = '';
    };

    const handleSubmit = async () => {
      if (props.app_for === 'demo') {
        Swal.fire(t('error'), t('you_are_not_authorised'), 'error');
        return;
      }

      try {
        const formData = new FormData();
        formData.append('map_show', form.map_show);

        const response = await axios.post('/map-show/update', formData);

        if (response.status === 201) {
          successMessage.value = t('map_settings_updated_successfully') || 'Map show setting updated successfully';
          router.get('/map-show');
        } else {
          alertMessage.value = t('failed_to_update_map_show') || 'Failed to update map show setting';
        }
      } catch (error) {
        if (error.response && error.response.status === 422) {
          errors.value = error.response.data.errors;
        } else {
          console.error(t('error_updating_map_show'), error);
          alertMessage.value = t('failed_to_update_map_show') || 'Failed to update map show setting';
        }
      }
    };

    return {
      form,
      errors,
      successMessage,
      alertMessage,
      dismissMessage,
      handleSubmit,
      t,
    };
  },
};
</script>

<template>
  <Layout>
    <Head title="Map Show" />
    <PageHeader :title="t('map_show') || 'Map Show'" :pageTitle="t('third-party-settings') || 'Third-party Settings'" />

    <BRow>
      <BCard v-if="app_for === 'demo'" no-body id="tasksList">
        <BCardHeader class="border-0">
          <div class="alert bg-warning border-warning fs-18" role="alert">
            <strong> {{t('note')}} : <em> {{t('actions_restricted_due_to_demo_mode')}}</em> </strong>
          </div>
        </BCardHeader>
      </BCard>

      <BCol lg="12">
        <form @submit.prevent="handleSubmit">
          <BCard no-body id="mapShow">
            <BCardHeader>
              <h4 class="border-0">{{ t('choose_map_show') || 'Choose Map Show' }}</h4>
            </BCardHeader>
            <BCardBody>
              <div class="row">
                <div class="col-lg-6">
                  <div class="mb-5">
                    <div class="card rounded border border-2 shadow-lg" style="width: 19rem;">
                      <label class="map_show_true">
                        <img class="rounded p-2 map-img" alt="show_map" src="@assets/images/show-map.jpeg" width="100%" height="250px" />
                        <h5 class="text-center mt-3">{{ t('with_map') || 'With Map' }}</h5>
                        <div class="form-check form-check-success" style="margin-left: 140px;">
                          <input type="radio" :disabled="app_for === 'demo'" name="map_show" value="classic_layout" class="form-check-input" v-model="form.map_show" />
                        </div>
                      </label>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="mb-5">
                    <div class="card rounded border border-2 shadow-lg" style="width: 19rem;">
                      <label class="map_show_false">
                        <img class="rounded p-2 map-img" alt="hide_map" src="@assets/images/hide-map.jpeg" width="100%" height="250px" />
                        <h5 class="text-center mt-3">{{ t('without_map') || 'Without Map' }}</h5>
                        <div class="form-check form-check-success" style="margin-left: 140px;">
                          <input type="radio" :disabled="app_for === 'demo'" name="map_show" value="morden_layout" class="form-check-input" v-model="form.map_show" />
                        </div>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">{{ t('update') || 'Update' }}</button>
              </div>
            </BCardBody>
          </BCard>
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
<style>
.card{
  margin-left: auto;
  margin-right: auto;
}
.map-img{
  height: auto;
}
</style>