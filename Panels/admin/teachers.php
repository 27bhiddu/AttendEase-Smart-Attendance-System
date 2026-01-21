<?php
$pageTitle = "Teachers";

require_once __DIR__ . '/layout/header.php';

// Handle delete action messages
if (isset($_GET['delete']) && $_GET['delete'] === 'success') {
    echo '<div class="alert alert-success">Teacher deleted successfully!</div>';
}

if (isset($_GET['delete']) && $_GET['delete'] === 'error') {
    echo '<div class="alert alert-error">Error deleting teacher. Please try again.</div>';
}

if (isset($_GET['create']) && $_GET['create'] === 'success') {
    echo '<div class="alert alert-success">Teacher created successfully!</div>';
}

if (isset($_GET['update']) && $_GET['update'] === 'success') {
    echo '<div class="alert alert-success">Teacher updated successfully!</div>';
}
?>

<div class="page-header-modern">
    <div class="page-header-left">
        <h2>Teachers Management</h2>
        <p class="page-subtitle">Manage teacher accounts and view attendance statistics</p>
    </div>
    <div class="page-header-right">
        <a href="<?php echo BASE_PATH ?>add_teacher.php" class="btn btn-primary">
            <span>➕</span> <span>Add New Teacher</span>
        </a>
    </div>
</div>

<div class="table-wrapper">
    <div class="table-container-modern">
        <table class="data-table-modern" id="teachers-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Status</th> <!-- ✅ new -->
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="teachers-tbody">
                <tr>
                    <td colspan="7" class="loading">Loading teachers...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    loadTeachers();
});

function loadTeachers() {
    fetch('<?php echo BASE_PATH ?>api/get_teachers.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('teachers-tbody');

            if (data.success && data.data && data.data.length > 0) {
                // Load attendance counts for each teacher
                const teacherPromises = data.data.map(teacher => {
                    return fetch('<?php echo BASE_PATH ?>api/get_teacher_attendance.php?teacher_id=' + teacher.id)
                        .then(res => res.json())
                        .then(attData => {
                            teacher.attendance_count = attData.success ? (attData.data.unique_dates || 0) : 0;
                            return teacher;
                        })
                        .catch(() => {
                            teacher.attendance_count = 0;
                            return teacher;
                        });
                });

                Promise.all(teacherPromises).then(teachers => {
                    tbody.innerHTML = teachers.map(teacher => {
                        const isVerified = teacher.is_verified === true || teacher.is_verified === 1 || teacher.is_verified === '1';

                        const statusBadge = isVerified
                            ? '<span class="status-badge status-verified">Verified</span>'
                            : '<span class="status-badge status-pending">Pending</span>';

                        const approveButton = isVerified
                            ? ''
                            : `<button onclick="verifyTeacher(${teacher.id}, '${escapeHtml(teacher.username)}')" class="btn-icon btn-icon-success" title="Approve / Verify">
                                   ✅
                               </button>`;

                        return `
                            <tr>
                                <td><span class="id-badge">${teacher.id}</span></td>
                                <td>
                                    <div class="teacher-info">
                                        <strong>${escapeHtml(teacher.username)}</strong>
                                    </div>
                                </td>
                                <td>${escapeHtml(teacher.email || '')}</td>
                                <td>${escapeHtml(teacher.contact || '')}</td>
                                <td>
                                    ${statusBadge}
                                </td>
                                <!-- Attendance Sessions column removed -->
                                <td class="actions">
                                    <a href="<?php echo BASE_PATH ?>teacher_profile.php?id=${teacher.id}" class="btn-icon btn-icon-info" title="View Profile">
                                        👁
                                    </a>
                                    <a href="<?php echo BASE_PATH ?>edit_teacher.php?id=${teacher.id}" class="btn-icon" title="Edit">
                                        ✏️
                                    </a>
                                    ${approveButton}
                                    <button onclick="deleteTeacher(${teacher.id}, '${escapeHtml(teacher.username)}')" class="btn-icon btn-icon-danger" title="Delete">
                                        🗑
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                });
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="no-data">
                            <div class="empty-state">
                                <div class="empty-icon">📭</div>
                                <p>No teachers found</p>
                                <a href="add_teacher.php" class="btn btn-primary">Add Your First Teacher</a>
                            </div>
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            document.getElementById('teachers-tbody').innerHTML = `
                <tr>
                    <td colspan="7" class="error">Error loading teachers. Please refresh the page.</td>
                </tr>
            `;
            console.error('Error:', error);
        });
}

function verifyTeacher(id, name) {
    if (!confirm('Approve / verify teacher "' + name + '"?')) {
        return;
    }

    fetch('<?php echo BASE_PATH ?>api/verify_teacher.php?id=' + id, {
        method: 'POST',
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Teacher verified successfully!', 'success');
                loadTeachers();
            } else {
                showAlert(data.message || 'Error verifying teacher', 'error');
            }
        })
        .catch(error => {
            showAlert('Error verifying teacher. Please try again.', 'error');
            console.error('Error:', error);
        });
}

function deleteTeacher(id, name) {
    if (!confirm('Are you sure you want to delete teacher "' + name + '"? This action cannot be undone.')) {
        return;
    }

    fetch('<?php echo BASE_PATH ?>api/delete_teacher.php?id=' + id, {
        method: 'POST',
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Teacher deleted successfully!', 'success');
                loadTeachers();
            } else {
                showAlert(data.message || 'Error deleting teacher', 'error');
            }
        })
        .catch(error => {
            showAlert('Error deleting teacher. Please try again.', 'error');
            console.error('Error:', error);
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
