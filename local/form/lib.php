<?php
defined('MOODLE_INTERNAL') || die();

define('PERPAGE', 5);
define('COURSEERPAGE', 5);


// To get the data in the table.
function show_table()
{
  global $DB;
  $show = $DB->get_records('myform', array());
  return $show;
}

// To fetch the data of user from the database.
function fetch_data($userid)
{
  global $DB;
  $data = $DB->get_record('myform', array('id' => $userid));
  return $data;
}

function local_form_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array())
{

  global $CFG, $DB;
  require_once("$CFG->libdir/resourcelib.php");

  $filename = array_pop($args);
  $itemid = array_shift($args);

  $fs = get_file_storage();

  $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
  if (!$file = $fs->get_file($context->id, 'local_form', $filearea, $itemid, $filepath, $filename) or $file->is_directory()) {
    send_file_not_found();
  }
  // finally send the file
  send_stored_file($file, null, 0, $forcedownload, $options);
}

// code for sorting
function allcoursesswaprecords($tablename, $sortid, $tableid, $move, $param = array())
{
  global $DB;
  $params = array($sortid);
  if ($move == 'up') {
    $select = 'sortorder < ? ';
    $sort = 'sortorder DESC';
  } else {
    $select = 'sortorder > ? ';
    $sort = 'sortorder ASC';
  }
  allcourses_fixed_sortorder($tablename, $param);

  $swaprecord = $DB->get_records_select($tablename, $select, $params, $sort, '*', 0, 1);
  if ($swaprecord) {
    $swaprecord = reset($swaprecord);
    $DB->set_field($tablename, 'sortorder', $swaprecord->sortorder, array('id' => $tableid));
    $DB->set_field($tablename, 'sortorder', $sortid, array('id' => $swaprecord->id));
    allcourses_fixed_sortorder($tablename, $param);
  }
}

function allcourses_fixed_sortorder($tablename, $params = array())
{
  global $DB;
  $i = 1;
  $records = $DB->get_records($tablename, $params, 'sortorder ASC, id DESC', 'id, sortorder');
  foreach ($records as $record) {
    if ($record->sortorder != $i) {
      $record->sortorder = $i;
      $DB->update_record_raw($tablename, $record, true);
    }
    $i++;
  }
}

function get_homepage_leftimage()
{

  $images = array();
  $context = context_system::instance();
  $fs = get_file_storage();
  $files = $fs->get_area_files($context->id, 'local_form', 'formleftimage', 0);

  foreach ($files as $file) {
    $filename = $file->get_filename();
    if ($filename !== '.') {
      $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $filename);
      $imagesdata = new stdClass();
      $imagesdata->filename = $filename;
      $imagesdata->url = $url;
      $images[] = $imagesdata;
    }
  }
  return $images;
}

function get_homepage_rightimage()
{

  $images = array();
  $context = context_system::instance();
  $fs = get_file_storage();
  $files = $fs->get_area_files($context->id, 'local_form', 'formrightimage', 0);

  foreach ($files as $file) {
    $filename = $file->get_filename();
    if ($filename !== '.') {
      $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $filename);
      $imagesdata = new stdClass();
      $imagesdata->filename = $filename;
      $imagesdata->url = $url;
      $images[] = $imagesdata;
    }
  }
  return $images;
}

function printData($formid, $columns, $page, $perpage, $uid)
{
  global $DB, $USER;

  // Calculate pagination offsets
  $offset = $page * $perpage;

  // Fetch all records for the given form ID and user
  $sql = "SELECT id,uid, fieldname, fieldvalue
          FROM {form_submissions}
          WHERE uid = :uid AND formid = :formid
          ORDER BY uid ASC
         ";

  $params = [
    'uid' => $uid,
    'formid' => $formid,
    // 'limit' => $perpage,
    // 'offset' => $offset,
  ];

  $records = $DB->get_records_sql($sql, $params);

  // Process data into rows by `uid`
  $users = [];
  foreach ($records as $record) {
    $uid = $record->uid;

    // Initialize row data if it's the first time we see this UID
    if (!isset($users[$uid])) {
      // Add static columns first (explicit order: UID and username)
      $users[$uid] = [
        'UID' => $uid,
        'Username' => $DB->get_field('user', 'username', ['id' => $uid]) ?? 'N/A',
      ];

      // Initialize dynamic columns with empty values
      foreach ($columns as $column) {
        if (!isset($users[$uid][$column])) {
          $users[$uid][$column] = '';
        }
      }
    }

    // Check if the field requires `get_name_by_id` processing
    $fieldname = $record->fieldname;
    $fieldvalue = $record->fieldvalue;
    // if (in_array($fieldname, ['country', 'state', 'district', 'language', 'admissioncategory','stream','institution','
    //                           jobtype','boardname','qualification','religion','service','sports','clothsize','fcscale','distinction','fatherprofession',
    //                           'pantsize','shoessize','studentskill' ])) {
    //   $record->fieldvalue = get_name_by_id($fieldvalue, $fieldname) ?: 'Not provided';
    // }

    $validFields = [
      'country',
      'state',
      'district',
      'language',
      'admissioncategory',
      'stream',
      'institution',
      'jobtype',
      'boardname',
      'qualification',
      'religion',
      'service',
      'sports',
      'size',
      'fcscale',
      'distinction',
      'fatherprofession',
      'trouser',
      'shoessize',
      'studentskill'
    ];

    $isFieldValid = false;

    foreach ($validFields as $validField) {
      if (strpos($fieldname, $validField) !== false) {
        $isFieldValid = true;
        break; // Exit the loop once a match is found
      }
    }

    // if (stripos(trim($field), 'size') !== false) {
    if ($isFieldValid) {

      $record->fieldvalue = get_name_by_id($fieldvalue, $fieldname) ?: 'Not provided';
    }
    // }
    $users[$uid][$record->fieldname] = $record->fieldvalue;
  }

  return array_values($users); // Return as a list of rows
}


function printcourseData($formid, $columns, $page, $perpage, $visible, $dataformat)
{
  global $DB, $CFG;
  // Fetch all records for the given form ID
  $sql = "SELECT id, uid, fieldname, fieldvalue
              FROM {form_submissions}
             WHERE formid = :formid
               AND visible = :visible
          ORDER BY id ASC";

  $params  = ['formid' => $formid, 'visible' => 1];
  $records = $DB->get_records_sql($sql, $params);
  // Process data into rows by `uid`
  $users = [];

  foreach ($records as $record) {

    $uid = (int)$record->uid;

    // Initialize row data if it's the first time we see this UID
    if (!isset($users[$uid])) {
      // Get confirmflag status
      $confirmflag = $DB->get_field_sql(
        "SELECT MAX(confirmflag) 
       FROM {form_submissions} 
      WHERE uid = :uid AND formid = :formid",
        ['uid' => $uid, 'formid' => $formid]
      );

      // Determine status based on string values
      if ($confirmflag === false || $confirmflag === null) {
        $status = 'Not Submitted';
      } else if (strcasecmp($confirmflag, 'Confirmed') === 0) {
        $status = 'Confirmed';
      } else {
        $status = 'Not Confirmed';
      }


      $users[$uid] = [
        'UID'      => $uid,
        'Status'   => $status,
        'Username' => $DB->get_field('user', 'username', ['id' => $uid]) ?? 'N/A',
      ];

      foreach ($columns as $column) {
        if (!isset($users[$uid][$column])) {
          $users[$uid][$column] = '';
        }
      }
    }

    $fieldname  = $record->fieldname;
    $fieldvalue = $record->fieldvalue;

    // print_object($fieldvalue);
    // if (in_array($fieldname, ['country', 'state', 'district', 'language', 'admissioncategory','stream','institution',
    //                          'jobtype','boardname','qualification','religion','service','sports','clothsize','fcscale','distinction','fatherprofession',
    //                          'pantsize','shoessize','studentskill'])) {
    //   $record->fieldvalue = get_name_by_id($fieldvalue, $fieldname) ?: 'Not provided';
    // }

    $validFields = [
      // 'country',
      // 'state',
      // 'district',
      // 'language',
      // 'admissioncategory',
      // 'stream',
      // 'institution',
      // 'jobtype',
      // 'boardname',
      // 'qualification',
      // 'religion',
      // 'service',
      // 'sports',
      // 'size',
      // 'fcscale',
      // 'distinction',
      // 'fatherprofession',
      // 'trouser',
      // 'shoessize',
      // 'studentskill'
    ];

    $isFieldValid = false;

    foreach ($validFields as $validField) {
      if (strpos($fieldname, $validField) !== false) {
        $isFieldValid = true;
        break;
      }
    }

    if ($isFieldValid) {
      $record->fieldvalue = get_name_by_id($fieldvalue, $fieldname) ?: 'Not provided';
    }

    // Handle file fields
    if (!empty($fieldvalue)) {

      $filename = basename($fieldvalue);
      $filepath = $CFG->dirroot . '/local/form/pix/' . $filename;

      // Allowed file extensions
      $fileextensions = [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'gif',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'zip'
      ];

      $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

      if (in_array($ext, $fileextensions) && file_exists($filepath)) {

        // $url = $CFG->wwwroot . '/local/form/pix/' . rawurlencode($filename);
        $url = $CFG->wwwroot . '/local/form/pix/' . rawurlencode($filename);

        if ($dataformat === 'html') {

          // HTML page
          $record->fieldvalue = html_writer::link(
            $url,
            $filename,
            ['target' => '_blank']
          );
        } else if ($dataformat === 'pdf') {
          $safeurl = s($url);
          $record->fieldvalue =
            '<a href="' . $safeurl . '">' . $filename . '</a>';
        } else {
          $record->fieldvalue = $url;
        }
      }
    }

    // Populate dynamic fields
    $users[$uid][$fieldname] = $record->fieldvalue;
  }

  return array_values($users);
}



/**
 * Get dynamic column names (fieldnames) for a specific form.
 *
 * @param string $tablename The table name.
 * @param int $formid The form ID.
 * @return array List of fieldnames as column headings.
 */
function get_dynamic_columns($tablename, $formid)
{
  global $DB, $USER;

  // Fetch all distinct fieldnames for the given form ID and user
  $sql = "SELECT DISTINCT fieldname
          FROM {form_submissions}
          WHERE formid = :formid";
  $params = [
    // 'uid' => 3,
    'formid' => $formid,
  ];

  $records = $DB->get_records_sql($sql, $params);


  // Extract fieldnames into an array
  $fields[] = 'UID';
  $fields[] = 'Status';
  $fields[] = 'Username';

  foreach ($records as $record) {
    $fields[] = $record->fieldname;
  }

  return $fields;
}

// function get_name_by_id($id, $type)
// {
//   global $DB;

//   // Check if id and type are not empty
//   if (empty($id) || empty($type)) {
//     return null;  // or handle the error as needed (e.g., return an error message or log the issue)
//   }
//   // Perform the database query based on the type
//   if ($type === 'country') {
//     $sql = "SELECT country_name FROM `country_master` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   } elseif ($type === 'state') {
//     $sql = "SELECT state_name FROM `state_master` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   } elseif ($type === 'district') {
//     $sql = "SELECT district_name FROM `state_district_mapping` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   } elseif ($type === 'language') {
//     $sql = "SELECT language_name FROM `language_master` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   } elseif ($type === 'admissioncategory') {
//     $sql = "SELECT seat_name FROM `admission_category` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   } elseif ($type === 'stream') {
//     $sql = "SELECT highest_stream FROM `highest_stream` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   } elseif ($type === 'institution') {
//     $sql = "SELECT institution_type FROM `institution_type` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   } elseif ($type === 'jobtype') {
//     $sql = "SELECT job_name FROM `job_type` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   }elseif ($type === 'boardname') {
//     $sql = "SELECT board_name FROM `online_board_name` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   } elseif ($type === 'qualification') {
//     $sql = "SELECT qualification FROM `online_qualification` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   } elseif ($type === 'religion') {
//     $sql = "SELECT religion_name FROM `religion_master` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   } elseif ($type === 'service') {
//     $sql = "SELECT service_name FROM `service_master` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   } elseif ($type === 'sports') {
//     $sql = "SELECT sports_name FROM `sports_master` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   // } elseif ($type === 'clothsize') {
//   //   $sql = "SELECT cloth_size FROM `student_clothsize` WHERE pk = $id";
//   //   return $DB->get_field_sql($sql);
//   } elseif (stripos(trim($type), 'size') !== false) {
//     $sql = "SELECT cloth_size FROM `student_clothsize` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   } elseif ($type === 'fcscale') {
//     $sql = "SELECT scale_detail FROM `student_fc_scale` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   } elseif ($type === 'distinction') {
//     $sql = "SELECT academic_distinction FROM `student_master_academic_distinction` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   } elseif ($type === 'fatherprofession') {
//     $sql = "SELECT father_profession FROM `student_master_father_profession` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   }  elseif ($type === 'pantsize') {
//     $sql = "SELECT pant_size FROM `student_pantsize` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   }  elseif ($type === 'shoessize') {
//     $sql = "SELECT ssize FROM `student_shoessize` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   } elseif ($type === 'studentskill') {
//     $sql = "SELECT skill_name FROM `student_skill_details` WHERE pk = $id";
//     return $DB->get_field_sql($sql);
//   }  else {
//     return null;  // Return null if the type is not recognized
//   }
// }

function get_name_by_id($id, $type)
{
  global $DB;

  // Check if id and type are not empty
  if (empty($id) || empty($type)) {
    return null; // Handle empty id or type
  }

  // Define a mapping of types to their corresponding table and column names
  $mapping = [
    'country' => ['table' => 'country_master', 'column' => 'country_name'],
    'state' => ['table' => 'state_master', 'column' => 'state_name'],
    'district' => ['table' => 'state_district_mapping', 'column' => 'district_name'],
    'language' => ['table' => 'language_master', 'column' => 'language_name'],
    'admissioncategory' => ['table' => 'admission_category', 'column' => 'seat_name'],
    'stream' => ['table' => 'highest_stream', 'column' => 'highest_stream'],
    'institution' => ['table' => 'institution_type', 'column' => 'institution_type'],
    'jobtype' => ['table' => 'job_type', 'column' => 'job_name'],
    'boardname' => ['table' => 'online_board_name', 'column' => 'board_name'],
    'qualification' => ['table' => 'online_qualification', 'column' => 'qualification'],
    'religion' => ['table' => 'religion_master', 'column' => 'religion_name'],
    'service' => ['table' => 'service_master', 'column' => 'service_name'],
    'sports' => ['table' => 'sports_master', 'column' => 'sports_name'],
    'size' => ['table' => 'student_clothsize', 'column' => 'cloth_size'],
    'fcscale' => ['table' => 'student_fc_scale', 'column' => 'scale_detail'],
    'distinction' => ['table' => 'student_master_academic_distinction', 'column' => 'academic_distinction'],
    'fatherprofession' => ['table' => 'student_master_father_profession', 'column' => 'father_profession'],
    'trouser' => ['table' => 'student_pantsize', 'column' => 'pant_size'],
    'shoessize' => ['table' => 'student_shoessize', 'column' => 'ssize'],
    'studentskill' => ['table' => 'student_skill_details', 'column' => 'skill_name'],
  ];

  // Determine the appropriate table and column based on the type
  $table = null;
  $column = null;

  foreach ($mapping as $key => $value) {
    if (stripos($type, $key) !== false) {
      $table = $value['table'];
      $column = $value['column'];
      break;
    }
  }

  // If no valid mapping is found, return null
  if (!$table || !$column) {
    return null;
  }

  // Construct and execute the SQL query
  $sql = "SELECT $column FROM `$table` WHERE pk = ?";
  return $DB->get_field_sql($sql, [$id]); // Use parameterized query to prevent SQL injection
}
/**
 * Generate a signed URL token for form access
 * @param int $formid The form ID
 * @return string Signed URL
 */
// function local_form_generate_signed_url($formid) {
//     global $CFG, $USER;

//     // Create a token with expiration (1 hour)
//     $data = [
//         'formid' => (int)$formid,
//         'timestamp' => time(),
//         'expires' => time() + 3600, // 1 hour expiration
//         'userid' => isloggedin() ? $USER->id : 0
//     ];

//     // Create signature
//     $payload = json_encode($data);
//     $signature = hash_hmac('sha256', $payload, $CFG->siteidentifier);

//     // Encode for URL
//     $encoded = base64_encode($payload);
//     return $CFG->wwwroot . '/local/form/addform.php?token=' . urlencode($encoded . '.' . $signature);
// }

// /**
//  * Validate the token and return form ID if valid
//  * @param string $token The token from URL
//  * @return mixed Form ID or false if invalid
//  */
// function local_form_validate_token($token) {
//     global $CFG, $USER;

//     if (empty($token)) {
//         return false;
//     }

//     $parts = explode('.', $token);
//     if (count($parts) !== 2) {
//         return false;
//     }

//     list($encoded, $signature) = $parts;
//     $payload = base64_decode($encoded);

//     if ($payload === false) {
//         return false;
//     }

//     // Verify signature
//     $expected_signature = hash_hmac('sha256', $payload, $CFG->siteidentifier);
//     if (!hash_equals($expected_signature, $signature)) {
//         return false;
//     }

//     $data = json_decode($payload, true);

//     if (!isset($data['formid'], $data['expires'])) {
//         return false;
//     }

//     // Check expiration
//     if (time() > $data['expires']) {
//         return false;
//     }

//     // Check user if logged in
//     if (isloggedin() && $data['userid'] != $USER->id) {
//         return false;
//     }

//     return (int)$data['formid'];
// }

function local_form_generate_signed_url($formid, $page = 'addform', $additional_data = [])
{
  global $CFG, $USER;

  // Create a token with expiration (1 hour by default)
  $data = [
    'formid' => (int)$formid,
    'page' => $page, // Page identifier
    'timestamp' => time(),
    'expires' => time() + 3600, // 1 hour expiration
    'userid' => isloggedin() ? $USER->id : 0,
    'data' => $additional_data // Additional page-specific data
  ];

  // Create signature
  $payload = json_encode($data);
  $signature = hash_hmac('sha256', $payload, $CFG->siteidentifier);

  // Encode for URL
  $encoded = base64_encode($payload);

  // Determine the target page
  $pages = [
    'addform' => '/local/form/addform.php',
    'register' => '/local/form/register.php',
    'view' => '/local/form/view.php',
    'edit' => '/local/form/edit.php',
    'list' => '/local/form/list.php',
    'report' => '/local/form/report.php',
    'courselist' => '/local/form/courselist.php',
    'displayform' => '/local/form/displayform.php',
    'nonregistered' => '/local/form/nonregistered.php',
    'report_courselist' => '/local/form/report_courselist.php'

  ];

  $page_url = $pages[$page] ?? '/local/form/addform.php';

  return $CFG->wwwroot . $page_url . '?token=' . urlencode($encoded . '.' . $signature);
}

/**
 * Validate the token and return data if valid
 * @param string $token The token from URL
 * @param string $expected_page (optional) Expected page identifier
 * @return array|false Token data or false if invalid
 */
// function local_form_validate_token($token, $expected_page = null) {
//     global $CFG, $USER;
    
//     if (empty($token)) {
//         return false;
//     }
    
//     $parts = explode('.', $token);
//     if (count($parts) !== 2) {
//         return false;
//     }
    
//     list($encoded, $signature) = $parts;
//     $payload = base64_decode($encoded);
    
//     if ($payload === false) {
//         return false;
//     }
    
//     // Verify signature
//     $expected_signature = hash_hmac('sha256', $payload, $CFG->siteidentifier);
//     if (!hash_equals($expected_signature, $signature)) {
//         return false;
//     }
    
//     $data = json_decode($payload, true);
    
//     if (!isset($data['formid'], $data['expires'], $data['page'])) {
//         return false;
//     }
    
//     // Check expiration
//     if (time() > $data['expires']) {
//         return false;
//     }
    
//     // Check expected page if specified
//       // Check expected page if specified
//     if ($expected_page !== null) {
//         // Define allowed page relationships
//         $allowed_relationships = [
//             'courselist' => ['courselist', 'nonregistered', 'displayform'], // courselist token can access these
//             'nonregistered' => ['nonregistered', 'courselist'], // nonregistered token can access these
//             'displayform' => ['displayform', 'courselist'], // displayform token can access these
//                         'addform' => ['addform', 'courselist', 'nonregistered'], // Add this line

//         ];
        
//         // If the token's page is in the allowed relationships, check if expected page is allowed
//         if (isset($allowed_relationships[$data['page']])) {
//             if (!in_array($expected_page, $allowed_relationships[$data['page']])) {
//                 return false;
//             }
//         } else {
//             // No special relationships, require exact match
//             if ($data['page'] !== $expected_page) {
//                 return false;
//             }
//         }
//     }  // Check expected page if specified
//     if ($expected_page !== null) {
//         // Define allowed page relationships
//         $allowed_relationships = [
//             'courselist' => ['courselist', 'nonregistered', 'displayform'], // courselist token can access these
//             'nonregistered' => ['nonregistered', 'courselist'], // nonregistered token can access these
//             'displayform' => ['displayform', 'courselist'], // displayform token can access these
//                         'addform' => ['addform', 'courselist', 'nonregistered'], // Add this line

//         ];
        
//         // If the token's page is in the allowed relationships, check if expected page is allowed
//         if (isset($allowed_relationships[$data['page']])) {
//             if (!in_array($expected_page, $allowed_relationships[$data['page']])) {
//                 return false;
//             }
//         } else {
//             // No special relationships, require exact match
//             if ($data['page'] !== $expected_page) {
//                 return false;
//             }
//         }
//     }
    
//     // Check user if logged in
//     if (isloggedin() && $data['userid'] != $USER->id) {
//         return false;
//     }
    
//     return $data;
// }
/**
 * Validate the token and return data if valid
 * @param string $token The token from URL
 * @param string $expected_page (optional) Expected page identifier
 * @return array|false Token data or false if invalid
 */
function local_form_validate_token($token, $expected_page = null)
{
  global $CFG, $USER;

  if (empty($token)) {
    error_log('local_form_validate_token: Empty token');
    return false;
  }

  $parts = explode('.', $token);
  if (count($parts) !== 2) {
    error_log('local_form_validate_token: Invalid token format, parts: ' . count($parts));
    return false;
  }

  list($encoded, $signature) = $parts;
  $payload = base64_decode($encoded);

  if ($payload === false) {
    error_log('local_form_validate_token: Failed to decode base64');
    return false;
  }

  // Verify signature
  $expected_signature = hash_hmac('sha256', $payload, $CFG->siteidentifier);
  if (!hash_equals($expected_signature, $signature)) {
    error_log('local_form_validate_token: Signature mismatch');
    error_log('Expected: ' . $expected_signature);
    error_log('Received: ' . $signature);
    return false;
  }

  $data = json_decode($payload, true);

  if (!$data) {
    error_log('local_form_validate_token: Failed to decode JSON');
    return false;
  }

  error_log('local_form_validate_token: Token data: ' . print_r($data, true));

  if (!isset($data['formid'], $data['expires'], $data['page'])) {
    error_log('local_form_validate_token: Missing required fields');
    return false;
  }

  // Check expiration
  if (time() > $data['expires']) {
    error_log('local_form_validate_token: Token expired. Time: ' . time() . ', Expires: ' . $data['expires']);
    return false;
  }

  // Check expected page if specified
  if ($expected_page !== null) {
    error_log('local_form_validate_token: Checking page. Expected: ' . $expected_page . ', Actual: ' . $data['page']);

    // Define allowed page relationships
    $allowed_relationships = [
      'courselist' => ['courselist', 'nonregistered', 'displayform', 'addform'],
      'nonregistered' => ['nonregistered', 'courselist', 'addform', 'displayform'],
      'displayform' => ['displayform', 'courselist', 'addform', 'nonregistered'],
      'addform' => ['addform', 'courselist', 'nonregistered', 'displayform'],
      'register' => ['register', 'addform'], // Add this line
      'form_reminder' => ['form_reminder', 'addform'], // If you're using form_reminder
    ];

    // If the token's page is in the allowed relationships, check if expected page is allowed
    if (isset($allowed_relationships[$data['page']])) {
      if (!in_array($expected_page, $allowed_relationships[$data['page']])) {
        error_log('local_form_validate_token: Page not in allowed relationships for ' . $data['page']);
        return false;
      }
    } else {
      // No special relationships, require exact match
      if ($data['page'] !== $expected_page) {
        error_log('local_form_validate_token: Page mismatch');
        return false;
      }
    }
  }

  // Check user if logged in (optional - comment out if not needed)
  // if (isloggedin() && $data['userid'] != $USER->id && $data['userid'] != 0) {
  //     error_log('local_form_validate_token: User ID mismatch');
  //     return false;
  // }

  error_log('local_form_validate_token: Token valid');
  return $data;
}
/**
 * Quick function to get form ID from token
 */
function local_form_get_formid_from_token($token)
{
  $data = local_form_validate_token($token);
  return $data ? (int)$data['formid'] : false;
}

// Add this helper function at the top or in lib.php
function send_moodle_notification($fromuser, $touser, $subject, $message, $formid)
{
  global $DB, $CFG;

  // First, try the standard message API
  try {
    $msg = new \core\message\message();
    $msg->component = 'moodle';
    $msg->name = 'instantmessage';
    $msg->userfrom = $fromuser;
    $msg->userto = $touser;
    $msg->subject = $subject;
    $msg->fullmessage = $message;
    $msg->fullmessageformat = FORMAT_PLAIN;
    $msg->fullmessagehtml = '<p>' . nl2br($message) . '</p>';
    $msg->smallmessage = $subject;
    $msg->notification = 1;
    $msg->contexturl = $CFG->wwwroot . '/local/form/nonregistered.php?formid=' . $formid;
    $msg->contexturlname = get_string('view_form', 'local_form');

    $result = message_send($msg);

    // Even if message_send returns false, check if message was created
    if ($result === false) {
      // Check if message exists in database
      $existing = $DB->get_records_sql(
        "SELECT id FROM {messages} 
                 WHERE useridto = ? AND useridfrom = ? 
                 AND subject = ? AND timecreated > ? 
                 ORDER BY timecreated DESC LIMIT 1",
        [$touser->id, $fromuser->id, $subject, time() - 30]
      );

      if (!empty($existing)) {
        $result = reset($existing)->id; // Return the message ID
      }
    }

    return $result !== false;
  } catch (Exception $e) {
    error_log("Notification error: " . $e->getMessage());
    return false;
  }
}


/**
 * Check if user has teacher or admin access
 * @param int $userid User ID (defaults to current user)
 * @return bool True if user is teacher or admin
 */
function local_form_is_teacher_or_admin($userid = null)
{
  global $USER, $DB;

  if ($userid === null) {
    $userid = $USER->id;
  }

  // Site admin always allowed
  if (is_siteadmin($userid)) {
    return true;
  }

  // Check for teacher roles in system context
  $context = context_system::instance();
  $teacher_roles = array('editingteacher', 'teacher', 'manager');

  foreach ($teacher_roles as $role_shortname) {
    $role = $DB->get_record('role', array('shortname' => $role_shortname));
    if ($role && user_has_role_assignment($userid, $role->id, $context->id)) {
      return true;
    }
  }

  // Check for teacher roles in any course
  $sql = "SELECT COUNT(*) 
            FROM {role_assignments} ra
            JOIN {context} ctx ON ra.contextid = ctx.id
            JOIN {role} r ON ra.roleid = r.id
            WHERE ra.userid = :userid 
            AND ctx.contextlevel = :courselevel
            AND r.shortname IN ('editingteacher', 'teacher')";

  $params = array(
    'userid' => $userid,
    'courselevel' => CONTEXT_COURSE
  );

  return $DB->count_records_sql($sql, $params) > 0;
}

//LDap function to check if user exists in Active Directory
function local_form_user_exists_in_ad($username)
{
  global $CFG;


  $ldap_host = "103.225.204.25";
  $ldap_port = 389;

  // Update these with your actual values from Moodle LDAP settings
  $ldap_dn = "dc=lbsnaa,dc=gov,dc=in";  // Base DN
  $ldap_user = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";  // Bind DN
  $ldap_pass = "lbsnaa123";  // IMPORTANT: Add the actual password here

  $conn = ldap_connect($ldap_host, $ldap_port);

  if (!$conn) {
    return false;
  }

  ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);

  if (!ldap_bind($conn, $ldap_user, $ldap_pass)) {
    return false;
  }

  $filter = "(sAMAccountName={$username})";

  $result = ldap_search($conn, $ldap_dn, $filter);

  if (!$result) {
    return false;
  }

  $entries = ldap_get_entries($conn, $result);

  ldap_close($conn);

  return ($entries["count"] > 0);
}

<?php
// Add these functions to your lib.php file

/**
 * Create user in Active Directory (PHP version based on Java implementation)
 * 
 * @param string $username
 * @param string $firstname
 * @param string $lastname
 * @param string $password
 * @param string $email
 * @param string $phone
 * @return bool
 */
function local_form_create_ad_user($username, $firstname, $lastname, $password, $email, $phone) {
    global $CFG;
    
    // LDAP Configuration (from your Java code)
    $ldap_server = "103.225.204.25";
    $ldap_port = 389;
    $bind_dn = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";
    $bind_password = "lbsnaa123";
    $ldap_version = 3;
    
    // Container for new users (from your Java: ou=FC97,ou=LBSNAA,dc=lbsnaa,dc=gov,dc=in)
    // Using the Users container as fallback
    $container = "cn=Users,dc=lbsnaa,dc=gov,dc=in";
    
    // If you have specific OU, you can use:
    // $container = "ou=FC97,ou=LBSNAA,dc=lbsnaa,dc=gov,dc=in";
    
    if (empty($phone)) {
        error_log("Phone number is required to create AD user");
        return false;
    }
    
    // Connect to LDAP server
    $ldapconn = ldap_connect($ldap_server, $ldap_port);
    if (!$ldapconn) {
        error_log("Failed to connect to LDAP server: {$ldap_server}");
        return false;
    }
    
    // Set LDAP options
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, $ldap_version);
    ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
    ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 20);
    
    // Bind to LDAP
    $ldapbind = @ldap_bind($ldapconn, $bind_dn, $bind_password);
    if (!$ldapbind) {
        error_log("LDAP bind failed: " . ldap_error($ldapconn));
        ldap_close($ldapconn);
        return false;
    }
    
    error_log("Connected and bound to LDAP successfully");
    
    // Prepare DN (Distinguished Name) - matches Java: cn=givenname,containerName
    $givenname = $firstname . " " . $lastname;
    $dn = "cn={$givenname},{$container}";
    
    // Encode password for AD (UTF-16LE format with quotes)
    $encoded_password = ldap_encode_password($password);
    
    // Prepare attributes (matching Java implementation)
    $userdata = [
        'objectClass' => ['top', 'person', 'organizationalPerson', 'user', 'inetOrgPerson'],
        'cn' => $givenname,
        'givenName' => $firstname,
        'sn' => $lastname,
        'telephoneNumber' => $phone,
        'mail' => $email,
        'sAMAccountName' => $username,
        'userPrincipalName' => "{$username}@lbsnaa.gov.in",
        'userAccountControl' => '544', // 512 (normal) + 32 (password not required initially)
        'pwdLastSet' => '-1', // Force password change at next logon
        'unicodePwd' => $encoded_password,
        'displayName' => "{$firstname} {$lastname}",
        'mobile' => $phone,
        'instanceType' => '4',
        'accountExpires' => '0'
    ];
    
    // Try to create the user
    $result = @ldap_add($ldapconn, $dn, $userdata);
    
    if (!$result) {
        $error = ldap_error($ldapconn);
        $errno = ldap_errno($ldapconn);
        
        // Detailed error logging (like Java exception handling)
        switch($errno) {
            case 50:
                error_log("LDAP Error: Insufficient access - Bind user lacks write permissions");
                break;
            case 19:
                error_log("LDAP Error: Constraint violation - Password doesn't meet complexity requirements");
                break;
            case 68:
                error_log("LDAP Error: User already exists - DN: {$dn}");
                break;
            case 32:
                error_log("LDAP Error: No such object - Check container path: {$container}");
                break;
            default:
                error_log("LDAP Error {$errno}: {$error} - DN: {$dn}");
        }
        
        ldap_close($ldapconn);
        return false;
    }
    
    error_log("AD user created successfully: {$username} (DN: {$dn})");
    
    // After successful creation, enable the account (like Java's userAccountControl 553)
    $modify = [
        'userAccountControl' => '512', // Normal account (enable after password set)
        'pwdLastSet' => '-1'
    ];
    
    ldap_modify($ldapconn, $dn, $modify);
    
    ldap_close($ldapconn);
    
    return true;
}

/**
 * Check if user exists in Active Directory
 * (Updated to match Java search pattern)
 */
function local_form_user_exists_in_ad($username) {
    $ldap_server = "103.225.204.25";
    $ldap_port = 389;
    $bind_dn = "cn=lbs,cn=Users,dc=lbsnaa,dc=gov,dc=in";
    $bind_password = "lbsnaa123";
    $ldap_version = 3;
    
    // Search containers (matching Java's container structure)
    $containers = [
        "cn=Users,dc=lbsnaa,dc=gov,dc=in",
        "ou=FC97,ou=LBSNAA,dc=lbsnaa,dc=gov,dc=in",
        "ou=LBSNAA,dc=lbsnaa,dc=gov,dc=in"
    ];
    
    $ldapconn = ldap_connect($ldap_server, $ldap_port);
    if (!$ldapconn) {
        return false;
    }
    
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, $ldap_version);
    ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
    ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 20);
    
    $ldapbind = @ldap_bind($ldapconn, $bind_dn, $bind_password);
    if (!$ldapbind) {
        ldap_close($ldapconn);
        return false;
    }
    
    // Search for user by sAMAccountName (like Java)
    $search_filter = "(&(objectClass=user)(sAMAccountName={$username}))";
    
    foreach ($containers as $container) {
        $search_result = @ldap_search($ldapconn, $container, $search_filter, ['sAMAccountName', 'cn']);
        
        if ($search_result) {
            $entries = ldap_get_entries($ldapconn, $search_result);
            if ($entries['count'] > 0) {
                ldap_close($ldapconn);
                error_log("User {$username} found in container: {$container}");
                return true;
            }
        }
    }
    
    ldap_close($ldapconn);
    return false;
}

/**
 * Encode password for Active Directory (UTF-16LE format with quotes)
 * Matches Java implementation: newpass = "\""+user_password+"\""
 */
function ldap_encode_password($password) {
    // Add quotes around password (as in Java: "\""+user_password+"\"")
    $password = "\"" . $password . "\"";
    $encoded = "";
    
    // Convert to UTF-16LE (matching Java's getBytes("UTF-16LE"))
    for ($i = 0; $i < strlen($password); $i++) {
        $encoded .= "{$password[$i]}\000";
    }
    
    return $encoded;
}
