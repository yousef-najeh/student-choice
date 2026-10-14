<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Choice — Admin</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <script src="/js/api.js"></script>
    <script>requireAdmin();</script>
    @include('app.nav')

    <div class="shell">
        <div class="page-hd">
            <h1>Admin panel</h1>
            <p>Manage majors, scholarships, students, and reference data.</p>
        </div>

        <div class="tabbar">
            <button class="tab active" onclick="showTab('majors', event)">Majors</button>
            <button class="tab" onclick="showTab('scholarships', event)">Scholarships</button>
            <button class="tab" onclick="showTab('students', event)">Students</button>
            <button class="tab" onclick="showTab('lookups', event)">Lookups</button>
        </div>

        {{-- ===== Majors ===== --}}
        <section id="tab-majors">
            <div class="card">
                <div class="card-hd"><h2 style="margin:0">Add / edit major</h2></div>
                <div class="error-box" id="major-error"></div>
                <input type="hidden" id="m-id">
                <div class="grid-2">
                    <div>
                        <div class="field"><label>Name</label><input id="m-name"></div>
                        <div class="field"><label>Institution</label><select id="m-institution"></select></div>
                        <div class="field"><label>Faculty</label><select id="m-faculty"><option value="">—</option></select></div>
                        <div class="field"><label>Degree level</label><select id="m-degree"><option value="">—</option></select></div>
                        <div class="field"><label>Field of study</label><select id="m-field"><option value="">—</option></select></div>
                    </div>
                    <div>
                        <div class="field"><label>Credit hour price</label><input id="m-price" type="number" step="0.01"></div>
                        <div class="field"><label>Credit hours</label><input id="m-hours" type="number"></div>
                        <div class="field"><label>Admission average</label><input id="m-avg" type="number" step="0.01"></div>
                        <div class="field"><label>Min GPA to apply</label><input id="m-mingpa" type="number" step="0.01"></div>
                        <div class="field"><label>Study duration (text)</label><input id="m-duration"></div>
                        <div class="field"><label>Admission link</label><input id="m-link"></div>
                    </div>
                </div>
                <div class="field"><label>Campuses (ctrl/cmd-click for multiple)</label><select id="m-campuses" multiple size="4"></select></div>
                <div class="field"><label>Tawjihi branches (leave empty = open to all)</label><select id="m-branches" multiple size="4"></select></div>
                <p class="hint">Scholarships are assigned to students (Students tab), not to majors — a scholarship discounts whatever major a student is matched against.</p>
                <button class="btn btn-primary" onclick="saveMajor()">Save major</button>
                <button class="btn btn-secondary" onclick="resetMajorForm()">Clear form</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Institution</th><th>Field</th><th>Hours</th><th></th></tr></thead>
                    <tbody id="majors-tbody"></tbody>
                </table>
            </div>
            <div id="majors-pagination"></div>
        </section>

        {{-- ===== Scholarships ===== --}}
        <section id="tab-scholarships" style="display:none">
            <div class="card">
                <div class="card-hd"><h2 style="margin:0">Add / edit scholarship</h2></div>
                <div class="error-box" id="scholarship-error"></div>
                <input type="hidden" id="s-id">
                <div class="grid-2">
                    <div class="field"><label>Name</label><input id="s-name"></div>
                    <div class="field"><label>Discount %</label><input id="s-discount" type="number" step="0.01"></div>
                </div>
                <div class="field"><label>Description</label><textarea id="s-description" rows="2"></textarea></div>
                <div class="field"><label>Eligibility notes</label><textarea id="s-notes" rows="2"></textarea></div>
                <button class="btn btn-primary" onclick="saveScholarship()">Save scholarship</button>
                <button class="btn btn-secondary" onclick="resetScholarshipForm()">Clear form</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Discount</th><th></th></tr></thead>
                    <tbody id="scholarships-tbody"></tbody>
                </table>
            </div>
            <div id="scholarships-pagination"></div>
        </section>

        {{-- ===== Students ===== --}}
        <section id="tab-students" style="display:none">
            <div class="card">
                <div class="card-hd"><h2 style="margin:0">Add / edit student</h2></div>
                <div class="error-box" id="student-error"></div>
                <input type="hidden" id="u-id">
                <div class="grid-2">
                    <div>
                        <div class="field"><label>Name</label><input id="u-name"></div>
                        <div class="field"><label>Email</label><input id="u-email"></div>
                        <div class="field"><label>Password (leave empty to keep current when editing)</label><input id="u-password" type="password"></div>
                        <div class="field"><label>GPA</label><input id="u-gpa" type="number" step="0.01"></div>
                    </div>
                    <div>
                        <div class="field"><label>Budget max (per credit hour)</label><input id="u-budget" type="number" step="0.01"></div>
                        <div class="field"><label>Tawjihi branch</label><select id="u-branch"><option value="">—</option></select></div>
                        <div class="field"><label>Preferred city</label><select id="u-city"><option value="">—</option></select></div>
                        <div class="field"><label>Description</label><textarea id="u-description" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="section-title">Scholarships</div>
                <p class="hint" style="margin-top:-0.4rem;margin-bottom:0.6rem">Check a box to award that scholarship to this student. It isn't tied to a major — it discounts whatever major they're matched against.</p>
                <div class="field">
                    <div class="checkbox-grid" id="u-scholarships"></div>
                </div>
                <button class="btn btn-primary" onclick="saveStudent()">Save student</button>
                <button class="btn btn-secondary" onclick="resetStudentForm()">Clear form</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Email</th><th>Status</th><th></th></tr></thead>
                    <tbody id="students-tbody"></tbody>
                </table>
            </div>
            <div id="students-pagination"></div>
        </section>

        {{-- ===== Lookups ===== --}}
        <section id="tab-lookups" style="display:none">
            <div class="card">
                <h2>Reference data</h2>
                <p class="muted">Institutions, campuses, faculties, and the simple lookup tables (region, city, degree level, field of study, tawjihi branch) all follow the same add-by-name pattern as majors/scholarships above. Use a REST client (Postman) against <code>/api/admin/*</code> for full CRUD on these — see <code>docs/api-reference.md</code>.</p>
                <div id="lookup-summary" style="margin-top:0.75rem"></div>
            </div>
        </section>
    </div>

    <script src="/js/ui.js"></script>
    <script>
        let lookups = {};
        let majorsPage = 1, scholarshipsPage = 1, studentsPage = 1;

        function showTab(name, evt) {
            ['majors', 'scholarships', 'students', 'lookups'].forEach(t => {
                document.getElementById('tab-' + t).style.display = t === name ? 'block' : 'none';
            });
            document.querySelectorAll('.tabbar .tab').forEach(t => t.classList.remove('active'));
            if (evt) evt.target.classList.add('active');
        }

        function opt(id, name) { return `<option value="${id}">${name}</option>`; }

        async function loadLookups() {
            const [institutions, faculties, campuses, degreeLevels, fields, branches, cities, scholarships] = await Promise.all([
                Api.get('/admin/institutions?per_page=200'),
                Api.get('/admin/faculties?per_page=200'),
                Api.get('/admin/campuses?per_page=200'),
                Api.get('/degree-levels'),
                Api.get('/fields-of-study'),
                Api.get('/tawjihi-branches'),
                Api.get('/cities'),
                Api.get('/admin/scholarships?per_page=200'),
            ]);
            lookups = {
                institutions: institutions.data, faculties: faculties.data, campuses: campuses.data,
                degreeLevels, fields, branches, cities, scholarships: scholarships.data,
            };

            document.getElementById('m-institution').innerHTML = lookups.institutions.map(i => opt(i.id, i.name)).join('');
            document.getElementById('m-faculty').innerHTML += lookups.faculties.map(f => opt(f.id, f.name)).join('');
            document.getElementById('m-degree').innerHTML += lookups.degreeLevels.map(d => opt(d.id, d.name)).join('');
            document.getElementById('m-field').innerHTML += lookups.fields.map(f => opt(f.id, f.name)).join('');
            document.getElementById('m-campuses').innerHTML = lookups.campuses.map(c => opt(c.id, `${c.institution?.name ?? ''} — ${c.city?.name ?? ''}`)).join('');
            document.getElementById('m-branches').innerHTML = lookups.branches.map(b => opt(b.id, b.name)).join('');

            document.getElementById('u-branch').innerHTML += lookups.branches.map(b => opt(b.id, b.name)).join('');
            document.getElementById('u-city').innerHTML += lookups.cities.map(c => opt(c.id, c.name)).join('');
            document.getElementById('u-scholarships').innerHTML = lookups.scholarships.map(s =>
                `<label class="checkbox-row" dir="auto"><input type="checkbox" class="scholarship-cb" value="${s.id}"> ${s.name} (${s.discount_percentage ?? '?'}%)</label>`
            ).join('') || '<p class="muted">No scholarships in the catalog yet — add one on the Scholarships tab first.</p>';

            document.getElementById('lookup-summary').innerHTML =
                `<span class="badge">${lookups.institutions.length} institutions</span>
                 <span class="badge">${lookups.campuses.length} campuses</span>
                 <span class="badge">${lookups.faculties.length} faculties</span>
                 <span class="badge">${lookups.fields.length} fields of study</span>
                 <span class="badge">${lookups.branches.length} tawjihi branches</span>`;
        }

        function selectedValues(id) {
            return [...document.getElementById(id).selectedOptions].map(o => parseInt(o.value));
        }

        // --- Majors ---
        async function loadMajors(page = 1) {
            majorsPage = page;
            const res = await Api.get(`/admin/majors?page=${page}`);
            document.getElementById('majors-tbody').innerHTML = res.data.map(m => `<tr>
                <td dir="auto">${m.name}</td><td dir="auto">${m.institution?.name ?? ''}</td><td dir="auto">${m.field_of_study ?? ''}</td><td>${m.credit_hours ?? '—'}</td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick='editMajor(${JSON.stringify(m)})'>Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteMajor(${m.id})">Delete</button>
                </td>
            </tr>`).join('');
            document.getElementById('majors-pagination').innerHTML = paginationControls(res.meta, 'loadMajors');
        }

        function editMajor(m) {
            document.getElementById('m-id').value = m.id;
            document.getElementById('m-name').value = m.name;
            document.getElementById('m-price').value = m.credit_hour_price ?? '';
            document.getElementById('m-hours').value = m.credit_hours ?? '';
            document.getElementById('m-avg').value = m.admission_average ?? '';
            document.getElementById('m-mingpa').value = m.min_gpa_to_apply ?? '';
            document.getElementById('m-duration').value = m.study_duration_text ?? '';
            document.getElementById('m-link').value = m.admission_link ?? '';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function resetMajorForm() {
            document.getElementById('m-id').value = '';
            ['m-name', 'm-price', 'm-hours', 'm-avg', 'm-mingpa', 'm-duration', 'm-link'].forEach(id => document.getElementById(id).value = '');
        }

        async function saveMajor() {
            hideError('major-error');
            const id = document.getElementById('m-id').value;
            const payload = {
                name: document.getElementById('m-name').value,
                institution_id: parseInt(document.getElementById('m-institution').value),
                faculty_id: document.getElementById('m-faculty').value || null,
                degree_level_id: document.getElementById('m-degree').value || null,
                field_of_study_id: document.getElementById('m-field').value || null,
                credit_hour_price: document.getElementById('m-price').value || null,
                credit_hours: document.getElementById('m-hours').value || null,
                admission_average: document.getElementById('m-avg').value || null,
                min_gpa_to_apply: document.getElementById('m-mingpa').value || null,
                study_duration_text: document.getElementById('m-duration').value || null,
                admission_link: document.getElementById('m-link').value || null,
                campus_ids: selectedValues('m-campuses'),
                tawjihi_branch_ids: selectedValues('m-branches'),
            };
            try {
                if (id) await Api.put(`/admin/majors/${id}`, payload);
                else await Api.post('/admin/majors', payload);
                resetMajorForm();
                await loadMajors(majorsPage);
                toast('Major saved.', 'success');
            } catch (e) {
                showError('major-error', e);
                toast(e.message, 'error');
            }
        }

        async function deleteMajor(id) {
            const { confirmed } = await confirmModal('Delete this major? This cannot be undone.', { danger: true, confirmText: 'Delete' });
            if (!confirmed) return;
            await Api.del(`/admin/majors/${id}`);
            await loadMajors(majorsPage);
            toast('Major deleted.', 'success');
        }

        // --- Scholarships ---
        async function loadScholarships(page = 1) {
            scholarshipsPage = page;
            const res = await Api.get(`/admin/scholarships?page=${page}`);
            document.getElementById('scholarships-tbody').innerHTML = res.data.map(s => `<tr>
                <td dir="auto">${s.name}</td><td>${s.discount_percentage ?? ''}</td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick='editScholarship(${JSON.stringify(s)})'>Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteScholarship(${s.id})">Delete</button>
                </td>
            </tr>`).join('');
            document.getElementById('scholarships-pagination').innerHTML = paginationControls(res.meta, 'loadScholarships');
        }

        function editScholarship(s) {
            document.getElementById('s-id').value = s.id;
            document.getElementById('s-name').value = s.name;
            document.getElementById('s-discount').value = s.discount_percentage ?? '';
            document.getElementById('s-description').value = s.description ?? '';
            document.getElementById('s-notes').value = s.eligibility_notes ?? '';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function resetScholarshipForm() {
            document.getElementById('s-id').value = '';
            ['s-name', 's-discount', 's-description', 's-notes'].forEach(id => document.getElementById(id).value = '');
        }

        async function saveScholarship() {
            hideError('scholarship-error');
            const id = document.getElementById('s-id').value;
            const payload = {
                name: document.getElementById('s-name').value,
                discount_percentage: document.getElementById('s-discount').value || null,
                description: document.getElementById('s-description').value || null,
                eligibility_notes: document.getElementById('s-notes').value || null,
            };
            try {
                if (id) await Api.put(`/admin/scholarships/${id}`, payload);
                else await Api.post('/admin/scholarships', payload);
                resetScholarshipForm();
                await loadScholarships(scholarshipsPage);
                await loadLookups();
                toast('Scholarship saved.', 'success');
            } catch (e) {
                showError('scholarship-error', e);
                toast(e.message, 'error');
            }
        }

        async function deleteScholarship(id) {
            const { confirmed } = await confirmModal('Delete this scholarship?', { danger: true, confirmText: 'Delete' });
            if (!confirmed) return;
            await Api.del(`/admin/scholarships/${id}`);
            await loadScholarships(scholarshipsPage);
            toast('Scholarship deleted.', 'success');
        }

        // --- Students ---
        async function loadStudents(page = 1) {
            studentsPage = page;
            const res = await Api.get(`/admin/students?page=${page}`);
            document.getElementById('students-tbody').innerHTML = res.data.map(u => `<tr>
                <td>${u.name}</td><td>${u.email}</td>
                <td>${u.is_banned ? `<span class="badge badge-danger">banned</span>` : `<span class="badge badge-success">active</span>`}</td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick='editStudent(${JSON.stringify(u)})'>Edit</button>
                    ${u.is_banned
                        ? `<button class="btn btn-sm" onclick="unbanStudent(${u.id})">Unban</button>`
                        : `<button class="btn btn-danger btn-sm" onclick="banStudent(${u.id})">Ban</button>`}
                    <button class="btn btn-danger btn-sm" onclick="deleteStudent(${u.id})">Delete</button>
                </td>
            </tr>`).join('');
            document.getElementById('students-pagination').innerHTML = paginationControls(res.meta, 'loadStudents');
        }

        function editStudent(u) {
            document.getElementById('u-id').value = u.id;
            document.getElementById('u-name').value = u.name;
            document.getElementById('u-email').value = u.email;
            document.getElementById('u-password').value = '';
            const p = u.profile || {};
            document.getElementById('u-gpa').value = p.gpa ?? '';
            document.getElementById('u-budget').value = p.budget_max ?? '';
            document.getElementById('u-description').value = p.description ?? '';
            const scholarshipIds = new Set((p.scholarships || []).map(s => String(s.id)));
            document.querySelectorAll('.scholarship-cb').forEach(cb => cb.checked = scholarshipIds.has(cb.value));
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function resetStudentForm() {
            document.getElementById('u-id').value = '';
            ['u-name', 'u-email', 'u-password', 'u-gpa', 'u-budget', 'u-description'].forEach(id => document.getElementById(id).value = '');
            document.querySelectorAll('.scholarship-cb').forEach(cb => cb.checked = false);
        }

        async function saveStudent() {
            hideError('student-error');
            const id = document.getElementById('u-id').value;
            const payload = {
                name: document.getElementById('u-name').value,
                email: document.getElementById('u-email').value,
                gpa: document.getElementById('u-gpa').value || null,
                budget_max: document.getElementById('u-budget').value || null,
                description: document.getElementById('u-description').value || null,
                tawjihi_branch_id: document.getElementById('u-branch').value || null,
                preferred_city_id: document.getElementById('u-city').value || null,
                scholarship_ids: [...document.querySelectorAll('.scholarship-cb:checked')].map(cb => parseInt(cb.value)),
            };
            const password = document.getElementById('u-password').value;
            if (password) payload.password = password;
            try {
                if (id) await Api.put(`/admin/students/${id}`, payload);
                else {
                    if (!password) throw new Error('Password is required to create a student.');
                    await Api.post('/admin/students', payload);
                }
                resetStudentForm();
                await loadStudents(studentsPage);
                toast('Student saved.', 'success');
            } catch (e) {
                showError('student-error', e);
                toast(e.message, 'error');
            }
        }

        async function deleteStudent(id) {
            const { confirmed } = await confirmModal('Delete this student account? This cannot be undone.', { danger: true, confirmText: 'Delete' });
            if (!confirmed) return;
            await Api.del(`/admin/students/${id}`);
            await loadStudents(studentsPage);
            toast('Student deleted.', 'success');
        }

        async function banStudent(id) {
            const { confirmed, value } = await confirmModal('Ban this student?', { danger: true, inputLabel: 'Reason (optional)', confirmText: 'Ban' });
            if (!confirmed) return;
            await Api.post(`/admin/students/${id}/ban`, { reason: value || null });
            await loadStudents(studentsPage);
            toast('Student banned.', 'success');
        }

        async function unbanStudent(id) {
            await Api.post(`/admin/students/${id}/unban`, {});
            await loadStudents(studentsPage);
            toast('Student unbanned.', 'success');
        }

        (async () => {
            await loadLookups();
            await loadMajors();
            await loadScholarships();
            await loadStudents();
        })();
    </script>
</body>
</html>
