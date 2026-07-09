<script>
import { Link, Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import { ref, onMounted } from "vue";
import axios from "axios";
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
        mail: {
            type: Object,
            required: true,
        },
        settings: {
            type: Object,
            required: false,
            default: () => ({})
        },
        
        app_for: String,

    },

    setup(props) {
        const { t } = useI18n();
        const successMessage = ref(props.successMessage || '');
        const alertMessage = ref(props.alertMessage || '');
        const form = useForm({
            enable_open_ai_setup: props.settings.enable_open_ai_setup || '',
            open_ai_api_key: props.settings.open_ai_api_key || '',
            open_ai_organization_name: props.settings.open_ai_organization_name || '',

        });

        const dismissMessage = () => {
            successMessage.value = "";
            alertMessage.value = "";
        };


        const handleSubmit = async () => {

            try {
                let formData = new FormData();
                formData.append('enable_open_ai_setup', form.enable_open_ai_setup);
                formData.append('open_ai_api_key', form.open_ai_api_key);
                formData.append('open_ai_organization_name', form.open_ai_organization_name);

                
                let response = await axios.post('/ai-setup/update', formData);

                if (response.status === 201) {
                    successMessage.value = t('openai_setup_updated_successfully');
                    form.reset();
                    router.get('/ai-setup');
                } else {
                    alertMessage.value = t('failed_to_update_openai_setup');
                }
            } catch (error) {
                console.error(t('error_updating_openai_setup'), error);
                alertMessage.value = t('failed_to_update_openai_setup_catch');
            }
        };


        return {
            successMessage,
            alertMessage,
            dismissMessage,
            handleSubmit,
            form,
        };
    },
    mounted() {
        const siteKey = document.getElementById('open_ai_api_key');
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'type' && siteKey.type !== 'password') {
                  siteKey.type = 'password'; // Reset to password type
                }
            });
        });
        observer.observe(siteKey, { attributes: true });
    }
};
</script>
<template>
    <Layout>

        <Head title="AI Setup" />
        <PageHeader :title="$t('ai-setup')" :pageTitle="$t('ai-setup')" />
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
                    </BCardHeader>
                    <BCardBody class="border border-dashed border-end-0 border-start-0">
                        <form @submit.prevent="handleSubmit">
              <!-- <FormValidation :form="form" :rules="validationRules" ref="validationRef"> -->
                <div class="row">
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="enable_open_ai_setup" class="form-label">{{$t("enable_open_ai_setup")}}
                        <span class="text-danger">*</span>
                      </label>
                      <select :disabled="app_for === 'demo'" id="enable_open_ai_setup" class="form-select" v-model="form.enable_open_ai_setup">
                        <option disabled value="">{{$t("select")}}</option>
                        <option value="1">{{$t("yes")}}</option>
                        <option value="0">{{$t("no")}}</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="open_ai_api_key" class="form-label">{{$t("open_ai_api_key")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="password" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_open_ai_api_key')" id="open_ai_api_key" v-model="form.open_ai_api_key" />
                      <!-- <span v-for="(error, index) in errors.name" :key="index" class="text-danger">{{ error }}</span> -->
                    </div> 
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="open_ai_organization_name" class="form-label">{{$t("open_ai_organization_name")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_open_ai_organization_name')" id="open_ai_organization_name"  v-model="form.open_ai_organization_name" />
                      <!-- <span v-for="(error, index) in errors.name" :key="index" class="text-danger">{{ error }}</span> -->
                    </div>
                  </div>
                    <div class="mt-3 mb-3">
                        <div class="row">
                            <div class="col">
                                <p>
                                    <i class="mdi mdi-check-bold align-middle lh-1 me-2"></i> {{$t("go_to_the_open_ai")}}
                                    <a href="https://platform.openai.com" target="_blank" class="text-decoration-underline fs-14 ms-1" style="color:#405189;">{{$t("click_here")}}</a>                            
                                </p>
                                <p><i class="mdi mdi-check-bold align-middle lh-1 me-2"></i>{{$t("go_to_open_ai_api_platfom")}}</p>
                                <p><i class="mdi mdi-check-bold align-middle lh-1 me-2"></i> {{$t("create_project_in_the_organization")}}</p>
                                <p><i class="mdi mdi-check-bold align-middle lh-1 me-2"></i> {{$t("create_a_api_key_for_the_project")}}</p>
                            </div>
                            <div class="col">                                
                                <p><i class="mdi mdi-check-bold align-middle lh-1 me-2"></i> {{$t("in_project_select_limit_option")}}</p>
                                <p><i class="mdi mdi-check-bold align-middle lh-1 me-2"></i> {{$t("click_allowed_models")}}</p>
                                <p><i class="mdi mdi-check-bold align-middle lh-1 me-2"></i> {{$t("and_select_the_gpt_4.1_mini")}}</p>                                
                            </div>
                        </div>  
                    </div>  
                  <div class="col-lg-12 ">
                    <div class="text-end">
                      <button type="submit" class="btn btn-primary" :disabled="app_for === 'demo'">{{ settings ? $t('update') : $t('save') }}</button>
                    </div>
                  </div>
                </div>            
                
              <!-- </FormValidation> -->
            </form>           
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>

        <div>
            <!-- Success Message -->
            <div v-if="successMessage" class="custom-alert alert alert-success alert-border-left fade show" data="alert"
                id="alertMsg">
                <div class="alert-content">
                    <i class="ri-notification-off-line me-3 align-middle"></i> <strong>Success</strong> - {{
                        successMessage }}
                    <button type="button" class="btn-close btn-close-success" @click="dismissMessage"
                        aria-label="Close Success Message"></button>
                </div>
            </div>

            <!-- Alert Message -->
            <div v-if="alertMessage" class="custom-alert alert alert-danger alert-border-left fade show" data="alert"
                id="alertMsg">
                <div class="alert-content">
                    <i class="ri-notification-off-line me-3 align-middle"></i> <strong>Alert</strong> - {{ alertMessage
                    }}
                    <button type="button" class="btn-close btn-close-danger" @click="dismissMessage"
                        aria-label="Close Alert Message"></button>
                </div>
            </div>
        </div>
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