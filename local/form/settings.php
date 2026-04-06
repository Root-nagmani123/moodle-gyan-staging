<?php
// /local/form/settings.php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    // -------------------------------
    // 1️⃣ Form Settings Page (Images + Page Title)
    // -------------------------------
    $settings = new admin_settingpage('local_form_settings', get_string('settings', 'local_form'));

    // Left Image
    $settings->add(new admin_setting_configstoredfile(
        'local_form/formleftimage',
        get_string('formleftimage', 'local_form'),
        get_string('formleftimagedesc', 'local_form'),
        'formleftimage',
        0,
        ['maxfiles' => 1, 'accepted_types' => ['.jpg', '.png']]
    ));

    // Right Image
    $settings->add(new admin_setting_configstoredfile(
        'local_form/formrightimage',
        get_string('formrightimage', 'local_form'),
        get_string('formrightimagedesc', 'local_form'),
        'formrightimage',
        0,
        ['maxfiles' => 1, 'accepted_types' => ['.jpg', '.png']]
    ));

    // Page Title
    $settings->add(new admin_setting_confightmleditor(
        'local_form/formpagetitle',
        get_string('formpagetitle', 'local_form'),
        get_string('formpagetitledesc', 'local_form'),
        ''
    ));

    // Add Settings page under "Local plugins"
    $ADMIN->add('localplugins', $settings);

    // -------------------------------
    // 2️⃣ Manage Forms page (Admin-only)
    // -------------------------------
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_form_manageform',                  // Internal name
        get_string('manageforms', 'local_form'), // Display name
        new moodle_url('/local/form/manageform.php'),
        'moodle/site:config'                      // Admin-only capability
    ));

    // -------------------------------
    // 3️⃣ LDAP Test Settings Page
    // -------------------------------
    $ldaptestpage = new admin_settingpage('local_form_ldaptest', get_string('ldaptest', 'local_form'));

    // Instructions
    $ldaptestpage->add(new admin_setting_heading(
        'local_form/ldap_test_heading',
        get_string('ldaptestheading', 'local_form'),
        get_string('ldaptestdesc', 'local_form')
    ));

    // Username to test
    $ldaptestpage->add(new admin_setting_configtext(
        'local_form/ldap_test_username',
        get_string('ldaptestusername', 'local_form'),
        get_string('ldaptestusernamedesc', 'local_form'),
        '',
        PARAM_TEXT,
        50
    ));

    // Test URL
    $ldaptestpage->add(new admin_setting_heading(
        'local_form/ldap_test_url',
        get_string('ldaptesturl', 'local_form'),
        '<a href="' . new moodle_url('/local/form/test_ldap.php') . '" target="_blank" class="btn btn-primary">' .
            get_string('runldaptest', 'local_form') . '</a><br><br>
        <small>' . get_string('ldaptesturldesc', 'local_form') . '</small>'
    ));

    // Add LDAP Test page
    $ADMIN->add('localplugins', $ldaptestpage);

    // -------------------------------
    // 4️⃣ Migration DB Settings
    // -------------------------------
    $migrationsettings = new admin_settingpage('local_form_migrationdb', get_string('migrationdbsettings', 'local_form'));

    // DB Host
    $migrationsettings->add(new admin_setting_configtext(
        'local_form/migration_db_host',
        get_string('migrationdbhost', 'local_form'),
        get_string('migrationdbhostdesc', 'local_form'),
        '',
        PARAM_TEXT
    ));

    // DB Port
    $migrationsettings->add(new admin_setting_configtext(
        'local_form/migration_db_port',
        get_string('migrationdbport', 'local_form'),
        get_string('migrationdbportdesc', 'local_form'),
        '3306',
        PARAM_INT
    ));

    // DB Name
    $migrationsettings->add(new admin_setting_configtext(
        'local_form/migration_db_name',
        get_string('migrationdbname', 'local_form'),
        get_string('migrationdbnamedesc', 'local_form'),
        '',
        PARAM_TEXT
    ));

    // DB Username
    $migrationsettings->add(new admin_setting_configtext(
        'local_form/migration_db_user',
        get_string('migrationdbuser', 'local_form'),
        get_string('migrationdbuserdesc', 'local_form'),
        '',
        PARAM_TEXT
    ));

    // DB Password
    $migrationsettings->add(new admin_setting_configpasswordunmask(
        'local_form/migration_db_pass',
        get_string('migrationdbpass', 'local_form'),
        get_string('migrationdbpassdesc', 'local_form'),
        ''
    ));

    // Add page
    $ADMIN->add('localplugins', $migrationsettings);
}
