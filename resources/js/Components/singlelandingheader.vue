<script>
import { CountTo } from "vue3-count-to";
import { Link } from '@inertiajs/vue3';
import { Autoplay, Navigation, Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";
import "swiper/css";
import "swiper/css/autoplay";
import 'swiper/css/navigation';
import 'swiper/css/pagination';

export default {
    
    data() {
       
        return {
            
            Autoplay, Navigation, Pagination,
            currentTab: '',
            isCollapsed: false, // Track the collapse state
          
            headers: this.$page.props.singlelandingHeader,
            enable_web_booking: window.headers[0].enable_web_booking,
            locales: this.$page.props.locales,
            selectedLocale: this.$page.props.singlelandingHeader.locale,
            selectedDirection: this.$page.props.singlelandingHeader.direction,
            user_login: window.headers[0].userlogin,
        };
        console.log(this.headers);
    },
    
    components: {
        Swiper,
        SwiperSlide,
        CountTo,
        Link,
    },
    methods: {
        toggleMenu() {
            this.isCollapsed = !this.isCollapsed; // Toggle collapse state
        },
        changeLocale(event) {
            const localeId = event.target.value;
            this.selectedLocale = this.locales[localeId];
            // localStorage.setItem('locale', true)
            window.location.href = `?locale=${this.selectedLocale}`;
        },

        changeLocale(locale) {
        this.selectedLocale = locale;
        
        window.location.href = `?locale=${this.selectedLocale}`;
        },

//        headerLogoUrl() {
//     if (!this.headers || !this.headers.header_logo) return '';

//     return `/storage/uploads/website/images/${this.headers.header_logo}`;
// }
        
headerLogoUrl() {
    return this.headers?.header_logo_url || '';
},
    },

    mounted(){
        console.log("this.$page.props.singlelandingHeader.locale,",this.$page.props.singlelandingHeader);
         const navbar = document.querySelector(".navbar-landing");

    window.addEventListener("scroll", function () {
        if (!navbar) return;

        if (window.scrollY > 50) {
            navbar.classList.add("is-sticky-nav-color", "is-sticky");

            if (heroType === 'type-2') {
                navbar.classList.remove("bg-dark1");
            }
        } else {
            navbar.classList.remove("is-sticky-nav-color", "is-sticky");

            if (heroType === 'type-2') {
                navbar.classList.add("bg-dark1");
            }
        }
    });
        const body = document.body;
        if( this.selectedDirection === 'rtl'){
            localStorage.setItem('directiontoggleValue', true);
            body.classList.add('rtl');
             body.classList.remove('ltr');
        }
        else{
            localStorage.setItem('directiontoggleValue', false);
            body.classList.add('ltr');
            body.classList.remove('rtl');
        }
    },
    created() {
        // Set initial active tab based on current route
        this.currentTab = window.location.pathname;
    },
};
</script>

<template>
    <nav class="navbar navbar-expand-lg navbar-landing fixed-top"  :class="{ 'bg-dark1': heroType === 'type-2' }"   id="navbar">
        <BContainer>
            <Link class="navbar-brand">
                <img :src="headerLogoUrl()" class="card-logo card-logo-dark" alt="logo light" width="150">
            </Link>
            <BButton variant="link" class="navbar-toggler py-0 fs-20 text-body" @click="toggleMenu()">
                <i class="mdi mdi-menu"></i>
            </BButton>

            <BCollapse class="navbar-collapse" id="navbarSupportedContent" v-model="isCollapsed">
                <ul class="navbar-nav mx-auto mt-2 mt-lg-0" id="navbar-example">
                    <li class="nav-item">
                        <Link class="nav-link" :class="{ 'active': currentTab === '/' }" href="/">{{ headers.home }}</Link>
                    </li>
                    <li class="nav-item">
                        <Link class="nav-link" :class="{ 'active': currentTab === '/about' }" href="#about">{{ headers.aboutus }}</Link>
                    </li>
                    <li class="nav-item">
                        <Link class="nav-link" :class="{ 'active': currentTab === '/apps' }" href="#driver&userapps">{{ headers.apps }}</Link>
                    </li>
                    <li class="nav-item">
                        <Link class="nav-link" :class="{ 'active': currentTab === '/contact' }" href="#contact">{{ headers.contact }}</Link>
                    </li>
                </ul>
                <div class="flex-shrink-0 me-5 selectLanguages">
                    <!-- <select v-model="selectedLocale" @change="changeLocale" class="form-select form-select-sm" aria-label=".form-select-sm example">
                        <option v-for="(locale, id) in locales" :key="id" :value="id">{{ locale }}</option>
                    </select> -->
                    <!-- <BDropdown class="dropdown" variant="ghost-secondary" dropstart
                        :offset="{ alignmentAxis: 55, crossAxis: 15, mainAxis: -50 }"
                        toggle-class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle arrow-none"
                        menu-class="dropdown-menu-end">
                        <template #button-content>
                            <div class="bg-success-subtle" style="height: 38px; width: 38px; border-radius: 45px;">
                            <i class="ri-translate fs-22 text-success"></i>
                            </div>
                        </template>
                        <BLink href="javascript:void(0);" class="dropdown-item notify-item language py-2"
                            v-for="(locale, id) in locales" :data-lang="locale"
                            :title="locale"
                            @click="changeLocale(locale)"
                            :key="id"
                            :class="{ 'bg-success-subtle text-dark': selectedLocale === locale }">
                            <span class="align-middle">{{ locale }}</span>

                            
                            <i v-if="selectedLocale === locale" class="bx bx-check text-success float-end fs-22"></i>
                        </BLink>
                    </BDropdown> -->
                    <BDropdown class="dropdown" variant="ghost-secondary" dropstart
                        :offset="{ alignmentAxis: 55, crossAxis: 15, mainAxis: -50 }"
                        toggle-class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle arrow-none"
                        menu-class="dropdown-menu-end">
                        
                        <template #button-content>
                            <div  style="height: 38px; width: 38px; border-radius: 45px;" class="lng-btn ">
                                <i class="ri-translate fs-22 "></i>
                            </div>
                        </template>

                        <BLink href="javascript:void(0);" class="dropdown-item notify-item language py-2"
                            v-for="(language, locale) in locales" 
                            :data-lang="locale"
                            :title="language"
                            @click="changeLocale(locale)"
                            :key="locale"
                            :class="{ 'bg-success-subtle text-dark': selectedLocale === locale } ">
                            
                            <span class="align-middle">{{ $t(language) }}</span>

                            <!-- Checkmark for selected language -->
                            <i v-if="selectedLocale === locale" class="bx bx-check text-success float-end fs-22"></i>
                        </BLink>
                    </BDropdown>
                </div>
                <div class="">
                    <BLink v-if="enable_web_booking == 1" class="btn book-btn"  :href="user_login">{{ headers.book_now_btn }}</BLink>
                </div>
            </BCollapse>
        </BContainer>
    </nav>
</template>
<style scoped>
.navbar-landing{
background-color: var(--single_landing_header_bg_color);
}
/* When scrolling → WHITE */
.navbar-landing.is-sticky {
        background-color: #ffffff;
}
.navbar-landing-mobile{
   background-color: var(--single_landing_header_bg_color);
    color:black !important;    
}
.navbar-landing-mobile .navbar-nav .nav-item .nav-link.active{
      color:var(--single_landing_header_act_text) !important;     
}

.navbar-landing .navbar-nav .nav-item .nav-link {
    color:white !important;
}

.navbar-landing.is-sticky-nav-color .navbar-nav .nav-item .nav-link {
    color:black !important;
}

.navbar-landing.is-sticky-nav-color .navbar-nav .nav-item .nav-link.active {
    color:var(--single_landing_header_act_text)!important;
}
.profile-menu::-webkit-scrollbar{
 display: none; 
}
.bg-dark1{
    background: #0b0f2a!important;  
    /* background: linear-gradient(180deg,rgba(42, 123, 155, 1) 20%, rgba(42, 123, 155, 0.92) 32%, rgba(237, 221, 83, 0) 100%); */
}
.bg-dark1.navbar-landing .navbar-nav .nav-item .nav-link {
    color: rgb(255, 255, 255) !important;
}

.card-logo-dark {
  display: block !important;
}
.navbar-landing.is-sticky .book-btn {
    background: var(--single_landing_header_bg);
}
.navbar-landing.is-sticky .lng-btn {
    background: var(--single_landing_header_bg);
}
.navbar-landing .book-btn{
    color: #ffffff;
    border: 1px solid #ffffff;
    transition: all 0.3s ease;
}
.navbar-landing .lng-btn{
    color: #ffffff;
    border: 1px solid #ffffff;
    transition: all 0.3s ease;
}
/* Sticky Active Link */
.navbar-landing.is-sticky .navbar-nav .nav-item .nav-link.active {
    color: var(--single_landing_header_act_text) !important;
}
</style>
