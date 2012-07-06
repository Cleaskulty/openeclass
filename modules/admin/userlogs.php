<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== */

$require_usermanage_user = true;
require_once '../../include/baseTheme.php';
require_once 'include/jscalendar/calendar.php';
require_once 'include/log.php';

$nameTools = $langUserLog;
$navigation[]= array('url' => 'index.php', 'name' => $langAdmin);
$navigation[]= array('url' => 'listusers.php', 'name' => $langListUsers);

load_js('jquery');
load_js('tools.js');
$head_content .= '<script type="text/javascript">
        var platform_actions = ["-2", "'.LOG_PROFILE.'", "'.LOG_CREATE_COURSE.'", "'.LOG_DELETE_COURSE.'"];
        $(course_log_controls_init);
</script>';
$jscalendar = new DHTML_Calendar($urlServer.'include/jscalendar/', $language, 'calendar-blue2', false);
$head_content .= $jscalendar->get_load_files_code();

$u = isset($_GET['u'])?intval($_GET['u']):'';
$u_date_start = isset($_GET['u_date_start'])? $_GET['u_date_start']: strftime('%Y-%m-%d', strtotime('now -15 day'));
$u_date_end = isset($_GET['u_date_end'])? $_GET['u_date_end']: strftime('%Y-%m-%d', strtotime('now +1 day'));
$logtype = isset($_GET['logtype'])? intval($_GET['logtype']): '0';
$u_course_id = isset($_GET['u_course_id'])? intval($_GET['u_course_id']): '-1';
$u_module_id = isset($_GET['u_module_id'])? intval($_GET['u_module_id']): '-1';

// display logs
if (isset($_GET['submit'])) {
    $log = new Log();
    if ($logtype == -2) { // display system logging
        $log->display(0, $u, 0, $logtype, $u_date_start, $u_date_end);
    } else { // display course modules logging         
        $log->display($u_course_id, $u, $u_module_id, $logtype, $u_date_start, $u_date_end);
    }
}

//calendar for determining start and end date
    $start_cal = $jscalendar->make_input_field(
           array('showsTime'      => false,
                 'showOthers'     => true,
                 'ifFormat'       => '%Y-%m-%d',
                 'timeFormat'     => '24'),
           array('style'       => '',
                 'name'        => 'u_date_start',
                 'value'       => $u_date_start));

    $end_cal = $jscalendar->make_input_field(
           array('showsTime'      => false,
                 'showOthers'     => true,
                 'ifFormat'       => '%Y-%m-%d',
                 'timeFormat'     => '24'),
           array('style'       => '',
                 'name'        => 'u_date_end',
                 'value'       => $u_date_end));
    
        //possible courses
        $qry = "SELECT LEFT(title, 1) AS first_letter FROM course
                GROUP BY first_letter ORDER BY first_letter";
        $result = db_query($qry);
        $letterlinks = '';
        while ($row = mysql_fetch_assoc($result)) {
                $first_letter = $row['first_letter'];
                $letterlinks .= "<a href='$_SERVER[SCRIPT_NAME]?first=".urlencode($first_letter)."'>".q($first_letter).'</a> ';
        }

        if (isset($_GET['first'])) {
                $firstletter = $_GET['first'];
                $qry = "SELECT id, title FROM course
                                WHERE LEFT(title,1) = ".quote($firstletter);
        } else {
                $qry = "SELECT id, title FROM course";
        }

        $cours_opts[-1] = $langAllCourses;
        $result = db_query($qry);
        while ($row = mysql_fetch_assoc($result)) {
                $cours_opts[$row['id']] = $row['title'];
        }    

    // --------------------------------------
    // display form
    // --------------------------------------
    $module_names[-1] = $langAllModules;
    foreach ($modules as $mid => $info) {
            $module_names[$mid] = $info['title'];
    }
    
    $i = html_entity_decode('&nbsp;&nbsp;&nbsp;', ENT_QUOTES, 'UTF-8');
    $log_types = array(0 => $langAllActions,
                       -1 => $i.$langCourseActions,
                       LOG_INSERT => $i.$i.$langInsert,
                       LOG_MODIFY => $i.$i.$langModify,
                       LOG_DELETE => $i.$i.$langDelete,
                       -2 => $i.$langSystemActions,
                       LOG_PROFILE => $i.$i.$langModProfile,
                       LOG_CREATE_COURSE => $i.$i.$langCourseCreate,
                       LOG_DELETE_COURSE => $i.$i.$langCourseDel);
    $tool_content .= "<form method='get' action='$_SERVER[SCRIPT_NAME]'>
    <fieldset>
      <legend>$langUserLog</legend>
      <table class='tbl'>
        <tr><th width='220' class='left'>$langStartDate</th>
            <td>$start_cal</td></tr>
        <tr><th class='left'>$langEndDate</th>
            <td>$end_cal</td></tr>
        <tr><th class='left'>$langLogTypes :</th>
            <td>".selection($log_types, 'logtype', $logtype)."</td></tr>
        <tr class='course'><th class='left'>$langFirstLetterCourse</th>
            <td>$letterlinks</td></tr>
        <tr class='course'><th class='left'>$langCourse</th>
            <td>".selection($cours_opts, 'u_course_id', $u_course_id)."</td></tr>
        <tr class='course'><th class='left'>$langLogModules:</th>
            <td>".selection($module_names, 'u_module_id', $m)."</td></tr>
        <tr><th class='left'>&nbsp;</th>
            <td><input type='submit' name='submit' value='$langSubmit'></td></tr>            
      </table>
    </fieldset>
    <input type='hidden' name='u' value='$u'>
  </form>";

$tool_content .= "<p align='right'><a href='listusers.php'>$langBack</a></p>";

draw($tool_content, 3, null, $head_content);
