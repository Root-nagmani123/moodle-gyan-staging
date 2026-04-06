<?php
require_once('../../config.php');
require_once('lib.php');

header('Content-Type: application/json');

// Enable debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$debug = [];

// Check login
if (!isloggedin()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Check permission
if (!function_exists('local_form_is_teacher_or_admin') || !local_form_is_teacher_or_admin()) {
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}


$sargam_config = [
    'host'     => get_config('local_form', 'migration_db_host'),
    'port'     => get_config('local_form', 'migration_db_port'),
    'database' => get_config('local_form', 'migration_db_name'),
    'username' => get_config('local_form', 'migration_db_user'),
    'password' => get_config('local_form', 'migration_db_pass'),
];

try {

    $debug[] = "Initializing MySQL connection...";

    $conn = mysqli_init();

    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 10);

    // Azure requires SSL
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

    $debug[] = "Attempting connection to Azure DB...";

    $connected = $conn->real_connect(
        $sargam_config['host'],
        $sargam_config['username'],
        $sargam_config['password'],
        $sargam_config['database'],
        $sargam_config['port']
    );

    if (!$connected) {
        throw new Exception(mysqli_connect_error());
    }

    $debug[] = "Connection successful";

    // Show server information
    $debug[] = "Host info: " . $conn->host_info;
    $debug[] = "Server version: " . $conn->server_info;

    // Confirm database
    $result = $conn->query("SELECT DATABASE() as db");
    $row = $result->fetch_assoc();
    $debug[] = "Connected Database: " . $row['db'];

    // Confirm hostname
    $result = $conn->query("SELECT @@hostname as hostname");
    $row = $result->fetch_assoc();
    $debug[] = "MySQL Server Hostname: " . $row['hostname'];

    // Check tables
    $tables = [
        'user_credentials',
        'student_master',
        'student_master_course__map'
    ];

    $existing_tables = [];

    foreach ($tables as $table) {

        $debug[] = "Checking table: " . $table;

        $result = $conn->query("SHOW TABLES LIKE '$table'");

        if (!$result) {
            $debug[] = "Query failed for $table: " . $conn->error;
        }

        if ($result && $result->num_rows > 0) {
            $existing_tables[] = $table;
            $debug[] = "Table exists: $table";
        } else {
            $debug[] = "Table NOT found: $table";
        }
    }

    // Test write permission
    $debug[] = "Testing INSERT permission...";

    $testquery = "INSERT INTO user_credentials (user_name, first_name, reg_date)
                  VALUES ('debug_test', 'debug_test', NOW())";

    if ($conn->query($testquery)) {
        $debug[] = "INSERT test successful";
    } else {
        $debug[] = "INSERT failed: " . $conn->error;
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'database' => $sargam_config['database'],
        'tables_found' => $existing_tables,
        'debug_log' => $debug
    ]);
} catch (Exception $e) {

    $debug[] = "Exception: " . $e->getMessage();

    echo json_encode([
        'success' => false,
        'error' => 'Connection failed',
        'debug_log' => $debug
    ]);
}
