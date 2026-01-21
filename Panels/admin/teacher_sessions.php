<?php
$pageTitle = "Teachers' Sessions";
require_once __DIR__ . '/layout/header.php';

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedTeacher = $_GET['teacher_id'] ?? '';
?>

<div class="page-header-modern">
    <div class="page-header-left">
        <h2>Teachers' Sessions</h2>
        <p class="page-subtitle">View teacher attendance sessions by date</p>
    </div>
    <div class="page-header-right">
        <input type="date" id="date-filter" class="filter-select" value="<?php echo htmlspecialchars($selectedDate); ?>" onchange="updateDate()">
        <select id="teacher-filter" class="filter-select" onchange="updateTeacher()">
            <option value="">All Teachers</option>
        </select>
    </div>
</div>

<div class="sessions-container">
    <div class="sessions-grid" id="sessions-grid">
        <div class="loading-text">Loading sessions...</div>
    </div>
</div>

<script>
let allTeachers = [];
const selectedDate = '<?php echo htmlspecialchars($selectedDate); ?>';
const selectedTeacher = '<?php echo htmlspecialchars($selectedTeacher); ?>';

document.addEventListener('DOMContentLoaded', function() {
    loadTeachers();
});

function loadTeachers() {
    fetch(BASE_PATH + 'api/get_teachers.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                allTeachers = data.data;
                populateTeacherFilter();
                loadSessions();
            }
        })
        .catch(error => {
            console.error('Error loading teachers:', error);
        });
}

function populateTeacherFilter() {
    const select = document.getElementById('teacher-filter');
    allTeachers.forEach(teacher => {
        const option = document.createElement('option');
        option.value = teacher.id;
        option.textContent = teacher.username || `Teacher #${teacher.id}`;
        if (selectedTeacher == teacher.id) {
            option.selected = true;
        }
        select.appendChild(option);
    });
}

function loadSessions() {
    const date = document.getElementById('date-filter').value;
    const teacherId = document.getElementById('teacher-filter').value;
    const grid = document.getElementById('sessions-grid');
    
    grid.innerHTML = '<div class="loading-text">Loading sessions...</div>';
    
    const teachersToLoad = teacherId ? allTeachers.filter(t => t.id == teacherId) : allTeachers;
    
    const sessionPromises = teachersToLoad.map(teacher => {
        return fetch(BASE_PATH + `api/get_teacher_attendance.php?teacher_id=${teacher.id}&date=${date}`)
            .then(res => res.json())
            .then(attData => {
                return {
                    teacher: teacher,
                    hasSession: attData.success && attData.data.records && attData.data.records.length > 0,
                    records: attData.success ? attData.data.records : []
                };
            })
            .catch(() => {
                return {
                    teacher: teacher,
                    hasSession: false,
                    records: []
                };
            });
    });
    
    Promise.all(sessionPromises).then(results => {
        const sessionsWithData = results.filter(r => r.hasSession);
        
        if (sessionsWithData.length === 0) {
            grid.innerHTML = '<div class="no-sessions">No sessions found for selected date</div>';
            return;
        }
        
        grid.innerHTML = sessionsWithData.map(result => {
            const teacher = result.teacher;
            const records = result.records;
            const sessionCount = records.length;
            
            return `
                <div class="session-card">
                    <div class="session-header">
                        <div class="session-teacher-avatar">${teacher.username ? teacher.username.charAt(0).toUpperCase() : 'T'}</div>
                        <div class="session-teacher-info">
                            <h3>${escapeHtml(teacher.username || `Teacher #${teacher.id}`)}</h3>
                            <p>${escapeHtml(teacher.email || '')}</p>
                        </div>
                    </div>
                    <div class="session-body">
                        <div class="session-stat">
                            <span class="session-stat-label">Sessions on this date:</span>
                            <span class="session-stat-value">${sessionCount}</span>
                        </div>
                        <div class="session-details">
                            <p><strong>Date:</strong> ${formatDate(date)}</p>
                            <p><strong>Contact:</strong> ${escapeHtml(teacher.contact || 'N/A')}</p>
                        </div>
                    </div>
                    <div class="session-footer">
                        <a href="${BASE_PATH}teacher_profile.php?id=${teacher.id}" class="btn btn-sm btn-primary">View Profile</a>
                        <button class="btn btn-sm btn-danger" onclick="confirmRemoveSession(${teacher.id}, '${date}')">Remove Session</button>
                    </div>
                </div>
            `;
        }).join('');
    });
}

function updateDate() {
    loadSessions();
}

function updateTeacher() {
    loadSessions();
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function confirmRemoveSession(teacherId, date) {
    if (!confirm('Are you sure you want to remove this teacher\'s session for ' + formatDate(date) + '? This will delete all attendance records for that teacher on that date.')) {
        return;
    }
    removeSession(teacherId, date);
}

function removeSession(teacherId, date) {
    const formData = new FormData();
    formData.append('teacher_id', teacherId);
    formData.append('date', date);

    fetch(BASE_PATH + 'api/delete_teacher_session.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Session removed successfully');
            loadSessions();
        } else {
            alert('Error removing session: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error('Error removing session:', err);
        alert('Error removing session');
    });
}
</script>

<style>
.sessions-container {
    margin-top: var(--spacing-md);
}

.sessions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--spacing-md);
}

.session-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    transition: all 0.2s ease;
}

.session-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.session-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
    padding-bottom: var(--spacing-md);
    border-bottom: 1px solid var(--border-color);
}

.session-teacher-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-white);
    font-weight: 600;
    font-size: 20px;
    flex-shrink: 0;
}

.session-teacher-info h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 4px 0;
}

.session-teacher-info p {
    font-size: 13px;
    color: var(--text-secondary);
    margin: 0;
}

.session-body {
    margin-bottom: var(--spacing-md);
}

.session-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-sm);
    background: var(--bg-tertiary);
    border-radius: var(--border-radius-sm);
    margin-bottom: var(--spacing-md);
}

.session-stat-label {
    font-size: 13px;
    color: var(--text-secondary);
}

.session-stat-value {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary);
}

.session-details p {
    font-size: 13px;
    color: var(--text-secondary);
    margin: 4px 0;
}

.session-footer {
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--border-color);
}

.no-sessions {
    text-align: center;
    padding: var(--spacing-xl);
    color: var(--text-secondary);
    font-size: 14px;
    grid-column: 1 / -1;
}
</style>

<?php require_once __DIR__ . '/layout/footer.php'; ?>



