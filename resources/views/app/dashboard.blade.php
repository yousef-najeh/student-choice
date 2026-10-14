<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Choice — Dashboard</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <script src="/js/api.js"></script>
    <script>requireStudent();</script>
    @include('app.nav')

    <div class="shell">
        <div class="page-hd">
            <h1>Your matches</h1>
            <p>Ranked by fit across preferences, GPA, tawjihi branch, budget, and location.</p>
        </div>

        <div id="nudge-wrap"></div>
        <div id="scholarship-wrap"></div>

        <div class="search-row">
            <select id="sort-by" onchange="matchesPage = 1; renderMatches()" style="max-width:220px">
                <option value="score">Sort: best match</option>
                <option value="cost">Sort: lowest cost</option>
                <option value="admission">Sort: lowest admission avg</option>
            </select>
            <span class="nav-spacer"></span>
            <button class="btn btn-secondary btn-sm" onclick="findMatches()">Refresh</button>
        </div>
        <div id="matches"></div>
        <div id="matches-pagination"></div>
    </div>

    <script src="/js/ui.js"></script>
    <script>
        const MATCHES_PAGE_SIZE = 10;
        let lastMatches = [];
        let matchesPage = 1;

        function renderNudge(user) {
            const p = user.profile || {};
            const missing = [];
            if (p.gpa === null || p.gpa === undefined) missing.push('GPA');
            if (!p.tawjihi_branch) missing.push('tawjihi branch');
            if (p.budget_max === null || p.budget_max === undefined) missing.push('budget');

            const wrap = document.getElementById('nudge-wrap');
            if (!missing.length) { wrap.innerHTML = ''; return; }
            wrap.innerHTML = `<div class="nudge">
                <span class="icon">👋</span>
                <div class="nudge-body">
                    <strong>Your matches aren't personalized yet</strong>
                    <span>Add your ${missing.join(', ')} on your profile to get accurate scores instead of neutral ones.</span>
                </div>
                <a href="/profile" class="btn btn-primary btn-sm">Complete profile</a>
            </div>`;
        }

        function renderScholarshipBanner(user) {
            const scholarships = (user.profile || {}).scholarships || [];
            const wrap = document.getElementById('scholarship-wrap');
            if (!scholarships.length) { wrap.innerHTML = ''; return; }
            const names = scholarships.map(s => `${s.name} (${s.discount_percentage}%)`).join(', ');
            wrap.innerHTML = `<div class="nudge" style="background:var(--success-light);border-color:#bfe8cc">
                <span class="icon">🎉</span>
                <div class="nudge-body">
                    <strong>You have a scholarship</strong>
                    <span>${names} — already applied to the per-hour cost shown below on every major.</span>
                </div>
            </div>`;
        }

        async function findMatches() {
            const el = document.getElementById('matches');
            el.innerHTML = Array.from({ length: 3 }).map(() =>
                `<div class="major-card"><div class="skeleton" style="width:56px;height:56px;border-radius:50%"></div>
                 <div style="flex:1"><div class="skeleton" style="height:16px;width:40%;margin-bottom:8px"></div>
                 <div class="skeleton" style="height:12px;width:70%"></div></div></div>`
            ).join('');
            try {
                // Fetch every scored major in one go (catalog is small, dozens not
                // thousands) so sorting/paging below works across all of them —
                // not just whatever page the API's default per_page happened to return.
                const res = await Api.get('/majors/match?per_page=500');
                lastMatches = res.data;
                matchesPage = 1;
                renderMatches();
            } catch (e) {
                el.innerHTML = `<div class="error-box" style="display:block">${e.message}</div>`;
            }
        }

        function goToMatchesPage(page) {
            matchesPage = page;
            renderMatches();
            document.getElementById('matches').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function renderMatches() {
            const el = document.getElementById('matches');
            if (!lastMatches.length) {
                el.innerHTML = `<div class="empty-state card"><div class="icon">🎓</div>No majors yet — ask an admin to add some.</div>`;
                document.getElementById('matches-pagination').innerHTML = '';
                return;
            }
            const sortBy = document.getElementById('sort-by').value;
            const sorted = [...lastMatches].sort((a, b) => {
                if (sortBy === 'cost') return (a.net_hour_price ?? a.credit_hour_price ?? Infinity) - (b.net_hour_price ?? b.credit_hour_price ?? Infinity);
                if (sortBy === 'admission') return (a.admission_average ?? Infinity) - (b.admission_average ?? Infinity);
                return (b.match?.score ?? 0) - (a.match?.score ?? 0);
            });
            const start = (matchesPage - 1) * MATCHES_PAGE_SIZE;
            el.innerHTML = sorted.slice(start, start + MATCHES_PAGE_SIZE).map(renderMajorCard).join('');
            document.getElementById('matches-pagination').innerHTML = paginationControls(
                { current_page: matchesPage, last_page: Math.ceil(sorted.length / MATCHES_PAGE_SIZE), total: sorted.length },
                'goToMatchesPage'
            );
        }

        function renderMajorCard(m) {
            const campuses = (m.campuses || []).map(c => c.city).filter(Boolean).join(', ');
            const costLine = m.net_hour_price !== null && m.net_hour_price !== undefined
                ? `<span>Cost/hr: <s class="muted">${m.credit_hour_price ?? '—'}</s> <b>${m.net_hour_price}</b> <span class="badge badge-success">with your scholarship</span></span>`
                : `<span>Cost/hr: <b>${m.credit_hour_price ?? '—'}</b></span>`;
            return `<a class="major-card" href="/majors/${m.id}">
                ${m.match ? scoreRing(m.match.score) : ''}
                <div class="major-main">
                    <div class="major-title" dir="auto">${m.name}</div>
                    <div class="major-sub" dir="auto">${m.institution?.name ?? ''} · ${m.field_of_study ?? ''} ${campuses ? '· ' + campuses : ''}</div>
                    <div class="major-stats">
                        ${costLine}
                        <span>Credit hours: <b>${m.credit_hours ?? '—'}</b></span>
                        <span>Admission avg: <b>${m.admission_average ?? '—'}</b></span>
                        <span>Min GPA: <b>${m.min_gpa_to_apply ?? '—'}</b></span>
                    </div>
                    ${m.match ? dimBars(m.match.breakdown) : ''}
                </div>
            </a>`;
        }

        (async () => {
            const res = await Api.get('/user');
            renderNudge(res.data);
            renderScholarshipBanner(res.data);
            await findMatches();
        })();
    </script>
</body>
</html>
