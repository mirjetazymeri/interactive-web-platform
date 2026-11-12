
        <!-- Bootstrap Css -->
        <link id="style" href="{{asset('build/assets/libs/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet" >
        
        <script>
            if(localStorage.vyzordarktheme){
                document.querySelector("html").setAttribute("data-theme-mode","dark")
            }
            if(localStorage.vyzorrtl){
                document.querySelector("html").setAttribute("dir","rtl")
                document.querySelector("#style")?.setAttribute("href", "{{asset('build/assets/libs/bootstrap/css/bootstrap.rtl.min.css')}}");
            }
            if(localStorage.vyzorltr){
                document.querySelector("html").setAttribute("dir","ltr")
                document.querySelector("#style")?.setAttribute("href", "{{asset('build/assets/libs/bootstrap/css/bootstrap.min.css')}}");
            }
            let html = document.querySelector("html");
            if (window.innerWidth < 992) {
            html.setAttribute("data-toggled", "close");
            }
        </script>

        <!-- Node Waves Css -->
        <link href="{{asset('build/assets/libs/node-waves/waves.min.css')}}" rel="stylesheet" >

        <!-- SwiperJS Css -->
        <link rel="stylesheet" href="{{asset('build/assets/libs/swiper/swiper-bundle.min.css')}}">

        <!-- Color Picker Css -->
        <link rel="stylesheet" href="{{asset('build/assets/libs/flatpickr/flatpickr.min.css')}}">
        <link rel="stylesheet" href="{{asset('build/assets/libs/@simonwep/pickr/themes/nano.min.css')}}">

        <!-- Choices Css -->
        <link rel="stylesheet" href="{{asset('build/assets/libs/choices.js/public/assets/styles/choices.min.css')}}">

        <!-- Swiper Css -->
        <link rel="stylesheet" href="{{asset('build/assets/libs/swiper/swiper-bundle.min.css')}}">
