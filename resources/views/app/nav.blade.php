<nav class="topnav">
    <a href="/dashboard" class="brand" id="nav-brand">
        <span class="mark">SC</span>
        <span>Student Choice</span>
    </a>
    <button class="nav-toggle" id="nav-toggle" aria-label="Toggle menu">☰</button>
    <div class="nav-links" id="nav-links">
        <a href="/dashboard" class="nav-link student-link" data-path="/dashboard">Dashboard</a>
        <a href="/majors" class="nav-link student-link" data-path="/majors">Majors &amp; Scholarships</a>
        <a href="/profile" class="nav-link student-link" data-path="/profile">Profile</a>
        <a href="/admin" class="nav-link" data-path="/admin" id="nav-admin-link" style="display:none">Admin</a>
    </div>
    <div class="nav-spacer"></div>
    <div class="nav-user" id="nav-user-wrap"></div>
    <button class="btn btn-secondary btn-sm" onclick="Api.clearSession(); window.location.href='/login';">Log out</button>
</nav>
<script>
    (function () {
        const user = Api.getUser();
        const wrap = document.getElementById('nav-user-wrap');
        if (user && wrap) {
            const initial = (user.name || '?').trim().charAt(0).toUpperCase();
            const photo = user.profile && user.profile.photo_url;
            wrap.innerHTML = `
                <span class="avatar-chip">${photo ? `<img src="${photo}">` : initial}</span>
                <span>${user.name} <span class="muted">(${user.role})</span></span>`;
            if (user.role === 'admin') {
                document.getElementById('nav-admin-link').style.display = '';
                // Admin has no student_profiles row — these pages aren't for them.
                document.querySelectorAll('.student-link').forEach(a => a.remove());
                document.getElementById('nav-brand').setAttribute('href', '/admin');
            }
        }
        document.querySelectorAll('.nav-link').forEach(a => {
            if (a.dataset.path === window.location.pathname) a.classList.add('active');
        });
        document.getElementById('nav-toggle').addEventListener('click', () => {
            document.getElementById('nav-links').classList.toggle('open');
        });
    })();
</script>
