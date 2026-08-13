/* ============================================
   Library Book Share System - Application Logic
   Talks to the PHP backend via api/index.php?path=...
   (uses helpers from js/api.js).
   ============================================ */

// ══════════════════════════════════════════════
//  STATE
// ══════════════════════════════════════════════

var currentUser = null;
var currentChatUserId = null;
var chatPollTimer = null;
var googleMapsReady = false;
var mapInstance = null;
var mapMarkers = [];

// ── Cached data so other handlers can reuse without re-fetching ──
var cache = { browseBooks: [], myBooks: [], requests: [] };

// ══════════════════════════════════════════════
//  INIT — runs as soon as the script loads
// ══════════════════════════════════════════════

(async function init() {
  if (!getToken()) {
    window.location.href = 'index.html';
    return;
  }
  try {
    // Confirm token is valid + load fresh user record
    currentUser = await api('/auth/me');
    setCurrentUser(currentUser);
  } catch (e) {
    clearAuth();
    window.location.href = 'index.html';
    return;
  }

  document.getElementById('navUserName').textContent = currentUser.fullName;
  document.getElementById('welcomeName').textContent = currentUser.fullName.split(' ')[0];

  showSection('home');
})();

// ══════════════════════════════════════════════
//  UTILITIES
// ══════════════════════════════════════════════

function getInitials(name) {
  if (!name) return '?';
  var parts = name.split(' ');
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
  return name[0].toUpperCase();
}

function formatDate(dateStr) {
  var d = new Date(dateStr);
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatTime(dateStr) {
  var d = new Date(dateStr);
  return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

// Returns how many whole days a loan is overdue, or null if it is not overdue.
// Overdue only applies to an active loan: approved, has a due date, not yet confirmed returned.
function overdueDays(r) {
  if (!r.dueDate || r.status !== 'approved' || r.returnConfirmed) return null;
  // Build a local-midnight date from the YYYY-MM-DD string to avoid timezone drift.
  var parts = String(r.dueDate).substring(0, 10).split('-');
  var due = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
  var today = new Date();
  today.setHours(0, 0, 0, 0);
  if (today > due) {
    return Math.floor((today - due) / 86400000); // 86400000 ms = 1 day
  }
  return null;
}

function getCategoryIcon(category) {
  var icons = {
    'Fiction': '📖', 'Non-Fiction': '📘', 'Academic': '🎓',
    'Science': '🔬', 'Technology': '💻', 'History': '📜',
    'Literature': '📕', 'Religion': '🕊️', 'Other': '📗'
  };
  return icons[category] || '📗';
}

function escapeHtml(text) {
  var div = document.createElement('div');
  div.appendChild(document.createTextNode(text == null ? '' : String(text)));
  return div.innerHTML;
}

function jsString(text) {
  // Safely places names inside double-quoted inline onclick attributes.
  // JSON.stringify adds quotes for JavaScript, then HTML entities stop those
  // quotes from closing the onclick="..." attribute early.
  return JSON.stringify(text == null ? '' : String(text))
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function getActiveChatUserId() {
  var id = Number(currentChatUserId || 0);
  if (id > 0) return id;

  var chatWindow = document.getElementById('chatWindow');
  id = Number(chatWindow && chatWindow.getAttribute('data-user-id'));
  return id > 0 ? id : null;
}

function normalizeMessages(data) {
  // Accepts all safe response formats:
  // 1) [message, message]
  // 2) { thread: [message, message] }
  // 3) { data: [message, message] } from older wrappers
  if (Array.isArray(data)) return data;
  if (data && Array.isArray(data.thread)) return data.thread;
  if (data && Array.isArray(data.data)) return data.data;
  if (data && data.message) return [data.message];
  return [];
}

function renderChatMessages(messages) {
  var container = document.getElementById('chatMessages');
  var msgs = normalizeMessages(messages);

  if (!msgs.length) {
    container.innerHTML = '<div class="empty-state" style="padding:40px;"><p>Start the conversation! Say hello.</p></div>';
    return;
  }

  container.innerHTML = msgs.map(function(m) {
    var isSent = Number(m.fromId) === Number(currentUser.id);
    return '<div class="chat-bubble ' + (isSent ? 'sent' : 'received') + '">' +
             escapeHtml(m.text || m.body || m.message || '') +
             '<div class="bubble-time">' + formatTime(m.timestamp || m.created_at || new Date()) + '</div>' +
           '</div>';
  }).join('');
  container.scrollTop = container.scrollHeight;
}

function appendChatMessage(m) {
  var container = document.getElementById('chatMessages');
  var empty = container.querySelector('.empty-state');
  if (empty) container.innerHTML = '';
  var isSent = Number(m.fromId) === Number(currentUser.id);
  container.innerHTML += '<div class="chat-bubble ' + (isSent ? 'sent' : 'received') + '">' +
    escapeHtml(m.text || m.body || m.message || '') +
    '<div class="bubble-time">' + formatTime(m.timestamp || m.created_at || new Date()) + '</div>' +
    '</div>';
  container.scrollTop = container.scrollHeight;
}

function showToast(message, type) {
  var existing = document.querySelector('.toast');
  if (existing) existing.remove();
  var toast = document.createElement('div');
  toast.className = 'toast ' + (type || '');
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(function() { toast.remove(); }, 3000);
}

function handleApiError(err) {
  showToast(err.message || 'Something went wrong', 'error');
}

// ══════════════════════════════════════════════
//  NAVIGATION
// ══════════════════════════════════════════════

function showSection(section) {
  // Stop chat polling when leaving Messages
  if (section !== 'messages') {
    stopChatPolling();
    currentChatUserId = null;
    var chatWindow = document.getElementById('chatWindow');
    if (chatWindow) chatWindow.removeAttribute('data-user-id');
  }

  var sections = ['Home', 'Mybooks', 'Browse', 'Requests', 'Messages', 'Profile', 'Reports'];
  sections.forEach(function(s) {
    document.getElementById('section' + s).classList.add('hidden');
    var navLink = document.getElementById('nav' + s);
    if (navLink) navLink.classList.remove('active');
  });

  var sectionId = 'section' + section.charAt(0).toUpperCase() + section.slice(1);
  document.getElementById(sectionId).classList.remove('hidden');

  var navId = 'nav' + section.charAt(0).toUpperCase() + section.slice(1);
  var navEl = document.getElementById(navId);
  if (navEl) navEl.classList.add('active');

  if (section === 'home')     loadHome();
  if (section === 'mybooks')  loadMyBooks();
  if (section === 'browse')   loadBrowseBooks();
  if (section === 'requests') loadRequests();
  if (section === 'messages') loadMessages();
  if (section === 'profile')  loadProfile();
  if (section === 'reports')  loadReports();
}

// ══════════════════════════════════════════════
//  HOME
// ══════════════════════════════════════════════

async function loadHome() {
  try {
    var [allBooks, myBooks, requests, conversations] = await Promise.all([
      api('/books'),
      api('/books/mine'),
      api('/requests'),
      api('/messages')
    ]);

    var availableNearby = allBooks.filter(function(b) {
      return b.ownerId !== currentUser.id && b.status === 'available';
    });
    var pending = requests.filter(function(r) { return r.status === 'pending'; });

    document.getElementById('statMyBooks').textContent    = myBooks.length;
    document.getElementById('statAvailable').textContent  = availableNearby.length;
    document.getElementById('statRequests').textContent   = pending.length;
    document.getElementById('statMessages').textContent   = conversations.length;

    // Most recent 6 books
    var recent = allBooks.slice(0, 6);
    var grid = document.getElementById('recentBooks');
    if (recent.length === 0) {
      grid.innerHTML = '<div class="empty-state"><div class="empty-icon">📚</div><h3>No books available yet</h3></div>';
    } else {
      grid.innerHTML = recent.map(function(b) { return createBookCard(b, false); }).join('');
    }
  } catch (err) { handleApiError(err); }
}

// ══════════════════════════════════════════════
//  MY BOOKS
// ══════════════════════════════════════════════

async function loadMyBooks() {
  try {
    cache.myBooks = await api('/books/mine');
    var grid  = document.getElementById('myBooksGrid');
    var empty = document.getElementById('myBooksEmpty');
    if (cache.myBooks.length === 0) {
      grid.innerHTML = '';
      empty.classList.remove('hidden');
    } else {
      empty.classList.add('hidden');
      grid.innerHTML = cache.myBooks.map(function(b) { return createBookCard(b, true); }).join('');
    }
  } catch (err) { handleApiError(err); }
}

function createBookCard(book, isOwner) {
  var icon = getCategoryIcon(book.category);
  var statusClass = book.status === 'available' ? 'available' : 'borrowed';
  var statusText  = book.status === 'available' ? 'Available' : 'Borrowed';

  var actions;
  if (isOwner) {
    actions = '<div class="book-card-actions">' +
      '<button class="btn btn-primary btn-sm" onclick="openEditBookModal(' + book.id + ')">Edit</button>' +
      '<button class="btn btn-danger btn-sm" onclick="deleteBook(' + book.id + ')">Delete</button>' +
      '</div>';
  } else {
    actions = '<div class="book-card-actions">' +
      '<button class="btn btn-primary btn-sm" onclick="viewBookDetail(' + book.id + ')">View Details</button>' +
      (book.status === 'available' ? '<button class="btn btn-success btn-sm" onclick="requestBook(' + book.id + ')">Request</button>' : '') +
      '</div>';
  }

  return '<div class="book-card">' +
    '<div class="book-card-cover">' + icon + '</div>' +
    '<div class="book-card-body">' +
      '<h4>' + escapeHtml(book.title) + '</h4>' +
      '<div class="book-author">by ' + escapeHtml(book.author) + '</div>' +
      '<div class="book-location">📍 ' + escapeHtml(book.ownerLocation) + '</div>' +
      '<span class="book-category">' + escapeHtml(book.category) + '</span> ' +
      '<span class="book-status ' + statusClass + '">' + statusText + '</span>' +
    '</div>' +
    actions +
  '</div>';
}

// ── Add/Edit modal ──
function openAddBookModal() {
  document.getElementById('bookModalTitle').textContent = 'Add New Book';
  document.getElementById('bookSubmitBtn').textContent = 'Add Book';
  document.getElementById('bookEditId').value = '';
  document.getElementById('bookForm').reset();
  document.getElementById('bookModal').classList.remove('hidden');
}

function openEditBookModal(bookId) {
  var book = cache.myBooks.find(function(b) { return b.id === bookId; });
  if (!book) return;
  document.getElementById('bookModalTitle').textContent = 'Edit Book';
  document.getElementById('bookSubmitBtn').textContent = 'Save Changes';
  document.getElementById('bookEditId').value      = book.id;
  document.getElementById('bookTitle').value       = book.title;
  document.getElementById('bookAuthor').value      = book.author;
  document.getElementById('bookCategory').value    = book.category;
  document.getElementById('bookDescription').value = book.description || '';
  document.getElementById('bookCondition').value   = book.condition;
  document.getElementById('bookModal').classList.remove('hidden');
}

function closeBookModal() {
  document.getElementById('bookModal').classList.add('hidden');
}

async function handleBookSubmit(e) {
  e.preventDefault();
  var editId      = document.getElementById('bookEditId').value;
  var payload = {
    title:       document.getElementById('bookTitle').value.trim(),
    author:      document.getElementById('bookAuthor').value.trim(),
    category:    document.getElementById('bookCategory').value,
    description: document.getElementById('bookDescription').value.trim(),
    condition:   document.getElementById('bookCondition').value
  };

  try {
    if (editId) {
      await api('/books/' + editId, { method: 'PUT', body: payload });
      showToast('Book updated successfully!', 'success');
    } else {
      await api('/books', { method: 'POST', body: payload });
      showToast('Book added successfully!', 'success');
    }
    closeBookModal();
    loadMyBooks();
  } catch (err) { handleApiError(err); }
}

async function deleteBook(bookId) {
  if (!confirm('Are you sure you want to delete this book?')) return;
  try {
    await api('/books/' + bookId, { method: 'DELETE' });
    showToast('Book deleted.', 'success');
    loadMyBooks();
  } catch (err) { handleApiError(err); }
}

// ══════════════════════════════════════════════
//  BROWSE
// ══════════════════════════════════════════════

async function loadBrowseBooks() {
  await filterBooks();
}

async function filterBooks() {
  var query    = document.getElementById('searchInput').value.trim();
  var category = document.getElementById('searchCategory').value;
  var nearby   = document.getElementById('searchNearby').checked;

  var qs = [];
  if (query)    qs.push('q=' + encodeURIComponent(query));
  if (category) qs.push('category=' + encodeURIComponent(category));
  if (nearby)   qs.push('nearby=1');
  var path = '/books' + (qs.length ? '?' + qs.join('&') : '');

  try {
    var books = await api(path);
    // Hide my own books in Browse view
    books = books.filter(function(b) { return b.ownerId !== currentUser.id; });
    cache.browseBooks = books;

    var grid  = document.getElementById('browseGrid');
    var empty = document.getElementById('browseEmpty');
    if (books.length === 0) {
      grid.innerHTML = '';
      empty.classList.remove('hidden');
    } else {
      empty.classList.add('hidden');
      grid.innerHTML = books.map(function(b) { return createBookCard(b, false); }).join('');
    }
    renderMap();
  } catch (err) { handleApiError(err); }
}

// ══════════════════════════════════════════════
//  GOOGLE MAPS
//  Called by the Maps script tag in dashboard.html
// ══════════════════════════════════════════════

function onGoogleMapsReady() {
  googleMapsReady = true;
  // If Browse section is visible, draw the map immediately
  if (!document.getElementById('sectionBrowse').classList.contains('hidden')) {
    renderMap();
  }
}

function renderMap() {
  if (!googleMapsReady || typeof google === 'undefined') return;
  var mapEl = document.getElementById('map');
  if (!mapEl) return;

  // Default centre = current user's coords or Nairobi CBD
  var centre = {
    lat: currentUser.latitude  || -1.2921,
    lng: currentUser.longitude || 36.8219
  };

  if (!mapInstance) {
    mapInstance = new google.maps.Map(mapEl, { center: centre, zoom: 11 });
  } else {
    mapInstance.setCenter(centre);
  }

  // Clear old markers
  mapMarkers.forEach(function(m) { m.setMap(null); });
  mapMarkers = [];

  cache.browseBooks.forEach(function(book) {
    if (book.ownerLatitude == null || book.ownerLongitude == null) return;
    var marker = new google.maps.Marker({
      position: { lat: Number(book.ownerLatitude), lng: Number(book.ownerLongitude) },
      map: mapInstance,
      title: book.title + ' — ' + book.ownerName
    });
    marker.addListener('click', function() { viewBookDetail(book.id); });
    mapMarkers.push(marker);
  });
}

// ══════════════════════════════════════════════
//  BOOK DETAIL MODAL
// ══════════════════════════════════════════════

async function viewBookDetail(bookId) {
  try {
    var book = await api('/books/' + bookId);
    document.getElementById('detailTitle').textContent = book.title;

    var body =
      '<div style="text-align:center; font-size:64px; margin-bottom:16px;">' + getCategoryIcon(book.category) + '</div>' +
      '<table style="width:100%; font-size:14px;">' +
        row('Author',      escapeHtml(book.author)) +
        row('Category',    escapeHtml(book.category)) +
        row('Condition',   escapeHtml(book.condition)) +
        row('Status',      '<span class="book-status ' + (book.status === 'available' ? 'available' : 'borrowed') + '">' + (book.status === 'available' ? 'Available' : 'Borrowed') + '</span>') +
        row('Owner',       escapeHtml(book.ownerName)) +
        row('Location',    '📍 ' + escapeHtml(book.ownerLocation)) +
        row('Description', escapeHtml(book.description || 'No description provided.')) +
        row('Listed On',   formatDate(book.dateAdded)) +
      '</table>';
    document.getElementById('detailBody').innerHTML = body;

    var footer = '';
    if (book.status === 'available' && book.ownerId !== currentUser.id) {
      footer += '<button class="btn btn-success" onclick="requestBook(' + book.id + '); closeDetailModal();">Request to Borrow</button> ';
    }
    if (book.ownerId !== currentUser.id) {
      footer += '<button class="btn btn-primary" onclick="startChat(' + book.ownerId + ', ' + jsString(book.ownerName) + '); closeDetailModal();">Message Owner</button>';
    }
    document.getElementById('detailFooter').innerHTML = footer;
    document.getElementById('detailModal').classList.remove('hidden');
  } catch (err) { handleApiError(err); }
}

function row(label, value) {
  return '<tr><td style="padding:8px; font-weight:600; width:120px;">' + label + '</td><td style="padding:8px;">' + value + '</td></tr>';
}

function closeDetailModal() {
  document.getElementById('detailModal').classList.add('hidden');
}

// ══════════════════════════════════════════════
//  REQUESTS
// ══════════════════════════════════════════════

async function requestBook(bookId) {
  try {
    await api('/requests', { method: 'POST', body: { bookId: bookId } });
    showToast('Request sent!', 'success');
    await loadRequests();
  } catch (err) { handleApiError(err); }
}

async function loadRequests() {
  try {
    var all = await api('/requests');
    cache.requests = all;

    // Incoming = where I am the owner
    var incoming = all.filter(function(r) { return r.ownerId === currentUser.id; });
    var incEl    = document.getElementById('incomingRequests');
    if (incoming.length === 0) {
      incEl.innerHTML = '<div class="empty-state" style="padding:30px;"><p>No incoming requests yet.</p></div>';
    } else {
      var html = '<table class="requests-table"><thead><tr><th>Book</th><th>Requested By</th><th>Date</th><th>Due Date</th><th>Status</th><th>Action</th></tr></thead><tbody>';
      incoming.forEach(function(r) {
        var actions = '';
        if (r.status === 'pending') {
          // Approve now opens a small modal so the owner can set a return date.
          actions = '<button class="btn btn-success btn-sm" onclick="openApproveModal(' + r.id + ')">Approve</button> ' +
                    '<button class="btn btn-danger btn-sm"  onclick="patchRequest(' + r.id + ', \'reject\')">Reject</button>';
        } else if (r.status === 'approved') {
          // Once the borrower has marked the book returned, offer "Confirm Return".
          if (r.returnedByBorrower && !r.returnConfirmed) {
            actions += '<button class="btn btn-success btn-sm" onclick="patchRequest(' + r.id + ', \'confirm-return\')">Confirm Return</button> ';
          }
          // Keep the original one-click completion as an owner fallback.
          actions += '<button class="btn btn-primary btn-sm" onclick="patchRequest(' + r.id + ', \'complete\')">Mark Completed</button>';
        }
        // Chat button lets the owner message the borrower (e.g. to agree on the return date).
        actions += ' <button class="btn btn-secondary btn-sm" onclick="startChat(' + r.requesterId + ', ' + jsString(r.requesterName) + ')">Chat</button>';

        var statusCell = '<span class="status-badge ' + r.status + '">' +
                         r.status.charAt(0).toUpperCase() + r.status.slice(1) + '</span>';
        var od = overdueDays(r);
        if (od) {
          statusCell += ' <span class="status-badge overdue">Overdue by ' + od + ' day' + (od === 1 ? '' : 's') + '</span>';
        } else if (r.returnedByBorrower && !r.returnConfirmed) {
          statusCell += ' <span class="status-badge pending">Return pending</span>';
        }

        html += '<tr><td>' + escapeHtml(r.bookTitle) + '</td><td>' + escapeHtml(r.requesterName) + '</td><td>' +
                formatDate(r.dateRequested) + '</td><td>' + (r.dueDate ? formatDate(r.dueDate) : '—') + '</td><td>' +
                statusCell + '</td><td>' + actions + '</td></tr>';
      });
      html += '</tbody></table>';
      incEl.innerHTML = html;
    }

    // Outgoing = where I am the requester
    var outgoing = all.filter(function(r) { return r.requesterId === currentUser.id; });
    var outEl    = document.getElementById('outgoingRequests');
    if (outgoing.length === 0) {
      outEl.innerHTML = '<div class="empty-state" style="padding:30px;"><p>You haven\'t made any requests yet.</p></div>';
    } else {
      var html2 = '<table class="requests-table"><thead><tr><th>Book</th><th>Owner</th><th>Date</th><th>Due Date</th><th>Status</th><th>Action</th></tr></thead><tbody>';
      outgoing.forEach(function(r) {
        var actions = '';
        if (r.status === 'pending') {
          actions = '<button class="btn btn-secondary btn-sm" onclick="patchRequest(' + r.id + ', \'cancel\')">Cancel</button>';
        } else if (r.status === 'approved') {
          if (!r.returnedByBorrower) {
            // Borrower can hand the book back.
            actions = '<button class="btn btn-primary btn-sm" onclick="patchRequest(' + r.id + ', \'mark-returned\')">Mark as Returned</button>';
          } else if (!r.returnConfirmed) {
            actions = '<span style="color:#6c757d; font-size:12px;">Waiting for owner to confirm</span>';
          }
        }
        // Chat button lets the borrower message the owner (e.g. to agree on the return date).
        actions += ' <button class="btn btn-secondary btn-sm" onclick="startChat(' + r.ownerId + ', ' + jsString(r.ownerName) + ')">Chat</button>';

        var statusCell = '<span class="status-badge ' + r.status + '">' +
                         r.status.charAt(0).toUpperCase() + r.status.slice(1) + '</span>';
        var od = overdueDays(r);
        if (od) {
          statusCell += ' <span class="status-badge overdue">Overdue by ' + od + ' day' + (od === 1 ? '' : 's') + '</span>';
        }

        html2 += '<tr><td>' + escapeHtml(r.bookTitle) + '</td><td>' + escapeHtml(r.ownerName) + '</td><td>' +
                 formatDate(r.dateRequested) + '</td><td>' + (r.dueDate ? formatDate(r.dueDate) : '—') + '</td><td>' +
                 statusCell + '</td><td>' + actions + '</td></tr>';
      });
      html2 += '</tbody></table>';
      outEl.innerHTML = html2;
    }
  } catch (err) { handleApiError(err); }
}

async function patchRequest(id, action) {
  try {
    await api('/requests/' + id, { method: 'PATCH', body: { action: action } });
    // Friendly success message per action (replaces the old grammar guess).
    var labels = {
      'approve': 'Request approved!',
      'reject': 'Request rejected!',
      'cancel': 'Request cancelled!',
      'complete': 'Request completed!',
      'mark-returned': 'Marked as returned!',
      'confirm-return': 'Return confirmed!'
    };
    showToast(labels[action] || 'Request updated!', 'success');
    loadRequests();
  } catch (err) { handleApiError(err); }
}

// ── Approve-with-due-date modal ──────────────────────────────
// Remembers which request the owner is approving while the modal is open.
var approveRequestId = null;

function openApproveModal(requestId) {
  approveRequestId = requestId;
  document.getElementById('approveForm').reset();
  document.getElementById('approveModal').classList.remove('hidden');
}

function closeApproveModal() {
  approveRequestId = null;
  document.getElementById('approveModal').classList.add('hidden');
}

async function submitApprove(e) {
  e.preventDefault();
  var dueDate = document.getElementById('approveDueDate').value; // YYYY-MM-DD from <input type="date">
  if (!dueDate) { showToast('Please choose a return date.', 'error'); return; }
  try {
    await api('/requests/' + approveRequestId, { method: 'PATCH', body: { action: 'approve', dueDate: dueDate } });
    showToast('Request approved!', 'success');
    closeApproveModal();
    loadRequests();
  } catch (err) { handleApiError(err); }
}

// ══════════════════════════════════════════════
//  MESSAGES
// ══════════════════════════════════════════════

async function loadMessages() {
  try {
    var convs = await api('api/messages/index.php');
    var listEl = document.getElementById('messageList');
    if (convs.length === 0) {
      listEl.innerHTML = '<div class="empty-state" style="padding:30px;"><p>No conversations yet.</p></div>';
    } else {
      listEl.innerHTML = convs.map(function(c) {
        return '<div class="message-item" onclick="openChat(' + c.partnerId + ', ' + jsString(c.partnerName) + ')">' +
          '<div class="message-avatar">' + getInitials(c.partnerName) + '</div>' +
          '<div class="message-content"><h4>' + escapeHtml(c.partnerName) + '</h4><p>' + escapeHtml(c.lastMessage) + '</p></div>' +
          '<div class="message-time">' + formatTime(c.lastTime) + '</div>' +
        '</div>';
      }).join('');
    }
    updateMessageBadge(convs.length);
  } catch (err) { handleApiError(err); }
}

function updateMessageBadge(count) {
  var badge = document.getElementById('msgBadge');
  if (count > 0) {
    badge.textContent = count;
    badge.classList.remove('hidden');
  } else {
    badge.classList.add('hidden');
  }
}

function startChat(userId, userName) {
  // Used by the Message Owner button.
  // Open the Messages section, then open a chat directly with the book owner.
  showSection('messages');
  openChat(Number(userId), userName || 'Book Owner');
  var section = document.getElementById('sectionMessages');
  if (section && section.scrollIntoView) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

async function openChat(userId, userName) {
  var selectedUserId = Number(userId);
  if (!selectedUserId) {
    showToast('Could not open this conversation.', 'error');
    return;
  }

  currentChatUserId = selectedUserId;
  document.getElementById('chatWindow').setAttribute('data-user-id', String(selectedUserId));
  document.getElementById('chatPlaceholder').classList.add('hidden');
  document.getElementById('chatWindow').classList.remove('hidden');
  document.getElementById('chatAvatar').textContent = getInitials(userName);
  document.getElementById('chatUserName').textContent = userName;
  await loadChatMessages(selectedUserId);
  document.getElementById('chatInput').focus();
  startChatPolling(selectedUserId);
}

async function loadChatMessages(userId) {
  try {
    var msgs = await api('api/messages/thread.php?userId=' + encodeURIComponent(userId));
    renderChatMessages(msgs);
  } catch (err) {
    document.getElementById('chatMessages').innerHTML =
      '<div class="empty-state" style="padding:40px;"><p>Could not load messages: ' + escapeHtml(err.message) + '</p></div>';
    handleApiError(err);
  }
}

async function sendMessage(event) {
  if (event && event.preventDefault) event.preventDefault();

  var input = document.getElementById('chatInput');
  var sendBtn = document.getElementById('chatSendBtn');
  var toId = getActiveChatUserId();
  var text = input.value.trim();
  if (!text) return false;

  if (!toId) {
    showToast('Open a conversation first.', 'error');
    return false;
  }

  currentChatUserId = toId;
  if (sendBtn) {
    sendBtn.disabled = true;
    sendBtn.textContent = 'Sending...';
  }

  try {
    var result = await api('api/messages/index.php', {
      method: 'POST',
      body: { toId: toId, body: text }
    });

    input.value = '';

    // New backend returns { message, thread }. Older backend may return just one message.
    if (result && Array.isArray(result.thread)) {
      renderChatMessages(result.thread);
    } else if (result && result.message) {
      appendChatMessage(result.message);
      await loadChatMessages(toId);
    } else {
      appendChatMessage(result);
      await loadChatMessages(toId);
    }

    loadMessages(); // refresh sidebar preview
  } catch (err) {
    document.getElementById('chatMessages').innerHTML +=
      '<div class="empty-state" style="padding:20px;"><p>Message was not sent: ' + escapeHtml(err.message) + '</p></div>';
    handleApiError(err);
  } finally {
    if (sendBtn) {
      sendBtn.disabled = false;
      sendBtn.textContent = 'Send';
    }
    input.focus();
  }

  return false;
}

// ── Polling: refresh open chat every 5 s ──
function startChatPolling(userId) {
  if (chatPollTimer) { clearInterval(chatPollTimer); chatPollTimer = null; }
  var pollUserId = Number(userId || getActiveChatUserId());
  chatPollTimer = setInterval(function() {
    var activeUserId = Number(currentChatUserId || pollUserId || getActiveChatUserId());
    if (activeUserId) loadChatMessages(activeUserId);
  }, 5000);
}

function stopChatPolling() {
  if (chatPollTimer) { clearInterval(chatPollTimer); chatPollTimer = null; }
}

// ══════════════════════════════════════════════
//  PROFILE
// ══════════════════════════════════════════════

function loadProfile() {
  document.getElementById('profileAvatar').textContent  = getInitials(currentUser.fullName);
  document.getElementById('profileName').value          = currentUser.fullName;
  document.getElementById('profileEmail').value         = currentUser.email;
  document.getElementById('profilePhone').value         = currentUser.phone || '';
  document.getElementById('profileLocation').value      = currentUser.location;
  document.getElementById('profileJoinDate').value      = formatDate(currentUser.joinDate);
  document.getElementById('profileGeoStatus').textContent =
    (currentUser.latitude != null && currentUser.longitude != null)
      ? 'GPS: ' + Number(currentUser.latitude).toFixed(4) + ', ' + Number(currentUser.longitude).toFixed(4)
      : 'No GPS coordinates saved.';
}

function recaptureLocation() {
  var status = document.getElementById('profileGeoStatus');
  if (!navigator.geolocation) { status.textContent = 'Geolocation not supported.'; return; }
  status.textContent = 'Requesting location...';
  navigator.geolocation.getCurrentPosition(function(pos) {
    currentUser._newLat = pos.coords.latitude;
    currentUser._newLng = pos.coords.longitude;
    status.textContent = '✓ New GPS captured (' + pos.coords.latitude.toFixed(4) + ', ' + pos.coords.longitude.toFixed(4) + '). Click Update Profile to save.';
  }, function(err) {
    status.textContent = 'Could not get location: ' + err.message;
  });
}

async function updateProfile(e) {
  e.preventDefault();
  var payload = {
    fullName:  document.getElementById('profileName').value.trim(),
    location:  document.getElementById('profileLocation').value.trim(),
    phone:     document.getElementById('profilePhone').value.trim(),
    latitude:  currentUser._newLat != null ? currentUser._newLat : currentUser.latitude,
    longitude: currentUser._newLng != null ? currentUser._newLng : currentUser.longitude
  };
  // Same Kenyan phone rule as registration (backend re-checks this too).
  if (!/^0[17][0-9]{8}$/.test(payload.phone)) {
    showToast('Phone number must be exactly 10 digits and start with 07 or 01', 'error');
    return;
  }
  try {
    currentUser = await api('/auth/me', { method: 'PUT', body: payload });
    setCurrentUser(currentUser);
    document.getElementById('navUserName').textContent = currentUser.fullName;
    showToast('Profile updated!', 'success');
  } catch (err) { handleApiError(err); }
}

// ══════════════════════════════════════════════
//  REPORTS  (Borrowing History Report)
// ══════════════════════════════════════════════

// Builds the logged-in user's borrowing history from the same /requests data
// the Requests page uses. "Borrowing" = requests where I am the requester.
async function loadReports() {
  try {
    var all = await api('/requests');
    cache.requests = all;
    // Only my borrowing records; the API already returns newest activity first.
    var mine = all.filter(function(r) { return r.requesterId === currentUser.id; });

    // Totals (computed live from the records, nothing stored separately).
    var borrowed = mine.filter(function(r) { return r.status === 'approved' || r.status === 'completed'; }).length;
    var returned = mine.filter(function(r) { return r.returnConfirmed || r.status === 'completed'; }).length;
    var active   = mine.filter(function(r) { return r.status === 'approved' && !r.returnConfirmed; }).length;
    document.getElementById('statTotalBorrowed').textContent = borrowed;
    document.getElementById('statTotalReturned').textContent = returned;
    document.getElementById('statActiveBorrow').textContent  = active;

    var el = document.getElementById('borrowingHistory');
    if (mine.length === 0) {
      el.innerHTML = '<div class="empty-state" style="padding:30px;"><p>You have no borrowing history yet.</p></div>';
      return;
    }

    var html = '<table class="requests-table"><thead><tr><th>Book Title</th><th>Book Owner</th><th>Borrow Date</th><th>Due Date</th><th>Return Date</th><th>Status</th></tr></thead><tbody>';
    mine.forEach(function(r) {
      var st = reportStatus(r);
      // No dedicated return-date column exists; updatedAt is the time the owner confirmed the return.
      var returnDate = r.returnConfirmed ? formatDate(r.updatedAt) : '—';
      html += '<tr><td>' + escapeHtml(r.bookTitle) + '</td><td>' + escapeHtml(r.ownerName) + '</td><td>' +
              formatDate(r.dateRequested) + '</td><td>' + (r.dueDate ? formatDate(r.dueDate) : '—') + '</td><td>' +
              returnDate + '</td><td><span class="status-badge ' + st.cls + '">' + st.label + '</span></td></tr>';
    });
    html += '</tbody></table>';
    el.innerHTML = html;
  } catch (err) { handleApiError(err); }
}

// Maps a raw request into a friendly report status + matching badge CSS class.
// (In this system approve and borrow happen together, so 'approved' shows as "Borrowed".)
function reportStatus(r) {
  if (r.status === 'completed') return { label: 'Completed', cls: 'completed' };
  if (r.status === 'approved') {
    if (overdueDays(r)) return { label: 'Overdue', cls: 'overdue' };
    if (r.returnedByBorrower && !r.returnConfirmed) return { label: 'Returned', cls: 'pending' };
    return { label: 'Borrowed', cls: 'approved' };
  }
  if (r.status === 'pending') return { label: 'Pending', cls: 'pending' };
  // rejected / cancelled shown as-is
  return { label: r.status.charAt(0).toUpperCase() + r.status.slice(1), cls: r.status };
}

// Print the report using the browser's built-in print dialog (no PDF library).
function printReport() {
  window.print();
}

// ══════════════════════════════════════════════
//  LOGOUT
// ══════════════════════════════════════════════

function handleLogout() {
  clearAuth();
  window.location.href = 'index.html';
}
