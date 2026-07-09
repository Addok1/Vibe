<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ app_name() ?? 'Restart' }} - Admin App</title>
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            if (!theme) theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', theme);
            document.documentElement.setAttribute('data-sidebar', theme === 'dark' ? 'dark' : 'light');
        })();
    </script>

    <!-- PWA manifest for Add to Home Screen -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <!-- App favicon -->
    <!-- <link rel="shortcut icon" href="{{ URL::asset('image/favicon.ico') }}"> -->
    <link rel="shortcut icon" id="dynamic-favicon" href="">

 <!-- Firebase SDK -->
<!-- Use the Firebase 8.x version for CommonJS support -->
<script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-database.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/SplitText.min.js"></script>

    @php
        $default_language = default_language();
    @endphp
    <script>

        window.defaultLocale = "{{ $default_language->code }}";
        window.direction = "{{ $default_language->direction }}";

    </script>
    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead

</head>

<body>
    @inertia
    <script>
        window.headers = @json($headers);
        window.recaptchaKey = @json(config('services.recaptcha.site_key'));
        window.enablerecaptcha = @json(config('services.recaptcha.enable_recapcha'));
        window.logo =  @json($logo);
        window.favicon =  @json($favicon);
        window.footer_content1 = @json($footer_content1);
        window.supportTicket = @json($supportTicket);
        window.footer_content2 = @json($footer_content2);
        window.agent_addons = @json($agent_addons);
        window.admin_url = @json($admin_url);
        window.user_url = @json($user_url);
        window.owner_url = @json($owner_url);
        window.dispatch_url = @json($dispatch_url);
        window.agent_url = @json($agent_url);
        window.dispatch_pro_url = @json($dispatch_pro_url);
        window.franchise_url = @json($franchise_url);
        window.franchise_addons = @json($franchise_addons);
        @php
            $enableMapbox = filter_var(get_map_settings('enable_mapbox') ?? false, FILTER_VALIDATE_BOOLEAN);
            $enableThunderforest = filter_var(get_map_settings('enable_thunderforest') ?? false, FILTER_VALIDATE_BOOLEAN);
            $enableStadia = filter_var(get_map_settings('enable_stadia') ?? false, FILTER_VALIDATE_BOOLEAN);
            $mapboxKey = get_map_settings('mapbox_public_key');
            $thunderforestKey = get_map_settings('thunderforest_api_key');
            $stadiaKey = get_map_settings('stadia_api_key');

            $tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
            $attribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

            if ($enableMapbox && !empty($mapboxKey)) {
                $tileUrl = "https://api.mapbox.com/v4/mapbox.streets/{z}/{x}/{y}.png?access_token={$mapboxKey}";
                $attribution = '© OpenStreetMap contributors © Mapbox';
            } elseif ($enableThunderforest && !empty($thunderforestKey)) {
                $tileUrl = "https://tile.thunderforest.com/atlas/{z}/{x}/{y}.png?apikey={$thunderforestKey}";
                $attribution = '© OpenStreetMap contributors © Thunderforest';
            } elseif ($enableStadia && !empty($stadiaKey)) {
                $tileUrl = "https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}.png?api_key={$stadiaKey}";
                $attribution = '© OpenStreetMap contributors © Stadia Maps';
            }
        @endphp
        window.osmTileUrlTemplate = @json($tileUrl);
        window.osmTileAttribution = @json($attribution);

    </script>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Check if window.favicon is available and set it dynamically
        if (window.favicon) {
            document.getElementById('dynamic-favicon').setAttribute('href', window.favicon);
        } else {
            // Fallback if the favicon is not set
            document.getElementById('dynamic-favicon').setAttribute('href', '{{ URL::asset("image/favicon.ico") }}');
        }
        // Register service worker for PWA (offline fallback)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('{{ asset("sw.js") }}', { scope: '/' }).catch(function () {});
        }
    });
</script>
<style>
:root{
    --top_nav: {{ $navs }};
    --side_menu: {{ $side }};
    --side_menu_txt: {{ $side_txt }};
    --loginbg: url('{{ $loginbg }}');
    --owner_loginbg: url('{{ $owner_loginbg }}');
    --landing_header_bg: {{ $landing_header_bg_color }};
    --landing_header_text: {{ $landing_header_text_color }};
    --landing_header_act_text: {{ $landing_header_active_text_color }};
    --landing_footer_bg: {{ $landing_footer_bg_color }};
    --landing_footer_text: {{ $landing_footer_text_color }};
    --dispatcher_sidebar_color: {{ $dispatcher_sidebar_color }};
    --dispatcher_sidebar_txt_color: {{ $dispatcher_sidebar_txt_color }};
    --single_landing_header_bg: {{ $single_landing_header_bg_color }};
    --single_landing_header_text: {{ $single_landing_header_text_color }};
    --single_landing_header_act_text: {{ $single_landing_header_active_text_color }};
    --single_landing_footer_bg_color: {{ $single_landing_footer_bg_color }};
    --single_landing_footer_text: {{ $single_landing_footer_text_color }};
    --banner_bg_color: {{$banner_bg_color}};
    --banner_title_color: {{$banner_title_color}};
    --banner_description_color: {{$banner_description_color}};
    --banner_button_color: {{$banner_button_color}};
    

}
</style>

</html>
