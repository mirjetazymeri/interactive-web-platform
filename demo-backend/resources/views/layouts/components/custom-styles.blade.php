        <!-- Main Theme Js -->
        @vite('resources/assets/js/authentication-main.js')

        <!-- Bootstrap Css -->
        <link id="style" href="{{asset('build/assets/libs/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet" > 

        <?= $this->renderSection('styles'); ?>
