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
}