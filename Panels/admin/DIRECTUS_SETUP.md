# Directus Setup Guide for Admin Panel

## Quick Setup Steps

### 1. Get Your Directus API Key

1. Open Directus: `http://localhost:8055`
2. Go to **Settings** (gear icon in sidebar)
3. Navigate to **Access Control** → **Roles** → **Administrator** (or your role)
4. Go to **API Access** tab
5. Copy the **Static Token** (API Key)
6. Or create a new token in **Settings** → **Access Tokens**

### 2. Configure Admin Panel

1. Open `admin-panel/config.php`
2. Replace `<PUT_MY_DIRECTUS_API_KEY_HERE>` with your API key:
   ```php
   define('DIRECTUS_API_KEY', 'your-actual-api-key-here');
   ```

### 3. Verify Collections Exist

Make sure these collections exist in Directus:

#### ✅ Students Collection
- Collection name: `students`
- Required fields: `id`, `name`, `roll_no` (or `roll_number`), `department`, `attendance_percentage`

#### ✅ Teachers Collection
- Collection name: `teachers`
- Required fields: `id`, `username`, `email`, `contact`, `password`

#### ✅ Attendance Collection
- Collection name: `attendance`
- Required fields: `id`, `student_id`, `teacher_id`, `date`, `branch`, `semester`, `present`

#### ✅ Branches Collection (Optional)
- Collection name: `branches`
- Required fields: `id`, `name`

### 4. Verify Field Names

Check that field names in Directus match what the admin panel expects:

**Students:**
- `roll_no` OR `roll_number` (admin panel uses `roll_no`)
- `department` (should be: CSE, IT, AI, or MCA)

**Teachers:**
- `username`
- `email`
- `contact`
- `password` (hashed)

**Attendance:**
- `teacher_id`
- `student_id`
- `date`
- `branch`
- `semester`
- `present`

### 5. Test Connection

1. Start your PHP server:
   ```bash
   cd admin-panel
   php -S localhost:8000
   ```

2. Open: `http://localhost:8000/login.php`

3. Login and check if data loads:
   - Dashboard should show student/teacher counts
   - Students page should list students
   - Teachers page should list teachers

### 6. Troubleshooting

#### "Connection error" or "Collection not found"
- ✅ Check Directus is running: `http://localhost:8055`
- ✅ Verify API key in `config.php`
- ✅ Check collection names match exactly (case-sensitive)
- ✅ Verify API key has read/write permissions

#### "Field not found" error
- ✅ Check field names match exactly
- ✅ Verify fields exist in Directus collection
- ✅ Check if field is required but missing

#### Data not loading
- ✅ Open browser console (F12) and check for errors
- ✅ Verify Directus API is accessible
- ✅ Check API key permissions in Directus

---

## API Key Permissions

Your Directus API key needs:
- ✅ **Read** access to: students, teachers, attendance, branches
- ✅ **Create** access to: students, teachers, attendance
- ✅ **Update** access to: students, teachers, attendance
- ✅ **Delete** access to: students, teachers, attendance

---

## Collection Field Mapping

### Students → Admin Panel
| Directus Field | Admin Panel Field | Type |
|---------------|-------------------|------|
| `id` | ID | Integer |
| `name` | Name | String |
| `roll_no` or `roll_number` | Roll Number | String |
| `department` | Department | String (CSE/IT/AI/MCA) |
| `attendance_percentage` | Attendance % | Float |

### Teachers → Admin Panel
| Directus Field | Admin Panel Field | Type |
|---------------|-------------------|------|
| `id` | ID | Integer |
| `username` | Username | String |
| `email` | Email | String |
| `contact` | Contact | String |
| `password` | Password | String (hashed, not displayed) |

### Attendance → Admin Panel
| Directus Field | Admin Panel Field | Type |
|---------------|-------------------|------|
| `id` | ID | Integer |
| `teacher_id` | Teacher ID | Integer |
| `student_id` | Student ID | Integer |
| `date` | Date | Date |
| `branch` | Branch | String |
| `semester` | Semester | String |
| `present` | Present | Boolean |

---

## Quick Test

Test your Directus connection:

```bash
# Test API endpoint (replace YOUR_API_KEY)
curl -H "Authorization: Bearer YOUR_API_KEY" \
     http://localhost:8055/items/students
```

Should return JSON with students data.

---

## Need Help?

1. Check `DIRECTUS_DATABASE.md` for full database structure
2. Verify all collections exist in Directus
3. Check API key permissions
4. Review browser console for errors
5. Check PHP error logs



