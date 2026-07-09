<script>
import { Head, useForm, router } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import Pagination from "@/Components/Pagination.vue";
import { ref, computed, onMounted, watch } from "vue";
import axios from "axios";
import imageUpload from "@/Components/widgets/imageUpload.vue";
import Multiselect from "@vueform/multiselect";
import FormValidation from "@/Components/FormValidation.vue";
import { useI18n } from 'vue-i18n';
import ImageUpload from '@/Components/ImageUpload.vue';
import { errorMessages } from 'vue/compiler-sfc';
import "@vueform/multiselect/themes/default.css";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import CKEditor from "@ckeditor/ckeditor5-vue";
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import html2canvas from "html2canvas";
export default {
    data() {
        return {
            selectedColor: "#1d114e", 
            editor: ClassicEditor,
            editorData: "",
        };
    },
  components: {
    Layout,
    PageHeader,
    Head,
    Pagination,
    Multiselect,
    FormValidation,
    ImageUpload,
    ckeditor: CKEditor.component,
    app_for: String,
  },
  props: {
    successMessage: String,
    alertMessage: String,
    bannerimages : Object,
    app_for: String,
    app_modules: Array,
    settings: Object,
    validate: Function, // Define the prop to receive the method
  },
  setup(props) {
    const { t } = useI18n();
    

    const form = useForm({
      image: props.bannerimages ? props.bannerimages.image || "" : "",
      appmodule_id: props.bannerimages? props.bannerimages.appmodule_id || "": "",
      title: props.bannerimages? props.bannerimages.title || "": "",
      imageurl: props.bannerimages? props.bannerimages.imageurl || "": "",
      bannertype: props.bannerimages? props.bannerimages.bannertype || "": "",
      description: props.bannerimages? props.bannerimages.description || "":"",
      enable_banner_button: props.bannerimages?.enable_banner_button ?? 0,
      button_name: props.bannerimages ? props.bannerimages.button_name || "":"",
      banner_bg_color: props.bannerimages ? props.bannerimages.banner_bg_color || "":"",
      banner_title_color: props.bannerimages ? props.bannerimages.banner_title_color || "":"",
      banner_description_color: props.bannerimages ? props.bannerimages.banner_description_color || "":"",
      banner_button_color: props.bannerimages ? props.bannerimages.banner_button_color || "":"",
      banner_button_text_color: props.bannerimages ? props.bannerimages.banner_button_text_color || "":""
    });
    const validationRules = {
      image: { required: true },
      bannertype: { required: true },
      title: { required: true },
      description: {required: true},
    };
    const validationRef = ref(null);
    const errors = ref({});
    const successMessage = ref(props.successMessage || '');
    const alertMessage = ref(props.alertMessage || '');

    const dismissMessage = () => {
      successMessage.value = "";
      alertMessage.value = "";
    };
    const selectedColor = ref("#1d114e");
    const applyColor = () => {
      form.banner_bg_color = selectedColor.value;
    };

    const bannerCardStyle = computed(() => ({
      backgroundColor: form.banner_bg_color || "#005555",
    }));
    const bannerTitleStyle = computed(() => ({
      color: form.banner_title_color || "#ffffff",
    }));
    const bannerDescriptionStyle = computed(() => ({
      color: form.banner_description_color || "#ffffff",
    }));
    const bannerButtonStyle = computed(() => ({
      backgroundColor: form.banner_button_color || "#ffffff",
    }));
     const bannerButtonTextStyle = computed(() => ({
      color: form.banner_button_text_color || "#ffffff",
    }));


    const handleSubmit = async () => {
      if(props.app_for == "demo"){
          Swal.fire(t('error'), t('you_are_not_authorised'), 'error');
          return;
      }
      console.log('errors', errors.value);
      errors.value = validationRef.value.validate();
      if (Object.keys(errors.value).length > 0) {
        return;
      }
      try {

        const element = document.getElementById("bannerCard");
        const canvas = await html2canvas(element, {
          scale: window.devicePixelRatio * 2,
          useCORS: true,
          allowTaint: true,
          backgroundColor: null,
          scale: 3,
          logging: false,
        });
        const previewImage = canvas.toDataURL("image/png");
        console.log(previewImage);
        
        const formData = new FormData();
          console.log('formData',formData);
          formData.append("previewimage", previewImage);
          formData.append("title", form.title);
          formData.append("description", form.description);
          formData.append("bannertype", form.bannertype);
          formData.append("appmodule_id", form.appmodule_id);
          formData.append("imageurl", form.imageurl);
          formData.append("image", form.image);
          formData.append("button_name", form.button_name);
          formData.append("enable_banner_button", Number(form.enable_banner_button));
          formData.append("banner_bg_color", form.banner_bg_color || "");
          formData.append("banner_title_color", form.banner_title_color || "");
          formData.append("banner_description_color", form.banner_description_color || "");
          formData.append("banner_button_color", form.banner_button_color || "");
          formData.append("banner_button_text_color",form.banner_button_text_color || "");
        if (Image.value) {
          formData.append("image", form.image);
        }
        let response;
        if (props.bannerimages && props.bannerimages.id) {
          response = await axios.post(`/bannerimage/update/${props.bannerimages.id}`, formData, {
            headers: {
              'Content-Type': 'multipart/form-data',
            },
          });
        } else {
          response = await axios.post('/bannerimage/store', formData);
        }
        if (response.status === 201) {
          successMessage.value = t('banner_image_created_successfully');
          form.reset();
          router.get('/bannerimage');
        } else {
          alertMessage.value = t('failed_to_create_banner_image');
        }
      } catch (error) {
       
        if (error.response && error.response.status === 422) {
          errors.value = error.response.data.errors;
        } else if (error.response && error.response.status === 403) {
          alertMessage.value = error.response.data.alertMessage;
          setTimeout(()=>{
            router.get('/bannerimage');
          },5000)
        } else {
          console.error(t('error_creating_banner_image'), error);
          alertMessage.value = t('failed_to_create_banner_image_catch');
        }
      }

    };

    const handleImageSelected = (file, fieldName) => {
      form[fieldName] = file;
    };

    const handleImageRemoved = (fieldName) => {
      form[fieldName] = null;
    };

    const previewImage = computed(() => {
      if (!form.image) return null;

      if (typeof form.image === "string") {
        return form.image;
      }

      return URL.createObjectURL(form.image);
    });

  
    const iconUrl = props.bannerimages ? props.bannerimages.image :null;

    return {
      form,
      successMessage,
      alertMessage,
      handleSubmit,
      dismissMessage,
      selectedCountry: ref(null),
      selectedTimezone: ref(null),
      validationRules,
      validationRef,
      errors,
      handleImageSelected,
      handleImageRemoved,
      iconUrl,
      selectedColor,
      applyColor,
      previewImage,
      bannerCardStyle,
      bannerTitleStyle,
      bannerDescriptionStyle,
      bannerButtonStyle,
      bannerButtonTextStyle,
    };
  }
};
</script>

<template>
  <Layout>

    <Head title="Banner Image" />
    <PageHeader :title="bannerimages ? $t('edit') : $t('create')" :pageTitle="$t('banner-image')" pageLink="/banner-image"/>
    <BRow>
     <BCard v-if="app_for === 'demo'" no-body id="tasksList">
          <BCardHeader class="border-0">
            <div class="alert bg-warning border-warning fs-18" role="alert">
              <strong> {{$t('note')}} : <em> {{$t('actions_restricted_due_to_demo_mode')}}</em> </strong>
          </div>
        </BCardHeader>
      </BCard>
      <BCol lg="6">
        <BCard no-body id="tasksList">
          <BCardHeader class="border-0">
            <div class="card-body">
                        <h4 class="card-title mb-4">{{$t("banner_background_color")}}</h4>
                        <div class="d-flex align-items-center">
                            <div class="color-picker me-3">
                                <!-- Color Picker -->
                                <label for="colorPicker" class="visually-hidden">Choose a Color</label>
                                <input
                                    type="color"
                                    id="colorPicker"
                                    v-model="selectedColor"
                                    class="rounded-color-picker"
                                />

                                <!-- Hex Code Display -->
                                <label for="colorCode" class="visually-hidden">Color Code</label>
                                <input
                                    type="text"
                                    id="colorCode"
                                    v-model="selectedColor"
                                    class="color-code-input"
                                    readonly
                                />
                            </div>
                            <div>("You can choose and copy color code from here and paste to below input fields")</div>
                        </div>
                    </div>
                   
          </BCardHeader>
          <BCardBody class="border border-dashed border-end-0 border-start-0">

            <form @submit.prevent="handleSubmit">
               <FormValidation :form="form" :rules="validationRules" ref="validationRef">
                    <div class="row">
                      <BCardHeader>
                        <div class="col-sm-12">
                          <div class="mb-3">
                            <label for="banner_bg_color" class="form-label">{{$t("banner_background_color")}}</label>
                            <input type="text" class="form-control" :readonly="app_for === 'demo'" :placeholder="$t('banner_bg_color')" id="banner_bg_color" v-model="form.banner_bg_color" />
                          </div>
                         
                        </div>
                        <div class="col-sm-12">
                            <div class="mb-3">
                                <label for="banner_title_color" class="form-label ">{{$t("banner_title_color")}}</label>
                                <input type="text" class="form-control" :readonly="app_for === 'demo'" :placeholder="$t('banner_title_color')" id="banner_title_color" v-model="form.banner_title_color" />
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="mb-3">
                                <label for="banner_description_color" class="form-label ">{{$t("banner_description_color")}}</label>
                                <input type="text" class="form-control" :readonly="app_for === 'demo'" :placeholder="$t('banner_description_color')" id="banner_description_color" v-model="form.banner_description_color" />
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="mb-3">
                                <label for="banner_button_color" class="form-label ">{{$t("banner_button_color")}}</label>
                                <input type="text" class="form-control" :readonly="app_for === 'demo'" :placeholder="$t('banner_button_color')" id="banner_button_color" v-model="form.banner_button_color" />
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="mb-3">
                                <label for="banner_button_text_color" class="form-label ">{{$t("banner_button_text_color")}}</label>
                                <input type="text" class="form-control" :readonly="app_for === 'demo'" :placeholder="$t('banner_button_text_color')" id="banner_button_text_color" v-model="form.banner_button_text_color" />
                            </div>
                        </div>
                        
                        <!-- <div class="col-sm-12">
                          <div class="mb-2 text-end">
                            <button 
                              type="button"
                              class="btn btn-sm btn-success"
                              @click="applyColor"
                            >
                              Update Colors
                            </button>
                          </div>
                        </div> -->
                      </BCardHeader>
                        <div class="col-sm-12 mt-4">
                            <div class="mb-3">
                                <label for="title" class="form-label">{{$t("title")}}
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" title="title" :placeholder="$t('enter_title')" id="title"  v-model="form.title" :readonly="app_for === 'demo'" 
                                />
                                <span v-if="errors.title" class="text-danger">{{ errors.title }}</span>
                            </div>
                        </div> 
                        <div class="col-sm-12">
                            <div class="mb-3">
                            <label>{{$t("description")}}</label>
                            <ckeditor v-model="form.description"  :editor="editor" :disabled="app_for === 'demo'" ></ckeditor>
                            <span v-for="(error, index) in errors.description" :key="index" class="text-danger">
                            {{ error }}
                            </span>
                            </div>
                        </div>
                        <!-- SERVICE  TYPE -->
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">
                                    {{ $t("banner_type") }} <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" v-model="form.bannertype" :disabled="app_for === 'demo'" >
                                    <option disabled value="">{{ $t('select') }}</option>
                                    <option value="0">{{ $t('default') }}</option>
                                    <option value="1">{{ $t('app_modules') }}</option>
                                </select>
                                <span v-for="(e,i) in errors.bannertype" :key="i" class="text-danger">{{ e }}</span>
                            </div>
                        </div>
                        <!-- App Module Dropdown -->
                        <div>
                            <div class="col-md-12" v-if="form.bannertype == 1">
                                <div class="mb-3">
                                    <label class="form-label">
                                    Select item <span class="text-danger"></span>
                                    </label>
                                    <select class="form-select" v-model="form.appmodule_id" :disabled="app_for === 'demo'" >
                                    <option disabled value="">Select</option>
                                    <option
                                        v-for="module in app_modules"
                                        :key="module.id"
                                        :value="module.id"
                                    >
                                    {{ module.name }}
                                    </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12" v-else>          
                                <div  class="mb-3">
                                    <label class="form-label">Image URL</label>
                                    <input
                                        type="url"
                                        class="form-control"
                                        v-model="form.imageurl"
                                        placeholder="https://example.com/banner.jpg" :disabled="app_for === 'demo'"
                                    />
                                </div>
                            </div>  
                        </div>
                        <!-- Default Input Field -->
                        <div class="col-sm-12">
                            <div class="mb-3">
                                <label for="onboarding_image" class="form-label d-flex">{{$t("banner-image")}}
                                    <span><h5 class="text-muted mt-1 mb-0 fs-13">(500px x 100px)</h5></span>
                                    <span class="text-danger">*</span>
                                </label>
                                
                                <ImageUpload  :readonly="app_for === 'demo'"  :imageType="'banner'" :flexStyle="'0 0 min(500px ,100%)'" :aspectRatio="'5 / 1'"   :initialImageUrl="form.image"  
                                    @image-selected="(file) => handleImageSelected(file, 'image')" @image-removed="() => handleImageRemoved('image')">
                                </ImageUpload>
                                <span v-for="(error, index) in errors.image" :key="index" class="text-danger">{{ error }}</span>
                            </div>
                        </div>  

                        <div class="col-sm-12">
                      <div class="mb-3">
                        <label class="form-label">Enable Button</label>
                        <div class="form-check form-switch">
                          <input :disabled="app_for === 'demo'"
                            class="form-check-input"
                            type="checkbox"
                            v-model="form.enable_banner_button"
                            :true-value="1"
                            :false-value="0"
                          />
                        </div>
                      </div>
                    </div>

                    <div class="col-sm-12" v-if="form.enable_banner_button">
                      <div class="mb-3">
                        <label class="form-label">Button Text</label>
                        <input
                          type="text"
                          class="form-control"
                          placeholder="Enter button text"
                          v-model="form.button_name"
                        />
                      </div>
                    </div>
                        
                  <div class="col-lg-12">
                    <div class="text-end">
                      <button type="submit" class="btn btn-primary"> {{ bannerimages ? $t('update') : $t('save') }}</button>
                    </div>
                  </div>
                </div>                 
                
              </FormValidation>
            </form>
          </BCardBody>
        </BCard>
      </BCol>
      <!---MOBILE VIEW--->
        <BCol lg="6">
            <BCard no-body id="tasksList">
                <BCardHeader class="border-0"><h5>{{$t("mobile_view")}}</h5></BCardHeader>
                <BCardBody class="border border-dashed border-end-0 border-start-0">
                    <div class="col-sm-12">
                      <div class="mb-3" style="display: grid;place-items:center;">
                          
                        <div id="bannerPreview" class="banner"><div class="overlap">
                          <div style="card cards">
                           <div id="bannerCard" class="cards banner-flex" :style="bannerCardStyle">
  
                            <div class="banner-left">
                             <img 
                              v-if="previewImage"
                              :src="previewImage"
                              class="preview-img"
                            />
                            </div>
                            <div class="banner-right">
                              <h5 class="banner-title" :style="bannerTitleStyle">{{ form.title }}</h5>
                              <div class="banner-description" :style="bannerDescriptionStyle" v-html="form.description"></div>
                               <button
                                  v-if="form.enable_banner_button"
                                  class="banner-btn"
                                   :style="{ ...bannerButtonStyle, ...bannerButtonTextStyle }"
                                >
                                  {{ form.button_name  || 'Click Here' }}
                                </button>
                            </div>
                            </div>
                          </div>
                        </div>  
                      </div>
                      </div>
                    </div>  
                </BCardBody>
            </BCard>
        </BCol>
    </BRow>
    <div>
      <div v-if="successMessage" class="custom-alert alert alert-success alert-border-left fade show" role="alert"
        id="alertMsg">
        <div class="alert-content">
          <i class="ri-notification-off-line me-3 align-middle"></i>
          <strong>Success</strong> - {{ successMessage }}
          <button type="button" class="btn-close btn-close-success" @click="dismissMessage"
            aria-label="Close Success Message"></button>
        </div>
      </div>

      <div v-if="alertMessage" class="custom-alert alert alert-danger alert-border-left fade show" role="alert"
        id="alertMsg">
        <div class="alert-content">
          <i class="ri-notification-off-line me-3 align-middle"></i>
          <strong>Alert</strong> - {{ alertMessage }}
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
<style scoped>
.banner{
  width: 299px;
  height: 678px;
  background-image: url("/images/new-banner.jpeg");
  background-position: center;
  background-repeat: no-repeat;
  background-size: contain;
}
.rtl .banner{
  width: 300px;
  height: 666px;
  background-image: url("/images/new-banner.jpeg");
  background-position: center;
  background-repeat: no-repeat;
  background-size: contain;
}
.overlap{
  position: relative;
  left: -1px;
  padding: 0 15px;
  width: 301px;
  height: 450px;
  background: #6464646b;
 
}

.rtl .overlap{
  position: relative;
  right: 10px;
  padding: 0 15px;
  width: 280px;
  height: 550px;
  background: #6464646b;
 
}
.cards{
  position: relative;
  top: 463px;
  left: -1px;
  z-index: 2;
  width: 272px;
  height: 108px;
  padding: 10px;
  border-radius: 15px 15px 15px 15px;
  /* background-color:  var(--banner_bg_color); */
}

.rtl .cards{
  position: relative;
  top: 316px;
  left: 0px;
  z-index: 2;
  width: 250px;
  height: 200px;
  padding:10px;
  border-radius: 15px 15px 0 0 ;
}
.banner-preview img{
  width:100%;
  border-radius:10px;
}

.banner-title{
  font-size:16px;
  font-weight:600;
}

.banner-description{
  font-size:13px;
  margin-top:5px;
}

.color-picker {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 1rem;
  font-family: Arial, sans-serif;
}

.visually-hidden {
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
  position: absolute;
}

.rounded-color-picker {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.rounded-color-picker:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 10px rgba(0, 0, 0, 0.2);
}

.color-code-input {
  width: 120px;
  padding: 5px;
  border: 1px solid #ccc;
  border-radius: 5px;
  text-align: center;
  font-size: 1rem;
  color: #333;
  background-color: #f9f9f9;
}

.color-code-input:focus {
  outline: none;
  border-color: #777;
}

.banner-title{
  /* color: var(--banner_title_color); */
}
.banner-description{
  /* color: var(--banner_description_color); */
  margin-top: 4px;
}
.banner-flex{
  display:flex;
  align-items:center;
  gap:10px;
}
.banner-left{
  flex:0 0 80px;
}
.banner-left img{
  width: 118px;
  height: 63px;
  object-fit:cover;
  border-radius:10px;
}
.banner-right{
  flex:1;
}
.banner-title{
  font-size:14px;
  font-weight:600;
  margin-bottom:4px;
}
.banner-description{
  font-size:12px;
  line-height:1.3;
}
.banner-btn{
  margin-top: 6px;
  position: absolute;
  padding: 1px 10px;
  font-size: 10px;
  border-radius: 6px;
  border: none;
  background: #ffffff;
  color: #000;
  cursor: pointer;
  /* background-color: var(--banner_button_color); */
  top: 73px;
}
/* media query */
@media (min-width: 100px) and (max-width: 320px) {
.banner {
  width: 234px;
  height: 683px;
}
.overlap {
  left: -10px;
  width: 255px;
  height: 429px;
}
.cards {
  top: 439px;
  left: -11px;
  width: 238px;
  height: 83px;
  padding: 10px;
}
}
</style>