<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once('../../config.php');
require_once('lib.php');

global $DB, $CFG;

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

$userids = $input['userids'] ?? [];
$mappings = $input['mappings'] ?? [];
$formid = $input['formid'] ?? 0;
$token = $input['token'] ?? '';
$cohortid = $input['cohortid'] ?? 0;

// Validate token if provided
if (!empty($token)) {
    $data = local_form_validate_token($token, 'courselist');
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }
}

// Validate user permissions
require_login();
if (!local_form_is_teacher_or_admin()) {
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

if (empty($userids) || empty($mappings)) {
    echo json_encode(['success' => false, 'error' => 'Missing required data']);
    exit;
}

// ============================================
// CONFIGURE YOUR LOCAL SARGAM DATABASE CONNECTION
// ============================================
// $sargam_config = [
//     'host' => 'localhost',
//     'port' => 3306,
//     'database' => 'sargam',
//     'username' => 'hardeep',
//     'password' => 'phpmyadmin'
// ];

$sargam_config = [
    'host' => 'db-centcom-staging-cin.mysql.database.azure.com',
    'port' => 3306,
    'database' => 'staging_sargam_db',
    'username' => 'staging_sargam',
    'password' => 'Welcome@#2027'
];

try {
    // Connect to Sargam database
    $sargamdb = new mysqli(
        $sargam_config['host'],
        $sargam_config['username'],
        $sargam_config['password'],
        $sargam_config['database'],
        $sargam_config['port']
    );

    if ($sargamdb->connect_error) {
        throw new Exception("Sargam connection failed: " . $sargamdb->connect_error);
    }

    // Set charset
    $sargamdb->set_charset("utf8mb4");

    // Begin transaction
    $sargamdb->begin_transaction();

    $migrated_count = 0;
    $details = [];
    $student_pk_map = [];

    // Log the mappings we received
    $details[] = "📋 Received mappings: " . json_encode($mappings);

    // Process each user
    // In the main migration loop (inside foreach ($userids as $userid))
    foreach ($userids as $userid) {
        // Get Moodle user data
        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user) {
            $details[] = "User ID $userid not found in Moodle";
            continue;
        }

        $details[] = "==========================================";
        $details[] = "👤 Processing user: {$user->username} (ID: {$user->id})";

        // Get password from local_user if exists (optional)
        $password = '';
        if ($DB->get_manager()->table_exists('local_user')) {
            $password = $DB->get_field('local_user', 'password', ['userid' => $userid]) ?: '';
        }

        // ============================================
        // STEP 1: MIGRATE TO STUDENT_MASTER FIRST (to get PK)
        // ============================================
        $student_master_pk = null;
        if (!empty($mappings['student_master'])) {
            $details[] = "--- Student Master Migration ---";
            $student_master_pk = migrate_student_master_simple($sargamdb, $user, $mappings['student_master'], $password, $details);
            if ($student_master_pk && $student_master_pk > 0) {
                $student_pk_map[$user->username] = $student_master_pk;
                $details[] = "  ✅ Student Master PK: $student_master_pk";
            }
        }

        // ============================================
        // STEP 2: MIGRATE TO USER_CREDENTIALS (with student_master_pk)
        // ============================================
        if (!empty($mappings['user_credentials'])) {
            $details[] = "--- User Credentials Migration ---";
            migrate_user_credentials_simple($sargamdb, $user, $mappings['user_credentials'], $password, $student_master_pk, $details);
        }

        // ============================================
        // STEP 3: MIGRATE TO STUDENT_MASTER_COURSE__MAP
        // ============================================
        $details[] = "--- Course Enrollment Migration ---";

        // Check both possible key names (with double underscore and without)
        $course_map_mappings = null;
        if (!empty($mappings['student_master_course__map'])) {
            $course_map_mappings = $mappings['student_master_course__map'];
            $details[] = "  ✅ Found mappings with key 'student_master_course__map'";
        } else if (!empty($mappings['student_master_course_map'])) {
            $course_map_mappings = $mappings['student_master_course_map'];
            $details[] = "  ✅ Found mappings with key 'student_master_course_map'";
        }

        // if ($course_map_mappings) {
        //     $details[] = "  📝 Course map mappings: " . json_encode($course_map_mappings);

        //     // For course map, we need a student_master_pk
        //     // If we don't have one from step 1, we need to create a basic student record first
        //     if (!$student_master_pk || $student_master_pk <= 0) {
        //         $details[] = "  🔍 No student PK available, creating minimal student record...";
        //         $student_master_pk = create_minimal_student($sargamdb, $user, $details);
        //     }

        //     if ($student_master_pk && $student_master_pk > 0) {
        //         $details[] = "  ✅ Using student_master_pk: $student_master_pk";
        //         migrate_course_map_simple($sargamdb, $user, $course_map_mappings, $student_master_pk, $details);
        //     } else {
        //         $details[] = "  ❌ Cannot migrate course map: Failed to get/create student_master record";
        //     }
        // } else {
        //     $details[] = "  ⚠️ No course map mappings found";
        //     $details[] = "  📝 Available mapping keys: " . implode(', ', array_keys($mappings));
        // }

        // In the main migration loop (inside foreach ($userids as $userid))
        // After STEP 2, update the call to migrate_course_map_simple:

        if ($course_map_mappings) {
            $details[] = "  📝 Course map mappings: " . json_encode($course_map_mappings);

            if (!$student_master_pk || $student_master_pk <= 0) {
                $details[] = "  🔍 No student PK available, creating minimal student record...";
                $student_master_pk = create_minimal_student($sargamdb, $user, $details);
            }

            if ($student_master_pk && $student_master_pk > 0) {
                $details[] = "  ✅ Using student_master_pk: $student_master_pk";

                // ============ PASS THE FORMID ============
                migrate_course_map_simple($sargamdb, $user, $course_map_mappings, $student_master_pk, $formid, $details);
                // ==========================================
            } else {
                $details[] = "  ❌ Cannot migrate course map: Failed to get/create student_master record";
            }
        }

        $migrated_count++;
        $details[] = "==========================================";
    }

    // Commit transaction
    $sargamdb->commit();

    // Close connection
    $sargamdb->close();

    // Return success
    echo json_encode([
        'success' => true,
        'migrated_count' => $migrated_count,
        'details' => $details
    ]);
} catch (Exception $e) {
    // Rollback on error
    if (isset($sargamdb) && $sargamdb->connect_error === null) {
        $sargamdb->rollback();
        $sargamdb->close();
    }

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Create a minimal student record if none exists
 */
function create_minimal_student($sargamdb, $user, &$details)
{
    $details[] = "    Creating minimal student record for {$user->username}";

    // Check if already exists
    $check = $sargamdb->query("SELECT pk FROM student_master WHERE user_id = '" . $sargamdb->real_escape_string($user->username) . "'");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $details[] = "    ✅ Found existing student with PK: " . $row['pk'];
        return $row['pk'];
    }

    // Insert minimal record
    $fields = ["`user_id`", "`first_name`", "`service_master_pk`"];
    $values = [
        "'" . $sargamdb->real_escape_string($user->username) . "'",
        "'" . $sargamdb->real_escape_string($user->firstname ?: 'Unknown') . "'",
        "1"
    ];

    $sql = "INSERT INTO student_master (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
    $details[] = "    ➕ Insert SQL: " . $sql;

    $result = $sargamdb->query($sql);

    if ($result) {
        $pk = $sargamdb->insert_id;
        $details[] = "    ✅ Created minimal student record with PK: $pk";
        return $pk;
    } else {
        $details[] = "    ❌ Failed to create student record: " . $sargamdb->error;
        return null;
    }
}

/**
 * Simple user credentials migration with user_category default and user_id mapping
 */
function migrate_user_credentials_simple($sargamdb, $user, $field_mappings, $password, $student_master_pk = null, &$details)
{
    $fields = [];
    $values = [];
    $has_required_fields = false;

    foreach ($field_mappings as $moodlecol => $sargamcol) {
        $value = get_optimized_value($user, $moodlecol, $password);

        // Handle value based on field type and content
        if ($value === null) {
            // For required fields, we need defaults
            if (in_array($sargamcol, ['user_name', 'first_name', 'last_name', 'email_id'])) {
                // These are required - get default values
                $default_value = get_default_value($user, $sargamcol, $moodlecol);
                $fields[] = "`$sargamcol`";
                $values[] = $default_value;
                $details[] = "  📝 Mapping: $moodlecol → $sargamcol = '$default_value' (default for empty)";
                $has_required_fields = true;
            } else {
                // Optional fields can be NULL
                $fields[] = "`$sargamcol`";
                $values[] = null;
                $details[] = "  📝 Mapping: $moodlecol → $sargamcol = NULL (empty)";
            }
        } else {
            $fields[] = "`$sargamcol`";
            $values[] = $value;
            $details[] = "  📝 Mapping: $moodlecol → $sargamcol = '$value'";
            $has_required_fields = true;
        }
    }

    // ============ NEW: Add user_category with default 'S' ============
    $user_category_exists = false;
    foreach ($fields as $field) {
        if (strpos($field, '`user_category`') !== false) {
            $user_category_exists = true;
            break;
        }
    }

    if (!$user_category_exists) {
        $fields[] = "`user_category`";
        $values[] = 'S';  // Default value 'S' for Student
        $details[] = "  ➕ Adding user_category = 'S' (default for student)";
        $has_required_fields = true;
    }
    // ================================================================

    // ============ NEW: Map user_id to student_master_pk ============
    if ($student_master_pk && $student_master_pk > 0) {
        $user_id_exists = false;
        foreach ($fields as $field) {
            if (strpos($field, '`user_id`') !== false) {
                $user_id_exists = true;
                break;
            }
        }

        if (!$user_id_exists) {
            $fields[] = "`user_id`";
            $values[] = $student_master_pk;
            $details[] = "  ➕ Adding user_id = $student_master_pk (mapped from student_master PK)";
            $has_required_fields = true;
        } else {
            // Update existing user_id mapping
            for ($i = 0; $i < count($fields); $i++) {
                if (strpos($fields[$i], '`user_id`') !== false) {
                    $values[$i] = $student_master_pk;
                    $details[] = "  🔄 Updating user_id = $student_master_pk (mapped from student_master PK)";
                    break;
                }
            }
        }
    }
    // ================================================================

    if (!$has_required_fields) {
        $details[] = "  ⚠️ No required fields to migrate";
        return null;
    }

    // Check if user exists
    $check = $sargamdb->query("SELECT pk FROM user_credentials WHERE user_name = '" . $sargamdb->real_escape_string($user->username) . "'");

    if ($check && $check->num_rows > 0) {
        // Update existing
        $row = $check->fetch_assoc();
        $set = [];
        foreach ($fields as $i => $field) {
            if ($values[$i] === null) {
                $set[] = "$field = NULL";
            } else {
                $set[] = "$field = '" . $sargamdb->real_escape_string($values[$i]) . "'";
            }
        }
        $sql = "UPDATE user_credentials SET " . implode(', ', $set) . " WHERE pk = " . $row['pk'];
        $result = $sargamdb->query($sql);

        if ($result) {
            $details[] = "  ✅ Updated user_credentials (PK: {$row['pk']})";
            return $row['pk'];
        } else {
            $details[] = "  ❌ Update failed: " . $sargamdb->error;
            return null;
        }
    } else {
        // Insert new - build query with proper NULL handling
        $insert_fields = [];
        $insert_values = [];

        foreach ($fields as $i => $field) {
            $insert_fields[] = $field;
            if ($values[$i] === null) {
                $insert_values[] = "NULL";
            } else {
                $insert_values[] = "'" . $sargamdb->real_escape_string($values[$i]) . "'";
            }
        }

        $sql = "INSERT INTO user_credentials (" . implode(', ', $insert_fields) . ") VALUES (" . implode(', ', $insert_values) . ")";
        $details[] = "  ➕ Insert SQL: " . $sql;

        $result = $sargamdb->query($sql);

        if ($result) {
            $pk = $sargamdb->insert_id;
            $details[] = "  ✅ Inserted user_credentials (PK: $pk)";
            return $pk;
        } else {
            $details[] = "  ❌ Insert failed: " . $sargamdb->error;
            return null;
        }
    }
}

/**
 * Simple student master migration
 */

function migrate_student_master_simple($sargamdb, $user, $field_mappings, $password, &$details)
{
    $fields = [];
    $values = [];

    // Add mapped fields
    foreach ($field_mappings as $moodlecol => $sargamcol) {
        $value = get_optimized_value($user, $moodlecol, $password);

        if ($value === null) {
            // For student master, handle empty values appropriately
            if (in_array($sargamcol, ['user_id', 'first_name', 'service_master_pk'])) {
                // These are critical fields - get defaults
                $default_value = get_default_value($user, $sargamcol, $moodlecol);
                $fields[] = "`$sargamcol`";
                $values[] = $default_value;
                $details[] = "  📝 Mapping: $moodlecol → $sargamcol = '$default_value' (default for empty)";
            } else {
                // Optional fields can be NULL
                $fields[] = "`$sargamcol`";
                $values[] = null;
                $details[] = "  📝 Mapping: $moodlecol → $sargamcol = NULL (empty)";
            }
        } else {
            $fields[] = "`$sargamcol`";
            $values[] = $value;
            $details[] = "  📝 Mapping: $moodlecol → $sargamcol = '$value'";
        }
    }

    // Add required fields if missing
    $required = [
        'service_master_pk' => 1
    ];

    foreach ($required as $field => $default) {
        $field_exists = false;
        foreach ($fields as $f) {
            if (strpos($f, "`$field`") !== false) {
                $field_exists = true;
                break;
            }
        }

        if (!$field_exists) {
            $fields[] = "`$field`";
            $values[] = $default;
            $details[] = "  ➕ Adding required: $field = '$default'";
        }
    }

    // ============ NEW: Add generated_OT_code with random unique code ============
    $otp_code_exists = false;
    foreach ($fields as $field) {
        if (strpos($field, '`generated_OT_code`') !== false) {
            $otp_code_exists = true;
            break;
        }
    }

    if (!$otp_code_exists) {
        $otp_code = generate_unique_otp_code($sargamdb);
        $fields[] = "`generated_OT_code`";
        $values[] = $otp_code;
        $details[] = "  🎫 Adding generated_OT_code = '$otp_code' (unique random code)";
    } else {
        // If already mapped, ensure it's unique
        for ($i = 0; $i < count($fields); $i++) {
            if (strpos($fields[$i], '`generated_OT_code`') !== false) {
                $existing_code = $values[$i];
                // Check if code exists in database
                $check = $sargamdb->query("SELECT pk FROM student_master WHERE generated_OT_code = '" . $sargamdb->real_escape_string($existing_code) . "'");
                if ($check && $check->num_rows > 0) {
                    // Code already exists, generate new one
                    $new_code = generate_unique_otp_code($sargamdb);
                    $values[$i] = $new_code;
                    $details[] = "  🔄 Updated generated_OT_code: '$existing_code' → '$new_code' (duplicate avoidance)";
                } else {
                    $details[] = "  ✅ Using existing generated_OT_code mapping: '$existing_code'";
                }
                break;
            }
        }
    }
    // ============================================================================

    if (empty($fields)) {
        $details[] = "  ⚠️ No fields to migrate";
        return null;
    }

    // Check if exists
    $check = $sargamdb->query("SELECT pk FROM student_master WHERE user_id = '" . $sargamdb->real_escape_string($user->username) . "'");

    if ($check && $check->num_rows > 0) {
        // Update existing
        $row = $check->fetch_assoc();
        $set = [];
        foreach ($fields as $i => $field) {
            if ($values[$i] === null) {
                $set[] = "$field = NULL";
            } else {
                $set[] = "$field = '" . $sargamdb->real_escape_string($values[$i]) . "'";
            }
        }
        $sql = "UPDATE student_master SET " . implode(', ', $set) . " WHERE pk = " . $row['pk'];
        $result = $sargamdb->query($sql);

        if ($result) {
            $details[] = "  ✅ Updated student_master (PK: {$row['pk']})";
            return $row['pk'];
        } else {
            $details[] = "  ❌ Update failed: " . $sargamdb->error;
            return null;
        }
    } else {
        // Insert new
        $insert_fields = [];
        $insert_values = [];

        foreach ($fields as $i => $field) {
            $insert_fields[] = $field;
            if ($values[$i] === null) {
                $insert_values[] = "NULL";
            } else {
                $insert_values[] = "'" . $sargamdb->real_escape_string($values[$i]) . "'";
            }
        }

        $sql = "INSERT INTO student_master (" . implode(', ', $insert_fields) . ") VALUES (" . implode(', ', $insert_values) . ")";
        $details[] = "  ➕ Insert SQL: " . $sql;

        $result = $sargamdb->query($sql);

        if ($result) {
            $pk = $sargamdb->insert_id;
            $details[] = "  ✅ Inserted student_master (PK: $pk)";
            $details[] = "  🎫 Generated OTP Code: " . ($otp_code_exists ? $otp_code : 'N/A');
            return $pk;
        } else {
            $details[] = "  ❌ Insert failed: " . $sargamdb->error;
            return null;
        }
    }
}

/**
 * Generate a random OTP code like A52, B78, etc.
 */
function generate_otp_code()
{
    // Prefix: A-Z (uppercase letters)
    $prefix = chr(rand(65, 90)); // ASCII 65-90 = A-Z

    // Suffix: 2-digit number from 10-99
    $suffix = rand(10, 99);

    return $prefix . $suffix;
}

/**
 * Generate unique OTP code (ensures no duplicates in database)
 */
function generate_unique_otp_code($sargamdb)
{
    $max_attempts = 10;
    $attempt = 0;

    while ($attempt < $max_attempts) {
        $otp_code = generate_otp_code();

        // Check if code already exists
        $check = $sargamdb->query("SELECT pk FROM student_master WHERE generated_OT_code = '" . $sargamdb->real_escape_string($otp_code) . "'");

        if (!$check || $check->num_rows == 0) {
            return $otp_code; // Unique code found
        }

        $attempt++;
    }

    // Fallback: Use timestamp-based unique code
    return 'T' . time() . rand(10, 99);
}


/**
 * Get optimized value - returns null for empty strings
 */
function get_optimized_value($user, $field, $password = '')
{
    // Handle password specially
    if (in_array($field, ['password_hash', 'password']) && !empty($password)) {
        return $password;
    }

    // Handle ID field
    if ($field == 'id') {
        return $user->id;
    }

    // Get the value
    $value = $user->$field ?? null;

    // Return null for empty strings, 0, false, etc.
    if ($value === '' || $value === null || $value === false) {
        return null;
    }

    // For numeric fields that should be numbers, ensure they're not empty
    if (is_numeric($value) && $value == 0 && in_array($field, ['phone1', 'phone2', 'mobile_no', 'contact_no'])) {
        return null; // Treat zero phone numbers as null
    }

    return $value;
}

/**
 * Get default value for required fields when empty
 */
/**
 * Get default value for required fields when empty
 */
function get_default_value($user, $sargamcol, $moodlecol, $sargamdb = null)
{
    switch ($sargamcol) {
        case 'user_name':
        case 'user_id':
            return $user->username ?: 'unknown_user';

        case 'first_name':
            return $user->firstname ?: 'Unknown';

        case 'last_name':
            return $user->lastname ?: 'User';

        case 'email':
        case 'email_id':
            return $user->email ?: $user->username . '@example.com';

        case 'mobile_no':
        case 'contact_no':
            return '0000000000'; // Default phone number

        case 'service_master_pk':
            return 1;

        case 'user_category':
            return 'S'; // Default student category

        case 'generated_OT_code':
            if ($sargamdb) {
                return generate_unique_otp_code($sargamdb);
            }
            return generate_otp_code(); // Fallback without DB check

        case 'password':
            return !empty($password) ? $password : 'default_password';

        default:
            return 'N/A';
    }
}

/**
 * Simple course map migration
 */

/**
 * Migrate course map - ONLY FOR SPECIFIC FORM
 * Only creates enrollment for the specific form being migrated
 */
function migrate_course_map_simple($sargamdb, $user, $field_mappings, $student_master_pk, $formid, &$details)
{

    global $DB;

    $details[] = "  🔍 Processing course map with student PK: $student_master_pk for form ID: $formid";

    // Validate student_master_pk
    if (!$student_master_pk || $student_master_pk <= 0) {
        $details[] = "  ❌ Invalid student_master_pk: $student_master_pk";
        return null;
    }

    // Step 1: Get the specific form submission for this user and form
    $submission = $DB->get_record('form_submissions', [
        'formid' => $formid,
        'uid' => $user->id,
        'visible' => 1
    ]);

    if (!$submission) {
        $details[] = "  ⚠️ No form submission found for user {$user->username} and form ID: {$formid}";
        return null;
    }

    $details[] = "  ✅ Found form submission for form ID: {$formid}";

    // Step 2: Get the local form details
    $local_form = $DB->get_record('local_form', ['id' => $formid]);

    if (!$local_form) {
        $details[] = "  ❌ No local_form found with id: {$formid}";
        return null;
    }

    $shortname = trim($local_form->shortname);
    $details[] = "  📝 Local form: {$local_form->name} (Shortname: '{$shortname}')";

    // Step 3: Match with course_master in Sargam using shortname
    $course_master = get_course_master_by_shortname($sargamdb, $shortname);

    if (!$course_master) {
        $details[] = "  ❌ No course_master found with shortname: '{$shortname}' in Sargam";
        return null;
    }

    $course_master_pk = $course_master['pk'];
    $details[] = "  ✅ Found course_master: {$course_master['course_name']} (PK: {$course_master_pk})";

    // Step 4: Get existing enrollments for this student
    $existing_sql = "SELECT pk, course_master_pk, active_inactive 
                     FROM student_master_course__map 
                     WHERE student_master_pk = ?";
    $existing_stmt = $sargamdb->prepare($existing_sql);
    $existing_stmt->bind_param("i", $student_master_pk);
    $existing_stmt->execute();
    $existing_result = $existing_stmt->get_result();

    $existing_enrollments = [];
    $existing_course_exists = false;
    $existing_pk = null;

    while ($row = $existing_result->fetch_assoc()) {
        $existing_enrollments[$row['course_master_pk']] = $row;
        if ($row['course_master_pk'] == $course_master_pk) {
            $existing_course_exists = true;
            $existing_pk = $row['pk'];
            $details[] = "  ℹ️ Found existing enrollment for this course (PK: {$existing_pk})";
        }
    }

    $details[] = "  📊 Total existing enrollments: " . count($existing_enrollments);
    foreach ($existing_enrollments as $course_pk => $enrollment) {
        $status = $enrollment['active_inactive'] == 1 ? 'ACTIVE' : 'inactive';
        $details[] = "    • Course PK: {$course_pk} - {$status}";
    }

    // Step 5: Deactivate ALL existing active enrollments
    if (!empty($existing_enrollments)) {
        $details[] = "  🔄 Deactivating ALL existing enrollments...";
        $deactivate_sql = "UPDATE student_master_course__map 
                          SET active_inactive = 0, modified_date = NOW() 
                          WHERE student_master_pk = ? AND active_inactive = 1";
        $deactivate_stmt = $sargamdb->prepare($deactivate_sql);
        $deactivate_stmt->bind_param("i", $student_master_pk);
        $deactivate_result = $deactivate_stmt->execute();

        if ($deactivate_result) {
            $affected_rows = $sargamdb->affected_rows;
            $details[] = "  ✅ Deactivated {$affected_rows} existing enrollment(s)";
        }
    }

    // Step 6: Create or update enrollment for the current course
    $fields = ['`student_master_pk`', '`course_master_pk`'];
    $values = [$student_master_pk, $course_master_pk];

    // Add other mapped fields from $field_mappings
    foreach ($field_mappings as $moodlecol => $sargamcol) {
        if ($sargamcol == 'student_master_pk' || $sargamcol == 'course_master_pk') {
            continue;
        }

        $value = get_simple_value($user, $moodlecol, '');

        if ($value === null || $value === '') {
            $fields[] = "`$sargamcol`";
            $values[] = null;
            $details[] = "  📝 Mapping: $moodlecol → $sargamcol = NULL";
        } else {
            $fields[] = "`$sargamcol`";
            $values[] = $value;
            $details[] = "  📝 Mapping: $moodlecol → $sargamcol = '$value'";
        }
    }

    // Set active_inactive = 1 (active)
    $active_exists = false;
    foreach ($fields as $field) {
        if (strpos($field, '`active_inactive`') !== false) {
            $active_exists = true;
            break;
        }
    }

    if (!$active_exists) {
        $fields[] = '`active_inactive`';
        $values[] = 1;
        $details[] = "  ➕ Setting active_inactive = 1 (ACTIVE)";
    }

    // Set dates
    $created_date = $local_form->timecreated ? date('Y-m-d H:i:s', $local_form->timecreated) : date('Y-m-d H:i:s');
    $modified_date = date('Y-m-d H:i:s');

    if (!in_array('`created_date`', $fields)) {
        $fields[] = '`created_date`';
        $values[] = $created_date;
        $details[] = "  ➕ Adding created_date: '$created_date'";
    }

    if (!in_array('`modified_date`', $fields)) {
        $fields[] = '`modified_date`';
        $values[] = $modified_date;
        $details[] = "  ➕ Adding modified_date: '$modified_date'";
    }

    if ($existing_course_exists) {
        // Update existing enrollment to active
        $details[] = "  🔄 Updating existing enrollment to active";
        $set = [];
        $update_values = [];
        $update_types = "";

        for ($i = 2; $i < count($fields); $i++) {
            if ($values[$i] !== null && $values[$i] !== '') {
                $set[] = "$fields[$i] = ?";
                $update_values[] = $values[$i];

                if (is_numeric($values[$i]) && !is_string($values[$i])) {
                    $update_types .= "i";
                } else {
                    $update_types .= "s";
                }
            }
        }

        // Always update modified_date
        $now = date('Y-m-d H:i:s');
        $set[] = "`modified_date` = ?";
        $update_values[] = $now;
        $update_types .= "s";

        // Add PK for WHERE clause
        $update_values[] = $existing_pk;
        $update_types .= "i";

        if (!empty($set)) {
            $update_sql = "UPDATE student_master_course__map SET " . implode(', ', $set) . " WHERE pk = ?";
            $update_stmt = $sargamdb->prepare($update_sql);
            $update_stmt->bind_param($update_types, ...$update_values);
            $result = $update_stmt->execute();

            if ($result) {
                $details[] = "  ✅ Updated existing enrollment (PK: {$existing_pk}) to ACTIVE";
                $last_pk = $existing_pk;
            } else {
                $details[] = "  ❌ Update failed: " . $sargamdb->error;
                return null;
            }
        }
    } else {
        // Create new enrollment
        $details[] = "  🆕 Creating new enrollment for course PK: {$course_master_pk}";

        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $insert_sql = "INSERT INTO student_master_course__map (" . implode(', ', $fields) . ") VALUES (" . $placeholders . ")";

        $insert_types = "";
        foreach ($values as $val) {
            if (is_numeric($val) && !is_string($val)) {
                $insert_types .= "i";
            } else {
                $insert_types .= "s";
            }
        }

        $insert_stmt = $sargamdb->prepare($insert_sql);
        $insert_stmt->bind_param($insert_types, ...$values);
        $result = $insert_stmt->execute();

        if ($result) {
            $pk = $sargamdb->insert_id;
            $details[] = "  ✅✅✅ SUCCESS! Created new enrollment (PK: $pk)";
            $details[] = "  📝 Student PK: {$student_master_pk} → Course PK: {$course_master_pk}";
            $details[] = "  🟢 Active status: 1 (ACTIVE)";
            $last_pk = $pk;
        } else {
            $details[] = "  ❌ Insert failed: " . $sargamdb->error;
            return null;
        }
    }

    // Step 7: Final summary
    $details[] = "  📊 Final enrollment status for student PK: {$student_master_pk}";
    $final_sql = "SELECT course_master_pk, active_inactive, created_date 
                  FROM student_master_course__map 
                  WHERE student_master_pk = ? 
                  ORDER BY active_inactive DESC, created_date DESC";
    $final_stmt = $sargamdb->prepare($final_sql);
    $final_stmt->bind_param("i", $student_master_pk);
    $final_stmt->execute();
    $final_result = $final_stmt->get_result();

    while ($row = $final_result->fetch_assoc()) {
        $status = $row['active_inactive'] == 1 ? '🟢 ACTIVE' : '⚫ inactive';
        $details[] = "    • Course PK: {$row['course_master_pk']} - {$status} (created: {$row['created_date']})";
    }

    $details[] = "  ✅ Successfully processed course enrollment for user {$user->username}";
    return $last_pk;
}

/**
 * Get course master by shortname from Sargam database
 */
function get_course_master_by_shortname($sargamdb, $shortname)
{

    // Note: The column is named "couse_short_name" (typo) in your table
    $sql = "SELECT pk, course_name, couse_short_name 
            FROM course_master 
            WHERE couse_short_name = ? AND active_inactive = 1";

    $stmt = $sargamdb->prepare($sql);
    $stmt->bind_param("s", $shortname);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * Get simple value - enhanced version
 */
function get_simple_value($user, $field, $password = '')
{
    // Handle password specially
    if (in_array($field, ['password', 'password_hash', 'jbp_password']) && !empty($password)) {
        return $password;
    }

    // Handle ID field
    if ($field == 'id') {
        return $user->id;
    }

    // Handle username
    if ($field == 'username') {
        return $user->username;
    }

    // Handle timestamps - convert to datetime if needed
    if (in_array($field, ['timecreated', 'timemodified', 'lastaccess'])) {
        $value = $user->$field ?? null;
        return $value ? date('Y-m-d H:i:s', $value) : null;
    }

    // Get the value
    $value = $user->$field ?? null;

    // Return null for empty strings
    if ($value === '' || $value === null) {
        return null;
    }

    return $value;
}
