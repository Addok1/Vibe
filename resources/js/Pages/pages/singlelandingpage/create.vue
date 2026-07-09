<script>
import { Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import Pagination from "@/Components/Pagination.vue";
import { ref, computed,onMounted } from "vue";
import axios from "axios";
import Multiselect from "@vueform/multiselect";
import FormValidation from "@/Components/FormValidation.vue";
import imageUpload from "@/Components/widgets/imageUpload.vue";
import ImageUp from "@/Components/ImageUp.vue";
import CKEditor from "@ckeditor/ckeditor5-vue";
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import { useSharedState } from '@/composables/useSharedState';
import Swal from "sweetalert2";
import { useI18n } from 'vue-i18n';
import MultiUpload from "@/Components/SingleUpload.vue";

export default {
  components: {
    Layout,
    PageHeader,
    Head,
    Pagination,
    Multiselect,
    FormValidation,
    imageUpload,
    ImageUp,
    ckeditor: CKEditor.component,
    ImageUp,  
    MultiUpload 
  
  },
  data() {
    return {
      editor: ClassicEditor,
      editorData: "",
    };
  },
  props: {
    successMessage: String,
    alertMessage: String,
    singlelandingpage: Object,
    validate: Function,
    app_for: String,
    languages: {
      type: Array,
      required: true
    },
    settings: Array,
  },
  setup(props) {
    const enableOpenAI = ref(props.settings);
    console.log("enableOpenAI",enableOpenAI.value);
    const { t } = useI18n();
    const { languages } = useSharedState();
    const storedLanguages = ref([]);  
    const form = useForm({
      hero_para: props.singlelandingpage ? props.singlelandingpage.hero_para || "" : "",
      hero_img_1: props.singlelandingpage ? props.singlelandingpage.hero_img_1 || "" : "",
      hero_img_2: props.singlelandingpage ? props.singlelandingpage.hero_img_2 || "" : "",
      hero_img_3: props.singlelandingpage ? props.singlelandingpage.hero_img_3 || "" : "",
      hero_img_4: props.singlelandingpage ? props.singlelandingpage.hero_img_4 || "" : "",
      hero_img_5: props.singlelandingpage ? props.singlelandingpage.hero_img_5 || "" : "",
      adv_title: props.singlelandingpage ? props.singlelandingpage.adv_title || "" : "",
      adv_para: props.singlelandingpage ? props.singlelandingpage.adv_para || "" : "",
      adv_box1_title: props.singlelandingpage ? props.singlelandingpage.adv_box1_title || "" : "",
      adv_box1_para: props.singlelandingpage ? props.singlelandingpage.adv_box1_para || "" : "",
      adv_box1_img: [],
      adv_box2_title: props.singlelandingpage ? props.singlelandingpage.adv_box2_title || "" : "",
      adv_box2_para: props.singlelandingpage ? props.singlelandingpage.adv_box2_para || "" : "",
      adv_box2_img: [],
      adv_box3_title: props.singlelandingpage ? props.singlelandingpage.adv_box3_title || "" : "",
      adv_box3_para: props.singlelandingpage ? props.singlelandingpage.adv_box3_para || "" : "",
      adv_box3_img: [],
      adv_box4_title: props.singlelandingpage ? props.singlelandingpage.adv_box4_title || "" : "",
      adv_box4_para: props.singlelandingpage ? props.singlelandingpage.adv_box4_para || "" : "",
      adv_box4_img: [],
      adv_box5_title: props.singlelandingpage ? props.singlelandingpage.adv_box5_title || "" : "",
      adv_box5_para: props.singlelandingpage ? props.singlelandingpage.adv_box5_para || "" : "",
      adv_box5_img: [],
      app_works_title: props.singlelandingpage ? props.singlelandingpage.app_works_title || "" : "",
      app_works_para: props.singlelandingpage ? props.singlelandingpage.app_works_para || "" : "",
      app_works_user_title: props.singlelandingpage ? props.singlelandingpage.app_works_user_title || "" : "",
      app_works_driver_title: props.singlelandingpage ? props.singlelandingpage.app_works_driver_title || "" : "",
      user_box1_title: props.singlelandingpage ? props.singlelandingpage.user_box1_title || "" : "",
      user_box1_para: props.singlelandingpage ? props.singlelandingpage.user_box1_para || "" : "",
      user_box2_title: props.singlelandingpage ? props.singlelandingpage.user_box2_title || "" : "",
      user_box2_para: props.singlelandingpage ? props.singlelandingpage.user_box2_para || "" : "",
      user_box3_title: props.singlelandingpage ? props.singlelandingpage.user_box3_title || "" : "",
      user_box3_para: props.singlelandingpage ? props.singlelandingpage.user_box3_para || "" : "",
      user_box4_title: props.singlelandingpage ? props.singlelandingpage.user_box4_title || "" : "",
      user_box4_para: props.singlelandingpage ? props.singlelandingpage.user_box4_para || "" : "",
      driver_box1_title: props.singlelandingpage ? props.singlelandingpage.driver_box1_title || "" : "",
      driver_box1_para: props.singlelandingpage ? props.singlelandingpage.driver_box1_para || "" : "",
      driver_box2_title: props.singlelandingpage ? props.singlelandingpage.driver_box2_title || "" : "",
      driver_box2_para: props.singlelandingpage ? props.singlelandingpage.driver_box2_para || "" : "",
      driver_box3_title: props.singlelandingpage ? props.singlelandingpage.driver_box3_title || "" : "",
      driver_box3_para: props.singlelandingpage ? props.singlelandingpage.driver_box3_para || "" : "",
      driver_box4_title: props.singlelandingpage ? props.singlelandingpage.driver_box4_title || "" : "",
      driver_box4_para: props.singlelandingpage ? props.singlelandingpage.driver_box4_para || "" : "",
      app_user_img: props.singlelandingpage ? props.singlelandingpage.app_user_img || "" : "",
      app_driver_img: props.singlelandingpage ? props.singlelandingpage.app_driver_img || "" : "",
      why_choose_title: props.singlelandingpage ? props.singlelandingpage.why_choose_title || "" : "",
      why_choose_box1_title: props.singlelandingpage ? props.singlelandingpage.why_choose_box1_title || "" : "",
      why_choose_box1_para: props.singlelandingpage ? props.singlelandingpage.why_choose_box1_para || "" : "",
      why_choose_box2_title: props.singlelandingpage ? props.singlelandingpage.why_choose_box2_title || "" : "",
      why_choose_box2_para: props.singlelandingpage ? props.singlelandingpage.why_choose_box2_para || "" : "",
      why_choose_box3_title: props.singlelandingpage ? props.singlelandingpage.why_choose_box3_title || "" : "",
      why_choose_box3_para: props.singlelandingpage ? props.singlelandingpage.why_choose_box3_para || "" : "",
      why_choose_box4_title: props.singlelandingpage ? props.singlelandingpage.why_choose_box4_title || "" : "",
      why_choose_box4_para: props.singlelandingpage ? props.singlelandingpage.why_choose_box4_para || "" : "",
      why_choose_img: props.singlelandingpage ? props.singlelandingpage.why_choose_img || "" : "",
      about_title_1: props.singlelandingpage ? props.singlelandingpage.about_title_1 || "" : "",
      about_title_2: props.singlelandingpage ? props.singlelandingpage.about_title_2 || "" : "",
      about_img: props.singlelandingpage ? props.singlelandingpage.about_img || "" : "",
      about_para: props.singlelandingpage ? props.singlelandingpage.about_para || "" : "",
      ceo_title_1: props.singlelandingpage ? props.singlelandingpage.ceo_title_1 || "" : "",
      ceo_title_2: props.singlelandingpage ? props.singlelandingpage.ceo_title_2 || "" : "",
      ceo_para: props.singlelandingpage ? props.singlelandingpage.ceo_para || "" : "",
      ceo_img: props.singlelandingpage ? props.singlelandingpage.ceo_img || "" : "",
      download_title: props.singlelandingpage ? props.singlelandingpage.download_title || "" : "",
      download_para: props.singlelandingpage ? props.singlelandingpage.download_para || "" : "",
      download_img1: props.singlelandingpage ? props.singlelandingpage.download_img1 || "" : "",
      download_img2: props.singlelandingpage ? props.singlelandingpage.download_img2 || "" : "",
      contact_heading: props.singlelandingpage ? props.singlelandingpage.contact_heading || "" : "",
      contact_para: props.singlelandingpage ? props.singlelandingpage.contact_para || "" : "",
      contact_img:[],
      download_user_link_android: props.singlelandingpage ? props.singlelandingpage.download_user_link_android || "" : "",
      download_user_link_apple: props.singlelandingpage ? props.singlelandingpage.download_user_link_apple || "" : "",
      download_driver_link_android: props.singlelandingpage ? props.singlelandingpage.download_driver_link_android || "" : "",
      download_driver_link_apple: props.singlelandingpage ? props.singlelandingpage.download_driver_link_apple || "" : "",
      contact_address_title: props.singlelandingpage ? props.singlelandingpage.contact_address_title || "" : "",
      contact_address: props.singlelandingpage ? props.singlelandingpage.contact_address || "" : "",
      contact_phone_title: props.singlelandingpage ? props.singlelandingpage.contact_phone_title || "" : "",
      contact_phone: props.singlelandingpage ? props.singlelandingpage.contact_phone || "" : "",
      contact_mail_title: props.singlelandingpage ? props.singlelandingpage.contact_mail_title || "" : "",
      contact_mail: props.singlelandingpage ? props.singlelandingpage.contact_mail || "" : "",
      contact_web_title: props.singlelandingpage ? props.singlelandingpage.contact_web_title || "" : "",
      contact_web: props.singlelandingpage ? props.singlelandingpage.contact_web || "" : "",
      form_name: props.singlelandingpage ? props.singlelandingpage.form_name || "" : "",
      form_mail: props.singlelandingpage ? props.singlelandingpage.form_mail || "" : "",
      form_subject: props.singlelandingpage ? props.singlelandingpage.form_subject || "" : "",
      form_message: props.singlelandingpage ? props.singlelandingpage.form_message || "" : "",
      form_btn: props.singlelandingpage ? props.singlelandingpage.form_btn || "" : "",
      locale: props.singlelandingpage ? props.singlelandingpage.locale || "" : "",
      language: props.singlelandingpage ? props.singlelandingpage.language || "" : "",
    });


    const validationRules = {
      hero_para: { required: true },
      hero_img_1: { required: true },
      hero_img_2: { required: true },
      hero_img_3: { required: true },
      hero_img_4: { required: true },
      hero_img_5: { required: true },
      adv_title: { required: true },
      adv_para: { required: true },
      adv_box1_title: { required: true },
      adv_box1_para: { required: true },
      adv_box1_img: { required: true },
      adv_box2_title: { required: true },
      adv_box2_para: { required: true },
      adv_box2_img: { required: true },
      adv_box3_title: { required: true },
      adv_box3_para: { required: true },
      adv_box3_img: { required: true },
      adv_box4_title: { required: true },
      adv_box4_para: { required: true } ,
      adv_box4_img: { required: true },
      adv_box5_title: { required: true },
      adv_box5_para: { required: true },
      adv_box5_img: { required: true } ,
      app_works_title: { required: true },
      app_works_para: { required: true } ,
      app_works_user_title: { required: true },
      app_works_driver_title: { required: true } ,
      user_box1_title: { required: true },
      user_box1_para: { required: true },
      user_box2_title: { required: true } ,
      user_box2_para: { required: true } ,
      user_box3_title: { required: true } ,
      user_box3_para: { required: true } ,
      user_box4_title: { required: true } ,
      user_box4_para: { required: true } ,
      driver_box1_title: { required: true } ,
      driver_box1_para: { required: true },
      driver_box2_title: { required: true } ,
      driver_box2_para: { required: true } ,
      driver_box3_title: { required: true } ,
      driver_box3_para: { required: true } ,
      driver_box4_title: { required: true },
      driver_box4_para: { required: true },
      app_user_img: { required: true },
      app_driver_img: { required: true },
      why_choose_title: { required: true },
      why_choose_box1_title: { required: true },
      why_choose_box1_para: { required: true },
      why_choose_box2_title: { required: true },
      why_choose_box2_para: { required: true },
      why_choose_box3_title: { required: true },
      why_choose_box3_para: { required: true },
      why_choose_box4_title: { required: true },
      why_choose_box4_para: { required: true },
      why_choose_img: { required: true },
      about_title_1: { required: true },
      about_title_2: { required: true },
      about_img: { required: true },
      about_para: { required: true },
      ceo_title_1: { required: true } ,
      ceo_title_2: { required: true },
      ceo_para: { required: true },
      ceo_img: { required: true },
      download_title: { required: true } ,
      download_para: { required: true },
      download_user_link_android: { required: true } ,
      download_user_link_apple: { required: true },
      download_driver_link_android: { required: true } ,
      download_driver_link_apple: { required: true },
      download_img1: { required: true },
      download_img2: { required: true } ,
      contact_heading: { required: true } ,
      contact_para: { required: true } ,
      contact_img: { required: true } ,
      contact_address_title: { required: true } ,
      contact_address: { required: true } ,
      contact_phone_title: { required: true },
      contact_phone: { required: true },
      contact_mail_title: { required: true },
      contact_mail: { required: true },
      contact_web_title: { required: true } ,
      contact_web: { required: true },
      form_name: { required: true },
      form_mail: { required: true },
      form_subject: { required: true } ,
      form_message: { required: true },
      form_btn: { required: true } ,
      locale: { required: true } ,
      language: { required: true } ,
    };

    const validateIconSize = () => {
      const fileInput = document.getElementById('iconInput');
      if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        const img = new Image();
        img.onload = function () {
          if (img.width !== 512 || img.height !== 512) {
            alert('Icon must be 512x512 pixels.');
            fileInput.value = '';
          }
        };
        img.src = URL.createObjectURL(file);
      }
    };

    const validationRef = ref(null);
    const errors = ref({});
    const successMessage = ref(props.successMessage || '');
    const alertMessage = ref(props.alertMessage || '');

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
        const formData = new FormData();
        const formFields = form.data();
        for (const key in form) {
          if (key === 'iconFile' && form[key]) {
            formData.append('icon', form[key]);
          }  
      else if (
        ['adv_box1_img','adv_box2_img','adv_box3_img','adv_box4_img','adv_box5_img','contact_img']
        .includes(key)
      ) {

        if (form[key] && form[key].length) {
          form[key].forEach(fileObj => {
            const file = fileObj.file || fileObj;
            formData.append(`${key}[]`, file);
          });
        }

      }
          else {
            formData.append(key, form[key]);
          }
         
        }
   

        let response;
        console.log("fomr",form.data());
        if (props.singlelandingpage && props.singlelandingpage.id) {
          response = await axios.post(`/single-landing-page/update/${props.singlelandingpage.id}`, formData, {
            headers: {
              'Content-Type': 'multipart/form-data',
            },
          });

        } else {
          response = await axios.post('/single-landing-page/store', formData, {
            headers: {
              'Content-Type': 'multipart/form-data',
            },
          });
        }

        if (response.status === 201) {
          successMessage.value = t('landing_page_created_successfully');
          form.reset();
          router.get('/single-landing-page');
        } else {
          alertMessage.value = t('failed_to_create_landing_page');
        }
      } catch (error) {
        console.error(t('error_creating_landing_page'), error);
        alertMessage.value = t('failed_to_create_landing_page');
        if (error.response && error.response.data.errors) {
          errors.value = error.response.data.errors;
        }
      }
    };

    onMounted(async () => {
      
      if (Object.keys(languages).length == 0) {
        await fetchData();
      }
      fetchStoredLanguages();
    });


    const fetchStoredLanguages = async () => {     
      try {
        const response = await axios.get('/single-landing-page/list');
        storedLanguages.value = response.data.results.map(lang => ({language: lang.language }) );

        const commonLanguages = storedLanguages.value.filter(storedLang =>
            languages.value.some(lang => lang.label === storedLang.language)
        );

          languages.value = languages.value.map(lang => ({
          ...lang,  // All existing properties of lang
          disabled: commonLanguages.some(commonLang => commonLang.language === lang.label),
        }));
      } catch (error) {
        console.error(t('error_fetching_stored_languages'), error);
      }
    };

      // Construct the full URL for the vehicle type icon
      const iconUrl = props.vehicleType ? props.vehicleType.icon :null;

      const handleImageSelected = (file, fieldName) => {
      if(props.app_for == "demo"){
          Swal.fire(t('error'), t('you_are_not_authorised'), 'error');
          return;
      }
      form[fieldName] = file;
    };

    const handleImageRemoved = (fieldName) => {
      if(props.app_for == "demo"){
          Swal.fire(t('error'), t('you_are_not_authorised'), 'error');
          return;
      }
      form[fieldName] = null;
    };


    const selectlanguages = async () =>{
      const selectedLanguage = languages.value.find(
        (lang) => lang.label === form.language,(lang)=>lang.direction === form.direction
      );
        form.language = selectedLanguage.label;
        form.locale = selectedLanguage.code;
        form.direction = selectedLanguage.direction;
    }

   const handleFilesSelected = (files, field) => {
    form[field] = files;
};
        const  removeFile = (index) =>{
         form.contact_img.splice(index, 1);
        }
        
    const ai_text = ref({});
    const generateAiText = async (field) => {
      try {

          ai_text.value[field] = 'Generating...';

          let response = await axios.post('/generate-ai-landing-text', {
              type: field,
              value: form[field]
          });

          //  THIS decides which input to update
          form[field] = response.data.text;

          ai_text.value[field] = 'Re-generate';

      } catch (error) {
          ai_text.value[field] = 'Generate';
      }
  };

    return {
      form,
      successMessage,
      alertMessage,
      handleSubmit,
      dismissMessage,
      validationRules,    
      validationRef,
      errors,
      validateIconSize,
      singlelandingpage: props.singlelandingpage,
      iconUrl,
      handleImageSelected,
      handleImageRemoved,
      languages,
      selectlanguages,      
      handleFilesSelected,
      removeFile,
      ai_text,
      generateAiText,
      enableOpenAI
    };
  }
};
</script>

<template>
  <Layout>
    <Head title="Manage LandingSite-Home" />
    <PageHeader :title="singlelandingpage ? $t('edit') : $t('create')" :pageTitle="$t('single_landing_page')" />
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
          </BCardHeader>
          <BCardBody class="border border-dashed border-end-0 border-start-0">
            <form @submit.prevent="handleSubmit">
              <FormValidation :form="form" :rules="validationRules" ref="validationRef">
                <div class="row">
                  <div class="col-6 mt-3">
                    <div class="mb-3">
                      <label for="language" class="form-label">{{$t("languages")}}
                        <span class="text-danger">*</span>
                      </label>
                      <select :disabled="app_for === 'demo'" id="language" class="form-select" v-model="form.language"  @change="selectlanguages">
                        <option disabled value="">{{$t("choose_languages")}}</option>
                        <option v-for="languages in languages" :key="languages.id" :disabled="languages.disabled">
                        {{ languages.label }}
                      </option>
                      </select>
                      <span v-for="(error, index) in errors.language" :key="index" class="text-danger">{{ error }}</span>
                    </div>
                  </div>
                  <div class="card-header alert alert-success">
                      <h4 class="card-title mb-3">{{$t("hero_section")}}</h4>
                  </div>
                  <div class="col-12 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="hero_para" class="form-label">{{$t("hero_para")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('hero_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.hero_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.hero_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.hero_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                    <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="hero_img_1" class="form-label">{{$t("hero_image1")}}
                        <span class="text-danger">*</span>
                      </label>
                      <ImageUp :initialImageUrl="form.hero_img_1" @image-selected="(file) => handleImageSelected(file, 'hero_img_1')" @image-removed="() => handleImageRemoved('hero_img_1')"></ImageUp>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="hero_img_2" class="form-label">{{$t("hero_image2")}}
                        <span class="text-danger">*</span>
                      </label>
                      <ImageUp :initialImageUrl="form.hero_img_2" @image-selected="(file) => handleImageSelected(file, 'hero_img_2')" @image-removed="() => handleImageRemoved('hero_img_2')"></ImageUp>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="hero_img_3" class="form-label">{{$t("hero_image3")}}
                        <span class="text-danger">*</span>
                      </label>
                      <ImageUp :initialImageUrl="form.hero_img_3" @image-selected="(file) => handleImageSelected(file, 'hero_img_3')" @image-removed="() => handleImageRemoved('hero_img_3')"></ImageUp>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="hero_img_4" class="form-label">{{$t("hero_image4")}}
                        <span class="text-danger">*</span>
                      </label>
                      <ImageUp :initialImageUrl="form.hero_img_4" @image-selected="(file) => handleImageSelected(file, 'hero_img_4')" @image-removed="() => handleImageRemoved('hero_img_4')"></ImageUp>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="hero_img_5" class="form-label">{{$t("hero_image5")}}
                        <span class="text-danger">*</span>
                      </label>
                      <ImageUp :initialImageUrl="form.hero_img_5" @image-selected="(file) => handleImageSelected(file, 'hero_img_5')" @image-removed="() => handleImageRemoved('hero_img_5')"></ImageUp>
                    </div>
                  </div>
                 
                  <div class="card-header alert alert-success">
                      <h4 class="card-title mb-3">{{$t("advantage_section")}}</h4>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="adv_title" class="form-label">{{$t("advantage_title")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('adv_title')"  v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.adv_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_advantage_title')" id="adv_title" v-model="form.adv_title" />
                      <span v-for="(error, index) in errors.adv_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="adv_para" class="form-label">{{$t("advantage_para")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('adv_para')"  v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.adv_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.adv_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.adv_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                   
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                       <div class="d-flex justify-content-between">                        
                        <label for="adv_box1_title" class="form-label">{{$t("sub_heading_1")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('adv_box1_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.adv_box1_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_sub_heading_2')" id="adv_box1_title" v-model="form.adv_box1_title" />
                      <span v-for="(error, index) in errors.adv_box1_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="adv_box1_para" class="form-label">{{$t("sub_heading_para_1")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('adv_box1_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.adv_box1_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.adv_box1_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.adv_box1_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="adv_box1_img" class="form-label">{{$t("advantage_image1")}}
                        <span class="text-danger">*</span>
                      </label>
                     <MultiUpload @files-selected="files => handleFilesSelected(files, 'adv_box1_img')" />
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="adv_box2_title" class="form-label">{{$t("sub_heading_2")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('adv_box2_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.adv_box2_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_sub_heading_2')" id="adv_box2_title" v-model="form.adv_box2_title" />
                      <span v-for="(error, index) in errors.adv_box2_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="adv_box2_para" class="form-label">{{$t("sub_heading_para_2")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('adv_box2_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.adv_box2_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.adv_box2_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.adv_box2_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="adv_box2_img" class="form-label">{{$t("advantage_image2")}}
                        <span class="text-danger">*</span>
                      </label>
                      <MultiUpload @files-selected="files => handleFilesSelected(files, 'adv_box2_img')" />
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                       <div class="d-flex justify-content-between">                        
                        <label for="adv_box3_title" class="form-label">{{$t("sub_heading_3")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('adv_box3_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.adv_box3_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_sub_heading_3')" id="adv_box3_title" v-model="form.adv_box3_title" />
                      <span v-for="(error, index) in errors.adv_box3_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">                        
                        <label for="adv_box3_para" class="form-label">{{$t("sub_heading_para_3")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('adv_box3_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.adv_box3_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.adv_box3_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.adv_box3_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="adv_box3_img" class="form-label">{{$t("advantage_image3")}}
                        <span class="text-danger">*</span>
                      </label>
                      <MultiUpload @files-selected="files => handleFilesSelected(files, 'adv_box3_img')" />
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="adv_box4_title" class="form-label">{{$t("sub_heading_4")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('adv_box4_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.adv_box4_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_sub_heading_4')" id="adv_box4_title" v-model="form.adv_box4_title" />
                      <span v-for="(error, index) in errors.adv_box4_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                         <div class="d-flex justify-content-between">                        
                        <label for="adv_box4_para" class="form-label">{{$t("sub_heading_para_4")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('adv_box4_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.adv_box4_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.adv_box4_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.adv_box4_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                   <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="adv_box4_img" class="form-label">{{$t("advantage_image4")}}
                        <span class="text-danger">*</span>
                      </label>
                      <MultiUpload @files-selected="files => handleFilesSelected(files, 'adv_box4_img')" />
                    </div>
                  </div>
                   <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="adv_box5_title" class="form-label">{{$t("sub_heading_5")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('adv_box5_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.adv_box5_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_sub_heading_5')" id="adv_box5_title" v-model="form.adv_box5_title" />
                      <span v-for="(error, index) in errors.adv_box5_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                       <div class="d-flex justify-content-between">                        
                        <label for="adv_box5_para" class="form-label">{{$t("sub_heading_para_5")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('adv_box5_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.adv_box5_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.adv_box5_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.adv_box5_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                   <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="adv_box5_img" class="form-label">{{$t("advantage_image5")}}
                        <span class="text-danger">*</span>
                      </label>
                      <MultiUpload @files-selected="files => handleFilesSelected(files, 'adv_box5_img')" />
                    </div>
                  </div>
                  <div class="card-header alert alert-success">
                      <h4 class="card-title mb-3">{{$t("how_app_works_section")}}</h4>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                       <div class="d-flex justify-content-between">                        
                        <label for="app_works_title" class="form-label">{{$t("app_works_title")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('app_works_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.app_works_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_app_work_title')" id="app_works_title" v-model="form.app_works_title" />
                      <span v-for="(error, index) in errors.app_works_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                     <div class="d-flex justify-content-between">                        
                        <label for="app_works_para" class="form-label">{{$t("app_works_para")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('app_works_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.app_works_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.app_works_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.app_works_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                    <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                     <div class="d-flex justify-content-between">                        
                        <label for="app_works_user_title" class="form-label">{{$t("app_works_user_title")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('app_works_user_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.app_works_user_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_app_works_user_title')" id="app_works_user_title" v-model="form.app_works_user_title" />
                      <span v-for="(error, index) in errors.app_works_user_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                    <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                       <div class="d-flex justify-content-between">                        
                        <label for="app_works_driver_title" class="form-label">{{$t("app_works_driver_title")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('app_works_driver_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.app_works_driver_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_app_works_driver_title')" id="app_works_driver_title" v-model="form.app_works_driver_title" />
                      <span v-for="(error, index) in errors.app_works_driver_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                       <div class="d-flex justify-content-between">                        
                        <label for="user_box1_title" class="form-label">{{$t("driver_title1")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('user_box1_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.user_box1_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_driver_title1')" id="user_box1_title" v-model="form.user_box1_title" />
                      <span v-for="(error, index) in errors.user_box1_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="driver_box1_para" class="form-label">{{$t("driver_para_1")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('driver_box1_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.driver_box1_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.driver_box1_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.driver_box1_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="driver_box2_title" class="form-label">{{$t("driver_title2")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('driver_box2_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.driver_box2_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_driver_title2')" id="driver_box2_title" v-model="form.driver_box2_title" />
                      <span v-for="(error, index) in errors.driver_box2_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="driver_box2_para" class="form-label">{{$t("driver_para_2")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('driver_box2_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.driver_box2_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.driver_box2_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.driver_box2_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">                        
                        <label for="driver_box3_title" class="form-label">{{$t("driver_title3")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('driver_box3_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.driver_box3_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_driver_title3')" id="driver_box3_title" v-model="form.driver_box3_title" />
                      <span v-for="(error, index) in errors.driver_box3_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">                        
                        <label for="driver_box3_para" class="form-label">{{$t("driver_para_3")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('driver_box3_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.driver_box3_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.driver_box3_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.driver_box3_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                       <div class="d-flex justify-content-between">                        
                        <label for="driver_box4_title" class="form-label">{{$t("driver_title4")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('driver_box4_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.driver_box4_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_driver_title4')" id="driver_box4_title" v-model="form.driver_box4_title" />
                      <span v-for="(error, index) in errors.driver_box4_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="driver_box4_para" class="form-label">{{$t("driver_para_4")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('driver_box4_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.driver_box4_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.driver_box4_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.driver_box4_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                   <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                       <div class="d-flex justify-content-between">                        
                        <label for="driver_box1_title" class="form-label">{{$t("user_title1")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('driver_box1_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.driver_box1_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_user_title1')" id="driver_box1_title" v-model="form.driver_box1_title" />
                      <span v-for="(error, index) in errors.driver_box1_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">                        
                        <label for="user_box1_para" class="form-label">{{$t("user_para_1")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('user_box1_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.user_box1_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.user_box1_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.user_box1_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="user_box2_title" class="form-label">{{$t("user_title2")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('user_box2_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.user_box2_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_user_title2')" id="user_box2_title" v-model="form.user_box2_title" />
                      <span v-for="(error, index) in errors.user_box2_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="user_box2_para" class="form-label">{{$t("user_para_2")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('user_box2_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.user_box2_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.user_box2_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.user_box2_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="user_box3_title" class="form-label">{{$t("user_title3")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('user_box3_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.user_box3_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_user_title3')" id="user_box3_title" v-model="form.user_box3_title" />
                      <span v-for="(error, index) in errors.user_box3_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">                        
                        <label for="user_box3_para" class="form-label">{{$t("user_para_3")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('user_box3_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.user_box3_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.user_box3_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.user_box3_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="user_box4_title" class="form-label">{{$t("user_title4")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('user_box4_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.user_box4_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_user_title4')" id="user_box4_title" v-model="form.user_box4_title" />
                      <span v-for="(error, index) in errors.user_box4_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">                        
                        <label for="user_box4_para" class="form-label">{{$t("user_para_4")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('user_box4_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.user_box4_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.user_box4_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.user_box4_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="app_user_img" class="form-label">{{$t("user_image")}}
                        <span class="text-danger">*</span>
                      </label>
                      <ImageUp :initialImageUrl="form.app_user_img" @image-selected="(file) => handleImageSelected(file, 'app_user_img')" @image-removed="() => handleImageRemoved('app_user_img')"></ImageUp>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="app_driver_img" class="form-label">{{$t("driver_image")}}
                        <span class="text-danger">*</span>
                      </label>
                      <ImageUp :initialImageUrl="form.app_driver_img" @image-selected="(file) => handleImageSelected(file, 'app_driver_img')" @image-removed="() => handleImageRemoved('app_driver_img')"></ImageUp>
                    </div>
                  </div>
                  <div class="card-header alert alert-success">
                      <h4 class="card-title mb-3">{{$t("why choose_section")}}</h4>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="why_choose_title" class="form-label">{{$t("title")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('why_choose_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.why_choose_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_title')" id="why_choose_title" v-model="form.why_choose_title" />
                      <span v-for="(error, index) in errors.why_choose_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="why_choose_box1_title" class="form-label">{{$t("box_title1")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('why_choose_box1_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.why_choose_box1_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_box_title1')" id="why_choose_box1_title" v-model="form.why_choose_box1_title" />
                      <span v-for="(error, index) in errors.why_choose_box1_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="why_choose_box1_para" class="form-label">{{$t("box1_para")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('why_choose_box1_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.why_choose_box1_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.why_choose_box1_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.why_choose_box1_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                   <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="why_choose_box2_title" class="form-label">{{$t("box_title2")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('why_choose_box2_title')" v-if="enableOpenAI == 1">  
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.why_choose_box2_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_box_title1')" id="why_choose_box2_title" v-model="form.why_choose_box2_title" />
                      <span v-for="(error, index) in errors.why_choose_box2_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="why_choose_box2_para" class="form-label">{{$t("box2_para")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('why_choose_box2_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.why_choose_box2_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.why_choose_box2_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.why_choose_box2_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                   <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="why_choose_box3_title" class="form-label">{{$t("box_title3")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('why_choose_box3_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.why_choose_box3_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_box_title4')" id="why_choose_box3_title" v-model="form.why_choose_box3_title" />
                      <span v-for="(error, index) in errors.why_choose_box3_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                       <div class="d-flex justify-content-between">                        
                        <label for="why_choose_box3_para" class="form-label">{{$t("box3_para")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('why_choose_box3_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.why_choose_box3_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.why_choose_box3_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.why_choose_box3_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                   <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">                        
                        <label for="why_choose_box4_title" class="form-label">{{$t("box_title4")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('why_choose_box4_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.why_choose_box4_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_box_title4')" id="why_choose_box4_title" v-model="form.why_choose_box4_title" />
                      <span v-for="(error, index) in errors.why_choose_box4_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                         <div class="d-flex justify-content-between">                        
                        <label for="why_choose_box4_para" class="form-label">{{$t("box4_para")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('why_choose_box4_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.why_choose_box4_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.why_choose_box4_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.why_choose_box4_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="why_choose_img" class="form-label">{{$t("why_choose_image")}}</label>
                      <ImageUp :initialImageUrl="form.why_choose_img" @image-selected="(file) => handleImageSelected(file, 'why_choose_img')" @image-removed="() => handleImageRemoved('why_choose_img')"></ImageUp>
                    </div>
                  </div>
                  <div class="card-header alert alert-success">
                      <h4 class="card-title mb-3">{{$t("about_section")}}</h4>
                  </div>
                   <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                       <div class="d-flex justify-content-between">                        
                        <label for="about_title1" class="form-label">{{$t("about_title1")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('about_title1')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.about_title1 || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_about_title1')" id="about_title_1" v-model="form.about_title_1" />
                      <span v-for="(error, index) in errors.about_title_1" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                    <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">                        
                        <label for="about_title_2" class="form-label">{{$t("about_title_2")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('about_title_2')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.about_title_2 || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_about_title2')" id="about_title_2" v-model="form.about_title_2" />
                      <span v-for="(error, index) in errors.about_title_2" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                         <div class="d-flex justify-content-between">                        
                        <label for="about_para" class="form-label">{{$t("about_para")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('about_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.about_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.about_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.about_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="about_img" class="form-label">{{$t("about_img")}}
                        <span class="text-danger">*</span>
                      </label>
                      <ImageUp :initialImageUrl="form.about_img" @image-selected="(file) => handleImageSelected(file, 'about_img')" @image-removed="() => handleImageRemoved('about_img')"></ImageUp>
                    </div>
                  </div>
                    <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                       <div class="d-flex justify-content-between">                        
                        <label for="ceo_title_1" class="form-label">{{$t("ceo_title_1")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('ceo_title_1')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.ceo_title_1 || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_ceo_title_1')" id="ceo_title_1" v-model="form.ceo_title_1" />
                      <span v-for="(error, index) in errors.ceo_title_1" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                    <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                         <div class="d-flex justify-content-between">                        
                        <label for="ceo_title_2" class="form-label">{{$t("ceo_title_2")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('ceo_title_2')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.ceo_title_2 || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_about_title2')" id="ceo_title_2" v-model="form.ceo_title_2" />
                      <span v-for="(error, index) in errors.ceo_title_2" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                       <div class="d-flex justify-content-between">                        
                        <label for="ceo_para" class="form-label">{{$t("ceo_para")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('ceo_para')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.ceo_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.ceo_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.ceo_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="ceo_img" class="form-label">{{$t("ceo_img")}}
                        <span class="text-danger">*</span>
                      </label>
                      <ImageUp :initialImageUrl="form.ceo_img" @image-selected="(file) => handleImageSelected(file, 'ceo_img')" @image-removed="() => handleImageRemoved('ceo_img')"></ImageUp>
                    </div>
                  </div>
                  <div class="card-header alert alert-success">
                      <h4 class="card-title mb-3">{{$t("download_section")}}</h4>
                  </div>
                  <div class="col-12 mt-3">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="download_title" class="form-label">{{$t("download_title")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('download_title')" v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.download_title || 'Generate'}}</span>
                          </button>
                      </div>
                      <input type="text" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_download_title')" id="download_title" v-model="form.download_title" />
                      <span v-for="(error, index) in errors.download_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                       <div class="d-flex justify-content-between">                        
                        <label for="download_para" class="form-label">{{$t("download_para")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('download_para')"  v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.download_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.download_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.download_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                   <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="download_user_link_android" class="form-label">{{$t("download_user_link_android_app")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="url" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_link')" id="download_user_link_android" v-model="form.download_user_link_android" />
                      <span v-for="(error, index) in errors.download_user_link_android" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="download_user_link_apple" class="form-label">{{$t("download_user_link_apple_app")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="url" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_link')" id="download_user_link_apple" v-model="form.download_user_link_apple" />
                      <span v-for="(error, index) in errors.download_user_link_apple" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="download_driver_link_android" class="form-label">{{$t("download_driver_link_android_app")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="url" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_link')" id="download_driver_link_android" v-model="form.download_driver_link_android" />
                      <span v-for="(error, index) in errors.download_driver_link_android" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="mb-3">
                      <label for="download_driver_link_apple" class="form-label">{{$t("download_driver_link_apple_app")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input type="url" :readonly="app_for === 'demo'" class="form-control" :placeholder="$t('enter_link')" id="download_driver_link_apple" v-model="form.download_driver_link_apple" />
                      <span v-for="(error, index) in errors.download_driver_link_apple" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                   <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="download_img1" class="form-label">{{$t("download_img1")}}
                        <span class="text-danger">*</span>
                      </label>
                      <ImageUp :initialImageUrl="form.download_img1" @image-selected="(file) => handleImageSelected(file, 'download_img1')" @image-removed="() => handleImageRemoved('download_img1')"></ImageUp>
                    </div>
                  </div>
                   <div class="col-sm-6 mt-3">
                    <div class="mb-3">
                      <label for="download_img2" class="form-label">{{$t("download_img2")}}
                        <span class="text-danger">*</span>
                      </label>
                      <ImageUp :initialImageUrl="form.download_img2" @image-selected="(file) => handleImageSelected(file, 'download_img2')" @image-removed="() => handleImageRemoved('download_img2')"></ImageUp>
                    </div>
                  </div>
                    <div class="card-header alert alert-success">
                      <h4 class="card-title mb-3">{{$t("contact_section")}}</h4>
                  </div>
                    <div class="col-6">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">                        
                        <label for="contact_heading" class="form-label">{{$t("contact_heading")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('contact_heading')"  v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.contact_heading || 'Generate'}}</span>
                          </button>
                      </div>
                      <input :readonly="app_for === 'demo'" type="text" class="form-control" :placeholder="$t('enter_contact_heading')" id="contact_heading" v-model="form.contact_heading" />
                      <span v-for="(error, index) in errors.contact_heading" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-12 mt-3">
                    <div class="mb-3">
                       <div class="d-flex justify-content-between">                        
                        <label for="contact_para" class="form-label">{{$t("contact_para")}}
                          <span class="text-danger">*</span>
                        </label>
                          <button type="submit" class="btn" @click.prevent="generateAiText('contact_para')"  v-if="enableOpenAI == 1"> 
                            <img src="@assets/images/ai-icon.jpg" alt="ai-icon" width="23px">
                            <span class="text-primary fs-16">{{ai_text.contact_para || 'Generate'}}</span>
                          </button>
                      </div>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.contact_para"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.contact_para" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                    <div class="col-sm-12">
                      <div class="mb-3">
                        <label for="contact_img" class="form-label">{{$t("contact_img")}}
                        <span class="text-danger">*</span>
                      </label>
                      <!-- <ImageUp :initialImageUrl="form.contact_img" @image-selected="(file) => handleImageSelected(file, 'contact_img')" @image-removed="() => handleImageRemoved('contact_img')"></ImageUp> -->
                      <MultiUpload @files-selected="files => handleFilesSelected(files, 'contact_img')" />
                      </div>
                    </div> 
                  <div class="card-header alert alert-success">
                      <h4 class="card-title mb-3">{{$t("address_section")}}</h4>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label for="contact_address_title" class="form-label">{{$t("address_title")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input :readonly="app_for === 'demo'" type="text" class="form-control" :placeholder="$t('enter_address_title')" id="contact_address_title" v-model="form.contact_address_title" />
                      <span v-for="(error, index) in errors.contact_address_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label for="contact_address" class="form-label">{{$t("contact_address")}}
                        <span class="text-danger">*</span>
                      </label>
                      <ckeditor :disabled="app_for === 'demo'" v-model="form.contact_address"  :editor="editor"></ckeditor>
                      <span v-for="(error, index) in errors.contact_address" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label for="contact_phone_title" class="form-label">{{$t("phone_title")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input :readonly="app_for === 'demo'" type="text" class="form-control" :placeholder="$t('enter_phone_title')" id="contact_phone_title" v-model="form.contact_phone_title" />
                      <span v-for="(error, index) in errors.contact_phone_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label for="contact_phone" class="form-label">{{$t("phone_number")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input :readonly="app_for === 'demo'" type="text" class="form-control" :placeholder="$t('enter_phone')" id="contact_phone" v-model="form.contact_phone" />
                      <span v-for="(error, index) in errors.contact_phone" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label for="contact_mail_title" class="form-label">{{$t("mail_title")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input :readonly="app_for === 'demo'" type="text" class="form-control" :placeholder="$t('enter_mail')" id="contact_mail_title" v-model="form.contact_mail_title" />
                      <span v-for="(error, index) in errors.contact_mail_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label for="contact_mail" class="form-label">{{$t("mail")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input :readonly="app_for === 'demo'" type="text" class="form-control" :placeholder="$t('enter_mail')" id="contact_mail" v-model="form.contact_mail" />
                      <span v-for="(error, index) in errors.contact_mail" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label for="contact_web_title" class="form-label">{{$t("web_title")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input :readonly="app_for === 'demo'" type="text" class="form-control" :placeholder="$t('enter_web_title')" id="contact_web_title" v-model="form.contact_web_title" />
                      <span v-for="(error, index) in errors.contact_web_title" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label for="contact_web" class="form-label">{{$t("web")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input :readonly="app_for === 'demo'" type="url" class="form-control" :placeholder="$t('enter_web_address')" id="contact_web" v-model="form.contact_web" />
                      <span v-for="(error, index) in errors.contact_web" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>

                  <div class="card-header alert alert-success">
                      <h4 class="card-title mb-3">{{$t("form_section")}}</h4>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label for="form_name" class="form-label">{{$t("form_name_title")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input :readonly="app_for === 'demo'" type="text" class="form-control" :placeholder="$t('enter_name')" id="form_name" v-model="form.form_name" />
                      <span v-for="(error, index) in errors.form_name" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label for="form_mail" class="form-label">{{$t("form_mail_title")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input :readonly="app_for === 'demo'" type="text" class="form-control" :placeholder="$t('enter_mail')" id="form_mail" v-model="form.form_mail" />
                      <span v-for="(error, index) in errors.form_mail" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label for="form_subject" class="form-label">{{$t("form_subject_title")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input :readonly="app_for === 'demo'" type="text" class="form-control" :placeholder="$t('enter_subject')" id="form_subject" v-model="form.form_subject" />
                      <span v-for="(error, index) in errors.form_subject" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label for="form_message" class="form-label">{{$t("form_message_title")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input :readonly="app_for === 'demo'" type="text" class="form-control" :placeholder="$t('enter_message')" id="form_message" v-model="form.form_message" />
                      <span v-for="(error, index) in errors.form_message" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label for="form_btn" class="form-label">{{$t("form_button_name")}}
                        <span class="text-danger">*</span>
                      </label>
                      <input :readonly="app_for === 'demo'" type="text" class="form-control" :placeholder="$t('enter_button_name')" id="form_btn" v-model="form.form_btn" />
                      <span v-for="(error, index) in errors.form_btn" :key="index" class="text-danger">
                        {{ error }}
                      </span>
                    </div>
                  </div>
                  <div class="col-lg-12">
                    <div class="text-end">
                      <button type="submit" class="btn btn-primary">{{ singlelandingpage ? 'Update' : 'Create' }}</button>
                    </div>
                  </div>
                </div>
              </FormValidation>
            </form>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </Layout>
</template>
