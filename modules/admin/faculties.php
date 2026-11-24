<?php
// modules/admin/faculties.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('admin');

$pageTitle = 'Faculty Management';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Breadcrumb & Header -->
    <nav aria-label="Breadcrumb" class="mb-2">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars(base_url('/modules/admin/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-secondary text-decoration-none">
                    <i class="bi bi-house me-1" aria-hidden="true"></i>Admin
                </a>
            </li>
            <li class="breadcrumb-item active text-slate-800 fw-medium" aria-current="page">Faculties</li>
        </ol>
    </nav>

    <div class="cm-page-header">
        <div>
            <h1 class="cm-page-title">Faculty Division Directory</h1>
            <p class="cm-page-subtitle">Configure higher-level institutional faculties, colleges, and academic divisions</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" onclick="openModal()" class="btn btn-primary" id="btnOpenAddFaculty">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Add Faculty
            </button>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer" class="d-none"></div>

    <!-- Data Matrix Card -->
    <div class="cm-card">
        <div class="cm-card-header flex-column flex-md-row align-items-stretch align-items-md-center gap-3">
            <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                <!-- Search Input -->
                <div class="input-group" style="max-width: 340px;">
                    <span class="input-group-text bg-light text-muted border-end-0" id="search-addon">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control border-start-0" 
                           placeholder="Search faculty name..." 
                           aria-label="Search faculties" aria-describedby="search-addon"
                           oninput="filterFaculties()">
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 text-muted small">
                <span>Total Divisions:</span>
                <span class="badge cm-badge-neutral font-monospace" id="matchCount">0</span>
            </div>
        </div>

        <div class="cm-card-body p-0">
            <div class="table-responsive border-0">
                <table class="table cm-table table-hover mb-0" id="facultyTable">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 50px;">#</th>
                            <th scope="col">Faculty Division Name</th>
                            <th scope="col" class="text-end" style="width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="facultyTableBody">
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Loading faculty divisions...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="cm-card-footer d-flex justify-content-between align-items-center">
            <span class="text-muted small" id="recordSummary">Displaying academic faculties</span>
            <span class="badge cm-badge-neutral small">Divisional Registry</span>
        </div>
    </div>
</div>

<!-- Accessible Add / Edit Faculty Modal -->
<div class="modal fade" id="facultyModal" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-modal="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cm-card border-0">
            <div class="modal-header border-bottom">
                <h2 class="modal-title h5 mb-0 fw-semibold text-slate-800" id="modalTitle">Add Faculty</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close dialog"></button>
            </div>
            <form id="facultyForm" onsubmit="saveFaculty(event)" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" id="id">

                    <!-- Modal Validation Feedback -->
                    <div id="modalAlert" class="alert alert-danger d-none py-2 px-3 small" role="alert"></div>

                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Faculty Division Name <span class="required" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted" aria-hidden="true"><i class="bi bi-diagram-3"></i></span>
                            <input type="text" id="name" class="form-control" placeholder="e.g. Faculty of Engineering &amp; Technology" required>
                        </div>
                        <div class="form-text">Official name of the academic faculty or collegiate division.</div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light px-4 py-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveFaculty">
                        <i class="bi bi-check2 me-1" aria-hidden="true"></i>Save Faculty
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('/public/js/utils.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    let modalInstance = null;
    let allFaculties = [];

    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('facultyModal');
        if (modalEl) {
            modalInstance = new bootstrap.Modal(modalEl);
            modalEl.addEventListener('shown.bs.modal', () => {
                document.getElementById('name').focus();
            });
            modalEl.addEventListener('hidden.bs.modal', () => {
                const addBtn = document.getElementById('btnOpenAddFaculty');
                if (addBtn) addBtn.focus();
            });
        }

        loadFaculties();
    });

    function showAlert(message, type = 'success') {
        const c = document.getElementById('alertContainer');
        c.className = `alert cm-alert-${type} alert-dismissible fade show mb-4`;
        c.setAttribute('role', 'status');
        c.innerHTML = `
            <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} me-2" aria-hidden="true"></i>
            <div>${escapeHtml(message)}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
    }

    function loadFaculties() {
        fetch('<?= base_url('/api/get_faculties.php') ?>')
            .then(r => r.json())
            .then(list => {
                allFaculties = Array.isArray(list) ? list : [];
                filterFaculties();
            })
            .catch(err => {
                console.error('Error loading faculties:', err);
                const tbody = document.getElementById('facultyTableBody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="3">
                            <div class="cm-empty-state py-4">
                                <div class="cm-empty-icon text-danger" aria-hidden="true"><i class="bi bi-exclamation-octagon"></i></div>
                                <h3 class="cm-empty-title">Failed to Load Faculties</h3>
                                <p class="cm-empty-desc">An error occurred while connecting to the faculty division service.</p>
                                <button type="button" onclick="loadFaculties()" class="btn btn-sm btn-outline-primary">Try Again</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
    }

    function filterFaculties() {
        const search = (document.getElementById('searchInput').value || '').trim().toLowerCase();

        const filtered = allFaculties.filter(f => {
            return !search || (f.name && f.name.toLowerCase().includes(search));
        });

        document.getElementById('matchCount').textContent = filtered.length;
        document.getElementById('recordSummary').textContent = `Showing ${filtered.length} of ${allFaculties.length} faculties`;
        renderFaculties(filtered);
    }

    function renderFaculties(list) {
        const tbody = document.getElementById('facultyTableBody');
        tbody.innerHTML = '';

        if (list.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="3">
                        <div class="cm-empty-state py-4">
                            <div class="cm-empty-icon" aria-hidden="true"><i class="bi bi-diagram-3"></i></div>
                            <h3 class="cm-empty-title">No Faculties Found</h3>
                            <p class="cm-empty-desc">No academic faculties matched your search query. Add a new faculty or clear search.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        list.forEach((item, idx) => {
            const facultyJson = JSON.stringify(item).replace(/"/g, '&quot;');

            tbody.innerHTML += `
                <tr>
                    <td class="font-monospace text-muted small">${idx + 1}</td>
                    <td>
                        <div class="fw-semibold text-slate-900">
                            <i class="bi bi-diagram-3 me-2 text-primary" aria-hidden="true"></i>${escapeHtml(item.name)}
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Faculty actions">
                            <button type="button" onclick="editFaculty(${facultyJson})" class="btn btn-outline-primary" aria-label="Edit faculty ${escapeHtml(item.name)}">
                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                <span class="d-none d-md-inline ms-1">Edit</span>
                            </button>
                            <button type="button" onclick="deleteFaculty(${Number(item.id)}, '${escapeHtml(item.name)}')" class="btn btn-outline-danger" aria-label="Delete faculty ${escapeHtml(item.name)}">
                                <i class="bi bi-trash" aria-hidden="true"></i>
                                <span class="d-none d-md-inline ms-1">Delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }

    function openModal() {
        document.getElementById('facultyForm').reset();
        document.getElementById('id').value = '';
        document.getElementById('modalTitle').textContent = 'Add Faculty';
        document.getElementById('modalAlert').className = 'alert alert-danger d-none py-2 px-3 small';
        modalInstance.show();
    }

    function editFaculty(item) {
        document.getElementById('modalTitle').textContent = `Edit Faculty: ${item.name}`;
        document.getElementById('id').value = item.id;
        document.getElementById('name').value = item.name;
        document.getElementById('modalAlert').className = 'alert alert-danger d-none py-2 px-3 small';
        modalInstance.show();
    }

    function deleteFaculty(id, name) {
        if (!confirm(`Warning: Are you sure you want to delete faculty "${name}"? This may affect linked departments and users.`)) {
            return;
        }

        fetch('<?= base_url('/api/delete_faculty.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: id,
                csrf_token: '<?= generateCsrfToken() ?>'
            })
        })
        .then(r => r.json())
        .then(resp => {
            if (resp.success) {
                showAlert(`Faculty "${name}" was deleted successfully.`, 'success');
                loadFaculties();
            } else {
                showAlert(resp.error || 'Failed to delete faculty.', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showAlert('Network error during faculty deletion.', 'danger');
        });
    }

    function saveFaculty(event) {
        event.preventDefault();
        const modalAlert = document.getElementById('modalAlert');
        modalAlert.className = 'alert alert-danger d-none py-2 px-3 small';

        const id = document.getElementById('id').value;
        const name = document.getElementById('name').value.trim();

        if (!name) {
            modalAlert.textContent = 'Please enter a valid Faculty Division Name.';
            modalAlert.classList.remove('d-none');
            return;
        }

        const data = {
            id: id,
            name: name,
            csrf_token: '<?= generateCsrfToken() ?>'
        };

        const saveBtn = document.getElementById('btnSaveFaculty');
        const originalBtnText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...';

        fetch('<?= base_url('/api/save_faculty.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(resp => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnText;

            if (resp.success) {
                modalInstance.hide();
                showAlert(`Faculty "${name}" was saved successfully.`, 'success');
                loadFaculties();
            } else {
                modalAlert.textContent = resp.error || 'Failed to save faculty.';
                modalAlert.classList.remove('d-none');
            }
        })
        .catch(err => {
            console.error(err);
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnText;
            modalAlert.textContent = 'Network communication error while saving faculty.';
            modalAlert.classList.remove('d-none');
        });
    }
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
