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
        // STEP 1: MIGRATE TO USER_CREDENTIALS (if mappings exist)
        // ============================================
        if (!empty($mappings['user_credentials'])) {
            $details[] = "--- User Credentials Migration ---";
            migrate_user_credentials_simple($sargamdb, $user, $mappings['user_credentials'], $password, $details);
        }

        // ============================================
        // STEP 2: MIGRATE TO STUDENT_MASTER (if mappings exist)
        // ============================================
        $student_master_pk = null;
        if (!empty($mappings['student_master'])) {
            $details[] = "--- Student Master Migration ---";
            $student_master_pk = migrate_student_master_simple($sargamdb, $user, $mappings['student_master'], $password, $details);
            if ($student_master_pk && $student_master_pk > 0) {
                $student_pk_map[$user->username] = $student_master_pk;
            }
        }

        // ============================================
        // STEP 3: MIGRATE TO STUDENT_MASTER_COURSE__MAP - FIXED KEY NAME
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

        if ($course_map_mappings) {
            $details[] = "  📝 Course map mappings: " . json_encode($course_map_mappings);

            // For course map, we need a student_master_pk
            // If we don't have one from step 2, we need to create a basic student record first
            if (!$student_master_pk || $student_master_pk <= 0) {
                $details[] = "  🔍 No student PK available, creating minimal student record...";

                // Create a minimal student record using the username
                $student_master_pk = create_minimal_student($sargamdb, $user, $details);
            }

            if ($student_master_pk && $student_master_pk > 0) {
                $details[] = "  ✅ Using student_master_pk: $student_master_pk";
                migrate_course_map_simple($sargamdb, $user, $course_map_mappings, $student_master_pk, $details);
            } else {
                $details[] = "  ❌ Cannot migrate course map: Failed to get/create student_master record";
            }
        } else {
            $details[] = "  ⚠️ No course map mappings found";
            $details[] = "  📝 Available mapping keys: " . implode(', ', array_keys($mappings));
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
 * Simple user credentials migration
 */
function migrate_user_credentials_simple($sargamdb, $user, $field_mappings, $password, &$details)
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

            // Ultimate fallback - insert with only absolute required fields
            $details[] = "  🔄 Trying ultimate fallback...";
            $fallback_fields = ["`user_name`", "`first_name`", "`last_name`", "`email_id`"];
            $fallback_values = [
                "'" . $sargamdb->real_escape_string($user->username) . "'",
                "'" . $sargamdb->real_escape_string($user->firstname ?: 'Unknown') . "'",
                "'" . $sargamdb->real_escape_string($user->lastname ?: 'User') . "'",
                "'" . $sargamdb->real_escape_string($user->email ?: $user->username . '@example.com') . "'"
            ];

            $fallback_sql = "INSERT INTO user_credentials (" . implode(', ', $fallback_fields) . ") VALUES (" . implode(', ', $fallback_values) . ")";
            $details[] = "  ➕ Fallback SQL: " . $fallback_sql;

            $fallback_result = $sargamdb->query($fallback_sql);

            if ($fallback_result) {
                $pk = $sargamdb->insert_id;
                $details[] = "  ✅ Inserted user_credentials with fallback (PK: $pk)";
                return $pk;
            } else {
                $details[] = "  ❌ Fallback failed: " . $sargamdb->error;
                return null;
            }
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
            return $pk;
        } else {
            $details[] = "  ❌ Insert failed: " . $sargamdb->error;

            // Ultimate fallback - minimal insert
            $details[] = "  🔄 Trying minimal insert...";
            $min_fields = ["`user_id`", "`first_name`", "`service_master_pk`"];
            $min_values = [
                "'" . $sargamdb->real_escape_string($user->username) . "'",
                "'" . $sargamdb->real_escape_string($user->firstname ?: 'Unknown') . "'",
                "1"
            ];

            $min_sql = "INSERT INTO student_master (" . implode(', ', $min_fields) . ") VALUES (" . implode(', ', $min_values) . ")";
            $min_result = $sargamdb->query($min_sql);

            if ($min_result) {
                $pk = $sargamdb->insert_id;
                $details[] = "  ✅ Inserted student_master with minimal fields (PK: $pk)";
                return $pk;
            } else {
                $details[] = "  ❌ Minimal insert failed: " . $sargamdb->error;
                return null;
            }
        }
    }
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
function get_default_value($user, $sargamcol, $moodlecol)
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
 * Migrate course map - UPDATED with correct logic using form_submissions and shortname matching
 */
function migrate_course_map_simple($sargamdb, $user, $field_mappings, $student_master_pk, &$details) {
    global $DB;
    
    $details[] = "  🔍 Processing course map with student PK: $student_master_pk";
    
    // Validate student_master_pk
    if (!$student_master_pk || $student_master_pk <= 0) {
        $details[] = "  ❌ Invalid student_master_pk: $student_master_pk";
        return null;
    }

    // Step 1: Get all distinct form IDs from form_submissions for this user
    $sql = "SELECT DISTINCT fs.formid 
            FROM {form_submissions} fs
            WHERE fs.uid = ? AND fs.visible = 1";
    
    $submissions = $DB->get_records_sql($sql, [$user->id]);
    
    if (empty($submissions)) {
        $details[] = "  ⚠️ No form submissions found for user {$user->username}";
        return null;
    }
    
    $details[] = "  📊 Found " . count($submissions) . " distinct form submissions";
    $enrollment_count = 0;
    $last_pk = null;
    
    foreach ($submissions as $submission) {
        $formid = $submission->formid;
        $details[] = "  📝 Processing form ID: {$formid}";
        
        // Step 2: Get the local form details using formid
        $local_form = $DB->get_record('local_form', ['id' => $formid]);
        
        if (!$local_form) {
            $details[] = "  ⚠️ No local_form found with id: {$formid}";
            continue;
        }
        
        $shortname = trim($local_form->shortname);
        $details[] = "  ✅ Found local form: {$local_form->name} (Shortname: '{$shortname}')";
        
        // Step 3: Match with course_master in Sargam using shortname
        $course_master = get_course_master_by_shortname($sargamdb, $shortname);
        
        if (!$course_master) {
            $details[] = "  ⚠️ No course_master found with shortname: '{$shortname}' in Sargam";
            continue;
        }
        
        $details[] = "  ✅ Found course_master: {$course_master['course_name']} (PK: {$course_master['pk']})";
        
        // Step 4: Prepare fields for insertion - start with student_master_pk and course_master_pk
        $fields = ['`student_master_pk`', '`course_master_pk`'];
        $values = [$student_master_pk, $course_master['pk']];
        
        // Track if we found course_master_pk in mappings (for logging)
        $found_course_pk = true;
        $course_pk_value = $course_master['pk'];
        
        // Add other mapped fields from $field_mappings
        foreach ($field_mappings as $moodlecol => $sargamcol) {
            // Skip these as we've already added them
            if ($sargamcol == 'student_master_pk' || $sargamcol == 'course_master_pk') {
                continue;
            }
            
            $value = get_simple_value($user, $moodlecol, '');
            
            // Handle empty values - convert to NULL for optional fields
            if ($value === null || $value === '') {
                $fields[] = "`$sargamcol`";
                $values[] = null;
                $details[] = "  📝 Mapping: $moodlecol → $sargamcol = NULL (empty)";
            } else {
                $fields[] = "`$sargamcol`";
                $values[] = $value;
                $details[] = "  📝 Mapping: $moodlecol → $sargamcol = '$value'";
            }
        }
        
        // Add defaults if not already mapped
        if (!in_array('`active_inactive`', $fields)) {
            $fields[] = '`active_inactive`';
            $values[] = 1;
            $details[] = "  ➕ Adding default active_inactive = 1";
        }
        
        // Use local_form timestamps for dates if available, otherwise use current time
        $created_date = $local_form->timecreated ? date('Y-m-d H:i:s', $local_form->timecreated) : date('Y-m-d H:i:s');
        $modified_date = $local_form->timecreated ? date('Y-m-d H:i:s', $local_form->timecreated) : date('Y-m-d H:i:s');
        
        if (!in_array('`created_date`', $fields)) {
            $fields[] = '`created_date`';
            $values[] = $created_date;
            $details[] = "  ➕ Adding created_date from local_form: '$created_date'";
        }
        
        if (!in_array('`modified_date`', $fields)) {
            $fields[] = '`modified_date`';
            $values[] = $modified_date;
            $details[] = "  ➕ Adding modified_date from local_form: '$modified_date'";
        }
        
        // Prepare values for SQL with proper escaping
        $sql_values = [];
        foreach ($values as $val) {
            if ($val === null || $val === '') {
                $sql_values[] = "NULL";
            } elseif (is_numeric($val) && !is_string($val)) {
                $sql_values[] = $val;
            } else {
                $sql_values[] = "'" . $sargamdb->real_escape_string($val) . "'";
            }
        }
        
        // Check if record already exists for this student and course
        $check_sql = "SELECT pk FROM student_master_course__map 
                      WHERE student_master_pk = ? AND course_master_pk = ?";
        $check_stmt = $sargamdb->prepare($check_sql);
        $check_stmt->bind_param("ii", $student_master_pk, $course_master['pk']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result && $check_result->num_rows > 0) {
            // Update existing record
            $row = $check_result->fetch_assoc();
            $set = [];
            $update_values = [];
            $update_types = "";
            
            for ($i = 2; $i < count($fields); $i++) { // Skip first two fields (student_master_pk, course_master_pk)
                if ($values[$i] !== null && $values[$i] !== '') {
                    $field_name = str_replace('`', '', $fields[$i]);
                    $set[] = "$fields[$i] = ?";
                    $update_values[] = $values[$i];
                    
                    // Determine type for binding
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
            $update_values[] = $row['pk'];
            $update_types .= "i";
            
            if (!empty($set)) {
                $update_sql = "UPDATE student_master_course__map SET " . implode(', ', $set) . " WHERE pk = ?";
                $details[] = "  🔄 Update SQL: " . $update_sql;
                
                $update_stmt = $sargamdb->prepare($update_sql);
                $update_stmt->bind_param($update_types, ...$update_values);
                $result = $update_stmt->execute();
                
                if ($result) {
                    $details[] = "  ✅ Updated course map (PK: " . $row['pk'] . ")";
                    $last_pk = $row['pk'];
                    $enrollment_count++;
                } else {
                    $details[] = "  ❌ Update failed: " . $sargamdb->error;
                }
            }
        } else {
            // Insert new record
            $placeholders = implode(', ', array_fill(0, count($fields), '?'));
            $insert_sql = "INSERT INTO student_master_course__map (" . implode(', ', $fields) . ") VALUES (" . $placeholders . ")";
            $details[] = "  ➕ Insert SQL: " . $insert_sql;
            
            // Determine types for binding
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
                $details[] = "  ✅✅✅ SUCCESS! Inserted course map (PK: $pk)";
                $details[] = "  📝 Linked: student_master_pk {$student_master_pk} → course_master_pk {$course_master['pk']} (from shortname '{$shortname}')";
                $last_pk = $pk;
                $enrollment_count++;
            } else {
                $details[] = "  ❌ Insert failed: " . $sargamdb->error;
            }
        }
    }
    
    $details[] = "  ✅ Processed {$enrollment_count} course enrollments for user {$user->username}";
    return $last_pk;
}

/**
 * Get course master by shortname from Sargam database
 */
function get_course_master_by_shortname($sargamdb, $shortname) {
    
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
