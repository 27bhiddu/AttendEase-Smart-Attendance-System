<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/layout/header.php';
?>

<div class="dashboard-wrapper">

  <!-- Header -->
  <div class="dashboard-header">
    <div>
      <h1 class="dashboard-title">Admin Dashboard</h1>
      <p class="dashboard-subtitle">
        Overview of students, teachers, and attendance activity.
      </p>
    </div>
    <div class="dashboard-meta">
      <span class="dashboard-date">
        <?php echo date('d M Y'); ?>
      </span>
    </div>
  </div>

  <div class="dashboard-modern">

    <!-- Main Stats Row -->
    <div class="stats-row">

      <div
        class="stat-card-modern stat-indigo clickable-stat"
        onclick="window.location.href='<?php echo BASE_PATH; ?>students_detail.php'">
        <div class="stat-icon-modern">👥</div>
        <div class="stat-content">
          <p class="stat-label">Total Students</p>
          <h3 class="stat-number" id="total-students">-</h3>
        </div>
      </div>

      <div
        class="stat-card-modern stat-teal clickable-stat"
        onclick="window.location.href='<?php echo BASE_PATH; ?>teachers.php'">
        <div class="stat-icon-modern">👨‍🏫</div>
        <div class="stat-content">
          <p class="stat-label">Total Teachers</p>
          <h3 class="stat-number" id="total-teachers">-</h3>
        </div>
      </div>

      <div
        class="stat-card-modern stat-red clickable-stat"
        onclick="window.location.href='<?php echo BASE_PATH; ?>teachers.php'">
        <div class="stat-icon-modern">⚠️</div>
        <div class="stat-content">
          <p class="stat-label">Unverified Teachers</p>
          <h3 class="stat-number" id="total-unverified-teachers">-</h3>
        </div>
      </div>

    </div>

    <!-- Quick Actions -->
    <div class="dashboard-row">

      <div class="quick-actions-card">
        <div class="panel-header">
          <h3>Quick Actions</h3>
          <p class="panel-subtitle">Frequently used actions for daily work.</p>
        </div>

        <div class="quick-actions-grid">

          <a href="<?php echo BASE_PATH; ?>students.php" class="quick-action-card">
            <div class="action-icon-large">👥</div>
            <div class="action-content">
              <h4>Manage Students</h4>
              <p>View and manage all students.</p>
            </div>
            <span class="action-arrow">→</span>
          </a>

          <a href="<?php echo BASE_PATH; ?>add_student.php" class="quick-action-card">
            <div class="action-icon-large">➕</div>
            <div class="action-content">
              <h4>Add New Student</h4>
              <p>Register a new student.</p>
            </div>
            <span class="action-arrow">→</span>
          </a>

          <!-- ✅ NEW: Promote Students -->
          <a href="<?php echo BASE_PATH; ?>promote_students.php" class="quick-action-card">
            <div class="action-icon-large">📈</div>
            <div class="action-content">
              <h4>Promote Students</h4>
              <p>Promote whole class to next semester.</p>
            </div>
            <span class="action-arrow">→</span>
          </a>

        </div>
      </div>

    </div>

    <!-- Footer -->
    <div class="dashboard-footer">
      <span class="footer-text">
        Last updated:
        <span id="last-sync">Just now</span>
      </span>
      <span class="footer-separator">•</span>
      <span class="footer-text">AttendEase v1.0.0</span>
    </div>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  loadDashboardData();
  updateLastSync();
});

function loadDashboardData() {

  // Students
  fetch(BASE_PATH + 'api/get_students.php')
    .then(res => res.json())
    .then(data => {
      if (data.success && Array.isArray(data.data)) {
        document.getElementById('total-students').textContent = data.data.length;
      } else {
        document.getElementById('total-students').textContent = '0';
      }
    })
    .catch(() => {
      document.getElementById('total-students').textContent = '-';
    });

  // Teachers
  fetch(BASE_PATH + 'api/get_teachers.php')
    .then(res => res.json())
    .then(data => {
      if (!data.success || !Array.isArray(data.data)) return;

      const teachers = data.data;
      document.getElementById('total-teachers').textContent = teachers.length;

      let unverified = 0;
      teachers.forEach(t => {
        const verified = t.is_verified === true || t.is_verified === 1 || t.is_verified === '1';
        if (!verified) unverified++;
      });

      document.getElementById('total-unverified-teachers').textContent = unverified;
    })
    .catch(() => {});
}

function updateLastSync() {
  const updateText = () => {
    const now = new Date();
    document.getElementById('last-sync').textContent =
      now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
  };

  updateText();
  setInterval(updateText, 60000);
}
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
