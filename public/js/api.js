/**
 * Tiny fetch wrapper shared by every page. Attaches the Sanctum bearer token
 * (kept in localStorage) and always asks for/sends JSON.
 */
const Api = (() => {
  const TOKEN_KEY = 'sc_token';
  const USER_KEY = 'sc_user';

  function getToken() {
    return localStorage.getItem(TOKEN_KEY);
  }

  function setSession(token, user) {
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(USER_KEY, JSON.stringify(user));
  }

  function getUser() {
    const raw = localStorage.getItem(USER_KEY);
    return raw ? JSON.parse(raw) : null;
  }

  function clearSession() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
  }

  async function request(method, path, body) {
    const headers = {
      Accept: 'application/json',
    };
    const token = getToken();
    if (token) headers['Authorization'] = `Bearer ${token}`;

    let fetchBody = body;
    if (body && !(body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
      fetchBody = JSON.stringify(body);
    }

    const res = await fetch(`/api${path}`, { method, headers, body: fetchBody });
    const text = await res.text();
    const data = text ? JSON.parse(text) : null;

    if (!res.ok) {
      const message = data?.message || `Request failed (${res.status})`;
      const err = new Error(message);
      err.status = res.status;
      err.data = data;
      throw err;
    }

    return data;
  }

  return {
    get: (path) => request('GET', path),
    post: (path, body) => request('POST', path, body),
    put: (path, body) => request('PUT', path, body),
    del: (path) => request('DELETE', path),
    getToken,
    getUser,
    setSession,
    clearSession,
  };
})();

function requireAuth() {
  if (!Api.getToken()) {
    window.location.href = '/login';
  }
}

function requireAdmin() {
  requireAuth();
  const user = Api.getUser();
  if (!user || user.role !== 'admin') {
    if (typeof toast === 'function') {
      toast('Admin access only.', 'error');
      setTimeout(() => window.location.href = '/dashboard', 600);
    } else {
      window.location.href = '/dashboard';
    }
  }
}

/**
 * Gate the student-facing pages (dashboard, profile, majors browse/detail) from
 * admin accounts — admins have no student_profiles row, so /api/majors/match
 * and PUT /profile would just error for them. Admin manages everything through
 * /admin instead.
 */
function requireStudent() {
  requireAuth();
  const user = Api.getUser();
  if (user && user.role === 'admin') {
    window.location.href = '/admin';
  }
}

function showError(elId, err) {
  const el = document.getElementById(elId);
  if (!el) return;
  el.textContent = err.message || String(err);
  el.style.display = 'block';
}

function hideError(elId) {
  const el = document.getElementById(elId);
  if (el) el.style.display = 'none';
}
