 'use strict';

        function showLogin() {
            document.getElementById('loginWrapper').classList.remove('hidden');
            document.getElementById('signupWrapper').classList.remove('active');
        }

        function showSignup() {
            document.getElementById('loginWrapper').classList.add('hidden');
            document.getElementById('signupWrapper').classList.add('active');
        }

        window.showLogin = showLogin;
        window.showSignup = showSignup;