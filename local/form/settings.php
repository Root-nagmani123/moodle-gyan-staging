<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'local_form', language 'en', branch 'MOODLE_30_STABLE'
 *
 * @package   local_form
 * @author    hardeep.dagar@awzpact.com
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    // Create the new setting page
    $setting = new admin_settingpage('local_form', get_string('pluginname', 'local_form'));
    
    $name = 'local_form/formleftimage';
    $title = get_string('formleftimage', 'local_form');
    $description = get_string('formleftimagedesc', 'local_form');
    $slider = new admin_setting_configstoredfile($name, $title, $description, 'formleftimage', 0,['maxfiles' => 1, 'accepted_types' => ['.jpg', '.png']]);
    $setting->add($slider);
    

    $name = 'local_form/formrightimage';
    $title = get_string('formrightimage', 'local_form');
    $description = get_string('formrightimagedesc', 'local_form');
    $slider = new admin_setting_configstoredfile($name, $title, $description, 'formrightimage', 0,['maxfiles' => 1, 'accepted_types' => ['.jpg', '.png']]);
    $setting->add($slider);

    $setting->add(new admin_setting_confightmleditor(
        'local_form/formpagetitle',
        new lang_string('formpagetitle', 'local_form'),
        new lang_string('formpagetitledesc', 'local_form'),
        '',
    
    ));
    
    
    $ADMIN->add('localplugins', $setting);
    $ADMIN->add('localplugins', new admin_externalpage('local_form_manageform', 'Manageforms', new moodle_url('/local/form/manageform.php')));   

}