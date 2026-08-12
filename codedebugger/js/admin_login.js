 (function() {
        'use strict';

        // ----- DOM refs -----
        const signinForm = document.getElementById('signinForm');
        const signupForm = document.getElementById('signupForm');
        const formTitle = document.getElementById('formTitle');
        const formSubtitle = document.getElementById('formSubtitle');
        const switchText = document.getElementById('switchText');

        const signinEmail = document.getElementById('signinEmail');
        const signinPassword = document.getElementById('signinPassword');
        const signinError = document.getElementById('signinError');
        const rememberMe = document.getElementById('rememberMe');

        // Toggle buttons
        const toggleBtns = document.querySelectorAll('.toggle-password');

        // ----- Core switch function (exposed globally for inline onclick) -----
        window.switchForm = function(mode) {
            if (mode === 'signin') {
                signinForm.classList.remove('hidden');
                signupForm.classList.add('hidden');
                formTitle.textContent = 'Welcome Back';
                formSubtitle.textContent = 'Sign in to your admin account';
                switchText.innerHTML = `Don't have an account? <a id="switchLink" onclick="switchForm('signup')">Sign Up</a>`;
                signinError.classList.remove('show');
            } else {
                signupForm.classList.remove('hidden');
                signinForm.classList.add('hidden');
                formTitle.textContent = 'Create Account';
                formSubtitle.textContent = 'Sign up to get started';
                switchText.innerHTML = `Already have an account? <a id="switchLink" onclick="switchForm('signin')">Sign In</a>`;
                signinError.classList.remove('show');
            }
        };

        window.switchForm('signin');

        // ----- Toggle password visibility -----
        toggleBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (!input) return;
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                this.querySelector('i').className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
            });
        });

        // ----- Client-side validation before POST (Sign In) -----
        signinForm.addEventListener('submit', function(e) {
            const email = signinEmail.value.trim();
            const password = signinPassword.value.trim();
            signinError.classList.remove('show');

            if (!email || !password) {
                e.preventDefault();
                signinError.querySelector('span').textContent = 'Please fill in both fields.';
                signinError.classList.add('show');
                return;
            }
            if (password.length < 6) {
                e.preventDefault();
                signinError.querySelector('span').textContent = 'Password must be at least 6 characters.';
                signinError.classList.add('show');
                return;
            }
            if (rememberMe.checked) {
                localStorage.setItem('adminRemember', email);
            } else {
                localStorage.removeItem('adminRemember');
            }
            // Otherwise let the form POST to the server for real auth.
        });

        // ----- Load remembered email -----
        const remembered = localStorage.getItem('adminRemember');
        if (remembered) {
            signinEmail.value = remembered;
            rememberMe.checked = true;
        }
    })();