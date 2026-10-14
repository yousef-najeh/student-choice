<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Choice — Log in</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <script src="/js/api.js"></script>
    <script>
        // Already signed in? Skip straight to the app instead of showing the form again.
        if (Api.getToken()) {
            const user = Api.getUser();
            window.location.href = user && user.role === 'admin' ? '/admin' : '/dashboard';
        }
    </script>

    <div class="shell">
        <div class="hero-login">
            <div style="text-align:center;margin-bottom:1.5rem">
                <span class="mark" style="display:inline-flex;width:44px;height:44px;font-size:1.2rem;margin-bottom:0.75rem">SC</span>
                <h1>Student Choice</h1>
                <p class="muted" style="margin-top:0.35rem">Find the right major, backed by your GPA, budget, and preferences.</p>
            </div>

            <div class="card">
                <div class="tabbar" style="margin-bottom:1.1rem">
                    <button class="tab active" id="tab-login-btn" onclick="switchTab('login')">Log in</button>
                    <button class="tab" id="tab-register-btn" onclick="switchTab('register')">Register</button>
                </div>

                <div id="pane-login">
                    <div class="error-box" id="login-error"></div>
                    <div class="field">
                        <label>Email</label>
                        <input type="email" id="login-email" autocomplete="username">
                    </div>
                    <div class="field">
                        <label>Password</label>
                        <input type="password" id="login-password" autocomplete="current-password">
                    </div>
                    <button class="btn btn-primary btn-block" onclick="login()">Log in</button>
                </div>

                <div id="pane-register" style="display:none">
                    <div class="error-box" id="register-error"></div>
                    <div class="field">
                        <label>Name</label>
                        <input type="text" id="reg-name">
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <input type="email" id="reg-email" autocomplete="username">
                    </div>
                    <div class="grid-2">
                        <div class="field">
                            <label>Password</label>
                            <input type="password" id="reg-password" autocomplete="new-password">
                        </div>
                        <div class="field">
                            <label>Confirm password</label>
                            <input type="password" id="reg-password-confirm" autocomplete="new-password">
                        </div>
                    </div>
                    <button class="btn btn-primary btn-block" onclick="register()">Create account</button>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/ui.js"></script>
    <script>
        function switchTab(name) {
            document.getElementById('pane-login').style.display = name === 'login' ? 'block' : 'none';
            document.getElementById('pane-register').style.display = name === 'register' ? 'block' : 'none';
            document.getElementById('tab-login-btn').classList.toggle('active', name === 'login');
            document.getElementById('tab-register-btn').classList.toggle('active', name === 'register');
        }

        async function login() {
            hideError('login-error');
            try {
                const data = await Api.post('/login', {
                    email: document.getElementById('login-email').value,
                    password: document.getElementById('login-password').value,
                });
                Api.setSession(data.token, data.user);
                window.location.href = data.user.role === 'admin' ? '/admin' : '/dashboard';
            } catch (e) {
                showError('login-error', e);
            }
        }

        async function register() {
            hideError('register-error');
            try {
                const data = await Api.post('/register', {
                    name: document.getElementById('reg-name').value,
                    email: document.getElementById('reg-email').value,
                    password: document.getElementById('reg-password').value,
                    password_confirmation: document.getElementById('reg-password-confirm').value,
                });
                Api.setSession(data.token, data.user);
                window.location.href = '/dashboard';
            } catch (e) {
                showError('register-error', e);
            }
        }
    </script>
</body>
</html>
