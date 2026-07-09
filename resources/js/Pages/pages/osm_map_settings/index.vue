<script>
import { Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import Pagination from "@/Components/Pagination.vue";
import Swal from "sweetalert2";
import { ref, watch } from "vue";
import axios from "axios";
import { useI18n } from 'vue-i18n';

export default {
    components: {
        Layout,
        PageHeader,
        Head,
        Pagination,
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
            enable_mapbox: props.settings?.enable_mapbox ?? false,
            mapbox_public_key: props.settings?.mapbox_public_key ?? '',
            enable_thunderforest: props.settings?.enable_thunderforest ?? false,
            thunderforest_api_key: props.settings?.thunderforest_api_key ?? '',
            enable_stadia: props.settings?.enable_stadia ?? false,
            stadia_api_key: props.settings?.stadia_api_key ?? '',
        });

        const successMessage = ref(props.successMessage || '');
        const alertMessage = ref(props.alertMessage || '');
        const showHintModal = ref(false);

        const dismissMessage = () => {
            successMessage.value = "";
            alertMessage.value = "";
        };

        const providerKeys = ['enable_mapbox', 'enable_thunderforest', 'enable_stadia'];

        const handleProviderToggle = (key) => {
            if (props.app_for == "demo") {
                form[key] = !form[key];
                Swal.fire(t('error'), t('you_are_not_authorised'), 'error');
                return;
            }

            if (form[key]) {
                providerKeys.forEach((providerKey) => {
                    if (providerKey !== key) {
                        form[providerKey] = false;
                    }
                });
            }

            handleSubmit();
        };

        const handleSubmit = async () => {
            if (props.app_for == "demo") {
                Swal.fire(t('error'), t('you_are_not_authorised'), 'error');
                return;
            }
            try {
                let formData = new FormData();
                providerKeys.forEach((providerKey) => {
                    formData.append(providerKey, form[providerKey] ? 1 : 0);
                });
                formData.append('mapbox_public_key', form.mapbox_public_key ?? '');
                formData.append('thunderforest_api_key', form.thunderforest_api_key ?? '');
                formData.append('stadia_api_key', form.stadia_api_key ?? '');

                let response = await axios.post('/osm-map-setting/update', formData);

                if (response.status === 201) {
                    successMessage.value = t('osm_map_settings_updated_successfully') || 'OSM map settings updated successfully';
                    setTimeout(() => {
                        router.get('/osm-map-setting');
                    }, 1500);
                } else {
                    alertMessage.value = t('failed_to_update_osm_map_settings') || 'Failed to update OSM map settings';
                }
            } catch (error) {
                console.error(t('error_updating_osm_map_settings') || 'Error updating OSM map settings', error);
                alertMessage.value = t('failed_to_update_osm_map_settings_catch') || 'Failed to update OSM map settings';
            }
        };

        watch(() => props.settings, (newSettings) => {
            Object.assign(form, newSettings);
        }, { immediate: true });

        return {
            form,
            successMessage,
            alertMessage,
            dismissMessage,
            handleProviderToggle,
            handleSubmit,
             showHintModal,
        };
    },
};
</script>

<template>
    <Layout>
        <Head title="OSM Map Settings" />
        <PageHeader :title="$t('osm-map-settings') || 'OSM Map Settings'" :pageTitle="$t('third-party-settings') || 'Third-party Settings'" />
        <form @submit.prevent="handleSubmit">
            <BRow>
                <BCard v-if="app_for === 'demo'" no-body id="tasksList">
                    <BCardHeader class="border-0">
                        <div class="alert bg-warning border-warning fs-18" role="alert">
                            <strong> {{$t('note')}} : <em> {{$t('actions_restricted_due_to_demo_mode')}}</em> </strong>
                        </div>
                    </BCardHeader>
                </BCard>
                <BCol lg="12">
                    <BCard no-body id="tasksList">
                        <BCardHeader class="border-0">
                            <h5 class="mb-0">{{ $t('osm_provider_settings') || 'OSM Provider Settings' }}</h5>
                            <p class="text-muted mb-0">{{ $t('only_one_provider_can_be_enabled') || 'Only one provider can be enabled at a time. You can also turn all providers off.' }}</p>
                            <BLink @click="showHintModal = true">
                            <h6 class="text-success float-end d-flex align-items-center me-3 text-decoration-underline text-decoration-underline-success">
                                <!-- <i class="bx bx-info-circle fs-20 me-1"></i> -->
                                {{$t('hint')}}
                            </h6>
                        </BLink> 
                        </BCardHeader>
                        <BCardBody class="border border-dashed border-end-0 border-start-0">
                            <BRow class="mt-3">
                                <BCol lg="12">
                                    <BCard no-body id="tasksList" class="border">
                                        <BCardHeader class="border-0 mt-2 p-4 border-bottom">
                                            <div class="row">
                                                <div class="col-6">
                                                    <h5>Mapbox</h5>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch form-switch-lg float-end me-3">
                                                        <input v-model="form.enable_mapbox" class="form-check-input" type="checkbox" role="switch" id="enable_mapbox" @change="handleProviderToggle('enable_mapbox')" />
                                                    </div>
                                                </div>
                                            </div>
                                        </BCardHeader>
                                        <BCardBody>
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <label for="mapbox_public_key" class="form-label">{{ $t('mapbox_public_key') || 'Mapbox Public Key (pk)' }}</label>
                                                    <input type="password" class="form-control" id="mapbox_public_key" v-model="form.mapbox_public_key" :placeholder="$t('enter_mapbox_public_key') || 'Enter Mapbox public key (pk)'" />
                                                </div>
                                            </div>
                                        </BCardBody>
                                    </BCard>
                                </BCol>
                            </BRow>

                            <BRow class="mt-4">
                                <BCol lg="12">
                                    <BCard no-body id="tasksList" class="border">
                                        <BCardHeader class="border-0 mt-2 p-4 border-bottom">
                                            <div class="row">
                                                <div class="col-6">
                                                    <h5>Thunderforest</h5>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch form-switch-lg float-end me-3">
                                                        <input v-model="form.enable_thunderforest" class="form-check-input" type="checkbox" role="switch" id="enable_thunderforest" @change="handleProviderToggle('enable_thunderforest')" />
                                                    </div>
                                                </div>
                                            </div>
                                        </BCardHeader>
                                        <BCardBody>
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <label for="thunderforest_api_key" class="form-label">{{ $t('thunderforest_api_key') || 'Thunderforest API Key' }}</label>
                                                    <input type="password" class="form-control" id="thunderforest_api_key" v-model="form.thunderforest_api_key" :placeholder="$t('enter_thunderforest_api_key') || 'Enter Thunderforest API key'" />
                                                </div>
                                            </div>
                                        </BCardBody>
                                    </BCard>
                                </BCol>
                            </BRow>

                            <BRow class="mt-4">
                                <BCol lg="12">
                                    <BCard no-body id="tasksList" class="border">
                                        <BCardHeader class="border-0 mt-2 p-4 border-bottom">
                                            <div class="row">
                                                <div class="col-6">
                                                    <h5>Stadia</h5>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch form-switch-lg float-end me-3">
                                                        <input v-model="form.enable_stadia" class="form-check-input" type="checkbox" role="switch" id="enable_stadia" @change="handleProviderToggle('enable_stadia')" />
                                                    </div>
                                                </div>
                                            </div>
                                        </BCardHeader>
                                        <BCardBody>
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <label for="stadia_api_key" class="form-label">{{ $t('stadia_api_key') || 'Stadia API Key' }}</label>
                                                    <input type="password" class="form-control" id="stadia_api_key" v-model="form.stadia_api_key" :placeholder="$t('enter_stadia_api_key') || 'Enter Stadia API key'" />
                                                </div>
                                            </div>
                                        </BCardBody>
                                    </BCard>
                                </BCol>
                            </BRow>

                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary">{{ $t('update') || 'Update' }}</button>
                            </div>
                        </BCardBody>
                    </BCard>
                </BCol>
            </BRow>
        </form>

        <div>
            <div v-if="successMessage" class="custom-alert alert alert-success alert-border-left fade show" role="alert" id="alertMsg">
                <div class="alert-content">
                    <i class="ri-notification-off-line me-3 align-middle"></i>
                    <strong>Success</strong> - {{ successMessage }}
                    <button type="button" class="btn-close btn-close-success" @click="dismissMessage" aria-label="Close Success Message"></button>
                </div>
            </div>

            <div v-if="alertMessage" class="custom-alert alert alert-danger alert-border-left fade show" role="alert" id="alertMsg">
                <div class="alert-content">
                    <i class="ri-notification-off-line me-3 align-middle"></i>
                    <strong>Alert</strong> - {{ alertMessage }}
                    <button type="button" class="btn-close btn-close-danger" @click="dismissMessage" aria-label="Close Alert Message"></button>
                </div>
            </div>
        </div>
         <BModal v-model="showHintModal" hide-footer :title="$t('osm')" class="v-modal-custom" size="md">
          <div class="container"> 
            <ul class="list-unstyled vstack gap-3">
              <li>
                <div class="d-flex">
                  <div class="flex-shrink-0 text-success me-1">
                    <i class="ri-checkbox-circle-fill fs-15 align-middle"></i>
                  </div>
                  <div class="flex-grow-1">
                    <p> OSM provides free public map tiles, these are intended only for:</p>
                        <ul>
                            <li> Light usage</li>
                            <li> Testing and development purposes</li>
                        </ul>
                  </div>
                </div>
              </li>
               <li>
                <div class="d-flex">
                  <div class="flex-shrink-0 text-success me-1">
                    <i class="ri-checkbox-circle-fill fs-15 align-middle"></i>
                  </div>
                  <div class="flex-grow-1">
                     <h5>Limitation of Free OSM Tiles</h5>

                    <p>Using free OSM tile servers in a production application is not recommended because:</p>

                    <ul>
                        <li> Strict usage limits (high traffic may get blocked)</li>
                        <li> No guaranteed performance or uptime</li>
                        <li> No support for scaling applications</li>
                        <li> Bulk usage and caching are restricted</li>
                    </ul>

                    👉 If the application exceeds the allowed limits, map loading may slow down or stop working entirely.
                  </div>
                </div>
              </li>
              <li>
                <div class="d-flex">
                  <div class="flex-shrink-0 text-success me-1">
                    <i class="ri-checkbox-circle-fill fs-15 align-middle"></i>
                  </div>
                  <div class="flex-grow-1">
                     <h5>Recommended Approach for Production</h5>

                    <p> For production applications, it is recommended to use a commercial map tile provider (like Mapbox, Thunderforest, Stadia)</p>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </BModal>
    </Layout>
</template>

<style>
.custom-alert {
    max-width: 600px;
    float: right;
    position: fixed;
    top: 90px;
    right: 20px;
}
.rtl .custom-alert {
    max-width: 600px;
    float: left;
    top: -300px;
    right: 10px;
}
@media only screen and (max-width: 1024px) {
    .custom-alert {
        max-width: 600px;
        float: right;
        position: fixed;
        top: 90px;
        right: 20px;
    }
    .rtl .custom-alert {
        max-width: 600px;
        float: left;
        top: -230px;
        right: 10px;
    }
}
</style>
