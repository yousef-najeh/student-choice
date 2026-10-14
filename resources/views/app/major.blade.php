<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Choice — Major</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body data-major-id="{{ $id }}">
    <script src="/js/api.js"></script>
    <script>requireStudent();</script>
    @include('app.nav')

    <div class="shell">
        <a href="/majors" class="back-link">← Back to majors</a>
        <div id="major-content"><div class="skeleton" style="height:220px;border-radius:14px"></div></div>
    </div>

    <script src="/js/ui.js"></script>
    <script>
        const majorId = document.body.dataset.majorId;

        function renderMajor(m, matchEntry, myScholarships) {
            const campusRows = (m.campuses || []).map(c =>
                `<div class="checkbox-row" style="cursor:default" dir="auto">📍 ${c.city ?? ''}${c.region ? ' · ' + c.region : ''}${c.name ? ' (' + c.name + ')' : ''}</div>`
            ).join('') || '<p class="muted">No campus info recorded.</p>';

            const branchBadges = (m.tawjihi_branches || []).length
                ? m.tawjihi_branches.map(b => `<span class="badge">${b}</span>`).join(' ')
                : `<span class="badge badge-success">Open to all tawjihi branches</span>`;

            const matchBlock = matchEntry ? `
                <div style="display:flex;align-items:center;gap:1rem;margin-top:1rem">
                    ${scoreRing(matchEntry.match.score, 72)}
                    <div style="flex:1">${dimBars(matchEntry.match.breakdown)}</div>
                </div>` : '';

            const hourPriceValue = matchEntry && matchEntry.net_hour_price !== null && matchEntry.net_hour_price !== undefined
                ? `<s class="muted" style="font-size:0.8rem">${m.credit_hour_price ?? '—'}</s> ${matchEntry.net_hour_price}`
                : (m.credit_hour_price ?? '—');

            const myScholarshipCard = myScholarships.length ? `
                <div class="card">
                    <h2>Your scholarship${myScholarships.length > 1 ? 's' : ''}</h2>
                    <p class="muted" style="margin-bottom:0.75rem">Scholarships belong to you, not to this major specifically — they discount whatever major you study.</p>
                    ${myScholarships.map(s => `<div class="checkbox-row" style="cursor:default" dir="auto">🎓 ${s.name} — <b>${s.discount_percentage}%</b></div>`).join('')}
                </div>` : '';

            document.getElementById('major-content').innerHTML = `
                <div class="card">
                    <div class="detail-hero">
                        <div>
                            <h1 dir="auto">${m.name}</h1>
                            <p class="muted" style="margin-top:0.3rem" dir="auto">${m.institution?.name ?? ''} ${m.faculty ? '· ' + m.faculty : ''}</p>
                        </div>
                        ${m.admission_link ? `<a href="${m.admission_link}" target="_blank" rel="noopener" class="btn btn-primary">Apply / admission page ↗</a>` : ''}
                    </div>
                    ${matchBlock}
                    <div class="detail-stat-grid">
                        <div class="detail-stat"><div class="label">Cost per credit hour</div><div class="value">${hourPriceValue}</div></div>
                        <div class="detail-stat"><div class="label">Credit hours</div><div class="value">${m.credit_hours ?? '—'}</div></div>
                        <div class="detail-stat"><div class="label">Total program cost</div><div class="value">${m.total_cost ?? '—'}</div></div>
                        <div class="detail-stat"><div class="label">Admission avg</div><div class="value">${m.admission_average ?? '—'}</div></div>
                        <div class="detail-stat"><div class="label">Min GPA</div><div class="value">${m.min_gpa_to_apply ?? '—'}</div></div>
                        <div class="detail-stat"><div class="label">Duration</div><div class="value" style="font-size:0.85rem" dir="auto">${m.study_duration_text ?? '—'}</div></div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="card">
                        <h2>Campuses</h2>
                        ${campusRows}
                        <div class="section-title">Tawjihi branches accepted</div>
                        <div>${branchBadges}</div>
                    </div>
                    ${myScholarshipCard}
                </div>`;
        }

        async function load() {
            try {
                const [majorRes, user] = await Promise.all([
                    Api.get(`/majors/${majorId}`),
                    Api.get('/user'),
                ]);
                let matchEntry = null;
                if (user.data.role === 'student') {
                    try {
                        const matchRes = await Api.get('/majors/match?per_page=500');
                        matchEntry = matchRes.data.find(m => m.id == majorId);
                    } catch (e) { /* non-fatal — detail still renders without a score */ }
                }
                const myScholarships = (user.data.profile || {}).scholarships || [];
                renderMajor(majorRes.data, matchEntry, myScholarships);
            } catch (e) {
                document.getElementById('major-content').innerHTML = `<div class="error-box" style="display:block">${e.message}</div>`;
            }
        }

        load();
    </script>
</body>
</html>
