# Directus Database Structure

This document describes the database collections and their fields used in the AttendEase admin panel.

## Base Configuration

- **Directus URL**: `http://localhost:8055`
- **API Key**: Set in `config.php` as `DIRECTUS_API_KEY`

---

## Collections

### 1. Students Collection

**Collection Name**: `students`

**Fields**:
- `id` (Primary Key, Auto-increment, Integer)
- `name` (String, Required) - Student's full name
- `roll_no` or `roll_number` (String, Required) - Roll number
- `email` (String, Optional) - Email address
- `image` (File/UUID, Optional) - Student profile image
- `branch` (String, Required) - Department/Branch (CSE, IT, AI, MCA)
- `semester` (String, Required) - Current semester
- `total_classes` (Integer, Required) - Total classes
- `present_classes` (Integer, Required) - Present classes
- `attendance_percentage` (Float/Decimal, Optional) - Calculated attendance percentage
- `department` (String, Required) - Department (CSE, IT, AI, MCA)

**API Endpoints Used**:
- `GET /items/students` - List all students
- `GET /items/students/{id}` - Get single student
- `POST /items/students` - Create student
- `PATCH /items/students/{id}` - Update student
- `DELETE /items/students/{id}` - Delete student

---

### 2. Teachers Collection

**Collection Name**: `teachers`

**Fields**:
- `id` (Primary Key, Auto-increment, Integer)
- `username` (String, Required) - Teacher username
- `email` (String, Required) - Email address
- `password` (String, Required) - Hashed password (bcrypt)
- `contact` (String, Required) - Contact number
- `created_at` (DateTime, Auto-generated) - Account creation date

**API Endpoints Used**:
- `GET /items/teachers` - List all teachers
- `GET /items/teachers/{id}` - Get single teacher
- `POST /items/teachers` - Create teacher
- `PATCH /items/teachers/{id}` - Update teacher
- `DELETE /items/teachers/{id}` - Delete teacher

**Notes**:
- Passwords are hashed using bcrypt
- Password field should not be displayed in admin panel (security)

---

### 3. Attendance Collection

**Collection Name**: `attendance`

**Fields**:
- `id` (Primary Key, Auto-increment, Integer)
- `student_id` (Integer, Required) - Foreign key to students.id
- `student_name` (String, Required) - Student name (denormalized)
- `teacher_id` (Integer, Required) - Foreign key to teachers.id
- `teacher_name` (String, Required) - Teacher name (denormalized)
- `branch` (String, Required) - Branch/Department
- `semester` (String, Required) - Semester
- `date` (Date, Required) - Attendance date
- `present` (Boolean, Required) - Present status

**API Endpoints Used**:
- `GET /items/attendance` - List all attendance records
- `GET /items/attendance?filter[teacher_id][_eq]={id}` - Get attendance by teacher
- `GET /items/attendance?filter[student_id][_eq]={id}` - Get attendance by student
- `POST /items/attendance` - Create attendance record
- `PATCH /items/attendance/{id}` - Update attendance record
- `DELETE /items/attendance/{id}` - Delete attendance record

**Notes**:
- Used to track when teachers take attendance
- Links students and teachers
- Can filter by teacher_id to count attendance sessions

---

### 4. Branches Collection

**Collection Name**: `branches`

**Fields**:
- `id` (Primary Key, Auto-increment, Integer)
- `name` (String, Required) - Branch name

**API Endpoints Used**:
- `GET /items/branches` - List all branches
- `GET /items/branches/{id}` - Get single branch
- `POST /items/branches` - Create branch
- `PATCH /items/branches/{id}` - Update branch
- `DELETE /items/branches/{id}` - Delete branch

---

## Department Values

The admin panel uses these department values for students:

- **CSE** - Computer Science Engineering
- **IT** - Information Technology
- **AI** - Artificial Intelligence
- **MCA** - Master of Computer Applications

---

## API Authentication

All API requests use:

```
Authorization: Bearer <DIRECTUS_API_KEY>
Content-Type: application/json
```

Set in `config.php`:
```php
define('DIRECTUS_API_KEY', 'your-api-key-here');
```

---

## Relationships

### Students ↔ Attendance
- One student can have many attendance records
- Linked via `student_id` field

### Teachers ↔ Attendance
- One teacher can have many attendance records
- Linked via `teacher_id` field
- Used to count how many times a teacher took attendance

### Students ↔ Branches
- Students belong to a branch/department
- Stored as string in `branch` or `department` field

---

## Common Queries

### Get all students
```
GET /items/students
```

### Get students by department
```
GET /items/students?filter[department][_eq]=CSE
```

### Get teacher attendance count
```
GET /items/attendance?filter[teacher_id][_eq]=28
```
Then count unique dates.

### Get student attendance
```
GET /items/attendance?filter[student_id][_eq]=1
```

---

## Field Naming Notes

Some fields may have different names in your Directus setup:
- `roll_no` vs `roll_number`
- `branch` vs `department`

Update the API calls in the admin panel if your field names differ.

---

## Directus Setup Checklist

- [ ] Students collection created with all required fields
- [ ] Teachers collection created with all required fields
- [ ] Attendance collection created with all required fields
- [ ] Branches collection created (if used)
- [ ] API key generated in Directus
- [ ] API key added to `config.php`
- [ ] Field names match between Directus and admin panel
- [ ] Required fields are marked as required in Directus
- [ ] Permissions set for API access

---

## Troubleshooting

### "Collection not found" error
- Verify collection name matches exactly (case-sensitive)
- Check collection exists in Directus

### "Field not found" error
- Verify field names match exactly
- Check field exists in Directus collection

### "Permission denied" error
- Check API key has read/write permissions
- Verify API key is correct in `config.php`

### Data not showing
- Check Directus is running on port 8055
- Verify API key is valid
- Check browser console for errors
- Verify collection and field names match

---

## Admin Panel Integration

The admin panel connects to Directus using:

1. **PHP cURL** - Server-side API calls
2. **JavaScript Fetch** - Client-side API calls
3. **Static API Key** - No Directus authentication needed

All API calls go through:
- `/api/get_students.php`
- `/api/get_teachers.php`
- `/api/get_teacher_attendance.php`
- etc.

These files handle the Directus API communication.



