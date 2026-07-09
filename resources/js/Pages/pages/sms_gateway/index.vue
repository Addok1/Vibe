<script>
import { Link, Head, useForm, router, usePage } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import Pagination from "@/Components/Pagination.vue";
import Swal from "sweetalert2";
import { ref, watch } from "vue";
import axios from "axios";
import "@vueform/multiselect/themes/default.css";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import search from "@/Components/widgets/search.vue";
import searchbar from "@/Components/widgets/searchbar.vue";
import { useI18n } from 'vue-i18n';

export default {
    data() {
        return {
            rightOffcanvas: false,
        };
    },
    components: {
        Layout,
        PageHeader,
        Head,
        Pagination,
        flatPickr,
        Link,
        search,
        searchbar,
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
            enable_firebase_otp: props.settings?.enable_firebase_otp ?? false,

            enable_firebase_otp_control: props.settings?.enable_firebase_otp_control ?? false,

            enable_twilio: props.settings?.enable_twilio ?? false,
            twilio_sid: props.settings?.twilio_sid ?? '',
            twilio_token: props.settings?.twilio_token ?? '',
            twilio_mobile_number: props.settings?.twilio_mobile_number ?? '',

            enable_sms_ala: props.settings?.enable_sms_ala ?? false,
            sms_ala_api_key: props.settings?.sms_ala_api_key ?? '',
            sms_ala_api_secret_key: props.settings?.sms_ala_api_secret_key ?? '',
            sms_ala_token: props.settings?.sms_ala_token ?? '',
            sms_ala_mobile_number: props.settings?.sms_ala_mobile_number ?? '',

            enable_msg91: props.settings?.enable_msg91 ?? false,
            msg91_template_id: props.settings?.msg91_template_id ?? '',
            msg91_auth_key: props.settings?.msg91_auth_key ?? '',

            enable_sparrow: props.settings?.enable_sparrow ?? false,
            sparrow_sender_id: props.settings?.sparrow_sender_id ?? '',
            sparrow_token: props.settings?.sparrow_token ?? '',

            enable_sms_india_hub: props.settings?.enable_sms_india_hub ?? false,
            sms_india_hub_api_key: props.settings?.sms_india_hub_api_key ?? '',
            sms_india_hub_sid: props.settings?.sms_india_hub_sid ?? '',


            enable_kudi_sms_api_key: props.settings?.enable_kudi_sms_api_key ?? false,
            kudi_sms_sender_id: props.settings?.kudi_sms_sender_id ?? '',
            kudi_sms_api_key: props.settings?.kudi_sms_api_key ?? '',

            enable_infobip: props.settings?.enable_infobip ?? false,
            infobip_base_url: props.settings?.infobip_base_url ?? '',
            infobip_api_key: props.settings?.infobip_api_key ?? '',
            infobip_sender_id: props.settings?.infobip_sender_id ?? '',

            // enable_termii: props.settings?.enable_termii ?? false,
            // termii_base_url: props.settings?.termii_base_url ?? '',
            // termii_api_key: props.settings?.termii_api_key ?? '',
            // termii_sender_id: props.settings?.termii_sender_id ?? '',
            // termii_channel: props.settings?.termii_channel ?? 'generic',
            // termii_type: props.settings?.termii_type ?? 'plain',
        });

        const successMessage = ref(props.successMessage || '');
        const alertMessage = ref(props.alertMessage || '');

        const showMailPasswordModal = ref(false);

        const enableFirebaseOtp = ref(false);
        const enableFirebaseConsole = ref(false);


        const dismissMessage = () => {
            successMessage.value = "";
            alertMessage.value = "";
        };

        const handleCheckboxChange = (key) => {
            if(props.app_for == "demo"){
                form[key] = !form[key];
                Swal.fire(t('error'), t('you_are_not_authorised'), 'error');
                return;
            }

            if (key === 'enable_firebase_otp_control') {
                handleSubmit();
                return;
            }

            Object.keys(form).forEach((formKey) => {
                  if (formKey.startsWith('enable_') && formKey !== 'enable_firebase_otp_control') {
                    form[formKey] = (formKey === key);
                }
            });
            handleSubmit();
        };

        const handleSubmit = async () => {
            if(props.app_for == "demo"){
                Swal.fire(t('error'), t('you_are_not_authorised'), 'error');
                return;
            }
            try {
                let formData = new FormData();
                for (let key in form) {
                    if(key.startsWith('enable')){
                        formData.append(key, form[key] ? 1 : 0);
                    }else{
                        formData.append(key, form[key] ?? '');
                    }
                }

                let response = await axios.post('/sms-gateway/update', formData);
                console.log("formdata",form.data());

                if (response.status === 201) {
                    successMessage.value = t('sms_configuration_updated_successfully');
                    setTimeout(() => {
                        router.get('/sms-gateway');
                        form.reset();
                    }, 5000);
                } else {
                    alertMessage.value = t('failed_to_update_sms_configuration');
                }
            } catch (error) {
                console.error(t('error_updating_sms_configuration'), error);
                alertMessage.value = t('failed_to_update_sms_configuration_catch');
            }
        };

        watch(() => props.settings, (newSettings) => {
            Object.assign(form, newSettings);
        }, { immediate: true });

        watch(enableFirebaseOtp, (newValue) => {
            enableFirebaseConsole.value = newValue;
        });

        return {
            form,
            successMessage,
            alertMessage,
            dismissMessage,
            handleCheckboxChange,
            handleSubmit,
            showMailPasswordModal,
        };
    },
};
</script>

<template>
    <Layout>
        <Head title="SMS Gateway" />
        <PageHeader :title="$t('sms_gateway')" :pageTitle="$t('sms_gateway')" />
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
                        <BCardHeader class="border-0"></BCardHeader>
                        <BCardBody class="border border-dashed border-end-0 border-start-0">
                            <BRow class="mt-3">
                                <BCol lg="6">
                                    <BCard no-body id="tasksList shadow">
                                        <BCardHeader class="border-0">
                                            <div class="row">
                                                <div class="col-6">
                                                    <h5>{{$t("enable_firebase_otp")}}</h5>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch form-switch-lg float-end me-3">
                                                        <input v-model="form.enable_firebase_otp" class="form-check-input" type="checkbox" role="switch" id="enable_firebase_otp" @change="handleCheckboxChange('enable_firebase_otp')" />
                                                    </div>
                                                </div>
                                            </div>
                                        </BCardHeader>
                                    </BCard>
                                </BCol>
                                <BCol lg="6">
                                    <BCard no-body id="tasksList shadow">
                                        <BCardHeader class="border-0">
                                            <div class="row">
                                                <div class="col-6 d-flex align-items-center gap-3">
                                                    <h5>{{$t("firebase_otp")}}</h5>
                                                    <BLink @click="showMailPasswordModal = true">
                                                        <h6 class="text-success float-end d-flex align-items-center me-3 text-decoration-underline text-decoration-underline-success">
                                                            <!-- <i class="bx bx-info-circle fs-20 me-1"></i> -->
                                                            {{$t('hint')}}
                                                        </h6>
                                                    </BLink> 
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch form-switch-lg float-end me-3">
                                                        <input v-model="form.enable_firebase_otp_control" class="form-check-input" type="checkbox" role="switch" id="enable_firebase_otp_control" @change="handleCheckboxChange('enable_firebase_otp_control')" />
                                                    </div>
                                                </div>
                                            </div>
                                        </BCardHeader>
                                    </BCard>
                                </BCol> 
                            </BRow>

                            <BRow class="mt-4">
                                <BCol lg="6">
                                    <BCard no-body id="tasksList" class="border">
                                        <BCardHeader class="border-0 mt-2 p-4 border-bottom">
                                            <div class="row">
                                                <div class="col-6">
                                                    <h5>{{$t("twilio")}}</h5>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch form-switch-lg float-end me-3">
                                                        <input v-model="form.enable_twilio" class="form-check-input" type="checkbox" role="switch" id="enable_twilio" @change="handleCheckboxChange('enable_twilio')" />
                                                    </div>
                                                </div>
                                            </div>
                                        </BCardHeader>
                                        <BCardBody>
                                            <div class="text-center mb-4">
                                                <img src="@assets/images/twilio.png" style="width: 200px;" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="twilio_sid" class="form-label">{{$t("sid")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.twilio_sid" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_twilio_sid')" id="twilio_sid" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="twilio_token" class="form-label">{{$t("token")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.twilio_token" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_twilio_token')" id="twilio_token" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="twilio_mobile_number" class="form-label">{{$t("twilio_mobile_number")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.twilio_mobile_number" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_twilio_mobile_number')" id="twilio_mobile_number" />
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="text-end">
                                                    <button type="submit" class="btn btn-primary">{{ $t('save') }}</button>
                                                </div>
                                            </div>
                                        </BCardBody>
                                    </BCard>
                                </BCol>

                                <BCol lg="6">
                                    <BCard no-body id="tasksList" class="border">
                                        <BCardHeader class="border-0 mt-2 p-4 border-bottom">
                                            <div class="row">
                                                <div class="col-6">
                                                    <h5>{{$t("sms_ala")}}</h5>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch form-switch-lg float-end me-3">
                                                        <input v-model="form.enable_sms_ala" class="form-check-input" type="checkbox" role="switch" id="enable_sms_ala" @change="handleCheckboxChange('enable_sms_ala')" />
                                                    </div>
                                                </div>
                                            </div>
                                        </BCardHeader>
                                        <BCardBody>
                                            <div class="text-center mb-4">
                                                <img src="@assets/images/smsala.webp" style="width: 200px;" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="sms_ala_api_key" class="form-label">{{$t("api_key")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.sms_ala_api_key" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_sms_ala_api_key')" id="sms_ala_api_key" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="sms_ala_api_secret_key" class="form-label">{{$t("api_secret_key")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.sms_ala_api_secret_key" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_sms_ala_api_secret_key')" id="sms_ala_api_secret_key" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="sms_ala_token" class="form-label">{{$t("token")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.sms_ala_token" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_sms_ala_token')" id="sms_ala_token" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="sms_ala_mobile_number" class="form-label">{{$t("sms_ala_mobile_number")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.sms_ala_mobile_number" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_sms_ala_mobile_number')" id="sms_ala_mobile_number" />
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="text-end">
                                                    <button type="submit" class="btn btn-primary">{{ $t('save') }}</button>
                                                </div>
                                            </div>
                                        </BCardBody>
                                    </BCard>
                                </BCol>
                                <BCol lg="6">
                                    <BCard no-body id="tasksList" class="border">
                                        <BCardHeader class="border-0 mt-2 p-4 border-bottom">
                                            <div class="row">
                                                <div class="col-6">
                                                    <h5>{{$t("msg91")}}</h5>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch form-switch-lg float-end me-3">
                                                        <input v-model="form.enable_msg91" class="form-check-input" type="checkbox" role="switch" id="enable_msg91" @change="handleCheckboxChange('enable_msg91')" />
                                                    </div>
                                                </div>
                                            </div>
                                        </BCardHeader>
                                        <BCardBody>
                                            <div class="text-center mb-4">
                                                <img src="@assets/images/msg91.png" style="width: 200px;" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="msg91_template_id" class="form-label">{{$t("msg91_template_id")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.msg91_template_id" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_msg91_template_id')" id="msg91_template_id" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="msg91_auth_key" class="form-label">{{$t("msg91_token")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.msg91_auth_key" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_msg91_token')" id="msg91_auth_key" />
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="text-end">
                                                    <button type="submit" class="btn btn-primary">{{ $t('save') }}</button>
                                                </div>
                                            </div>
                                        </BCardBody>
                                    </BCard>
                                </BCol>
                                <BCol lg="6">
                                    <BCard no-body id="tasksList" class="border">
                                        <BCardHeader class="border-0 mt-2 p-4 border-bottom">
                                            <div class="row">
                                                <div class="col-6">
                                                    <h5>{{$t("sparrow")}}</h5>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch form-switch-lg float-end me-3">
                                                        <input v-model="form.enable_sparrow" class="form-check-input" type="checkbox" role="switch" id="enable_sparrow" @change="handleCheckboxChange('enable_sparrow')" />
                                                    </div>
                                                </div>
                                            </div>
                                        </BCardHeader>
                                        <BCardBody>
                                            <div class="text-center mb-4">
                                                <img src="@assets/images/sparrow.png" style="width: 200px;" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="sparrow_sender_id" class="form-label">{{$t("sparrow_sender_id")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.sparrow_sender_id" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_sparrow_sender_id')" id="sparrow_sender_id" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="sparrow_token" class="form-label">{{$t("sparrow_token")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.sparrow_token" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_sparrow_token')" id="sparrow_token" />
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="text-end">
                                                    <button type="submit" class="btn btn-primary">{{ $t('save') }}</button>
                                                </div>
                                            </div>
                                        </BCardBody>
                                    </BCard>
                                </BCol>
                                <BCol lg="6">
                                    <BCard no-body id="tasksList" class="border">
                                        <BCardHeader class="border-0 mt-2 p-4 border-bottom">
                                            <div class="row">
                                                <div class="col-6">
                                                    <h5>{{$t("sms_india_hub")}}</h5>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch form-switch-lg float-end me-3">
                                                        <input v-model="form.enable_sms_india_hub" class="form-check-input" type="checkbox" role="switch" id="enable_sms_india_hub" @change="handleCheckboxChange('enable_sms_india_hub')" />
                                                    </div>
                                                </div>
                                            </div>
                                        </BCardHeader>
                                        <BCardBody>
                                            <div class="text-center mb-4">
                                                <img src="@assets/images/SMSINDIAHUB.png" style="width: 200px;" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="sms_india_hub_api_key" class="form-label">{{ $t("sms_india_hub_api_key") }}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.sms_india_hub_api_key" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_sms_india_hub_api_key')" id="sms_india_hub_api_key" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="sms_india_hub_sid" class="form-label">{{$t("sms_india_hub_sid")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.sms_india_hub_sid" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_sms_india_hub_sid')" id="sms_india_hub_sid" />
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="text-end">
                                                    <button type="submit" class="btn btn-primary">{{ $t('save') }}</button>
                                                </div>
                                            </div>
                                        </BCardBody>
                                    </BCard>
                                </BCol>
                                <BCol lg="6">
                                    <BCard no-body id="tasksList" class="border">
                                        <BCardHeader class="border-0 mt-2 p-4 border-bottom">
                                            <div class="row">
                                                <div class="col-6">
                                                    <h5>{{$t("kudi_sms")}}</h5>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch form-switch-lg float-end me-3">
                                                        <input v-model="form.enable_kudi_sms_api_key" class="form-check-input" type="checkbox" role="switch" id="enable_kudi_sms_api_key" @change="handleCheckboxChange('enable_kudi_sms_api_key')" />
                                                    </div>
                                                </div>
                                            </div>
                                        </BCardHeader>
                                        <BCardBody>
                                            <div class="text-center mb-4">
                                                <img src="@assets/images/kudi-sms.png" style="width: 200px;" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="kudi_sms_api_key" class="form-label">{{ $t("kudi_sms_api_key") }}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.kudi_sms_api_key" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_kudi_sms_api_key')" id="kudi_sms_api_key" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="kudi_sms_sender_id" class="form-label">{{$t("kudi_sms_sender_id")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.kudi_sms_sender_id" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_kudi_sms_sender_id')" id="kudi_sms_sender_id" />
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="text-end">
                                                    <button type="submit" class="btn btn-primary">{{ $t('save') }}</button>
                                                </div>
                                            </div>
                                        </BCardBody>
                                    </BCard>
                                </BCol>
                                <BCol lg="6">
                                    <BCard no-body id="tasksList" class="border">
                                        <BCardHeader class="border-0 mt-2 p-4 border-bottom">
                                            <div class="row">
                                                <div class="col-6">
                                                    <h5>{{$t("infobip")}}</h5>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch form-switch-lg float-end me-3">
                                                        <input v-model="form.enable_infobip" class="form-check-input" type="checkbox" role="switch" id="enable_infobip" @change="handleCheckboxChange('enable_infobip')" />
                                                    </div>
                                                </div>
                                            </div>
                                        </BCardHeader>
                                        <BCardBody>
                                            <div class="text-center mb-4">
                                                <img src="@assets/images/infobip.png" style="width: 200px;" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="infobip_base_url" class="form-label">{{ $t("infobip_base_url") }}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.infobip_base_url" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_infobip_base_url')" id="infobip_base_url" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="infobip_api_key" class="form-label">{{$t("infobip_api_key")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.infobip_api_key" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_infobip_api_key')" id="infobip_api_key" />
                                            </div>
                                             <div class="mb-3">
                                                <label for="infobip_sender_id" class="form-label">{{$t("infobip_sender_id")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.infobip_sender_id" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_infobip_sender_id')" id="infobip_sender_id" />
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="text-end">
                                                    <button type="submit" class="btn btn-primary">{{ $t('save') }}</button>
                                                </div>
                                            </div>
                                        </BCardBody>
                                    </BCard>
                                </BCol>
                                <!-- <BCol lg="6">
                                    <BCard no-body id="tasksList" class="border">
                                        <BCardHeader class="border-0 mt-2 p-4 border-bottom">
                                            <div class="row">
                                                <div class="col-6">
                                                    <h5>{{$t("termii")}}</h5>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check form-switch form-switch-lg float-end me-3">
                                                        <input v-model="form.enable_termii" class="form-check-input" type="checkbox" role="switch" id="enable_termii" @change="handleCheckboxChange('enable_termii')" />
                                                    </div>
                                                </div>
                                            </div>
                                        </BCardHeader>
                                        <BCardBody>
                                            <div class="text-center mb-4">
                                                <img src="@assets/images/termii.png" style="width: 200px;" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="termii_base_url" class="form-label">{{ $t("termii_base_url") }}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.termii_base_url" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_termii_base_url')" id="termii_base_url" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="termii_api_key" class="form-label">{{$t("termii_api_key")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.termii_api_key" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_termii_api_key')" id="termii_api_key" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="termii_sender_id" class="form-label">{{$t("termii_sender_id")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.termii_sender_id" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_termii_sender_id')" id="termii_sender_id" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="termii_channel" class="form-label">{{$t("termii_channel")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.termii_channel" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_termii_channel')" id="termii_channel" />
                                            </div>
                                            <div class="mb-3">
                                                <label for="termii_type" class="form-label">{{$t("termii_type")}}</label>
                                                <input :readonly="app_for === 'demo'" v-model="form.termii_type" :type="app_for === 'demo' ? 'password' : 'text'" class="form-control" :placeholder="$t('your_termii_type')" id="termii_type" />
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="text-end">
                                                    <button type="submit" class="btn btn-primary">{{ $t('save') }}</button>
                                                </div>
                                            </div>
                                        </BCardBody>
                                    </BCard>
                                </BCol> -->
                            </BRow>
                        </BCardBody>
                    </BCard>
                </BCol>
            </BRow>
        </form>
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

        <BModal v-model="showMailPasswordModal" hide-footer :title="$t('firebase_otp')" class="v-modal-custom" size="md">
          <div class="container"> 
            <ul class="list-unstyled vstack gap-3">
              <li>
                <div class="d-flex">
                  <div class="flex-shrink-0 text-success me-1">
                    <i class="ri-checkbox-circle-fill fs-15 align-middle"></i>
                  </div>
                    <div class="flex-grow-1">
                       When Firebase OTP(call_FB_OTP) is enabled, users will receive a real OTP via Firebase authentication.
                    </div>
                </div>
                 <div class="d-flex">
                  <div class="flex-shrink-0 text-success me-1 mt-2">
                    <i class="ri-checkbox-circle-fill fs-15 align-middle"></i>
                  </div>
                    <div class="flex-grow-1 mt-2">
                        If Firebase OTP is disabled, a demo OTP will be used instead for testing purposes.                
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
