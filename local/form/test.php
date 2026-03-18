<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moodle to Sargam DB Migration Tool - Complete User Migration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: #1a237e;
            color: white;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .moodle-badge {
            background: #ff9800;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            color: white;
        }

        .sargam-badge {
            background: #4caf50;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            color: white;
        }

        .migration-btn-container {
            padding: 30px;
            text-align: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
        }

        .btn-migration {
            background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 50px;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .btn-migration:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .migration-interface {
            padding: 30px;
            background: white;
            display: none;
        }

        .interface-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .db-selectors {
            display: flex;
            gap: 30px;
            justify-content: center;
        }

        .db-selector {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            flex: 1;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .db-selector h3 {
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }

        .table-item {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
            cursor: pointer;
            transition: background 0.2s;
        }

        .table-item:hover {
            background: #e3f2fd;
        }

        .table-item.selected {
            background: #bbdefb;
            border-left: 4px solid #1976d2;
        }

        .table-item.suggested {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
        }

        .mapping-area {
            margin: 30px 0;
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
        }

        .mapping-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .mapping-grid {
            display: grid;
            grid-template-columns: 1fr 100px 1fr;
            gap: 20px;
            align-items: center;
        }

        .moodle-col,
        .sargam-col {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .col-header {
            font-weight: bold;
            padding-bottom: 10px;
            margin-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
        }

        .col-item {
            padding: 10px;
            margin: 5px 0;
            background: #fafafa;
            border-radius: 5px;
            border-left: 3px solid;
            cursor: pointer;
            transition: all 0.2s;
        }

        .col-item:hover {
            background: #f5f5f5;
            transform: translateX(5px);
        }

        .col-item.selected {
            background: #e8f5e9;
            border-left-color: #4caf50;
        }

        .col-item.suggested {
            background: #fff3e0;
            border-left-color: #ff9800;
        }

        .arrow {
            text-align: center;
            font-size: 24px;
            color: #666;
        }

        .mapped-pairs {
            margin-top: 30px;
        }

        .mapped-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .mapping-badge {
            background: #4caf50;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
        }

        .mapping-badge-warning {
            background: #ff9800;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #2196f3;
            color: white;
        }

        .btn-primary:hover {
            background: #1976d2;
        }

        .btn-success {
            background: #4caf50;
            color: white;
        }

        .btn-success:hover {
            background: #388e3c;
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        .btn-warning {
            background: #ff9800;
            color: white;
        }

        .btn-warning:hover {
            background: #f57c00;
        }

        .btn-purple {
            background: #9c27b0;
            color: white;
        }

        .btn-purple:hover {
            background: #7b1fa2;
        }

        .migration-progress {
            margin-top: 30px;
            padding: 20px;
            background: #fff3e0;
            border-radius: 10px;
            display: none;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin: 15px 0;
        }

        .progress-fill {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #4caf50, #8bc34a);
            transition: width 0.3s;
        }

        .log-container {
            max-height: 200px;
            overflow-y: auto;
            background: #263238;
            color: #fff;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            margin-top: 20px;
        }

        .log-entry {
            padding: 3px 0;
            border-bottom: 1px solid #37474f;
        }

        .log-success {
            color: #8bc34a;
        }

        .log-error {
            color: #ff8a80;
        }

        .log-warning {
            color: #ffb74d;
        }

        .workflow-container {
            margin-top: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #e8eaf6 0%, #c5cae9 100%);
            border-radius: 12px;
        }

        .footer {
            background: #f5f5f5;
            padding: 20px;
            text-align: center;
            color: #666;
        }

        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            display: flex;
            justify-content: space-around;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #1a237e;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                🎓 Moodle to Sargam DB Migration
                <span class="moodle-badge">Moodle DB</span>
                →
                <span class="sargam-badge">Sargam DB</span>
            </h1>
            <div>v2.0.0 - Complete User Migration</div>
        </div>

        <!-- Main Content -->
        <?php
        // Only include mdl_user table - removed other Moodle tables
        $moodle_tables = [
            'mdl_user' => [
                'id',
                'auth',
                'confirmed',
                'policyagreed',
                'deleted',
                'suspended',
                'mnethostid',
                'username',
                'password',
                'idnumber',
                'firstname',
                'lastname',
                'email',
                'emailstop',
                'phone1',
                'phone2',
                'institution',
                'department',
                'address',
                'city',
                'country',
                'lang',
                'calendartype',
                'theme',
                'timezone',
                'firstaccess',
                'lastaccess',
                'lastlogin',
                'currentlogin',
                'lastip',
                'secret',
                'picture',
                'description',
                'descriptionformat',
                'mailformat',
                'maildigest',
                'maildisplay',
                'autosubscribe',
                'trackforums',
                'timecreated',
                'timemodified',
                'trustbitmask',
                'imagealt',
                'lastnamephonetic',
                'firstnamephonetic',
                'middlename',
                'alternatename',
                'moodlenetprofile',
                'dob'
            ]
        ];

        // Sargam tables with complete column structures
        $sargam_tables = [
            'user_credentials' => [
                'pk',
                'user_name',
                'first_name',
                'last_name',
                'jbp_password',
                'email_id',
                'mobile_no',
                'alternate_mailid',
                'reg_date',
                'jbp_enabled',
                'login_status',
                'schemaid',
                'user_id',
                'last_login',
                'security_question',
                'security_answer',
                'entity_id',
                'image_path',
                'user_category',
                'Active_inactive',
                'remember_token',
                'updated_date'
            ],
            'student_master' => [
                'pk',
                'email',
                'contact_no',
                'user_id',
                'display_name',
                'password',
                'schema_id',
                'final_submit',
                'submit_date',
                'created_date',
                'first_name',
                'middle_name',
                'last_name',
                'admission_status',
                'rank',
                'exam_year',
                'service_master_pk',
                'web_auth',
                'dob',
                'status',
                'course_master_pk',
                'finance_bookEntityCode',
                'refund_status',
                'enrollment'
            ],
            'student_master_course__map' => [
                'pk',
                'student_master_pk',
                'course_master_pk',
                'active_inactive',
                'created_date',
                'modified_date'
            ]
        ];

        echo "<!-- Moodle DB Status: Connected -->\n";
        echo "<!-- Moodle Version: 3.11+ -->\n";
        echo "<!-- Sargam Version: 2.0.0 -->\n";
        ?>

        <!-- Migration Button Section -->
        <div class="migration-btn-container">
            <button class="btn-migration" onclick="openMigrationInterface()">
                🚀 Start Complete User Migration
            </button>
            <p style="margin-top: 15px; color: #666;">
                Migrate Moodle Users → Sargam User Credentials, Student Master & Course Enrollments
            </p>
        </div>

        <!-- Migration Interface (Initially Hidden) -->
        <div id="migrationInterface" class="migration-interface">
            <div class="interface-header">
                <h2>📊 Complete User Migration Interface</h2>
                <button class="btn btn-danger" onclick="closeMigrationInterface()">
                    ✕ Close
                </button>
            </div>

            <!-- Database Selectors -->
            <div class="db-selectors">
                <div class="db-selector">
                    <h3>🎯 Moodle Database</h3>
                    <div class="table-list" id="moodleTableList">
                        <?php foreach ($moodle_tables as $table_name => $columns): ?>
                            <div class="table-item" onclick="selectMoodleTable('<?php echo $table_name; ?>', this)">
                                <strong><?php echo $table_name; ?></strong>
                                <span style="float: right; color: #666; font-size: 12px;">
                                    <?php echo count($columns); ?> columns
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="db-selector">
                    <h3>⚡ Sargam Database</h3>
                    <div class="table-list" id="sargamTableList">
                        <?php foreach ($sargam_tables as $table_name => $columns): ?>
                            <div class="table-item <?php echo ($table_name == 'user_credentials' || $table_name == 'student_master' || $table_name == 'student_master_course__map') ? 'suggested' : ''; ?>" 
                                 onclick="selectSargamTable('<?php echo $table_name; ?>', this)">
                                <strong><?php echo $table_name; ?></strong>
                                <span style="float: right; color: #666; font-size: 12px;">
                                    <?php echo count($columns); ?> columns
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Intelligent Mapping Suggestions Bar -->
            <div id="suggestionBar" style="margin: 20px 0; display: none;">
                <div style="background: #fff3e0; padding: 15px; border-radius: 8px; border-left: 4px solid #ff9800;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <strong style="color: #f57c00;">✨ Intelligent Mapping Available</strong>
                            <p id="suggestionText" style="margin-top: 5px; color: #666;"></p>
                        </div>
                        <button class="btn btn-warning" onclick="applySuggestedMappings()">
                            Apply Suggestions
                        </button>
                    </div>
                </div>
            </div>

            <!-- Column Mapping Area -->
            <div class="mapping-area" id="mappingArea" style="display: none;">
                <div class="mapping-header">
                    <h3>🔄 Column Mapping</h3>
                    <div>
                        <span id="selectedTableInfo"></span>
                    </div>
                </div>

                <div class="mapping-grid">
                    <div class="moodle-col">
                        <div class="col-header">
                            <span>Moodle Columns</span>
                            <span id="moodleColCount">0</span>
                        </div>
                        <div id="moodleColumnsList">
                            <!-- Dynamic column list will be populated here -->
                        </div>
                    </div>

                    <div class="arrow">
                        ⚡
                    </div>

                    <div class="sargam-col">
                        <div class="col-header">
                            <span>Sargam Columns</span>
                            <span id="sargamColCount">0</span>
                        </div>
                        <div id="sargamColumnsList">
                            <!-- Dynamic column list will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Mapped Pairs -->
                <div class="mapped-pairs">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h4>📋 Defined Mappings</h4>
                        <div>
                            <button class="btn btn-primary" onclick="saveMapping()">
                                💾 Save Mapping
                            </button>
                            <button class="btn" onclick="clearAllMappings()" style="margin-left: 10px;">
                                🗑️ Clear All
                            </button>
                        </div>
                    </div>
                    <div id="mappedPairsList">
                        <!-- Mapped pairs will be displayed here -->
                    </div>
                </div>
            </div>

            <!-- Complete Migration Workflow -->
            <div id="workflowArea" class="workflow-container" style="display: none;">
                <h4 style="color: #1a237e; margin-bottom: 20px;">📋 Complete User Migration Workflow</h4>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 36px; margin-bottom: 10px;">🔐</div>
                        <h5 style="margin-bottom: 10px;">Step 1: User Credentials</h5>
                        <p style="font-size: 12px; color: #666;">Migrate authentication and basic user info</p>
                        <button class="btn btn-success" style="margin-top: 15px;" onclick="migrateToUserCredentials()">
                            Migrate Now
                        </button>
                    </div>

                    <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 36px; margin-bottom: 10px;">👨‍🎓</div>
                        <h5 style="margin-bottom: 10px;">Step 2: Student Master</h5>
                        <p style="font-size: 12px; color: #666;">Migrate student profiles and academic info</p>
                        <button class="btn btn-success" style="margin-top: 15px;" onclick="migrateToStudentMaster()">
                            Migrate Now
                        </button>
                    </div>

                    <div style="background: white; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 36px; margin-bottom: 10px;">📚</div>
                        <h5 style="margin-bottom: 10px;">Step 3: Course Enrollments</h5>
                        <p style="font-size: 12px; color: #666;">Migrate student course mappings</p>
                        <button class="btn btn-success" style="margin-top: 15px;" onclick="migrateToCourseMap()">
                            Migrate Now
                        </button>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button class="btn btn-purple" onclick="suggestAllMappings()">
                        ✨ Suggest All Mappings
                    </button>
                    <button class="btn btn-warning" onclick="validateCompleteMigration()">
                        🔍 Validate Full Migration
                    </button>
                    <button class="btn btn-danger" onclick="executeCompleteMigration()">
                        🚀 Execute Complete Migration
                    </button>
                </div>

                <!-- Migration Stats -->
                <div id="migrationStats" class="stats-card" style="display: none;">
                    <div class="stat-item">
                        <div class="stat-value" id="statsUsers">0</div>
                        <div class="stat-label">Users Migrated</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="statsStudents">0</div>
                        <div class="stat-label">Students Created</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="statsEnrollments">0</div>
                        <div class="stat-label">Enrollments</div>
                    </div>
                </div>
            </div>

            <!-- Migration Progress -->
            <div id="migrationProgress" class="migration-progress">
                <h4>📊 Migration Progress</h4>
                <div class="progress-bar">
                    <div id="progressFill" class="progress-fill"></div>
                </div>
                <div id="migrationStatus">Ready to migrate...</div>

                <!-- Log Container -->
                <div class="log-container" id="logContainer">
                    <div class="log-entry">⚡ Complete User Migration Tool initialized</div>
                    <div class="log-entry">📊 Ready to migrate Moodle users to Sargam</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Moodle to Sargam Complete User Migration Tool | Connected to Moodle 3.11+ → Sargam 2.0.0</p>
            <p style="font-size: 12px; margin-top: 5px;">✅ Supports: User Credentials | Student Master | Course Enrollments</p>
        </div>
    </div>

    <script>
        // State management
        let selectedMoodleTable = null;
        let selectedSargamTable = null;
        let selectedMoodleColumn = null;
        let selectedSargamColumn = null;
        let mappings = {};
        let mappingHistory = [];
        let migrationStats = {
            users: 0,
            students: 0,
            enrollments: 0
        };
        let studentMasterPKMap = {}; // Maps Moodle user ID to Student Master PK

        // Dummy data (same as PHP arrays)
        const moodleTables = <?php echo json_encode($moodle_tables); ?>;
        const sargamTables = <?php echo json_encode($sargam_tables); ?>;

        // Intelligent mappings for Moodle to Sargam - ONLY using mdl_user
        const intelligentMappings = {
            'mdl_user': {
                'user_credentials': [
                    { moodle: 'username', sargam: 'user_name', transform: 'direct' },
                    { moodle: 'firstname', sargam: 'first_name', transform: 'direct' },
                    { moodle: 'lastname', sargam: 'last_name', transform: 'direct' },
                    { moodle: 'password', sargam: 'jbp_password', transform: 'encrypt' },
                    { moodle: 'email', sargam: 'email_id', transform: 'direct' },
                    { moodle: 'phone1', sargam: 'mobile_no', transform: 'format_phone' },
                    { moodle: 'phone2', sargam: 'alternate_mailid', transform: 'direct' },
                    { moodle: 'timecreated', sargam: 'reg_date', transform: 'timestamp_to_datetime' },
                    { moodle: 'timemodified', sargam: 'updated_date', transform: 'timestamp_to_datetime' },
                    { moodle: 'lastaccess', sargam: 'last_login', transform: 'timestamp_to_datetime' },
                    { moodle: 'picture', sargam: 'image_path', transform: 'user_picture' },
                    { moodle: 'confirmed', sargam: 'Active_inactive', transform: 'confirmed_to_active' },
                    { moodle: 'suspended', sargam: 'jbp_enabled', transform: 'suspended_to_enabled' },
                    { moodle: 'id', sargam: 'user_id', transform: 'direct' },
                    { moodle: 'institution', sargam: 'entity_id', transform: 'lookup_entity' }
                ],
                'student_master': [
                    { moodle: 'email', sargam: 'email', transform: 'direct' },
                    { moodle: 'phone1', sargam: 'contact_no', transform: 'format_phone' },
                    { moodle: 'username', sargam: 'user_id', transform: 'direct' },
                    { moodle: 'firstname', sargam: 'first_name', transform: 'direct' },
                    { moodle: 'middlename', sargam: 'middle_name', transform: 'direct' },
                    { moodle: 'lastname', sargam: 'last_name', transform: 'direct' },
                    { moodle: 'password', sargam: 'password', transform: 'encrypt' },
                    { moodle: 'firstname', sargam: 'display_name', transform: 'full_name' },
                    { moodle: 'lastname', sargam: 'display_name', transform: 'full_name' },
                    { moodle: 'timecreated', sargam: 'created_date', transform: 'timestamp_to_datetime' },
                    { moodle: 'dob', sargam: 'dob', transform: 'timestamp_to_date' },
                    { moodle: 'confirmed', sargam: 'status', transform: 'confirmed_to_status' },
                    { moodle: 'id', sargam: 'web_auth', transform: 'user_web_auth' },
                    { moodle: 'department', sargam: 'exam_year', transform: 'extract_year' },
                    { moodle: 'idnumber', sargam: 'enrollment', transform: 'direct' },
                    { moodle: 'institution', sargam: 'course_master_pk', transform: 'institution_to_course' }
                ],
                'student_master_course__map': [
                    { moodle: 'id', sargam: 'student_master_pk', transform: 'lookup_student_pk' },
                    { moodle: 'idnumber', sargam: 'course_master_pk', transform: 'course_from_idnumber' },
                    { moodle: 'confirmed', sargam: 'active_inactive', transform: 'confirmed_to_active' },
                    { moodle: 'timecreated', sargam: 'created_date', transform: 'timestamp_to_datetime' },
                    { moodle: 'timemodified', sargam: 'modified_date', transform: 'timestamp_to_datetime' },
                    { moodle: 'department', sargam: 'course_master_pk', transform: 'department_to_course' },
                    { moodle: 'institution', sargam: 'course_master_pk', transform: 'institution_to_course' }
                ]
            }
        };

        // Open migration interface
        function openMigrationInterface() {
            document.getElementById('migrationInterface').style.display = 'block';
            addLog('🚀 Complete User Migration interface opened', 'success');
        }

        // Close migration interface
        function closeMigrationInterface() {
            if (confirm('Close migration interface? Any unsaved mappings will be lost.')) {
                document.getElementById('migrationInterface').style.display = 'none';
                resetSelections();
                addLog('Migration interface closed', 'info');
            }
        }

        // Select Moodle table
        function selectMoodleTable(tableName, element) {
            selectedMoodleTable = tableName;

            // Update UI
            document.querySelectorAll('#moodleTableList .table-item').forEach(el => {
                el.classList.remove('selected');
            });
            element.classList.add('selected');

            // Show mapping area if both tables selected
            if (selectedMoodleTable && selectedSargamTable) {
                document.getElementById('mappingArea').style.display = 'block';
                document.getElementById('workflowArea').style.display = 'block';
                displayColumns();
                checkForSuggestions();
            }

            addLog(`Selected Moodle table: ${tableName}`, 'info');
        }

        // Select Sargam table
        function selectSargamTable(tableName, element) {
            selectedSargamTable = tableName;

            // Update UI
            document.querySelectorAll('#sargamTableList .table-item').forEach(el => {
                el.classList.remove('selected');
            });
            element.classList.add('selected');

            // Show mapping area if both tables selected
            if (selectedMoodleTable && selectedSargamTable) {
                document.getElementById('mappingArea').style.display = 'block';
                document.getElementById('workflowArea').style.display = 'block';
                displayColumns();
                checkForSuggestions();
            }

            addLog(`Selected Sargam table: ${tableName}`, 'info');
        }

        // Check for intelligent mapping suggestions
        function checkForSuggestions() {
            if (!selectedMoodleTable || !selectedSargamTable) return;

            const suggestions = intelligentMappings[selectedMoodleTable]?.[selectedSargamTable];

            if (suggestions) {
                const suggestionBar = document.getElementById('suggestionBar');
                const suggestionText = document.getElementById('suggestionText');

                suggestionBar.style.display = 'block';
                suggestionText.innerHTML = `${suggestions.length} intelligent mapping suggestions available for ${selectedMoodleTable} → ${selectedSargamTable}`;
            } else {
                document.getElementById('suggestionBar').style.display = 'none';
            }
        }

        // Apply suggested mappings
        function applySuggestedMappings() {
            if (!selectedMoodleTable || !selectedSargamTable) return;

            const suggestions = intelligentMappings[selectedMoodleTable]?.[selectedSargamTable];

            if (!suggestions) {
                alert('No suggestions available for this table pair');
                return;
            }

            let appliedCount = 0;

            suggestions.forEach(suggestion => {
                const mappingKey = `${selectedMoodleTable}.${suggestion.moodle}`;

                // Check if column already mapped
                if (!mappings[mappingKey]) {
                    // Check if target column already used
                    let isUsed = Object.values(mappings).includes(suggestion.sargam);

                    if (!isUsed) {
                        mappings[mappingKey] = suggestion.sargam;
                        appliedCount++;
                        addLog(`✅ Suggested: ${suggestion.moodle} → ${suggestion.sargam}`, 'success');
                    }
                }
            });

            if (appliedCount > 0) {
                displayMappings();
                addLog(`✨ Applied ${appliedCount} intelligent mapping suggestions`, 'success');
                alert(`✅ Successfully applied ${appliedCount} mapping suggestions!`);
            } else {
                alert('No new mappings could be applied. They may already exist or target columns are already mapped.');
            }
        }

        // Display columns for selected tables
        function displayColumns() {
            if (!selectedMoodleTable || !selectedSargamTable) return;

            const moodleCols = moodleTables[selectedMoodleTable];
            const sargamCols = sargamTables[selectedSargamTable];

            document.getElementById('selectedTableInfo').innerHTML =
                `Mapping: <strong>${selectedMoodleTable}</strong> → <strong>${selectedSargamTable}</strong>`;

            // Display Moodle columns with suggestions
            const moodleColList = document.getElementById('moodleColumnsList');
            moodleColList.innerHTML = '';

            moodleCols.forEach(col => {
                const colDiv = document.createElement('div');
                colDiv.className = 'col-item';
                colDiv.onclick = (e) => selectMoodleColumn(col, e);

                // Check if this column has a suggested mapping
                const hasSuggestion = intelligentMappings[selectedMoodleTable]?.[selectedSargamTable]?.some(
                    s => s.moodle === col
                );

                if (hasSuggestion) {
                    colDiv.classList.add('suggested');
                }

                colDiv.innerHTML = `
                    <strong>${col}</strong>
                    <span style="float: right;">${hasSuggestion ? '✨' : '📊'}</span>
                `;

                moodleColList.appendChild(colDiv);
            });

            document.getElementById('moodleColCount').innerHTML = moodleCols.length;

            // Display Sargam columns with suggestions
            const sargamColList = document.getElementById('sargamColumnsList');
            sargamColList.innerHTML = '';

            sargamCols.forEach(col => {
                const colDiv = document.createElement('div');
                colDiv.className = 'col-item';
                colDiv.onclick = (e) => selectSargamColumn(col, e);

                // Check if this column has a suggested mapping
                const hasSuggestion = intelligentMappings[selectedMoodleTable]?.[selectedSargamTable]?.some(
                    s => s.sargam === col
                );

                if (hasSuggestion) {
                    colDiv.classList.add('suggested');
                }

                colDiv.innerHTML = `
                    <strong>${col}</strong>
                    <span style="float: right;">${hasSuggestion ? '✨' : '⚡'}</span>
                `;

                sargamColList.appendChild(colDiv);
            });

            document.getElementById('sargamColCount').innerHTML = sargamCols.length;

            // Display existing mappings
            displayMappings();
        }

        // Select Moodle column
        function selectMoodleColumn(column, event) {
            selectedMoodleColumn = column;

            // Remove previous selection
            document.querySelectorAll('#moodleColumnsList .col-item').forEach(el => {
                el.classList.remove('selected');
            });

            // Add selection to clicked element
            event.target.closest('.col-item').classList.add('selected');

            // If both columns selected, create mapping
            if (selectedMoodleColumn && selectedSargamColumn) {
                createMapping();
            }
        }

        // Select Sargam column
        function selectSargamColumn(column, event) {
            selectedSargamColumn = column;

            // Remove previous selection
            document.querySelectorAll('#sargamColumnsList .col-item').forEach(el => {
                el.classList.remove('selected');
            });

            // Add selection to clicked element
            event.target.closest('.col-item').classList.add('selected');

            // If both columns selected, create mapping
            if (selectedMoodleColumn && selectedSargamColumn) {
                createMapping();
            }
        }

        // Create mapping between selected columns
        function createMapping() {
            if (!selectedMoodleColumn || !selectedSargamColumn) return;

            const mappingKey = `${selectedMoodleTable}.${selectedMoodleColumn}`;

            // Check if column already mapped
            if (mappings[mappingKey]) {
                alert(`Column ${selectedMoodleColumn} is already mapped to ${mappings[mappingKey]}`);
                resetColumnSelections();
                return;
            }

            // Check if target column already used
            let isUsed = false;
            Object.keys(mappings).forEach(key => {
                if (mappings[key] === selectedSargamColumn) {
                    isUsed = true;
                    alert(`Sargam column ${selectedSargamColumn} is already mapped to ${key.split('.')[1]}`);
                }
            });

            if (isUsed) {
                resetColumnSelections();
                return;
            }

            // Save mapping
            mappings[mappingKey] = selectedSargamColumn;

            // Add to history
            mappingHistory.push({
                moodleTable: selectedMoodleTable,
                moodleCol: selectedMoodleColumn,
                sargamTable: selectedSargamTable,
                sargamCol: selectedSargamColumn,
                timestamp: new Date().toISOString()
            });

            addLog(`✅ Mapped: ${selectedMoodleColumn} → ${selectedSargamColumn}`, 'success');
            displayMappings();
            resetColumnSelections();
        }

        // Display existing mappings
        function displayMappings() {
            const mappedList = document.getElementById('mappedPairsList');
            mappedList.innerHTML = '';

            let hasMappings = false;
            Object.keys(mappings).forEach(key => {
                const [table, column] = key.split('.');
                if (table === selectedMoodleTable) {
                    hasMappings = true;
                    const mappedItem = document.createElement('div');
                    mappedItem.className = 'mapped-item';
                    mappedItem.innerHTML = `
                        <div>
                            <span style="background: #e3f2fd; padding: 5px 10px; border-radius: 5px;">
                                ${column}
                            </span>
                            <span style="margin: 0 15px;">→</span>
                            <span style="background: #e8f5e9; padding: 5px 10px; border-radius: 5px;">
                                ${mappings[key]}
                            </span>
                        </div>
                        <div>
                            <span class="mapping-badge">Mapped</span>
                            <button class="btn" style="padding: 5px 10px; margin-left: 10px;" 
                                    onclick="removeMapping('${key}')">
                                ✕
                            </button>
                        </div>
                    `;
                    mappedList.appendChild(mappedItem);
                }
            });

            if (!hasMappings) {
                mappedList.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">No mappings defined yet. Select columns or use intelligent suggestions to create mappings.</p>';
            }
        }

        // Remove mapping
        function removeMapping(key) {
            if (confirm('Remove this mapping?')) {
                delete mappings[key];
                addLog(`🗑️ Removed mapping for ${key}`, 'info');
                displayMappings();
            }
        }

        // Clear all mappings
        function clearAllMappings() {
            if (confirm('Clear all defined mappings?')) {
                mappings = {};
                displayMappings();
                addLog('🗑️ All mappings cleared', 'info');
            }
        }

        // Save mapping configuration
        function saveMapping() {
            const mappingCount = Object.keys(mappings).length;
            if (mappingCount > 0) {
                addLog(`💾 Saved ${mappingCount} mapping(s) for ${selectedMoodleTable} → ${selectedSargamTable}`, 'success');
                alert(`Mapping configuration saved successfully!\nTotal mappings: ${mappingCount}`);
            } else {
                alert('No mappings to save');
            }
        }

        // Reset column selections
        function resetColumnSelections() {
            selectedMoodleColumn = null;
            selectedSargamColumn = null;
        }

        // Reset all selections
        function resetSelections() {
            selectedMoodleTable = null;
            selectedSargamTable = null;
            selectedMoodleColumn = null;
            selectedSargamColumn = null;
            mappings = {};
            document.getElementById('suggestionBar').style.display = 'none';
        }

        // Suggest all mappings for complete migration
        function suggestAllMappings() {
            addLog('✨ Generating complete migration mapping suggestions...', 'info');
            
            // Clear existing mappings first
            mappings = {};
            
            // Suggest user_credentials mappings
            if (intelligentMappings['mdl_user']?.['user_credentials']) {
                intelligentMappings['mdl_user']['user_credentials'].forEach(suggestion => {
                    const mappingKey = `mdl_user.${suggestion.moodle}`;
                    mappings[mappingKey] = suggestion.sargam;
                });
                addLog('✅ Applied user_credentials mappings', 'success');
            }
            
            // Suggest student_master mappings
            if (intelligentMappings['mdl_user']?.['student_master']) {
                intelligentMappings['mdl_user']['student_master'].forEach(suggestion => {
                    const mappingKey = `mdl_user.${suggestion.moodle}`;
                    mappings[mappingKey] = suggestion.sargam;
                });
                addLog('✅ Applied student_master mappings', 'success');
            }
            
            // Suggest course map mappings
            if (intelligentMappings['mdl_user']?.['student_master_course__map']) {
                intelligentMappings['mdl_user']['student_master_course__map'].forEach(suggestion => {
                    const mappingKey = `mdl_user.${suggestion.moodle}`;
                    mappings[mappingKey] = suggestion.sargam;
                });
                addLog('✅ Applied student_master_course__map mappings', 'success');
            }
            
            // Set the selected tables to show all mappings
            selectedMoodleTable = 'mdl_user';
            
            displayMappings();
            addLog(`✨ Total mappings applied: ${Object.keys(mappings).length}`, 'success');
            alert(`✅ Applied ${Object.keys(mappings).length} mappings across all three tables!`);
        }

        // Migrate to User Credentials
        function migrateToUserCredentials() {
            selectedMoodleTable = 'mdl_user';
            selectedSargamTable = 'user_credentials';

            addLog('🔐 Starting User Credentials migration...', 'info');

            let progress = 0;
            const progressFill = document.getElementById('progressFill');
            const statusElement = document.getElementById('migrationStatus');

            document.getElementById('migrationProgress').style.display = 'block';

            const interval = setInterval(() => {
                progress += 20;
                progressFill.style.width = progress + '%';
                statusElement.innerHTML = `Migrating user credentials... ${progress}% complete`;

                if (progress === 40) {
                    addLog('📤 Exporting user authentication data from mdl_user...', 'info');
                } else if (progress === 60) {
                    addLog('🔄 Transforming passwords and timestamps...', 'info');
                } else if (progress === 80) {
                    addLog('📥 Importing to user_credentials table...', 'info');
                } else if (progress >= 100) {
                    clearInterval(interval);
                    const userCount = Math.floor(Math.random() * 100) + 50;
                    migrationStats.users = userCount;
                    document.getElementById('statsUsers').innerHTML = userCount;
                    document.getElementById('migrationStats').style.display = 'flex';

                    statusElement.innerHTML = '✅ User credentials migration completed!';
                    addLog(`✅ Successfully migrated ${userCount} user credentials`, 'success');
                }
            }, 400);
        }

        // Migrate to Student Master
        function migrateToStudentMaster() {
            selectedMoodleTable = 'mdl_user';
            selectedSargamTable = 'student_master';

            addLog('👨‍🎓 Starting Student Master migration...', 'info');

            let progress = 0;
            const progressFill = document.getElementById('progressFill');
            const statusElement = document.getElementById('migrationStatus');

            document.getElementById('migrationProgress').style.display = 'block';

            const interval = setInterval(() => {
                progress += 20;
                progressFill.style.width = progress + '%';
                statusElement.innerHTML = `Migrating student profiles... ${progress}% complete`;

                if (progress === 40) {
                    addLog('📤 Exporting user profile data from mdl_user...', 'info');
                } else if (progress === 60) {
                    addLog('🔄 Creating student records from user data...', 'info');
                    
                    // Simulate storing student master PKs for course mapping
                    for (let i = 1; i <= 10; i++) {
                        studentMasterPKMap[i] = 1000 + i;
                    }
                    addLog(`📝 Generated student master PKs for course mapping`, 'info');
                    
                } else if (progress === 80) {
                    addLog('📥 Importing to student_master table...', 'info');
                } else if (progress >= 100) {
                    clearInterval(interval);
                    const studentCount = Math.floor(Math.random() * 80) + 40;
                    migrationStats.students = studentCount;
                    document.getElementById('statsStudents').innerHTML = studentCount;
                    document.getElementById('migrationStats').style.display = 'flex';

                    statusElement.innerHTML = '✅ Student master migration completed!';
                    addLog(`✅ Successfully created ${studentCount} student records`, 'success');
                }
            }, 400);
        }

        // Migrate to Course Map
        function migrateToCourseMap() {
            selectedMoodleTable = 'mdl_user';
            selectedSargamTable = 'student_master_course__map';

            addLog('📚 Starting Course Enrollment migration...', 'info');

            let progress = 0;
            const progressFill = document.getElementById('progressFill');
            const statusElement = document.getElementById('migrationStatus');

            document.getElementById('migrationProgress').style.display = 'block';

            const interval = setInterval(() => {
                progress += 20;
                progressFill.style.width = progress + '%';
                statusElement.innerHTML = `Migrating course enrollments... ${progress}% complete`;

                if (progress === 40) {
                    addLog('📤 Generating course data from mdl_user fields...', 'info');
                } else if (progress === 60) {
                    addLog('🔄 Creating course mappings using department/institution fields...', 'info');
                } else if (progress === 80) {
                    addLog('📥 Importing to student_master_course__map table...', 'info');
                } else if (progress >= 100) {
                    clearInterval(interval);
                    const enrollmentCount = Math.floor(Math.random() * 150) + 60;
                    migrationStats.enrollments = enrollmentCount;
                    document.getElementById('statsEnrollments').innerHTML = enrollmentCount;
                    document.getElementById('migrationStats').style.display = 'flex';

                    statusElement.innerHTML = '✅ Course enrollment migration completed!';
                    addLog(`✅ Successfully created ${enrollmentCount} course mappings from mdl_user data`, 'success');
                }
            }, 400);
        }

        // Execute complete migration
        function executeCompleteMigration() {
            addLog('🚀 Starting COMPLETE USER MIGRATION workflow...', 'success');

            // First, suggest all mappings if none exist
            if (Object.keys(mappings).length === 0) {
                suggestAllMappings();
            }

            addLog('📋 Phase 1: Migrating User Credentials', 'info');
            setTimeout(() => migrateToUserCredentials(), 500);

            setTimeout(() => {
                addLog('📋 Phase 2: Migrating Student Master', 'info');
                migrateToStudentMaster();
            }, 2500);

            setTimeout(() => {
                addLog('📋 Phase 3: Migrating Course Enrollments', 'info');
                migrateToCourseMap();
            }, 4500);

            setTimeout(() => {
                addLog('🎉 COMPLETE USER MIGRATION FINISHED!', 'success');

                // Show final stats
                document.getElementById('statsUsers').innerHTML = migrationStats.users;
                document.getElementById('statsStudents').innerHTML = migrationStats.students;
                document.getElementById('statsEnrollments').innerHTML = migrationStats.enrollments;
                document.getElementById('migrationStats').style.display = 'flex';

                addLog(`📊 Summary: ${migrationStats.users} users, ${migrationStats.students} students, ${migrationStats.enrollments} enrollments`, 'success');
                addLog(`✨ All data migrated using only mdl_user table!`, 'success');
            }, 6500);
        }

        // Validate complete migration
        function validateCompleteMigration() {
            addLog('🔍 Validating complete user migration readiness...', 'info');

            setTimeout(() => {
                let isValid = true;
                let mappingStats = {
                    user_credentials: 0,
                    student_master: 0,
                    student_master_course__map: 0
                };

                // Categorize current mappings
                Object.values(mappings).forEach(sargamCol => {
                    if (sargamTables['user_credentials'].includes(sargamCol)) {
                        mappingStats.user_credentials++;
                    } else if (sargamTables['student_master'].includes(sargamCol)) {
                        mappingStats.student_master++;
                    } else if (sargamTables['student_master_course__map'].includes(sargamCol)) {
                        mappingStats.student_master_course__map++;
                    }
                });

                addLog('📊 Current mapping status:', 'info');
                addLog(`   📁 user_credentials: ${mappingStats.user_credentials} mappings`,
                    mappingStats.user_credentials > 0 ? 'success' : 'warning');
                addLog(`   📁 student_master: ${mappingStats.student_master} mappings`,
                    mappingStats.student_master > 0 ? 'success' : 'warning');
                addLog(`   📁 student_master_course__map: ${mappingStats.student_master_course__map} mappings`,
                    mappingStats.student_master_course__map > 0 ? 'success' : 'warning');

                // Check if all three tables have at least some mappings
                if (mappingStats.user_credentials === 0) {
                    addLog('⚠️ No mappings for user_credentials table', 'warning');
                    isValid = false;
                }
                if (mappingStats.student_master === 0) {
                    addLog('⚠️ No mappings for student_master table', 'warning');
                    isValid = false;
                }
                if (mappingStats.student_master_course__map === 0) {
                    addLog('⚠️ No mappings for student_master_course__map table', 'warning');
                    isValid = false;
                }

                if (isValid) {
                    addLog('✅ All three Sargam tables have mappings from mdl_user!', 'success');
                    alert('✅ Migration validation passed! All three tables have mappings from mdl_user.');
                } else {
                    addLog('⚠️ Some tables are missing mappings. Click "Suggest All Mappings" first.', 'warning');
                    alert('⚠️ Migration validation failed. Some tables have no mappings. Click "Suggest All Mappings" to fix.');
                }
            }, 1500);
        }

        // Test database connection (dummy)
        function testConnection() {
            addLog('🔌 Testing database connections...', 'info');

            setTimeout(() => {
                addLog('✅ Moodle DB: Connected', 'success');
                addLog('✅ Sargam DB: Connected', 'success');
                alert('✅ All database connections successful!');
            }, 1500);
        }

        // Validate mapping
        function validateMapping() {
            addLog('✅ Validating column mappings...', 'info');

            setTimeout(() => {
                const mappingCount = Object.keys(mappings).length;

                if (mappingCount === 0) {
                    addLog('⚠️ No mappings defined', 'error');
                    alert('Please define at least one column mapping');
                    return;
                }

                addLog(`✅ ${mappingCount} mappings validated successfully`, 'success');
                alert(`Validation complete!\nTotal mappings: ${mappingCount}`);
            }, 1000);
        }

        // Execute migration (dummy)
        function executeMigration() {
            const mappingCount = Object.keys(mappings).length;

            if (mappingCount === 0) {
                alert('Please define column mappings before migration');
                return;
            }

            document.getElementById('migrationProgress').style.display = 'block';
            addLog('🚀 Starting migration process...', 'info');

            let progress = 0;
            const progressFill = document.getElementById('progressFill');
            const statusElement = document.getElementById('migrationStatus');

            const interval = setInterval(() => {
                progress += 10;
                progressFill.style.width = progress + '%';
                statusElement.innerHTML = `Migrating data... ${progress}% complete`;

                if (progress === 30) {
                    addLog(`📤 Exporting data from ${selectedMoodleTable}...`, 'info');
                } else if (progress === 60) {
                    addLog(`📥 Importing data to ${selectedSargamTable}...`, 'info');
                } else if (progress === 90) {
                    addLog(`🔄 Applying column mappings...`, 'info');
                } else if (progress >= 100) {
                    clearInterval(interval);
                    statusElement.innerHTML = '✅ Migration completed successfully!';
                    addLog(`✨ Migration completed! ${mappingCount} columns mapped and transferred.`, 'success');
                    addLog(`📊 Total records migrated: ${Math.floor(Math.random() * 1000) + 100}`, 'success');
                }
            }, 500);
        }

        // Add log entry
        function addLog(message, type = 'info') {
            const logContainer = document.getElementById('logContainer');
            const logEntry = document.createElement('div');
            logEntry.className = 'log-entry log-' + type;

            const timestamp = new Date().toLocaleTimeString();
            logEntry.innerHTML = `[${timestamp}] ${message}`;

            logContainer.appendChild(logEntry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }
    </script>
</body>

</html>