/* ============================================
   Tiny API helper used by index.html / app.js.
   Wraps fetch(), attaches the JWT token, and sends
   every request through api/index.php?path=...
   so Apache does not redirect POST/PATCH requests
   to real folders like api/books/ or api/messages/.
   ============================================ */

var TOKEN_KEY = 'lbs_token';
var USER_KEY  = 'lbs_currentUser';

function getToken() { return localStorage.getItem(TOKEN_KEY); }
function setToken(t) { localStorage.setItem(TOKEN_KEY, t); }
function clearAuth() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
}

function setCurrentUser(u) { localStorage.setItem(USER_KEY, JSON.stringify(u)); }
function getCurrentUser()  {
  var raw = localStorage.getItem(USER_KEY);
  return raw ? JSON.parse(raw) : null;
}

function buildApiUrl(path) {
  var route = String(path || '').replace(/^\/+/, '');

  // Allow direct PHP entry files when a feature needs to avoid Apache folder rewrites.
  // Example: api/messages/index.php and api/messages/thread.php are real PHP files.
  if (/^api\/.*\.php(\?|$)/.test(route)) {
    return route;
  }

  var queryIndex = route.indexOf('?');
  var routePath = queryIndex >= 0 ? route.slice(0, queryIndex) : route;
  var query = queryIndex >= 0 ? route.slice(queryIndex + 1) : '';
  var url = 'api/index.php?path=' + encodeURIComponent(routePath);
  if (query) url += '&' + query;
  return url;
}

async function api(path, opts) {
  opts = opts || {};
  var headers = opts.headers || {};
  headers['Content-Type'] = 'application/json';

  var token = getToken();
  if (token) headers['Authorization'] = 'Bearer ' + token;

  var body = opts.body;
  if (body && typeof body !== 'string') body = JSON.stringify(body);

  var res = await fetch(buildApiUrl(path), {
    method: opts.method || 'GET',
    headers: headers,
    body: body
  });

  var json;
  try { json = await res.json(); } catch (e) { json = {}; }

  if (!res.ok || json.ok === false) {
    if (res.status === 401 && getToken()) {
      clearAuth();
      if (location.pathname.indexOf('index.html') === -1 && location.pathname !== '/') {
        location.href = 'index.html';
      }
    }
    throw new Error(json.error || ('Request failed (' + res.status + ')'));
  }

  return json.data;
}
