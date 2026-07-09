<script>
import LandingHeader from "@/Components/singlelandingheader.vue";
import LandingFooter from "@/Components/singlelandingfooter.vue";
import { Autoplay, Navigation, Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";
import "swiper/css";
import "swiper/css/autoplay";
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from "vue";
import axios from "axios";
import FormValidation from "@/Components/FormValidation.vue";


export default {
  data() {
    return {
      animationClass: 'slide-in-right',
      activeStep: 0,
      direction: 1,        // 1 = down, -1 = up
      maxStep: 3,
      stepHeight: 195,
      userType: 'user',  
      driverType: 'driver',
      Autoplay, Navigation, Pagination,
    }
  },
  components: {
    Swiper,
    SwiperSlide,  
    LandingHeader,
    LandingFooter,
      FormValidation,
        Head
  },
  props:{
   home:'',
   about:'',
   user_app:'',
   driver_app:'',
   contact:'',
   singlelandingpage: Object,
  },  
   setup(props) {
    const form = useForm({
        name: props.singlelandingpage ? props.singlelandingpage.name || "" : "",
        mail: props.singlelandingpage ? props.singlelandingpage.mail || "" : "",
        subject: props.singlelandingpage ? props.singlelandingpage.subject || "" : "",
        comments: props.singlelandingpage ? props.singlelandingpage.comments || "" : "",
    });

    const validationRules = {
        name: { required: true },
        mail: { required: true },
        subject: { required: true },
        comments: { required: true },
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
      errors.value = validationRef.value.validate();
      console.log("errors.value,",errors.value);
      if (Object.keys(errors.value).length > 0) {
        return;       
      }
        if (enablerecaptcha == 1) {
            const recaptchaResponse = grecaptcha.getResponse();
            console.log("recaptchaResponse", recaptchaResponse);
            if (!recaptchaResponse) {
            alertMessage.value = 'Failed to get reCAPTCHA.';
            return;
            }

            // Add reCAPTCHA response to the request payload
            form.data().recaptchaResponse = recaptchaResponse;
        }
     
      try {
        let response;
        const requestData = { ...form.data(),
                            //   recaptchaResponse, // Add the reCAPTCHA response to the request payload
                             };
        response = await axios.post('/single-landing-page/contactmessage',requestData);
        console.log("response",response); 

        if (response.status === 201) {
          successMessage.value = 'Message saved successfully.';
          form.reset();
         // Use router.push instead of router.get
        } else {
          alertMessage.value = 'Failed to save Message.';
        }
      } catch (error) {
        if (error.response && error.response.status === 422) {
          errors.value = error.response.data.errors;
        } else {
          console.error('Error saving Message:', error);
          alertMessage.value = 'Failed to save Message.';
        }
      }
    };

    return {
        Pagination, 
        form,
        successMessage,
        alertMessage,
        handleSubmit,
        dismissMessage,
        validationRules,
        validationRef,
        errors,
        recaptchaKey: window.recaptchaKey,    
        enablerecaptcha: window.enablerecaptcha    
    };
  },
 mounted() {
  window.myRecaptchaMethod = this.myRecaptchaMethod;
  this.loadRecaptcha();
  const text = document.querySelector(".line-text");
    const container = document.querySelector(".line-container");
    this.$nextTick(() => {

    const texts = document.querySelectorAll(".line-text");

    texts.forEach((text) => {

        const width = text.offsetWidth;

        const tl = gsap.timeline({
        repeat: -1,
        defaults: { ease: "power2.inOut", duration: 1 }
        });

        tl.to(text, { x: width })
        .to(text, { x: 0 });

    });

    });
    gsap.fromTo(".about-img",
        { x: 120 },
        {
            x: -120,
            ease: "none",
            scrollTrigger: {
            trigger: ".about-section",
            start: "top bottom",
            end: "bottom top",
            scrub: true
            }
        }
        );
        gsap.fromTo(".ceo-img",
        { x: 190 },
        {
            x: -190,
            ease: "none",
            scrollTrigger: {
            trigger: ".about-ceo-section",
            start: "top bottom",
            end: "bottom top",
            scrub: true
            }
        }
        );
  // ===== HERO SCROLL ANIMATION =====
  this.handleScroll = () => {

    const phones = document.querySelectorAll('.phone');
    const heroSection = document.querySelector('.hero-wrapper');

    if (!heroSection) return;

    const rect = heroSection.getBoundingClientRect();
    const windowHeight = window.innerHeight;

    if (rect.top < windowHeight && rect.bottom > 0) {

      const progress = 1 - (rect.bottom / (windowHeight + rect.height));
      const moveAmount = progress * 300;

     phones.forEach((phone) => {

    let direction = 1;

    if (phone.classList.contains('far-left-phone')) direction = -1.5;
    if (phone.classList.contains('left-phone')) direction = -1;
    if (phone.classList.contains('center-phone')) direction = 0.3;
    if (phone.classList.contains('right-phone')) direction = 1;
    if (phone.classList.contains('far-right-phone')) direction = 1.5;
      // 🚫 Disable center-phone movement on mobile
  if (phone.classList.contains('center-phone')) {
    if (window.innerWidth <= 768) {
      direction = 0; // no movement on mobile
    } else {
      direction = 0.3;
    }
  }

    phone.style.setProperty('--translateX', `${moveAmount * direction}px`);
    });

    }
  };

  window.addEventListener('scroll', this.handleScroll);

  // ===== Your Existing Hero Animation =====
  setTimeout(() => {
    this.animationClass = 'slide-rise'
  }, 700)

  setTimeout(() => {
    this.animationClass = 'slide-out-right'
  }, 1400)

  this.startTaxiAnimation();
},


  beforeUnmount() {
    clearInterval(this.taxiTimer)
  },
  

  methods: {
    stripHtmlTags(content) {
            const parser = new DOMParser();
            const parsedContent = parser.parseFromString(content, 'text/html');
            return parsedContent.body.textContent || "";
            },

    //recaptcha
     loadRecaptcha() {
        const script = document.createElement('script');
        script.src = 'https://www.google.com/recaptcha/api.js';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
        },
        myRecaptchaMethod(response) {
            this.recaptchaToken = response;
            // console.log('Captcha completed:', this.recaptchaToken);
        },
    startTaxiAnimation() {
      this.taxiTimer = setInterval(() => {

        this.activeStep += this.direction

        // If reached step 3 (index 2), reverse direction
        if (this.activeStep === this.maxStep) {
          this.direction = -1
        }

        // If reached step 1 (index 0), reverse direction
        if (this.activeStep === 0) {
          this.direction = 1
        }

      }, 2000)
    }
    
  }
};
</script>
<template>
  <div style="overflow:hidden;">
  <!--Hero section -->
  <section class="hero-wrapper d-flex align-items-center">
    <LandingHeader /> 
    <div class="container-fluid px-0 py-0">
      <div class="hero-card mx-auto" id="home">
        <!-- Navbar -->
        <!-- Hero Content -->
        <div class="text-center py-5 position-relative">

          <h1 class="hero-title text-white mt-5 py-5">
          {{ stripHtmlTags(singlelandingpage.hero_para)}}
          </h1>       
          <!-- Phones -->
          <div class="phone-group mt-5">
            <img :src="singlelandingpage.hero_img_1_url" class="phone left-phone">
            <img :src="singlelandingpage.hero_img_2_url" class="phone far-right-phone">
            <img :src="singlelandingpage.hero_img_3_url" class="phone far-left-phone">
            <img :src="singlelandingpage.hero_img_4_url" class="phone center-phone">
            <img :src="singlelandingpage.hero_img_5_url" class="phone right-phone">
          </div>
        </div>
      </div>
    </div>
  </section>
  <!--End Hero section -->
  <!--Advantage of app -->
   <section style ="background: white;">
      <!-- features -->
        <div class="features pt-5">
            <div class="container"> 
                    <h1 class="fw-bolder text-center text-black">
                       {{ singlelandingpage.adv_title }}
       
                    </h1>
                    <p class="text-center text-muted mt-4 mb-3" style ="font-size: 15px;">{{ stripHtmlTags(singlelandingpage.adv_para)}}
                    </p>
                    <!-- <BContainer class="mt-5"> -->
                    <div class="routes-swiper rounded">
                        <swiper class="navigation-swiper rounded teamMember slider position-relative" :loop="true"
                            :modules="[Autoplay]"
                            :centeredSlides="true"
                            :slides-per-view="3"
                            :space-between="30"
                            :autoplay="{
                              delay: 2500,
                               disableOnInteraction: false
                            }"
                            :breakpoints="{
                                320: { // Mobile screens
                                slidesPerView: 1,
                                spaceBetween: 10
                                },
                                640: { // Small tablets
                                slidesPerView: 2,
                                spaceBetween: 15
                                },
                                768: { // Tablets
                                slidesPerView: 2,
                                spaceBetween: 50
                                },
                                1024: { // Desktops and larger screens
                                slidesPerView: 3,
                                spaceBetween: 20
                                },
                                1440: { // Desktops and larger screens
                                slidesPerView: 3,
                                spaceBetween: 20
                                }
                            }">
                                <swiper-slide>
                                    <div class="slide">
                                        <div class="card  features-card rounded-4 border shadow-lg text-center p-3" style="width: 270px;">

                                            <!-- Header with Icon Background -->
                                            <div class="mb-3">
                                                 <div class="feature-card">
                                                    <h4 class="fw-bold advantage-card-description">{{ singlelandingpage.adv_box1_title}}</h4>
                                                    <p class="text-light">
                                                      {{ stripHtmlTags(singlelandingpage.adv_box1_para) }}
                                                    </p>
                                                    <div class="mock-box">
                                                      <video class="mock-video" autoplay muted loop playsinline>
                                                          <source :src="singlelandingpage.adv_box1_img_url" type="video/mp4">
                                                      </video>
                                                    </div>
                                                  </div>
                                            </div>
                                        </div>
                                    </div>

                                </swiper-slide>
                                <swiper-slide>
                                    <div class="slide">
                                      <div class="card  features-card rounded-4 border shadow-lg text-center p-3" style="width: 270px;">
                                        <!-- Header with Icon Background -->
                                        <div class="mb-3">
                                          <div class="feature-card">
                                            <h4 class="fw-bold advantage-card-description">{{ singlelandingpage.adv_box2_title}}</h4>
                                          <p class="text-light">
                                              {{ stripHtmlTags(singlelandingpage.adv_box2_para) }}
                                          </p>
                                          <div class="mock-box">
                                            <video class="mock-video" autoplay muted loop playsinline>
                                              <source :src="singlelandingpage.adv_box2_img_url" type="video/mp4">
                                            </video>
                                          </div>
                                        </div>
                                      </div>               
                                    </div>
                                  </div>
                                </swiper-slide>
                                
                                <swiper-slide>
                                  <div class="slide">
                                    <div class="card  features-card rounded-4 border shadow-lg text-center p-3" style="width: 270px;">
                                      <!-- Header with Icon Background -->
                                      <div class="mb-3">
                                        <div class="feature-card ">
                                          <h4 class="fw-bold advantage-card-description">{{ singlelandingpage.adv_box3_title}}</h4>
                                          <p class="text-light">
                                             {{ stripHtmlTags(singlelandingpage.adv_box3_para) }}
                                          </p>
                                          <div class="mock-box">
                                            <video class="mock-video" autoplay muted loop playsinline>
                                              <source :src="singlelandingpage.adv_box3_img_url" type="video/mp4">
                                            </video>
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                </swiper-slide>                               
                                <swiper-slide>
                                  <div class="slide">
                                    <div class="card  features-card rounded-4 border shadow-lg text-center p-3" style="width: 270px;">

                                      <!-- Header with Icon Background -->
                                      <div class="mb-3">
                                        <div class="feature-card bg-beige">
                                          <h4 class="fw-bold advantage-card-description">{{ singlelandingpage.adv_box4_title}}</h4>
                                          <p class="text-light">
                                             {{ stripHtmlTags(singlelandingpage.adv_box4_para) }}
                                          </p>
                                          <div class="mock-box">
                                            <video class="mock-video" autoplay muted loop playsinline>
                                              <source :src="singlelandingpage.adv_box4_img_url" type="video/mp4">
                                            </video>
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                </swiper-slide>
                                <swiper-slide>
                                  <div class="slide">
                                    <div class="card  features-card rounded-4 border shadow-lg text-center p-3" style="width: 270px;">

                                      <!-- Header with Icon Background -->
                                      <div class="mb-3">
                                        <div class="feature-card bg-beige">
                                          <h4 class="fw-bold advantage-card-description">{{ singlelandingpage.adv_box5_title}}</h4>
                                          <p class="text-light">
                                           {{ stripHtmlTags(singlelandingpage.adv_box5_para) }}
                                          </p>
                                          <div class="mock-box">
                                            <video class="mock-video" autoplay muted loop playsinline>
                                              <source :src="singlelandingpage.adv_box5_img_url" type="video/mp4">
                                            </video>
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                </swiper-slide>
                               

                               
                                <!-- <div class="swiper-button-next bg-white rounded-circle shadow-lg" style="top:52px;position: absolute;"></div> -->
                                <!-- <div class="swiper-button-prev bg-white rounded-circle shadow-lg" style="left:3px;top:52px;position: absolute;"></div> -->
                        </swiper>
                    </div>
                <!-- </BContainer> -->
            </div>
</div>
</section>
  <!--End Advantage of app -->  
  <!--user and driver app --> 
  <section class="ride-section" id="driver&userapps">
    <div class="container position-relative d-block"> 
      <div class="role-toggle-wrapper mb-5 text-center">
        <h1 class="text-light section-title">
          {{ singlelandingpage.app_works_title}}
        </h1>
        <p class="top-description">
         {{ stripHtmlTags(singlelandingpage.app_works_para) }}
        </p>
        <div class="text-center mb-4">
          <div class="btn-group p-1 bg-dark rounded-pill shadow">
            <button 
              @click="userType = 'user'"
              :class="['btn rounded-pill px-4', userType === 'user' ? 'btn-primary' : 'text-white']">
              {{ singlelandingpage.app_works_user_title}}
            </button>

            <button 
              @click="userType = 'driver'"
              :class="['btn rounded-pill px-4', userType === 'driver' ? 'btn-primary' : 'text-white']">
                {{ singlelandingpage.app_works_driver_title}}
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class=" position-relative">
      <div class="row align-items-center">
        <!-- LEFT SIDE -->
        <div class="col-lg-6 text-center text-lg-start mb-2 mb-lg-0">
          <div class="phone-wrapper">
            <!-- User Image -->
            <img 
              v-if="userType === 'user'"
              :src="singlelandingpage.app_user_img_url"
              class="user-phone-img " 
              alt="User App" data-aos="zoom-in-down">
            <!-- Driver Image -->
            <img 
              v-else
              :src="singlelandingpage.app_driver_img_url"
              class="driver-phone-img " 
              alt="Driver App" data-aos="zoom-in-down" >
          </div>
        </div>
        <!-- RIGHT SIDE -->
        <div class="col-lg-6 position-relative">
          <div class="timeline">
            <!-- Moving Taxi (only for user if you want) -->
            <div
              v-if="userType === 'user'"
              class="moving-taxi"
              :style="{ top: (activeStep * stepHeight) - 5 + 'px' }">
              <img src="/images/whitecar.png" alt="Taxi">
            </div>

            <!-- ================= USER CONTENT ================= -->
            <template v-if="userType === 'user'" >

              <div class="timeline-item" :class="{ active: activeStep === 0 }">
                <span class="circle"></span>
                <div class="content">
                  <h5>   {{ singlelandingpage.user_box1_title}}</h5>
                  <p>{{ stripHtmlTags(singlelandingpage.user_box1_para) }}</p>
                </div>
              </div>

              <div class="timeline-item" :class="{ active: activeStep === 1 }">
                <span class="circle"></span>
                <div class="content">
                  <h5> {{ singlelandingpage.user_box2_title}}</h5>
                  <p>{{ stripHtmlTags(singlelandingpage.user_box2_para) }}</p>
                </div>
              </div>

              <div class="timeline-item" :class="{ active: activeStep === 2 }">
                <span class="circle"></span>
                <div class="content">
                  <h5>{{ singlelandingpage.user_box3_title}}</h5>
                  <p>{{ stripHtmlTags(singlelandingpage.user_box3_para) }}</p>
                </div>
              </div>

              <div class="timeline-item" :class="{ active: activeStep === 3 }">
                <span class="circle"></span>
                <div class="content">
                  <h5>{{ singlelandingpage.user_box4_title}}</h5>
                  <p>{{ stripHtmlTags(singlelandingpage.user_box4_para) }}</p>
                </div>
              </div>

            </template>

            <!-- ================= DRIVER CONTENT ================= -->
            <template v-else>
              <div 
                v-if="
                driverType === 'driver'"
                class="moving-taxi"
                :style="{ top: (activeStep * stepHeight) - 5 + 'px' }">
                <img src="/images/whitecar.png" alt="Taxi">
              </div>
              <div class="timeline-item":class="{ active: activeStep === 0 }">
                <span class="circle"></span>
                <div class="content">
                  <h5>{{ singlelandingpage.driver_box1_title}}</h5>
                  <p>{{ stripHtmlTags(singlelandingpage.driver_box1_para) }}</p>
                </div>
              </div>
              <div class="timeline-item":class="{ active: activeStep === 1 }">
                <span class="circle"></span>
                <div class="content">
                  <h5>{{ singlelandingpage.driver_box2_title}}</h5>
                  <p>{{ stripHtmlTags(singlelandingpage.driver_box2_para) }}</p>
                </div>
              </div>
              <div class="timeline-item":class="{ active: activeStep === 2 }">
                <span class="circle"></span>
                <div class="content">
                  <h5>{{ singlelandingpage.driver_box3_title }}</h5>
                  <p>{{ stripHtmlTags(singlelandingpage.driver_box3_para) }}</p>
                </div>
              </div>
              <div class="timeline-item":class="{ active: activeStep === 3 }">
                <span class="circle"></span>
                <div class="content">
                  <h5>{{ singlelandingpage.driver_box4_title }}</h5>
                  <p>{{ stripHtmlTags(singlelandingpage.driver_box4_para) }}</p>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!--end of user and driver app -->
   <!-- restart section -->
  <section class="why_us pt-5">
    <div class="container">
      <h1 class="fw-bolder text-center mb-5">
        {{ singlelandingpage.why_choose_title }}
      </h1>
      <div class="row align-items-center">
        <!-- Left Features -->
          <div class="col-lg-4" >
            <div class="feature-box" data-aos="fade-right">
              <div class="feature-number">01</div>
                <div class="feature-content">
                  <h5>{{ singlelandingpage.why_choose_box1_title }}</h5>
                  <p>{{ stripHtmlTags(singlelandingpage.why_choose_box1_para) }}</p>
                </div>
              </div>

              <div class="feature-box" data-aos="fade-right" data-aos-delay="1500">
                <div class="feature-number">03</div>
                  <div class="feature-content">
                    <h5>{{ singlelandingpage.why_choose_box3_title }}</h5>
                    <p>{{ stripHtmlTags(singlelandingpage.why_choose_box3_para) }}</p>
                  </div>
                </div>

              </div>
              <!-- Center Phones -->
              <div class="col-lg-4 phone-wrapper" data-aos="fade-up">
                <img :src="singlelandingpage.why_choose_img_url" class="feature-phone-img" alt="App Screenshot">
              </div>
              <!-- Right Features -->
              <div class="col-lg-4">
                <div class="feature-box" data-aos="fade-left" data-aos-delay="1000">
                  <div class="feature-number">02</div>
                    <div class="feature-content">
                      <h5>{{ singlelandingpage.why_choose_box2_title }}</h5>
                      <p>{{ stripHtmlTags(singlelandingpage.why_choose_box2_para) }}</p>
                    </div>
                  </div>
                  <div class="feature-box" data-aos="fade-left" data-aos-delay="2000">
                    <div class="feature-number">04</div>
                      <div class="feature-content">
                        <h5>{{ singlelandingpage.why_choose_box4_title }}</h5>
                        <p>{{ stripHtmlTags(singlelandingpage.why_choose_box4_para) }}</p>
                      </div>
                    </div>
                  </div>
                </div>
    </div>
  </section>
  <!---about section -->
   <section class="about-section" id="about">
        <div class="container-fluid">
            <div class="row align-items-center">
                <!-- Image Side -->
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="img-wrapper">
                        <img :src="singlelandingpage.about_img_url"
                        alt="About Image" class="about-img">
                    </div>
                </div>
                <!-- Content Side -->
                <div class="col-lg-6">
                    <h2 class="about-title ceo-heading ">
                        <span class="line-container ms-4">
                            <span style="color:#495057">{{ singlelandingpage.about_title_1 }}</span><span class="line-text mt-4"> {{ singlelandingpage.about_title_2 }}</span>
                            <span class="vertical-line"></span>
                        </span>
                    </h2>
                    <p class="about-text">
                       {{ stripHtmlTags(singlelandingpage.about_para) }}
                    </p>
                  
                </div>
            </div>
        </div>
    </section>
     <!-- CEO Section -->
    <section class="about-ceo-section">
        <div class="container-fluid">
            <div class="row align-items-start text-center text-lg-start">
                <!-- CEO Content -->
                <div class="col-lg-6 p-5">
                    <h2 class="about-title ceo-heading ceo-animated">
                         <span class="line-container ms-4">
                            <span style="color:#495057">   {{ singlelandingpage.ceo_title_1 }}</span><span class="line-text mt-4">    {{ singlelandingpage.ceo_title_2 }}</span>
                            <span class="vertical-line"></span>
                        </span>
                    </h2>
                    <p class="about-text">
                         {{ stripHtmlTags(singlelandingpage.ceo_para) }}
                    </p>
                    
                </div>
                <!-- CEO Image -->
                <div class="col-lg-6 text-center">
                    <div class="ceo-img-wrapper">
                        <img :src="singlelandingpage.ceo_img_url" alt="CEO Image" class="ceo-img">
                    </div>
                </div>
            </div>
        </div>
    </section>
 <!--downoad section -->
 <section class="app-download-section py-5">
  <div class="container">
    <div class="app-card">
      <div class="row align-items-center">
        <!-- Left Content -->
        <div class="col-lg-6 text-light">
          <h2 class="fw-bold display-5 mb-3 text-white">
            {{ singlelandingpage.download_title }}
          </h2>

          <p class="mb-4 text-light">
           {{ stripHtmlTags(singlelandingpage.download_para) }}
          </p>
          <h5 class="mt-3"style="color: grey;">User App</h5>
          <div class="d-flex gap-3 flex-wrap">
            <BLink type="button" class="btn btn-primary btn-label waves-effect waves-light" :href="singlelandingpage.download_user_link_apple" target="_blank"><i class="ri-apple-fill label-icon align-middle fs-16 me-2"></i>App Store</BLink>
            <BLink type="button" class="btn btn-success btn-label waves-effect waves-light" :href="singlelandingpage.download_user_link_android" target="_blank"><i class="ri-google-play-fill label-icon align-middle fs-16 me-2"></i>Play Store</BLink>
          </div>
          <h5 class="mt-3"style="color: grey;">Driver App</h5>
          <div class="d-flex gap-3 flex-wrap">
            <BLink type="button" class="btn btn-primary btn-label waves-effect waves-light" :href="singlelandingpage.download_driver_link_apple" target="_blank"><i class="ri-apple-fill label-icon align-middle fs-16 me-2" ></i>App Store</BLink>
            <BLink type="button" class="btn btn-success btn-label waves-effect waves-light" :href="singlelandingpage.download_driver_link_android" target="_blank"><i class="ri-google-play-fill label-icon align-middle fs-16 me-2"></i>Play Store</BLink>
          </div>
        </div>
        <!-- Right Images -->
        <div class="col-lg-6 position-relative text-center mb-4 mb-lg-0">
          <img :src="singlelandingpage.download_img1_url" class="phone-img phone-1 img-fluid"  data-aos="zoom-in-down">
          <img :src="singlelandingpage.download_img2_url" class="phone-img phone-2 img-fluid" data-aos="zoom-in-up">
        </div>
      </div>
    </div>
  </div>
</section>
<!-- contact us section -->
  <section class="contact-section" id="contact">
     <video autoplay muted loop playsinline class="bg-video">
        <source :src="singlelandingpage.contact_img_url" type="video/mp4">
      </video>
      <div class="contact-overlay"></div>
      <div class="container contact-content">
        <!-- Section Title -->
        <div class="text-center mb-5">
          <h2 class="fw-bold text-light">{{ singlelandingpage.contact_heading }}</h2>
          <p>{{ singlelandingpage.contact_para }}</p>
        </div>
        <div class="row align-items-center">
          <!-- Left Contact Info -->
          <div class="col-lg-6 contact-info">
            <div class="d-flex align-items-start">
              <img src= "/images/location.gif" class="contact-icon"></img>
              <div class="contact-description">
                <strong>{{ singlelandingpage.contact_address_title }}:</strong>
                <p>{{ stripHtmlTags(singlelandingpage.contact_address) }}</p>
              </div>
            </div>
            <div class="d-flex align-items-start">
              <img src= "/images/phone.gif" class="contact-icon"></img>
              <div class="contact-description">
                <strong>{{ singlelandingpage.contact_phone_title }}:</strong>
                <p>{{ singlelandingpage.contact_phone }}</p>
              </div>
            </div>
            <div class="d-flex align-items-start">
              <img src= "/images/envelope.gif" class="contact-icon"></img>
              <div class="contact-description">
                <strong>{{ singlelandingpage.contact_mail_title }}:</strong>
                <p>{{ singlelandingpage.contact_mail }}</p>
              </div>
            </div>
            <div class="d-flex align-items-start ">
              <img src= "/images/globe.gif" class="contact-icon"></img>
              <div class="contact-description">
                <strong>{{ singlelandingpage.contact_web_title }}:</strong>
               <p><BLink :href="singlelandingpage.contact_web" target="_blank" class="text-light">{{ singlelandingpage.contact_web }}</BLink></p>
              </div>
            </div>
          </div>
          <!-- Right Form -->
          <div class="col-lg-6">
            <div class="contact-card">
              <h5>Send Message</h5>
              <form @submit.prevent="handleSubmit">
                <FormValidation :form="form" :rules="validationRules" ref="validationRef">
                                   
                <div class="mb-3">
                 <input name="name" id="name" type="text"
                    class="form-control" :placeholder="singlelandingpage.form_name" v-model="form.name">
                    <span v-for="(error, index) in errors.name" :key="index" class="text-danger">{{ error }}</span>
                </div>
                <div class="mb-3">
                  <input name="email" id="email" type="email"
                    class="form-control" :placeholder="singlelandingpage.form_mail" v-model="form.mail">
                    <span v-for="(error, index) in errors.mail" :key="index" class="text-danger">{{ error }}</span>
                </div>
                <div class="mb-3">
                  <input name="subject" id="subject" type="subject"
                    class="form-control" :placeholder="singlelandingpage.form_subject" v-model="form.subject">
                    <span v-for="(error, index) in errors.subject" :key="index" class="text-danger">{{ error }}</span>
                </div>
                <div class="mb-3">
                  <textarea name="comments" id="comments" rows="3"
                    class="form-control"
                    :placeholder="singlelandingpage.form_message" v-model="form.comments">
                  </textarea>
                  <span v-for="(error, index) in errors.comments" :key="index" class="text-danger">{{ error }}</span>
                </div>
                <div class="mb-3 contact-recaptcha" v-if="enablerecaptcha == 1">
                  <div class="g-recaptcha" :data-sitekey="recaptchaKey" data-callback="myRecaptchaMethod"></div>                 
                </div>
                <button type="submit" id="submit" name="send" class="btn btn-danger sendbtn text-white">{{singlelandingpage.form_btn}}</button>
</FormValidation>
              </form>
            </div>
        </div>
      </div>
    </div>
  </section>
  <section>
    <LandingFooter/>
</section>
</div>
</template>
<style>
  [data-aos] {
  overflow-x: hidden;
}

.features-card {
  width: 280px;
  border-radius: 20px;
  padding: 30px;
  background: var(--single_landing_header_bg);
  color: white;
  transition: all 0.4s ease;
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 20px 40px rgba(0,0,0,0.3);
  position: relative;
  overflow: hidden;
}

.features-card::before {
  content: "";
  position: absolute;
  width: 250px;
  height: 250px;
  background: radial-gradient(circle, rgba(95,111,255,0.3), transparent 70%);
  top: -100px;
  right: -100px;
  filter: blur(50px);
}

.features-card:hover {
  transform: translateY(-12px) scale(1.03);
  box-shadow: 0 30px 60px rgba(95,111,255,0.4);
}
.teamMember .swiper-slide {
  opacity: 0.5;
  transform: scale(0.85);
  transition: all 0.4s ease;
  display: flex;
  justify-content: center;
  margin-top: 50px;
  margin-bottom: 20px;
}

.teamMember .swiper-slide-active {
  opacity: 1;
  transform: scale(1);
  z-index: 2;
}

.teamMember .swiper-slide-active .features-card {
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
  border: 2px solid #0d6efd;
}
.advantage-card {
  padding: 30px;
  border-radius: 20px;
  height: 510px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.05);
  transition: 0.3s ease;
  background: radial-gradient(circle at 75% 40%, rgba(90,100,255,0.15), transparent 45%), linear-gradient(135deg, #0b0f2a 0%, #12163f 100%);
}
.feature-card-description{
  font-size: 23px;
  color: white;
}
.feature-card:hover {
  transform: translateY(-5px);
}

.mock-box {
  height: 290px;
  background: #ffffff;
  border-radius: 15px;
  margin-top: 20px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}
.mock-video {
  width: 103%;
  height: 105%;
  object-fit: cover;
  border-radius: 15px;       
}
.feature-phone-img{
  width: 325px;
  margin-bottom: 50px;
  left: 40px
}
.why_us h1{
text-shadow: 13px 27px 4px rgba(0, 0, 0, 0.3);
}
.hero-wrapper {
  background: var(--single_landing_header_bg);
  min-height: auto;
  position: relative;
  /* padding-top: 30px; */
}

.hero-card {
  min-height: 700px;
  max-width: 1400px;
  width: 100%;
  background: var(--single_landing_header_bg);
  overflow: hidden;
  border-radius: 25px;
 /* box-shadow: 0 20px 50px rgba(0,0,0,0.08);*/
  /* margin-bottom: 30px; */
}
.hero-card::before {
  content: "";
  position: absolute;
  inset: 0;
  background-image:  linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
  background-size: 60px 60px;
  pointer-events: none;
}
.hero-title {
  font-size: 28px;
  font-weight: 500;
  line-height: 1.4;
  color: #fff;
  text-align: center;
  max-width: 900px;
  margin: 0 auto;
}

.hero-subtitle {
  font-size: 18px;
  color: #666;
}

/* .phone-group {
  position: relative;
  height: 260px;
}

.phone {
  position: absolute;
  width: 220px;
  transition: 0.3s;
} */
.download-btn {
  background: transparent;
  border: 2px solid #ffffff;
  color: #ffffff;
  font-weight: 500;
  transition: all 0.3s ease;
  margin-top: -33px;
}
.download-btn:hover {
  background: #ffffff;
  color: var(--single_landing_header_bg); /* matches your gradient background */
}
/* .center-phone {
  left: 50%;
  transform: translateX(-50%) translateX(var(--translateX, 0px));
  z-index: 3;
}
.left-phone {
  left: 30%;
  transform: translateX(-50%) translateX(var(--translateX, 0px)) scale(0.9);
  z-index: 2;
}
.right-phone {
  left: 70%;
  transform: translateX(-50%) translateX(var(--translateX, 0px)) scale(0.9);
  z-index: 2;
} */
.phone-group {
  position: relative;
  height: 320px;
  perspective: 1200px; /* Adds depth */
}

.phone {
  position: absolute;
  width: 220px;
  transition: transform 0.6s ease-out, opacity 0.6s ease-out;
  --translateX: 0px;
  will-change: transform;
}

/* FAR LEFT */
.far-left-phone {
  left: 28%;
  transform: translateX(-50%) translateX(var(--translateX)) scale(0.75);
  z-index: 1;
  /* opacity: 0.6; */
}

/* LEFT */
.left-phone {
  left: 40%;
  transform: translateX(-50%) translateX(var(--translateX)) scale(0.85);
  z-index: 2;
  /* opacity: 0.8; */
}

/* CENTER */
.center-phone {
  left: 48%;
  transform: translateX(-50%) translateX(var(--translateX)) scale(1);
  z-index: 5; /* MUST be highest */
  filter: drop-shadow(0 40px 60px rgba(0,0,0,0.4));
}

/* RIGHT */
.right-phone {
  left: 60%;
  transform: translateX(-50%) translateX(var(--translateX)) scale(0.85);
  z-index: 2;
  /* opacity: 0.8; */
}

/* FAR RIGHT */
.far-right-phone {
  left: 72%;
  transform: translateX(-50%) translateX(var(--translateX)) scale(0.75);
  z-index: 1;
  /* opacity: 0.6; */
}
.card-section {
  min-height: 600px;   /* increase height */
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 100px 0;
  background: white;
}
.card-heading {
  text-align: center;
  margin-bottom: 60px;
}
.card-heading h2 {
  font-size: 38px;
  font-weight: 600;
  color: #0b0f2a;
}
/* Taxi Container */
.moving-taxi {
  position: absolute;
  left: -10px;
  width: 42px;
  z-index: 20;
  transition: top 1.2s cubic-bezier(0.45, 0.05, 0.55, 0.95);
  transform: translateX(-50%); /* Center it on the line */
}
.moving-taxi img {
  width: 230%;
  filter: 
  hue-rotate(-15deg)   /* adjust yellow tone */
  saturate(85%)        /* reduce cartoon feel */
  brightness(90%)      /* slightly darker */
  contrast(110%);
}
/* Real Driving Motion */
@keyframes driveTaxi {

  0% {
    top: 0px;
  }

  25% {
    top: 110px;
  }

  50% {
    top: 220px;
  }

  75% {
    top: 330px;
  }

  100% {
    top: 0px;
  }
}
.ride-section {
  padding: 60px 0;
  position: relative;
  overflow: hidden;
  background: var(--single_landing_header_bg);
}
/* Subtle grid background */
.ride-section::before {
  content: "";
  position: absolute;
  inset: 0;
  background-image:  linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
  background-size: 60px 60px;
  pointer-events: none;
}
.advantage-card-description{
  color: white;
}
.section-title {
  font-size: 36px;
  font-weight: 600;
  margin-bottom: 20px;
  color: white;
}

/* Phone */
.phone-wrapper {
  text-align: center;
}
.user-phone-img {
  width: 465px;
  height: 100%;
}
.driver-phone-img{
  width: 465px;
  height: 100%;
}
/* Right Side */
.top-description {
  font-size: 16px;
  color: #ffffff;
  margin-bottom: 40px;
}
/* Timeline */
.timeline {
  position: relative;
  padding-left: 40px;
}
/* Vertical Line */
.timeline::before {
  content: "";
  position: absolute;
  left: 14px;
  top: 0;
  width: 2px;
  height: 100%;
  background: #9AA7FF;
}
.phone-img{
  width: 372px;
  margin-bottom: 50px;
}
.timeline-item {
  position: relative;
  align-items: flex-start;
  width: 100%;     /* or just completely remove width */
}
.timeline-item .circle {
  position: absolute;
  left: -38px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #a8aed6;
  border: 2px solid #0d112f;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  color: #161414;
}
.timeline-item .content {
  padding-left: 40px;
}
.timeline-item h5 {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 8px;
  color: #000000;
}
.timeline-item p {
  font-size: 14px;
  color: #5a5353;
  max-width: 420px;
}
/* Active Step */
.timeline-item.active .circle {
  background: #584ba0;
  border-color: #362d5f;
  color: #fff;
  box-shadow: 0 0 15px rgba(177, 149, 221, 0.6);
}
.timeline-item.active h5 {
  color: var(--single_landing_header_bg);
}
.timeline-item.active p {
  color: #78747c;
}
.timeline::after {
  display: none !important;
}
.about-company {
  padding: 100px 0;
  position: relative;
  overflow: hidden;
  background: #ffffff; /* keep white */
}
/* ===== Keep Content Above Shapes ===== */
.about-company .container {
  position: relative;
  z-index: 2;
}
.bg-shapes {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  pointer-events: none;
  z-index: 0;
}
.shape {
  position: absolute;
  opacity: 0.07;
  animation: floatShape 14s ease-in-out infinite alternate;
}
/* LEFT SIDE LARGE CIRCLE */
.circle-left {
  width: 220px;
  height: 220px;
  background: #263293;
  border-radius: 50%;
  top: 20%;
  left: -80px;
}
/* RIGHT SIDE CIRCLE */
.circle-right {
  width: 150px;
  height: 150px;
  background: #6f7cff;
  border-radius: 50%;
  bottom: 25%;
  right: -60px;
}
/* BOTTOM TRIANGLE */
.triangle-bottom {
  width: 0;
  height: 0;
  border-left: 120px solid transparent;
  border-right: 120px solid transparent;
  border-top: 200px solid #263293;
  bottom: -200px;
  left: 30%;
}
/* SMALL DOT FOR DETAIL */
.small-dot {
  width: 80px;
  height: 80px;
  background: #263293;
  border-radius: 50%;
  top: 65%;
  right: 30%;
}

/* Smooth Floating */
@keyframes floatShape {
  from { transform: translateY(0px) rotate(0deg); }
  to { transform: translateY(40px) rotate(15deg); }
}
/* ===== Image Enhancement ===== */
.about-company-img {
  width: 100%;
  max-width: 500px;
  border-radius: 20px;
  transition: 0.5s ease;
}
.about-company-img:hover {
  transform: scale(1.05) rotate(1deg);
}
/* ===== Tag Upgrade ===== */
.about-tag {
  background: var(--single_landing_header_bg);
  color: white;
  padding: 6px 18px;
  font-size: 14px;
  font-weight: 600;
  border-radius: 50px;
  display: inline-block;
  margin-bottom: 20px;
  letter-spacing: 1px;
}
/* ===== Title Enhancement ===== */
.about-title {
  font-size: 32px;
  font-weight: 700;
  margin-bottom: 20px;
  position: relative;
}
.about-title::after {
  content: "";
  width: 60px;
  height: 4px;
  background: var(--single_landing_header_bg);
  display: block;
  margin-top: 10px;
  border-radius: 2px;
}
/* ===== List Animation ===== */
.about-list li {
  position: relative;
  padding-left: 40px;
  margin-bottom: 18px;
  font-weight: 500;
  font-size: 18px;
  transition: 0.3s ease;
  margin-top: 30px;
}
.about-list li::before {
  content: "✔";
  position: absolute;
  left: 0;
  top: 0;
  width: 28px;
  height: 28px;
  background: var(--single_landing_header_bg);
  color: #fff;
  border-radius: 50%;
  text-align: center;
  line-height: 28px;
  font-size: 14px;
  transition: 0.3s ease;
}
.about-list li:hover {
  transform: translateX(8px);
  color: var(--single_landing_header_bg);
}
.about-list li:hover::before {
  transform: scale(1.1) rotate(10deg);
}
/* SECTION */
.contact-section {
  position: relative;
  padding: 50px 0;  
  color: #fff;
  overflow: hidden;
}
.contact-section .container {
  max-width: 1000px;   
}
.bg-video {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: 0;
}
.contact-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.55);
  z-index: 1;
}

.contact-content {
  position: relative;
  z-index: 2;
}
.contact-section h2 {
  font-size: 24px;   
}
.contact-section p {
  font-size: 14px;   
}
.contact-card {
  background: #fff;
  padding: 20px;
  border-radius: 6px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.2);
  width: 485px;
  min-height: 116px;
}
.contact-card h5 {
  margin-bottom: 15px;
  font-size: 16px;
}
.form-control {
  border-radius: 0;
  border: 1px solid #ddd;
  font-size: 13px;
  padding: 6px 10px;
}
.sendbtn {
  background-color: var(--single_landing_header_bg);
  border: none;
  padding: 6px 20px;
  font-size: 13px;
}
.contact-info p {
  margin-bottom: 15px;
  font-size: 13px;
}
.contact-icon {
  width: 41px;   
  border-radius: 14px;
}
.contact-description {
  margin-left: 15px;
  font-size: 13px;
}

.feature-box {
  display: flex;
  gap: 15px;
  margin-bottom: 40px;
}
.feature-number {
  min-width: 60px;
  height: 60px;
  background: #ffffff;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 20px;
  color: var(--single_landing_header_bg);
  box-shadow: 0 8px 20px rgba(0,0,0,0.05);
}
.feature-content h5 {
  font-weight: 600;
  margin-bottom: 8px;
}
.feature-content p {
  font-size: 14px;
  color: #6c757d;
  margin: 0;
}
.app-card {
  background: var(--single_landing_header_bg);
  border-radius: 20px;
  padding: 40px 50px;
  /* overflow: hidden; */
  position: relative;
}
.app-card h2 {
  font-size: 32px;   /* Smaller heading */
  line-height: 1.3;
  color: white;
}
.app-card p {
  font-size: 14px;   /* Smaller paragraph */
  max-width: 420px;
  color: white;
}
/* Mobile Images */
.phone-wrapper {
  position: relative;
  min-height: 380px; /* controls section height */
}
/* Base phone style */
.phone-img {
  position: absolute;
  bottom: 0;
  transition: 0.3s ease;
}
/* LEFT BIG PHONE */
.phone-1 {
  left: 40px;
  max-width: 226px;
  z-index: 2;
  top: -241px;
}
/* RIGHT SMALL PHONE */
.phone-2 {
  left: 245px;
  max-width: 180px;
  z-index: 1;
  top: -152px;
}
.store-btn{
  height: 45px;  
  width: auto;
  object-fit: contain;
  cursor: pointer;
  transition: 0.3s ease;
}
.store-btn:hover {
  transform: scale(1.05);
}
.about-section {
    position: relative;
    padding: 100px 0;
    color: #fff;
    overflow: hidden;
    background-color: white;
      overflow-x: hidden;   /* only horizontal */
}
.about-title {
    font-size: 42px;
    font-weight: 700;
    margin-bottom: 20px;
}
.about-title span {
    color: var(--single_landing_header_bg);
}
.about-text {
    font-size: 16px;
    line-height: 1.8;
    opacity: 0.9;
    color: #000000;
        padding: 8px;
    margin-top: 37px;
}
.about-btn {
    border-color: #000000;
    padding: 12px 30px;
    border-radius: 30px;
    font-weight: 600;
    transition: 0.3s;
    background-color: #fffefe;
    color: #000000;
}
.about-btn:hover {
    background: #000000;
    color: #fafafa;
    transform: translateY(-3px);
}
.img-wrapper {
    width: 475px;
    max-width: 100%;
    overflow: hidden;
    border-radius: 20px;
    position: relative;
}
.about-section .row {
    overflow: hidden;
}
.about-section .col-lg-6 {
    overflow: hidden;
}
.about-img {
    width: 100%;
    display: block;
    border-radius: 20px;
    max-width: 500px;
}
.about-img:hover {
    transform: scale(1.05);
}
/* CEO Section Enhancement */
.about-ceo-section {
    background:  #ffffff;
    padding: 80px 0;
    overflow: hidden;
}
.ceo-heading {
    position: relative;
    display: inline-block;
    display: flex;
    align-items: center;   
    gap: 12px;
}

.ceo-heading::after {
    content: "";
    position: absolute;
    left: 10%;
    bottom: -10px;
    transform: translateX(-50%);
    width: 80px;
    height: 5px;
    background: var(--single_landing_header_bg);
    border-radius: 2px;
    margin-top: 10px;
}
.ceo-img-wrapper {
    display: inline-block;
    border-radius: 25px;
    position: relative;
    margin-top: 0px; 
}
.ceo-img {
    width: 100%;
    max-width: 580px;
    border-radius: 20px;
   
}

/* Floating Keyframes */
@keyframes floatImage {
    0% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-15px);
    }
    100% {
        transform: translateY(0px);
    }
}
.line-container {
    position: relative;
    display: inline-block;
    overflow: hidden;
}

.line-text {
    display: inline-block;
    white-space: nowrap;
    margin-left: 20px;
}
.vertical-line {
    position: absolute;
    top: 0;
    right: 0;
    width: 3px;
    height: 100%;
    background: var(--single_landing_header_bg);
}
@keyframes blink {
    0%,100% { opacity: 1; }
    50% { opacity: 0; }
}


/* RESPONSIVE */
@media (max-width: 992px) {
.phone-1,
.phone-2 {
  position: relative;
  transform: rotate(0);
  margin-bottom: 20px;
}
}

@media (min-width: 769px) and (max-width: 1024px) {
 
.hero-title {
  font-size: 1rem;
}
.orbit-1 {
  width: 250px;
  height: 250px;
} 
.orbit-2 {
  width: 350px;
  height: 350px;
  animation-duration: 30s;
}
.hero-right-fintech {
  right: 0px;
  height: 500px;
}
.hero-section{
  padding:50px;
  height: 663px;
}

.hero-phone-img {
  width: 456px;
  height: 100%;
  z-index: 2;
  position: relative;
  margin-left: 0px;
}
.mock-video {
  width: 103%;
  height: 102%;
  object-fit: cover;
}
.contact-card {
  width: 450px;
}
.contact-description{
  font-size: 15px;
}
.contact-icon{
  width: 40px;
  border-radius: 10px;
}
.driver-phone-img {
  width: 397px;
  height: 100%;
}
.user-phone-img {
  width: 397px;
  height: 100%;
}
.phone-img {
  width: 311px;
  margin-bottom: 20px;
}
.far-left-phone{
  display: none;
}
.far-right-phone{
  display: none;
}
.right-phone {
  left: 64%;
  transform: translateX(-50%) translateX(var(--translateX, 0px)) scale(0.9);
  z-index: 2;
}
.phone-1 {
  max-width: 204px;
  top: -222px;
}
.phone-2 {
  max-width: 168px;
  left: 225px;
}

}
@media (min-width: 427px) and (max-width: 768px) {
.timeline-item {
  position: relative;
  align-items: flex-start;
  width: 80%;
}
.hero-title {
  font-size: 1rem;
}
.orbit-1 {
  width: 250px;
  height: 250px;
} 
.orbit-2 {
  width: 350px;
  height: 350px;
  animation-duration: 30s;
}
.hero-right-fintech {
  right: 0px;
  height: 500px;
}
 .hero-section{
  padding:50px;
  height: 693px;
}
.hero-phone-img {
  width: 456px;
  height: 100%;
  z-index: 2;
  position: relative;
  margin-left: 0px;
}
.mock-video {
  width: 103%;
  height: 102%;
  object-fit: cover;
}
.contact-card {
  width: 550px;
  text-align: center;
  margin-left: auto;
  margin-right: auto;
}
.contact-description{
  font-size: 15px;
}
.contact-icon{
  width: 40px;
  border-radius: 10px;
 margin-left: 270px;
 margin-bottom: 30px;
}
.about-text {
  font-size: 14px;
}
.about-title {
  font-size: 25px;
}
.about-tag {
  font-size: 20px;
}
.phone-img {
  width: 311px;
  margin-bottom: 20px;
}
.far-left-phone{
  display: none;
}
.far-right-phone{
  display: none;
}
.center-phone {
  left: 44%;
  transform: translateX(-50%) translateX(var(--translateX, 0px)) scale(1);
  z-index: 3;
}
.phone-1 {
  left: -3px;
  max-width: 204px;
  z-index: 2;
  top: 46px;
}
.phone-2 {
  left: -18px;
  max-width: 168px;
  z-index: 1;
  top: 76px;
}

}

@media (min-width: 376px) and (max-width: 425px) {
.hero-title {
  font-size: 1rem;
}
.orbit-1 {
  width: 250px;
  height: 250px;
} 
.orbit-2 {
  width: 350px;
  height: 350px;
  animation-duration: 30s;
}
.hero-right-fintech {
  right: 0px;
  height: 500px;
}
 .hero-section{
  padding:50px;
  height: 850px;
}
.hero-phone-img {
  width: 456px;
  height: 100%;
  z-index: 2;
  position: relative;
  margin-left: 0px;
}
.mock-video {
  width: 103%;
  height: 102%;
  object-fit: cover;
}
.contact-description{
  font-size: 15px;
}
.contact-card {
  width: 390px;
}
.contact-icon{
  width: 40px;
  border-radius: 10px;
}
.timeline-item {
  position: relative;
  align-items: flex-start;
  width: 90%;
}
.user-phone-img {
  width: 319px;
  height: 100%;
}
.driver-phone-img {
  width: 319px;
  height: 100%;
}
.about-text {
  font-size: 14px;
}
.about-title {
  font-size: 25px;
}
.about-tag {
  font-size: 20px;
}
.about-company-img {
  width: 400px;
}
.phone-img {
  width: 211px;
  margin-bottom: 20px;
}
.far-left-phone{
  display: none;
}
.far-right-phone{
  display: none;
}
.right-phone {
 display: none;
}
.left-phone{
  display: none;
}
.phone-1 {
  left: 9px;
  max-width: 198px;
  z-index: 1;
  top: 47px;
}
.phone-2 {
  display: none;
}
.circle-left{
  display: none;
}
.circle-right{
  display:none;
}
.small-dot{
  display:none;
}
.about-list{
  margin-top:50px;
}

}
@media (min-width: 320px) and (max-width: 375px) {
.hero-title {
  font-size: 13px;
}
.orbit-1 {
  width: 190px;
  height: 190px;
} 
.orbit-2 {
  width: 250px;
  height: 250px;
  animation-duration: 30s;
}
.hero-right-fintech {
  right: 0px;
  height: 363px;
}
 .hero-section{
  padding:50px;
  height: 775px;
}
.hero-phone-img {
  width: 314px;
  height: 100%;
  z-index: 2;
  position: relative;
  margin-left: 0px;
}
.mock-video {
  width: 103%;
  height: 102%;
  object-fit: cover;
}
.contact-description{
  font-size: 15px;
}
.contact-card {
  width: 340px;
}
.contact-icon{
  width: 40px;
  border-radius: 10px;
}
.user-phone-img {
  width: 250px;
  height: 100%;
}
.driver-phone-img {
  width: 250px;
  height: 100%;
}
.timeline-item {
  position: relative;
  align-items: flex-start;
  width: 88%;
}
.about-text {
  font-size: 14px;
}
.about-title {
  font-size: 25px;
}
.about-tag {
  font-size: 20px;
}
.about-company-img {
  width: 350px;
}
.phone-img {
  width: 211px;
  margin-bottom: 20px;
}
.far-left-phone{
  display: none;
}
.far-right-phone{
  display: none;
}
.right-phone {
 display: none;
}
.left-phone{
  display: none;
}
.phone-1 {
  left: 13px;
  max-width: 226px;
  z-index: 2;
  top: 16px;
}
.phone-2{
  display: none;
}
.circle-left{
  display: none;
}
.circle-right{
  display:none;
}
.small-dot{
  display:none;
}
.about-list{
  margin-top:50px;
}
}
@media (min-width: 100px) and (max-width: 320px) {
.hero-section {
  height: 165vh; 
} 
.hero-title {
  font-size: 1.5rem;
}
.hero-phone-img {
  width: 341px;
  height: 190%;
  z-index: 2;
  position: relative;
  margin-left: 0px;
}
.orbit-1 {
  width: 210px;
  height: 210px;
}
.orbit-2 {
  width: 301px;
  height: 291px;
  animation-duration: 30s;
}
.play-btn,
.appstore-btn {
  padding: 8px 18px;      
  font-weight: 200;        
  font-size: 10px;         
  border-radius: 50px;
  transition: all 0.3s ease;
  box-shadow: none;        
  width: 400px;
  height: 62px;
}
.contact-description{
  font-size: 15px;
}
.contact-card {
  width: 293px;
}
.contact-icon{
  width: 40px;
  border-radius: 10px;
}
.driver-phone-img {
  width: 228px;
  height: 100%;
}
.timeline-item{
  width: 88%;
}
.user-phone-img {
  width: 228px;
  height: 100%;
}
.about-text {
  font-size: 17px;
}
.about-title {
  font-size: 28px;
}
.about-tag {
  font-size: 23px;
}
.about-company-img {
  width: 300px;
}

.far-left-phone{
  display: none;
}
.far-right-phone{
  display: none;
}
.right-phone {
 display: none;
}
.left-phone{
  display: none;
}
.hero-title{
  font-size: 17px;
}
.phone-1 {
  left: -7px;
  max-width: 226px;
  z-index: 2;
  top: 16px;
}
.phone-2{
  display: none;
}
.download-btn:hover {
  font-size: 11px;
  width: 183px;
  height: 56px;
}
.circle-left{
  display: none;
}
.circle-right{
  display:none;
}
.small-dot{
  display:none;
}
.about-list{
  margin-top:50px;
}
}
@media (max-width: 991px) {
.about-section{
   margin-top: 50px;
}
.about-ceo-section {
  text-align: center;
}
.about-section{
  text-align: center;
  padding: 0px;
}
.about-title{
  justify-content: center;
}
.ceo-heading::after {
  margin-bottom: 0px;
  left: 35%
}
}
@media (min-width: 100px) and (max-width: 320px) {
.about-title{
  font-size: 25px;
}
.about-text{
  font-size: 18px;
}
.ceo-heading::after {
  display: none;
}
.about-section{
  margin-top: 30px;
}
.contact-recaptcha{
  margin-left: 0px;
}
}
@media (max-width: 320px) {
  .g-recaptcha {
    transform: scale(0.75);
    transform-origin: left top;
  }
}
@media (max-width: 320px) {
  .g-recaptcha {
    transform: scale(0.85);
    transform-origin: 0 0;
  }
}
</style>