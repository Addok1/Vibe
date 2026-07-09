<script>
import { Link, Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import { ref, onMounted } from "vue";
import axios from "axios";
import { useI18n } from 'vue-i18n';
import Swal from "sweetalert2";
import CKEditor from "@ckeditor/ckeditor5-vue";
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";

export default {
    data() {
      return {
        priceperdistance: false,
        editor: ClassicEditor,
        copiedIndex: null,
      };
    },
    methods: {
      copyText(text, index) {
        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(text).then(() => {
            this.copiedIndex = index;
            setTimeout(() => (this.copiedIndex = null), 2000);
          }).catch(() => {
            this.fallbackCopy(text, index);
          });
        } else {
          this.fallbackCopy(text, index);
        }
      },

      fallbackCopy(text, index) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        try {
          document.execCommand('copy');
          this.copiedIndex = index;
          setTimeout(() => (this.copiedIndex = null), 2000);
        } catch (err) {
          console.error('Copy failed', err);
        }

        document.body.removeChild(textarea);
      },

    },
    components: {
        Layout,
        PageHeader,
        Head,
        ckeditor: CKEditor.component,
    },
    props: {
        successMessage: String,
        alertMessage: String,
        app_for: String,

    },

    setup(props) {
      const { t } = useI18n();
        const successMessage = ref(props.successMessage || '');
        const alertMessage = ref(props.alertMessage || '');
        const form = useForm({
            mail_mailer: '',
            mail_host: '',
            mail_port: '',
            mail_username: '',
            mail_password: '',
            mail_encryption: '',
            mail_from_address: '',
            mail_from_name: '',

            to_email:'',
            mail_subject: '',
            mail_body: '',

        });
        const errors = ref({});

        const prevConfig = ref({});
        const dismissMessage = () => {
            successMessage.value = "";
            alertMessage.value = "";
        };


        const handleSubmit = async () => {
            if(props.app_for == "demo"){
                Swal.fire(t('error'), t('you_are_not_authorised'), 'error');
                return;
            }

            try {
                let formData = new FormData();
                formData.append('mail_mailer', form.mail_mailer);
                formData.append('mail_host', form.mail_host);
                formData.append('mail_port', form.mail_port);
                formData.append('mail_username', form.mail_username);
                formData.append('mail_password', form.mail_password);
                formData.append('mail_encryption', form.mail_encryption);
                formData.append('mail_from_address', form.mail_from_address);
                formData.append('mail_from_name', form.mail_from_name);
                formData.append('to_email', form.to_email);
                formData.append('mail_subject', form.mail_subject);
                formData.append('mail_body', form.mail_body);

                
                let response = await axios.post('/mail-configuration/test/send', formData);

                if (response.status === 201) {
                    successMessage.value = t('mail_configuration_updated_successfully');
                    prevConfig.value = {
                        mail_mailer: form.mail_mailer,
                        mail_host: form.mail_host,
                        mail_port: form.mail_port,
                        mail_username: form.mail_username,
                        mail_password: form.mail_password,
                        mail_encryption: form.mail_encryption,
                        mail_from_address: form.mail_from_address,
                        mail_from_name: form.mail_from_name,
                    }
                    form.reset();
                } else {
                    alertMessage.value = t('failed_to_update_mail_configuration');
                }
            } catch (error) {
                console.error(t('error_updating_mail_configuration'), error);
                errors.value = error?.response?.data?.errors || {};
                alertMessage.value = error.response.data.message || t('failed_to_update_mail_configuration');
            }
        };
        
        const setPrevConfig = () => {
          form.mail_mailer = prevConfig.value.mail_mailer || '';
          form.mail_host = prevConfig.value.mail_host || '';
          form.mail_port = prevConfig.value.mail_port || '';
          form.mail_username = prevConfig.value.mail_username || '';
          form.mail_password = prevConfig.value.mail_password || '';
          form.mail_encryption = prevConfig.value.mail_encryption || '';
          form.mail_from_address = prevConfig.value.mail_from_address || '';
          form.mail_from_name = prevConfig.value.mail_from_name || '';
        };

        return {
            successMessage,
            alertMessage,
            dismissMessage,
            handleSubmit,
            setPrevConfig,
            prevConfig,
            form,
            errors,
        };
    },
};
</script>
<template>
    <Layout>

        <Head title="Mail Configuration" />
        <PageHeader :title="$t('mail-configuration')" :pageTitle="$t('mail-configuration')" />
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
                      <label for="mail_mailer" class="form-label">{{$t("mailer_name")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_mailer_name')" id="mail_mailer" v-model="form.mail_mailer" />
                      <span v-for="(error, index) in errors.mail_mailer" :key="index" class="text-danger">{{ error }}</span>
                    </div> 
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="mail_host" class="form-label">{{$t("mail_host")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_mail_host')" id="mail_host"  v-model="form.mail_host" />
                      <span v-for="(error, index) in errors.mail_host" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="mail_port" class="form-label">{{$t("mail_port")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input :type="app_for === 'demo' ? 'password' : 'text'" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_mail_port')" id="mail_port" v-model="form.mail_port" 
                      />
                      <span v-for="(error, index) in errors.mail_port" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="mail_username" class="form-label">{{$t("mail_username")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input :type="app_for === 'demo' ? 'password' : 'text'" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_mail_username')" id="mail_username" v-model="form.mail_username"/>
                      <span v-for="(error, index) in errors.mail_username" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="mail_password" class="form-label">{{$t("mail_password")}}
                        <span class="text-danger">*</span>
                      </label>
                      <div class="input-group">
                        <input type="password" :readonly="app_for === 'demo'" autocomplete="off" class="form-control" :placeholder="$t('enter_mail_password')" id="mail_password"  v-model="form.mail_password"/>
                      </div>                      
                      <span v-for="(error, index) in errors.mail_password" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="mail_encryption" class="form-label">{{$t("mail_encryption")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input :type="app_for === 'demo' ? 'password' : 'text'" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_mail_encryption')" id="mail_encryption" v-model="form.mail_encryption"/>
                      <span v-for="(error, index) in errors.mail_encryption" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="mail_from_address" class="form-label">{{$t("mail_from_address")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_mail_from_address')" id="mail_from_address"  v-model="form.mail_from_address"/>
                      <span v-for="(error, index) in errors.mail_from_address" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="mail_from_name" class="form-label">{{$t("mail_from_name")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_mail_from_name')" id="mail_from_name" v-model="form.mail_from_name"/>
                      <span v-for="(error, index) in errors.mail_from_name" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="col-sm-6" v-if="prevConfig && Object.keys(prevConfig).length">
                    <div class="text-end">
                      <button type="button" @click="setPrevConfig" class="btn btn-primary"> {{ $t('set_prev_config') }}</button>
                    </div>
                  </div>

                  <BRow>
                    <BCol lg="6">
                        <BCard no-body>
                          <BCardBody>
                              <BRow>
                                <BCol lg="12">
                                    <div class="col-sm-6 mt-3">
                                      <div class="mb-3">
                                        <label for="to_email" class="form-label">{{ $t("to_email") }}
                                          <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form-control" :placeholder="$t('enter_to_email')"
                                          :id="`to_email`" v-model="form.to_email"
                                          required>
                                      <span v-for="(error, index) in errors.to_email" :key="index" class="text-danger">{{ error }}</span>
                                      </div>
                                    </div>
                                    <div class="col-sm-6 mt-3">
                                      <div class="mb-3">
                                        <label for="email_subject" class="form-label">{{ $t("email_subject") }}
                                          <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" :placeholder="$t('enter_subject')"
                                          :id="`enter_subject`" v-model="form.mail_subject"
                                          required>
                                      <span v-for="(error, index) in errors.mail_subject" :key="index" class="text-danger">{{ error }}</span>
                                      </div>
                                    </div>
                                    <div>
                                      <label>{{$t("body_of_the_mail")}}</label>
                                      <ckeditor v-model="form.mail_body" id="email_subject"  :editor="editor"></ckeditor>
                                      <span v-for="(error, index) in errors.mail_body" :key="index" class="text-danger">{{ error }}</span>
                                    </div>
                                </BCol>
                              </BRow>
                          </BCardBody>
                        </BCard>
                    </BCol>

                    <BCol lg="6">
                    <BCard no-body >
                      <BCardHeader class="border-0">
                        <h4 class="card-title mb-0">{{$t("email_preview")}}</h4>
                        <BLink @click="priceperdistance = !priceperdistance">
                          <h6 class="text-success text-decoration-underline text-decoration-underline-success float-end heart">{{$t('how_it_works')}}</h6>
                        </BLink>
                      </BCardHeader>
                      <div class="col-12">
                          <table class="body-wrap">
                              <tbody><tr>
                                  <td></td>
                                  <td class="container" width="600">
                                      <div class="content">
                                          <table class="main" width="100%" cellpadding="0" cellspacing="0" itemprop="action" itemscope="" itemtype="http://schema.org/ConfirmAction" style="font-family: 'Roboto', sans-serif; box-sizing: border-box; font-size: 14px; border-radius: 3px; margin: 0; border: none;">
                                              <tbody>
                                                <tr>
                                                  <td class="content-wrap">
                                                      <meta itemprop="name" content="Confirm Email" style="font-family: 'Roboto', sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                                      <table width="100%" cellpadding="0" cellspacing="0" style="font-family: 'Roboto', sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                                          <tbody>
                                                          <tr v-if=" form && form.mail_subject">
                                                              <td  class="content-block" style="font-family: 'Roboto', sans-serif; box-sizing: border-box; font-size: 20px; line-height: 1.5; font-weight: 500; vertical-align: top; margin: 0; padding: 0 0 10px;" valign="top">
                                                                  {{form.mail_subject}}
                                                              </td>
                                                          </tr>
                                                          <tr v-else>   
                                                              <td  class="content-block" style="font-family: 'Roboto', sans-serif; box-sizing: border-box; font-size: 20px; line-height: 1.5; font-weight: 500; vertical-align: top; margin: 0; padding: 0 0 10px;" valign="top" >
                                                                Hello User
                                                              </td>
                                                          </tr> 
                                                          <tr v-if="form && form.mail_body">
                                                            <!-- {{ stripHtmlTags(form.mail_body) }} -->
                                                            <div v-html="form.mail_body"></div>
                                                          </tr>
                                                          <tr v-else>
                                                            <p>This is a Mock Email Body</p>
                                                            <p>Best regards, </p>         
                                                            <p>MI Softwares</p>
                                                          </tr>
                                                      </tbody></table>
                                                  </td>
                                                </tr>
                                            </tbody>
                                          </table>                         
                                      </div>
                                  </td>
                              </tr>
                          </tbody></table>
                          <!-- end table -->
                      </div>
                    </BCard>
                    </BCol>
                  </BRow>
                  <div class="col-lg-12">
                    <div class="text-end">
                      <button type="submit" class="btn btn-primary"> {{ $t('send') }}</button>
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

<!-- modal -->
    <BModal v-model="priceperdistance" hide-footer :title="$t('email_preview')" class="v-modal-custom fadeInUp" size="lg">
      <div class="modal-body">
          <h4>Mail Configuration Prerequisite</h4>
          <p><b>Queue Connection</b> must be set to database in the env file</p>
          <p>QUEUE_CONNECTION=database
            <button @click="copyText(`QUEUE_CONNECTION=database`, 0)" class="btn btn-light btn-sm ms-2">
                <i :class="copiedIndex === 0 ? 'bx bxs-check-circle text-success' : 'bx bx-copy'"></i>
            </button>
          </p>
          <p><b>Laravel supervisor</b> must be installed and set up properly</p>
          <p><em>If there are any changes made with mail configurations, Supervisor needs to be restarted to load the changes</em></p>
          <p>sudo supervisorctl stop laravel-worker:*
            <button @click="copyText(`sudo supervisorctl stop laravel-worker:*`, 1)" class="btn btn-light btn-sm ms-2">
                <i :class="copiedIndex === 1 ? 'bx bxs-check-circle text-success' : 'bx bx-copy'"></i>
            </button>
          </p>
          <p>php artisan optimize:clear
            <button @click="copyText(`php artisan optimize:clear`, 2)" class="btn btn-light btn-sm ms-2">
                <i :class="copiedIndex === 2 ? 'bx bxs-check-circle text-success' : 'bx bx-copy'"></i>
            </button>
          </p>
          <p>sudo supervisorctl start laravel-worker:*
            <button @click="copyText(`sudo supervisorctl start laravel-worker:*`, 3)" class="btn btn-light btn-sm ms-2">
                <i :class="copiedIndex === 3 ? 'bx bxs-check-circle text-success' : 'bx bx-copy'"></i>
            </button>
          </p>
      </div>
    </BModal>
<!-- modal end -->

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
.toggle-password-icon i{
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  color: #6c757d; /* Optional: Change the color of the icon */
}
</style>