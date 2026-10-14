/**
 * Shared UI helpers for the app's pages: toasts, a confirm/prompt modal, an
 * SVG score ring, and pagination controls. Loaded after api.js on every page.
 */

function toast(message, type = 'default') {
  let stack = document.querySelector('.toast-stack');
  if (!stack) {
    stack = document.createElement('div');
    stack.className = 'toast-stack';
    document.body.appendChild(stack);
  }
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.textContent = message;
  stack.appendChild(el);
  setTimeout(() => {
    el.style.transition = 'opacity .25s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 250);
  }, 3200);
}

function confirmModal(message, { danger = false, inputLabel = null, confirmText = 'Confirm' } = {}) {
  return new Promise((resolve) => {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `
      <div class="modal-box">
        <p style="font-weight:700;margin-bottom:${inputLabel ? '0.75rem' : '0'}">${message}</p>
        ${inputLabel ? `
          <label>${inputLabel}</label>
          <input type="text" id="modal-input" autocomplete="off">
        ` : ''}
        <div class="modal-actions">
          <button class="btn btn-secondary" data-act="cancel">Cancel</button>
          <button class="btn ${danger ? 'btn-danger' : 'btn-primary'}" data-act="ok">${confirmText}</button>
        </div>
      </div>`;
    document.body.appendChild(overlay);

    const input = overlay.querySelector('#modal-input');
    if (input) setTimeout(() => input.focus(), 30);

    const close = (confirmed) => {
      overlay.remove();
      resolve({ confirmed, value: input ? input.value : null });
    };

    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) close(false);
    });
    overlay.querySelector('[data-act="cancel"]').addEventListener('click', () => close(false));
    overlay.querySelector('[data-act="ok"]').addEventListener('click', () => close(true));
    if (input) {
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') close(true);
      });
    }
  });
}

function scoreRing(score, size = 56) {
  const s = Math.max(0, Math.min(100, Math.round(score)));
  const color = s >= 80 ? '#15993e' : s >= 50 ? '#b4650a' : '#d6293e';
  const stroke = Math.max(4, Math.round(size / 11));
  const r = (size - stroke) / 2;
  const c = 2 * Math.PI * r;
  const offset = c - (s / 100) * c;
  const fontSize = Math.round(size / 3.4);
  return `
    <span class="score-ring" style="width:${size}px;height:${size}px">
      <svg width="${size}" height="${size}">
        <circle cx="${size / 2}" cy="${size / 2}" r="${r}" fill="none" stroke="#e6e8f2" stroke-width="${stroke}" />
        <circle cx="${size / 2}" cy="${size / 2}" r="${r}" fill="none" stroke="${color}" stroke-width="${stroke}"
          stroke-dasharray="${c}" stroke-dashoffset="${offset}" stroke-linecap="round" />
      </svg>
      <span class="score-num" style="font-size:${fontSize}px;color:${color}">${s}</span>
    </span>`;
}

/**
 * Renders Prev/Next + "Page X of Y" controls from a Laravel paginator's meta
 * (handles both the standard {current_page,last_page,total} shape and the
 * custom {page,per_page,total} shape /majors/match uses). `onPageChangeFn` is
 * the *name* of a globally-defined function the buttons' onclick will call
 * with the target page number, e.g. paginationControls(meta, 'loadMajorsPage').
 */
function paginationControls(meta, onPageChangeFn) {
  if (!meta) return '';
  const current = meta.current_page ?? meta.page ?? 1;
  const perPage = meta.per_page ?? 20;
  const last = meta.last_page ?? Math.max(1, Math.ceil((meta.total ?? 0) / perPage));
  if (last <= 1) return '';

  const prevDisabled = current <= 1 ? 'disabled' : '';
  const nextDisabled = current >= last ? 'disabled' : '';
  return `<div class="pagination">
    <button class="btn btn-secondary btn-sm" ${prevDisabled} onclick="${onPageChangeFn}(${current - 1})">← Prev</button>
    <span class="page-info">Page ${current} of ${last} (${meta.total ?? ''} total)</span>
    <button class="btn btn-secondary btn-sm" ${nextDisabled} onclick="${onPageChangeFn}(${current + 1})">Next →</button>
  </div>`;
}

function dimBars(breakdown) {
  const labels = { preferences: 'Preferences', gpa: 'GPA', tawjihi: 'Tawjihi', budget: 'Budget', location: 'Location' };
  return `<div class="dim-bars">${Object.entries(breakdown).map(([key, val]) => {
    const color = val >= 80 ? '#15993e' : val >= 50 ? '#b4650a' : '#d6293e';
    return `<div class="dim-row">
      <span>${labels[key] || key}</span>
      <span class="dim-track"><span class="dim-fill" style="width:${val}%;background:${color}"></span></span>
      <span>${val}</span>
    </div>`;
  }).join('')}</div>`;
}
