<?php
/**
 * Practical 13: AJAX/Fetch-Based Event Manager (Single Page App without Reload)
 * Subject: ITUE203 - Web Development Frameworks
 * Student ID: 25CE089
 */

require_once __DIR__ . '/includes/auth.php';
init_session();

$page_title = "Practical 13: Asynchronous AJAX/Fetch CRUD";
$active_nav = "ajax";
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1>Practical 13: AJAX / Fetch API Dynamic Operations</h1>
        <p>Asynchronous List, Create, Update, Delete & Register without full page reloads, using standard RESTful JSON APIs.</p>
    </div>
    <div>
        <button onclick="openAjaxModal()" class="btn btn-primary">+ Create Event (AJAX POST)</button>
    </div>
</div>

<!-- Toast Notification Container -->
<div id="toastContainer" style="position: fixed; top: 80px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;"></div>

<!-- Live Controls: Debounced Search & Filter -->
<div class="filter-bar">
    <div class="search-input-wrap">
        <input type="text" id="ajaxSearchInput" class="form-control" placeholder="🔍 Type to search events (300ms debounce)...">
    </div>

    <select id="ajaxCategoryFilter" class="form-control" style="width: auto;">
        <option value="">All Categories</option>
        <option value="Technical">Technical</option>
        <option value="Workshop">Workshop</option>
        <option value="Sports">Sports</option>
        <option value="Cultural">Cultural</option>
    </select>

    <button onclick="loadEvents()" class="btn btn-secondary btn-sm" title="Refresh via Fetch API">🔄 Reload</button>
    <span id="apiStatusBadge" class="badge badge-success">API Ready: 200 OK</span>
</div>

<!-- Dynamic Card Container -->
<div class="backend-card">
    <div class="backend-card-header">
        <h3>Campus Events (Live API Data via Fetch)</h3>
        <span id="eventCountBadge" class="badge badge-info">Loading...</span>
    </div>
    <div class="backend-card-body">
        <!-- Loading Spinner -->
        <div id="loadingIndicator" style="display: none; text-align: center; padding: 2rem;">
            <div style="display: inline-block; width: 36px; height: 36px; border: 4px solid #cbd5e1; border-top-color: var(--primary-color); border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
            <p style="margin-top: 0.5rem; color: #64748b;">Fetching fresh JSON from <code>api/events_api.php</code>...</p>
        </div>

        <div class="table-responsive">
            <table class="backend-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Event Title & Category</th>
                        <th>Date & Time</th>
                        <th>Venue Location</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Async Actions</th>
                    </tr>
                </thead>
                <tbody id="eventsTableBody">
                    <!-- Populated via JavaScript Fetch API -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Create / Edit Event via Fetch -->
<div id="ajaxEventModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: #ffffff; border-radius: var(--radius-md); max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; padding: 2rem; box-shadow: var(--shadow-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 id="ajaxModalTitle" style="color: var(--primary-color);">Create Event via AJAX</h3>
            <button onclick="closeAjaxModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>

        <form id="ajaxEventForm" onsubmit="handleAjaxSubmit(event)">
            <input type="hidden" id="ajax_event_id" value="0">

            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Event Title <span class="required">*</span></label>
                    <input type="text" id="ajax_title" class="form-control" required placeholder="e.g. NextGen Web Conference">
                </div>

                <div class="form-group full-width">
                    <label>Description <span class="required">*</span></label>
                    <textarea id="ajax_description" class="form-control" rows="3" required placeholder="Event summary..."></textarea>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <select id="ajax_category" class="form-control">
                        <option value="Technical">Technical</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Sports">Sports</option>
                        <option value="Cultural">Cultural</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select id="ajax_status" class="form-control">
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Event Date <span class="required">*</span></label>
                    <input type="date" id="ajax_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Event Time <span class="required">*</span></label>
                    <input type="time" id="ajax_time" class="form-control" required value="10:00">
                </div>

                <div class="form-group">
                    <label>Venue Location <span class="required">*</span></label>
                    <input type="text" id="ajax_venue" class="form-control" required placeholder="Lab 304 / Auditorium">
                </div>

                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" id="ajax_capacity" class="form-control" min="10" max="1000" value="100">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeAjaxModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" id="ajaxSaveBtn" class="btn btn-primary">Save via Fetch API</button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.toast-msg {
    padding: 0.85rem 1.25rem;
    border-radius: var(--radius-sm);
    box-shadow: var(--shadow-lg);
    color: #fff;
    font-size: 0.92rem;
    font-weight: 500;
    animation: fadeIn 0.3s ease;
    min-width: 280px;
}
.toast-success { background: #16a34a; }
.toast-error { background: #dc2626; }
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
let searchDebounceTimeout = null;

// Display visual toast notification
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast-msg toast-${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// Fetch and Render Events List
async function loadEvents() {
    const search = document.getElementById('ajaxSearchInput').value;
    const category = document.getElementById('ajaxCategoryFilter').value;
    const loading = document.getElementById('loadingIndicator');
    const tbody = document.getElementById('eventsTableBody');
    const countBadge = document.getElementById('eventCountBadge');
    const statusBadge = document.getElementById('apiStatusBadge');

    loading.style.display = 'block';

    const url = `api/events_api.php?search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}`;

    try {
        const response = await fetch(url);
        statusBadge.textContent = `HTTP ${response.status} ${response.statusText}`;
        statusBadge.className = response.ok ? 'badge badge-success' : 'badge badge-danger';

        const result = await response.json();
        loading.style.display = 'none';

        if (result.success) {
            tbody.innerHTML = '';
            countBadge.textContent = `${result.data.length} Events Active`;

            if (result.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 2rem; color: #64748b;">No matching events found.</td></tr>`;
                return;
            }

            result.data.forEach(ev => {
                const tr = document.createElement('tr');
                tr.id = `row-event-${ev.id}`;

                let badgeClass = 'badge-secondary';
                if (ev.status === 'upcoming') badgeClass = 'badge-success';
                else if (ev.status === 'ongoing') badgeClass = 'badge-warning';
                else if (ev.status === 'completed') badgeClass = 'badge-info';

                tr.innerHTML = `
                    <td><strong>#${ev.id}</strong></td>
                    <td>
                        <strong>${escapeHtml(ev.title)}</strong><br>
                        <span class="badge badge-secondary">${escapeHtml(ev.category)}</span>
                    </td>
                    <td>${ev.event_date}<br><small style="color:#64748b;">${ev.event_time}</small></td>
                    <td>${escapeHtml(ev.venue)}</td>
                    <td>${ev.capacity} seats</td>
                    <td><span class="badge ${badgeClass}">${ev.status}</span></td>
                    <td>
                        <div style="display:flex; gap: 0.35rem; align-items:center;">
                            <button onclick='enrollAjax(${ev.id})' class="btn btn-success btn-sm" title="One-Click Register">Enrol ⚡</button>
                            <button onclick='editAjax(${JSON.stringify(ev)})' class="btn btn-secondary btn-sm" title="Edit via API">✏️</button>
                            <button onclick='deleteAjax(${ev.id}, "${escapeHtml(ev.title)}")' class="btn btn-danger btn-sm" title="Delete via API">🗑️</button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            showToast(result.message || 'Error fetching events', 'error');
        }
    } catch (err) {
        loading.style.display = 'none';
        showToast('Network error while connecting to JSON API.', 'error');
        statusBadge.textContent = 'API Connection Error';
        statusBadge.className = 'badge badge-danger';
    }
}

// Create or Update Event via Fetch POST
async function handleAjaxSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('ajax_event_id').value;
    const action = id === '0' ? 'create' : 'update';

    const payload = {
        action: action,
        id: id,
        title: document.getElementById('ajax_title').value,
        description: document.getElementById('ajax_description').value,
        category: document.getElementById('ajax_category').value,
        status: document.getElementById('ajax_status').value,
        event_date: document.getElementById('ajax_date').value,
        event_time: document.getElementById('ajax_time').value,
        venue: document.getElementById('ajax_venue').value,
        capacity: document.getElementById('ajax_capacity').value
    };

    try {
        const response = await fetch('api/events_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            closeAjaxModal();
            loadEvents(); // Reload live view without page refresh
        } else {
            showToast(result.message || 'Operation failed', 'error');
        }
    } catch (err) {
        showToast('API network request failed.', 'error');
    }
}

// Delete Event Asynchronously
async function deleteAjax(id, title) {
    if (!confirm(`Are you sure you want to delete "${title}" asynchronously?`)) {
        return;
    }

    try {
        const response = await fetch('api/events_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        });
        const result = await response.json();
        if (result.success) {
            showToast(`Event #${id} deleted successfully!`, 'success');
            // Instant DOM element removal without reload
            const row = document.getElementById(`row-event-${id}`);
            if (row) {
                row.style.transition = 'opacity 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        showToast('Error sending delete request.', 'error');
    }
}

// Quick Student Registration via AJAX
async function enrollAjax(eventId) {
    try {
        const response = await fetch('api/events_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'register', event_id: eventId })
        });
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        showToast('Enrollment request failed.', 'error');
    }
}

// Helper Modal Handlers
function openAjaxModal() {
    document.getElementById('ajaxModalTitle').textContent = "Create Event (AJAX POST)";
    document.getElementById('ajax_event_id').value = "0";
    document.getElementById('ajax_title').value = "";
    document.getElementById('ajax_description').value = "";
    document.getElementById('ajax_category').value = "Technical";
    document.getElementById('ajax_status').value = "upcoming";
    document.getElementById('ajax_date').value = "2026-11-28";
    document.getElementById('ajax_time').value = "11:00";
    document.getElementById('ajax_venue').value = "CSPIT Lab 202";
    document.getElementById('ajax_capacity').value = "80";
    document.getElementById('ajaxEventModal').style.display = "flex";
}

function editAjax(ev) {
    document.getElementById('ajaxModalTitle').textContent = `Edit Event #${ev.id} via Fetch`;
    document.getElementById('ajax_event_id').value = ev.id;
    document.getElementById('ajax_title').value = ev.title;
    document.getElementById('ajax_description').value = ev.description;
    document.getElementById('ajax_category').value = ev.category;
    document.getElementById('ajax_status').value = ev.status;
    document.getElementById('ajax_date').value = ev.event_date;
    document.getElementById('ajax_time').value = ev.event_time;
    document.getElementById('ajax_venue').value = ev.venue;
    document.getElementById('ajax_capacity').value = ev.capacity;
    document.getElementById('ajaxEventModal').style.display = "flex";
}

function closeAjaxModal() {
    document.getElementById('ajaxEventModal').style.display = "none";
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

// Search with 300ms Debounce (Intermediate Requirement)
document.getElementById('ajaxSearchInput').addEventListener('input', () => {
    clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(() => {
        loadEvents();
    }, 300);
});

document.getElementById('ajaxCategoryFilter').addEventListener('change', () => {
    loadEvents();
});

// Initial Load
document.addEventListener('DOMContentLoaded', loadEvents);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
