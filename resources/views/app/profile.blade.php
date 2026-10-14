<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Choice — Profile</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <script src="/js/api.js"></script>
    <script>requireStudent();</script>
    @include('app.nav')

    <div class="shell">
        <div class="page-hd">
            <h1>My profile</h1>
            <p>This is what drives your matches on the dashboard.</p>
        </div>

        <div class="card">
            <div class="error-box" id="profile-error"></div>

            <div class="section-title">Account</div>
            <div class="grid-2">
                <div class="field"><label>Name</label><input id="p-name"></div>
                <div class="field"><label>Email</label><input id="p-email" type="email"></div>
            </div>

            <div class="section-title">Academic</div>
            <div class="grid-2">
                <div class="field"><label>GPA</label><input id="p-gpa" type="number" step="0.01" min="0" max="100"></div>
                <div class="field"><label>Tawjihi branch</label><select id="p-branch"><option value="">—</option></select></div>
                <div class="field"><label>Budget (max per credit hour)</label><input id="p-budget" type="number" step="0.01" min="0"></div>
                <div class="field"><label>Preferred city</label><select id="p-city"><option value="">—</option></select></div>
            </div>
            <div class="field"><label>Preferred region</label><select id="p-region" style="max-width:260px"><option value="">—</option></select></div>

            <div class="section-title">Preferences &amp; bio</div>
            <div class="field">
                <label>Preferred fields of study</label>
                <div class="checkbox-grid" id="p-fields"></div>
            </div>
            <div class="field"><label>Description</label><textarea id="p-description" rows="2"></textarea></div>

            <div class="section-title">Scholarships</div>
            <div class="field" id="p-scholarships">
                <p class="muted">None yet — scholarships are awarded by an admin and discount whatever major you end up studying.</p>
            </div>

            <button class="btn btn-primary" onclick="saveProfile()">Save profile</button>
            <a href="/dashboard" class="btn btn-secondary">See my matches</a>

            <div class="divider"></div>

            <div class="section-title">Photo</div>
            <div style="display:flex;align-items:center;gap:1rem">
                <span class="avatar-chip" id="p-photo-preview" style="width:56px;height:56px;font-size:1.3rem"></span>
                <input type="file" id="p-photo" accept="image/*" style="flex:1;max-width:280px">
                <button class="btn btn-secondary btn-sm" onclick="uploadPhoto()">Upload</button>
            </div>
        </div>
    </div>

    <script src="/js/ui.js"></script>
    <script>
        async function loadLookups() {
            const [branches, regions, cities, fields] = await Promise.all([
                Api.get('/tawjihi-branches'),
                Api.get('/regions'),
                Api.get('/cities'),
                Api.get('/fields-of-study'),
            ]);
            fillSelect('p-branch', branches);
            fillSelect('p-region', regions);
            fillSelect('p-city', cities);

            document.getElementById('p-fields').innerHTML = fields.map(f =>
                `<label class="checkbox-row"><input type="checkbox" class="field-cb" value="${f.id}"> ${f.name}</label>`
            ).join('');
        }

        function fillSelect(id, items) {
            const el = document.getElementById(id);
            items.forEach(i => {
                const opt = document.createElement('option');
                opt.value = i.id;
                opt.textContent = i.name;
                el.appendChild(opt);
            });
        }

        async function loadProfile() {
            const res = await Api.get('/user');
            const user = res.data;
            document.getElementById('p-name').value = user.name || '';
            document.getElementById('p-email').value = user.email || '';
            const p = user.profile || {};
            document.getElementById('p-gpa').value = p.gpa ?? '';
            document.getElementById('p-budget').value = p.budget_max ?? '';
            document.getElementById('p-description').value = p.description ?? '';
            const preview = document.getElementById('p-photo-preview');
            if (p.photo_url) preview.innerHTML = `<img src="${p.photo_url}">`;
            else preview.textContent = (user.name || '?').trim().charAt(0).toUpperCase();
            selectByName('p-branch', p.tawjihi_branch);
            selectByName('p-region', p.preferred_region);
            selectByName('p-city', p.preferred_city);
            (p.preferred_fields || []).forEach(name => {
                document.querySelectorAll('.field-cb').forEach(cb => {
                    if (cb.parentElement.textContent.trim() === name) cb.checked = true;
                });
            });

            const scholarships = p.scholarships || [];
            if (scholarships.length) {
                document.getElementById('p-scholarships').innerHTML = scholarships.map(s =>
                    `<span class="badge badge-success" style="margin-inline-end:0.4rem" dir="auto">${s.name} — ${s.discount_percentage}%</span>`
                ).join('');
            }
        }

        function selectByName(selectId, name) {
            if (!name) return;
            const el = document.getElementById(selectId);
            [...el.options].forEach(o => { if (o.textContent === name) el.value = o.value; });
        }

        async function saveProfile() {
            hideError('profile-error');
            const fieldIds = [...document.querySelectorAll('.field-cb:checked')].map(cb => parseInt(cb.value));
            try {
                await Api.put('/profile', {
                    name: document.getElementById('p-name').value,
                    email: document.getElementById('p-email').value,
                    gpa: numOrNull('p-gpa'),
                    budget_max: numOrNull('p-budget'),
                    description: document.getElementById('p-description').value,
                    tawjihi_branch_id: numOrNull('p-branch'),
                    preferred_region_id: numOrNull('p-region'),
                    preferred_city_id: numOrNull('p-city'),
                    preferred_field_ids: fieldIds,
                });
                toast('Profile saved.', 'success');
            } catch (e) {
                showError('profile-error', e);
                toast(e.message, 'error');
            }
        }

        function numOrNull(id) {
            const v = document.getElementById(id).value;
            return v === '' ? null : Number(v);
        }

        async function uploadPhoto() {
            const file = document.getElementById('p-photo').files[0];
            if (!file) return toast('Choose a photo file first.', 'error');
            const form = new FormData();
            form.append('photo', file);
            try {
                await Api.post('/profile/photo', form);
                await loadProfile();
                toast('Photo uploaded.', 'success');
            } catch (e) {
                toast(e.message, 'error');
            }
        }

        (async () => {
            await loadLookups();
            await loadProfile();
        })();
    </script>
</body>
</html>
