<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Choice — Majors</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <script src="/js/api.js"></script>
    <script>requireStudent();</script>
    @include('app.nav')

    <div class="shell">
        <div class="page-hd">
            <h1>Majors</h1>
            <p>Browse the full catalog — no profile needed.</p>
        </div>
        <div class="search-row">
            <input type="text" id="major-search" placeholder="Search by name, institution, or field…" oninput="onSearchChange()">
        </div>
        <div id="majors"><div class="skeleton" style="height:90px;margin-bottom:12px;border-radius:14px"></div></div>
        <div id="majors-pagination"></div>

        <div class="page-hd" style="margin-top:2.5rem">
            <h1>Scholarships</h1>
            <p>Every scholarship imported from the catalog.</p>
        </div>
        <div id="scholarships"><div class="skeleton" style="height:70px;border-radius:14px"></div></div>
    </div>

    <script src="/js/ui.js"></script>
    <script>
        const PAGE_SIZE = 10;
        let allMajors = [];
        let filteredMajors = [];
        let currentPage = 1;

        async function loadMajors() {
            // Small catalog (dozens, not thousands) — fetch it all once so search
            // covers everything, and paginate the (filtered) results client-side.
            const res = await Api.get('/majors?per_page=500');
            allMajors = res.data;
            filteredMajors = allMajors;
            renderMajorsPage();
        }

        function onSearchChange() {
            const q = document.getElementById('major-search').value.trim().toLowerCase();
            filteredMajors = !q ? allMajors : allMajors.filter(m => {
                const haystack = `${m.name} ${m.institution?.name ?? ''} ${m.field_of_study ?? ''}`.toLowerCase();
                return haystack.includes(q);
            });
            currentPage = 1;
            renderMajorsPage();
        }

        function goToMajorsPage(page) {
            currentPage = page;
            renderMajorsPage();
            document.getElementById('majors').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function renderMajorsPage() {
            const el = document.getElementById('majors');
            if (!filteredMajors.length) {
                el.innerHTML = `<div class="empty-state card"><div class="icon">🔎</div>No majors match your search.</div>`;
                document.getElementById('majors-pagination').innerHTML = '';
                return;
            }
            const start = (currentPage - 1) * PAGE_SIZE;
            const pageItems = filteredMajors.slice(start, start + PAGE_SIZE);

            el.innerHTML = pageItems.map(m => {
                const campuses = (m.campuses || []).map(c => c.city).filter(Boolean).join(', ');
                return `<a class="major-card" href="/majors/${m.id}">
                    <div class="major-main">
                        <div class="major-title" dir="auto">${m.name}</div>
                        <div class="major-sub" dir="auto">${m.institution?.name ?? ''} · ${m.field_of_study ?? ''} ${campuses ? '· ' + campuses : ''}</div>
                        <div class="major-stats">
                            <span>Cost/hr: <b>${m.credit_hour_price ?? '—'}</b></span>
                            <span>Credit hours: <b>${m.credit_hours ?? '—'}</b></span>
                            <span>Admission avg: <b>${m.admission_average ?? '—'}</b></span>
                        </div>
                    </div>
                </a>`;
            }).join('');

            document.getElementById('majors-pagination').innerHTML = paginationControls(
                { current_page: currentPage, last_page: Math.ceil(filteredMajors.length / PAGE_SIZE), total: filteredMajors.length },
                'goToMajorsPage'
            );
        }

        async function loadScholarships() {
            const res = await Api.get('/scholarships');
            const el = document.getElementById('scholarships');
            if (!res.data.length) {
                el.innerHTML = `<div class="empty-state card"><div class="icon">🎁</div>No scholarships yet.</div>`;
                return;
            }
            el.innerHTML = res.data.map(s => `<div class="major-card">
                <div class="major-main">
                    <div class="major-title" dir="auto">${s.name} ${s.discount_percentage !== null ? `<span class="badge badge-success">${s.discount_percentage}%</span>` : ''}</div>
                    <div class="major-sub" dir="auto">${s.description ?? ''}</div>
                </div>
            </div>`).join('');
        }

        loadMajors();
        loadScholarships();
    </script>
</body>
</html>
