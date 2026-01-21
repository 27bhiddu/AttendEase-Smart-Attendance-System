// Utility Functions

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const contentArea = document.querySelector('.content-area');
    if (contentArea) {
        contentArea.insertBefore(alertDiv, contentArea.firstChild);
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item? This action cannot be undone.');
}

function handleFormSubmit(formId, submitButtonId) {
    const form = document.getElementById(formId);
    const submitBtn = document.getElementById(submitButtonId) || (form && form.querySelector('button[type="submit"]'));
    
    if (form && submitBtn) {
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide existing alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.3s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Add loading states to all forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Processing...';
                
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }, 10000);
            }
        });
    });

    // Pending teachers notification badge
    try {
        updatePendingTeachersCount();
    } catch (e) {
        console.error('Pending teachers init error:', e);
    }
});

// ============================
// Pending Teachers Notification
// ============================
function updatePendingTeachersCount() {
    const badge = document.getElementById('pending-teachers-count');
    if (!badge || typeof BASE_PATH === 'undefined') return;

    fetch(BASE_PATH + 'api/get_teachers.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.data) return;

            const teachers = data.data;
            const pending = teachers.filter(t =>
                t.is_verified === false ||
                t.is_verified === 0 ||
                t.is_verified === '0' ||
                t.is_verified === null ||
                typeof t.is_verified === 'undefined'
            ).length;

            if (pending > 0) {
                badge.textContent = pending;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(err => {
            console.error('Error loading pending teachers:', err);
        });
}

// ============================================
// STUDENTS PAGE LOGIC
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Only run this logic if pageConfig is defined (meaning we are on students.php)
    if (window.pageConfig) {
        initializeStudentsPage();
    }
});

function initializeStudentsPage() {
    const config = window.pageConfig;
    let allStudents = [];

    // Initialize Dropdowns (for both Selection Screen and Table Filters)
    const branchSelect   = document.getElementById('branch');
    const semesterSelect = document.getElementById('semester');
    const branchFilter   = document.getElementById('branch-filter');
    const semesterFilter = document.getElementById('semester-filter');

    // 1. Setup Selection Screen Dropdowns
    if (branchSelect && semesterSelect) {
        branchSelect.addEventListener('change', function() {
            updateSemesterOptions(semesterSelect, this.value, '', config.maxSemesters);
            semesterSelect.value = '';
        });
        // Pre-fill if value exists
        if (branchSelect.value) {
            updateSemesterOptions(semesterSelect, branchSelect.value, semesterSelect.value || '', config.maxSemesters);
        }
    }

    // 2. Setup Table Screen Filters
    if (branchFilter && semesterFilter) {
        branchFilter.addEventListener('change', function() {
            updateSemesterOptions(semesterFilter, this.value, '', config.maxSemesters);
            semesterFilter.value = '';
            updateStudentFilters(config.basePath);
        });
        
        semesterFilter.addEventListener('change', function() {
            updateStudentFilters(config.basePath);
        });

        if (config.selectedBranch) {
            updateSemesterOptions(semesterFilter, config.selectedBranch, config.selectedSemester, config.maxSemesters);
        }
    }

    // 3. Load Data if on Table View
    if (document.getElementById('students-table') && config.selectedBranch && config.selectedSemester) {
        loadStudentsData(config);
    }
}

function updateSemesterOptions(selectElement, branch, selectedValue, maxSemesters) {
    if (!selectElement) return;

    if (!branch) {
        selectElement.innerHTML = '<option value="">-- Select Semester --</option>';
        return;
    }

    const maxSem = maxSemesters[branch] || 8;
    const currentValue = selectElement.value;

    selectElement.innerHTML = '<option value="">-- Select Semester --</option>';

    for (let i = 1; i <= maxSem; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = 'Semester ' + i;

        if (selectedValue && String(i) === String(selectedValue)) {
            option.selected = true;
        } else if (!selectedValue && String(i) === String(currentValue)) {
            option.selected = true;
        }
        selectElement.appendChild(option);
    }
}

function updateStudentFilters(basePath) {
    const branch   = document.getElementById('branch-filter').value;
    const semester = document.getElementById('semester-filter').value;

    if (branch && semester) {
        window.location.href = basePath + 'students.php?branch=' + 
            encodeURIComponent(branch) + '&semester=' + encodeURIComponent(semester);
    } else if (branch) {
        window.location.href = basePath + 'students.php?branch=' + encodeURIComponent(branch);
    } else if (semester) {
        window.location.href = basePath + 'students.php?semester=' + encodeURIComponent(semester);
    } else {
        window.location.href = basePath + 'students.php';
    }
}

function loadStudentsData(config) {
    fetch(config.basePath + 'api/get_students.php')
        .then(response => {
            if (!response.ok) throw new Error('HTTP error: ' + response.status);
            return response.json();
        })
        .then(data => {
            const tbody = document.getElementById('students-tbody');

            if (!data.success) {
                tbody.innerHTML = `<tr><td colspan="7" class="error">${escapeHtml(data.message || 'Error loading students')}</td></tr>`;
                return;
            }

            if (data.data && Array.isArray(data.data) && data.data.length > 0) {
                const filtered = data.data.filter(s => {
                    const branch = (s.branch || s.department || '').trim().toUpperCase();
                    const rawSem = String(s.semester || '').trim();
                    const semNum = rawSem.replace(/[^0-9]/g, '') || rawSem;
                    return branch === config.selectedBranch && semNum === String(config.selectedSemester);
                });

                renderStudentsTable(filtered, config.basePath);
            } else {
                renderEmptyState();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('students-tbody').innerHTML =
                '<tr><td colspan="7" class="error">Error loading data. Please refresh.</td></tr>';
        });
}

function renderStudentsTable(students, basePath) {
    const tbody = document.getElementById('students-tbody');
    
    if (students.length === 0) {
        renderEmptyState();
        return;
    }

    tbody.innerHTML = students.map(student => {
        const deptBadge = getDepartmentBadge(student.branch || student.department);
        const rollNo    = student.roll_number || student.roll_no || '-';
        const semester  = student.semester || '-';
        const status    = getStudentStatus(student);

        return `
            <tr>
                <td><span class="id-badge">#${student.id}</span></td>
                <td>
                    <div class="student-info">
                        <strong>${escapeHtml(student.name || '-')}</strong>
                    </div>
                </td>
                <td>${escapeHtml(rollNo)}</td>
                <td>${deptBadge}</td>
                <td><span class="semester-badge">Sem ${escapeHtml(String(semester).replace(/[^0-9]/g, ''))}</span></td>
                <td>
                    <span class="status-badge ${status.class}">${status.text}</span>
                </td>
                <td class="actions">
                    <div class="action-buttons">
                       
                        <button class="btn-action btn-delete" 
                                onclick="deleteStudentAction(${student.id}, '${escapeHtml(student.name)}', '${escapeHtml(rollNo)}', '${basePath}')"
                                title="Delete">
                            <span>Delete</span>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function renderEmptyState() {
    document.getElementById('students-tbody').innerHTML = `
        <tr>
            <td colspan="7" class="no-data">
                <div class="empty-state">
                    <div class="empty-icon">👥</div>
                    <p>No students found for this semester.</p>
                    <a href="add_student.php" class="btn btn-primary btn-sm" style="margin-top:10px;">Add Student</a>
                </div>
            </td>
        </tr>`;
}

function getStudentStatus(student) {
    const branch   = (student.branch || student.department || '').toUpperCase();
    const semester = parseInt(String(student.semester).replace(/[^0-9]/g, '')) || 0;
    
    if (student.status === 'failed') return { class: 'failed', text: 'Failed' };
    
    if ((branch === 'MCA' && semester >= 4) || (branch !== 'MCA' && semester >= 8)) {
        return { class: 'graduated', text: 'Graduated' };
    }
    
    return { class: 'active', text: 'Active' };
}

function getDepartmentBadge(department) {
    if (!department) return '-';
    const dept = department.toUpperCase();
    const colorMap = { 'CSE': 'high', 'IT': 'medium', 'AI': 'high', 'MCA': 'medium' };
    const color = colorMap[dept] || 'medium';
    // yahan sirf branch badge, attendance se link nahi
    return `<span class="branch-badge ${color}">${escapeHtml(department)}</span>`;
}

// Global function to be called from onclick
window.deleteStudentAction = function(id, name, rollNo, basePath) {
    if (confirm(`Are you sure you want to delete this student?\n\nName: ${name}\nRoll No: ${rollNo}\n\nThis action cannot be undone.`)) {
        const btn = event.target.closest('.btn-delete');
        const originalContent = btn ? btn.innerHTML : '';
        
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span>...</span>';
        }

        fetch(basePath + 'api/delete_student.php?id=' + id, { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('Student deleted successfully!', 'success');
                    if (window.pageConfig) loadStudentsData(window.pageConfig);
                } else {
                    showAlert(data.message || 'Error deleting student', 'error');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    }
                }
            })
            .catch(err => {
                console.error(err);
                showAlert('Connection error. Please try again.', 'error');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            });
    }
};