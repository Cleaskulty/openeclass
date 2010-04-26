<?php

//
// Units utility functions	
//

// Add rich html editor JavaScript to $head_content
function add_html_editor()
{
        global $head_content, $language, $urlAppend;

        if ($language == 'greek') {
                $lang_editor = 'el';
        } else {
                $lang_editor = 'en';
        }

        $head_content .= "<script type='text/javascript'>
_editor_url  = '$urlAppend/include/xinha/';
_editor_lang = '$lang_editor';
</script>
<script type='text/javascript' src='$urlAppend/include/xinha/XinhaCore.js'></script>
<script type='text/javascript' src='$urlAppend/include/xinha/my_config.js'></script>";
}


// Process resource actions
function process_actions()
{
        global $tool_content, $id, $mysqlMainDb, $langResourceCourseUnitDeleted;

        if (isset($_REQUEST['edit'])) {
                $res_id = intval($_GET['edit']);
                if ($id = check_admin_unit_resource($res_id)) {
                        add_html_editor();
                        edit_res($res_id);
                }
        }  elseif(isset($_REQUEST['edit_res_submit'])) { // edit resource
                $res_id = intval($_REQUEST['resource_id']);	
                if ($id = check_admin_unit_resource($res_id)) {
                        @$restitle = autoquote(trim($_REQUEST['restitle']));
                        $rescomments = autoquote(trim($_REQUEST['rescomments']));
                        $result = db_query("UPDATE unit_resources SET
                                        title = $restitle,
                                        comments = $rescomments
                                        WHERE unit_id = $id AND id = $res_id");
                }
                $tool_content .= "<p class='success_small'>$langResourceUnitModified</p>";
        } elseif(isset($_REQUEST['del'])) { // delete resource from course unit
                $res_id = intval($_GET['del']);
                if ($id = check_admin_unit_resource($res_id)) {
                        db_query("DELETE FROM unit_resources WHERE id = '$res_id'", $mysqlMainDb);
                        $tool_content .= "<p class='success_small'>$langResourceCourseUnitDeleted</p>";
                }
        } elseif (isset($_REQUEST['vis'])) { // modify visibility in text resources only 
                $res_id = intval($_REQUEST['vis']);
                if ($id = check_admin_unit_resource($res_id)) {
                        $sql = db_query("SELECT `visibility` FROM unit_resources WHERE id=$res_id");
                        list($vis) = mysql_fetch_row($sql);
                        $newvis = ($vis == 'v')? 'i': 'v';
                        db_query("UPDATE unit_resources SET visibility = '$newvis' WHERE id = $res_id");
                }
        } elseif (isset($_REQUEST['down'])) { // change order down
                $res_id = intval($_REQUEST['down']);
                if ($id = check_admin_unit_resource($res_id)) {
                        move_order('unit_resources', 'id', $res_id, 'order', 'down',
                                   "unit_id=$id");
                }
        } elseif (isset($_REQUEST['up'])) { // change order up
                $res_id = intval($_REQUEST['up']);
                if ($id = check_admin_unit_resource($res_id)) {
                        move_order('unit_resources', 'id', $res_id, 'order', 'up',
                                   "unit_id=$id");
                }
        }
}


// Check that a specified resource id belongs to a resource in the
// current course, and that the user is an admin in this course.
// Return the id of the unit or false if user is not an admin 
function check_admin_unit_resource($resource_id)
{
	global $cours_id, $is_adminOfCourse;
	
	if ($is_adminOfCourse) {
		$q = db_query("SELECT course_units.id FROM course_units,unit_resources WHERE
			course_units.course_id = $cours_id AND course_units.id = unit_resources.unit_id
			AND unit_resources.id = $resource_id");
		if (mysql_num_rows($q) > 0) {
			list($unit_id) = mysql_fetch_row($q);
			return $unit_id;
		}
	}
	return false;
}


// Display resources for unit with id=$id
function show_resources($unit_id)
{
	global $tool_content, $max_resource_id;
	$req = db_query("SELECT * FROM unit_resources WHERE unit_id = $unit_id AND `order` >= 0 ORDER BY `order`");
	if (mysql_num_rows($req) > 0) {
		list($max_resource_id) = mysql_fetch_row(db_query("SELECT id FROM unit_resources
                                WHERE unit_id = $unit_id ORDER BY `order` DESC LIMIT 1"));
		$tool_content .= "<br /><table class='resources' width='99%'>";
		while ($info = mysql_fetch_array($req)) {
			show_resource($info);
		}	
		$tool_content .= "</table>\n";
	}
}


function show_resource($info)
{
        global $tool_content, $langUnknownResType, $is_adminOfCourse;
	
        if ($info['visibility'] == 'i' and !$is_adminOfCourse) {
                return;
        }
        switch ($info['type']) {
                case 'doc':
                        $tool_content .= show_doc($info['title'], $info['comments'], $info['id'], $info['res_id']);
                        break;
                case 'text':
                        $tool_content .= show_text($info['comments'], $info['id'], $info['visibility']);
                        break;
                case 'description':
                        $tool_content .= show_description($info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
		case 'lp':
                        $tool_content .= show_lp($info['title'], $info['comments'], $info['id'], $info['res_id']);
                        break;
		case 'video':
		case 'videolinks':
                        $tool_content .= show_video($info['type'], $info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
		case 'exercise':
                        $tool_content .= show_exercise($info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
		case 'work':
                        $tool_content .= show_work($info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
		case 'topic':
		case 'forum':
                        $tool_content .= show_forum($info['type'], $info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
		case 'wiki':
                        $tool_content .= show_wiki($info['title'], $info['comments'], $info['id'], $info['res_id'], $info['visibility']);
                        break;
                default:
                        $tool_content .= $langUnknownResType;
        }
}


// display resource documents
function show_doc($title, $comments, $resource_id, $file_id)
{
        global $is_adminOfCourse, $currentCourseID, $langWasDeleted,
               $visibility_check, $urlServer, $id;

        $title = htmlspecialchars($title);
        $r = db_query("SELECT * FROM document
	               WHERE id =" . intval($file_id) ." $visibility_check", $GLOBALS['currentCourseID']);
        if (mysql_num_rows($r) == 0) {
                if (!$is_adminOfCourse) {
                        return '';
                }
                $status = 'del';
                $image = '../../template/classic/img/delete.gif';
                $link = "<span class='invisible'>$title ($langWasDeleted)</span>";
        } else {
                $file = mysql_fetch_array($r, MYSQL_ASSOC);
                $status = $file['visibility'];
                if ($file['format'] == '.dir') {
                        $image = '../../template/classic/img/folder.gif';
                        $link = "<a href='{$urlServer}modules/document/document.php?openDir=$file[path]&amp;unit=$id'>";
                } else {
                        $image = '../document/img/' .
                                choose_image('.' . $file['format']);
                        $link = "<a href='" . file_url($file['path'], $file['filename']) . "' target='_blank'>";
                }
        }
	$class_vis = ($status == 'i' or $status == 'del')? ' class="invisible"': '';
        if (!empty($comments)) {
                $comment = "<tr><td>&nbsp;</td><td>$comments</td>";
        } else {
                $comment = "";
        }
        return "<tr$class_vis><td width='1'>$link<img src='$image' /></a></td><td align='left'>$link$title</a></td>" .
                actions('doc', $resource_id, $status) .
                '</tr>' . $comment;
}


// display resource text
function show_text($comments, $resource_id, $visibility)
{
        global $tool_content;

        $class_vis = ($visibility == 'i')? ' class="invisible"': '';
	$comments = mathfilter($comments, 12, "../../courses/mathimg/");
        $tool_content .= "<tr$class_vis><td colspan='2'>$comments</td>" .
		actions('text', $resource_id, $visibility) .
                "</tr>";
}


// display course description resource
function show_description($title, $comments, $id, $res_id, $visibility)
{
        global $tool_content;

	$comments = mathfilter($comments, 12, "../../courses/mathimg/");
        $tool_content .= "<tr><td colspan='2'><div class='title'>" . q($title) .
                "</div><div class='content'>$comments</div></td>" .
		actions('description', $id, $visibility, $res_id) .
                "</tr>";
}

// display resource learning path
function show_lp($title, $comments, $resource_id, $lp_id)
{
	global $id, $tool_content, $mysqlMainDb, $urlServer, $is_adminOfCourse,
               $langWasDeleted, $currentCourseID;

	$comment_box = $class_vis = $imagelink = $link = '';
        $title = htmlspecialchars($title);
	$r = db_query("SELECT * FROM lp_learnPath WHERE learnPath_id = $lp_id",
                      $currentCourseID);
	if (mysql_num_rows($r) == 0) { // check if lp was deleted
		if (!$is_adminOfCourse) {
			return '';
		} else {
			$status = 'del';
			$imagelink = "<img src='../../template/classic/img/delete.gif' />";
			$link = "<span class='invisible'>$title ($langWasDeleted)</span>";
		}
	} else {
                $lp = mysql_fetch_array($r, MYSQL_ASSOC);
		$status = ($lp['visibility'] == 'SHOW')? 'v': 'i';
		$link = "<a href='${urlServer}modules/learnPath/learningPath.php?path_id=$lp_id&amp;unit=$id'>";
		$imagelink = "<img src='../../template/classic/img/lp_" .
			($status == 'i'? 'off': 'on') . ".gif' />";
	}
        if ($status != 'v' and !$is_adminOfCourse) {
			return '';
        }
        if (!empty($comments)) {
                $comment_box = "<tr><td width='3%'>&nbsp;</td><td width='82%'>$comments</td>";
        }
        $class_vis = ($status == 'i' or $status == 'del')?
                ' class="invisible"': '';
	return "<tr$class_vis><td width='3%'>$link$imagelink</a></td><td width='82%'>$link$title</a></td>" .
		actions('lp', $resource_id, $status) .
		'</tr>' . $comment_box;
}


// display resource video
function show_video($table, $title, $comments, $resource_id, $video_id, $visibility)
{
        global $is_adminOfCourse, $currentCourseID, $tool_content;

        $result = db_query("SELECT * FROM $table WHERE id=$video_id",
                           $currentCourseID);
        if ($result and mysql_num_rows($result) > 0) {
                $row = mysql_fetch_array($result, MYSQL_ASSOC);
                $link = "<a href='" .
                             video_url($table, $row['url'], @$row['path']) .
                             "' target='_blank'>";
                $videolink = $link . htmlspecialchars($title) . '</a>';
                $imagelink = $link .
                             "<img src='../../template/classic/img/videos_" .
                             ($visibility == 'i'? 'off': 'on') . ".gif' /></a>";
        } else {
                if (!$is_adminOfCourse) {
                        return;
                }
                $videolink = $title;
                $imagelink = "<img src='../../template/classic/img/delete.gif' />";
                $visibility = 'del';
        }
        $class_vis = ($visibility == 'v')? '': ' class="invisible"';
        if (!empty($comments)) {
                $comment_box = "<tr$class_vis><td width='3%'>&nbsp;</td><td width='82%'>$comments</td>";
        } else {
                $comment_box = "";
        }
        $tool_content .= "<tr$class_vis><td width='3%'>$imagelink</td><td width='82%'>$videolink</td>" .
		actions('video', $resource_id, $visibility) .
                '</tr>' . $comment_box;
}


// display resource work (assignment)
function show_work($title, $comments, $resource_id, $work_id, $visibility)
{
	global $id, $tool_content, $mysqlMainDb, $urlServer, $is_adminOfCourse,
               $langWasDeleted, $currentCourseID;

	$comment_box = $class_vis = $imagelink = $link = '';
        $title = htmlspecialchars($title);
	$r = db_query("SELECT * FROM assignments WHERE id = $work_id",
                      $currentCourseID);
	if (mysql_num_rows($r) == 0) { // check if it was deleted
		if (!$is_adminOfCourse) {
			return '';
		} else {
			$status = 'del';
			$imagelink = "<img src='../../template/classic/img/delete.gif' />";
			$exlink = "<span class='invisible'>$title ($langWasDeleted)</span>";
		}
	} else {
                $work = mysql_fetch_array($r, MYSQL_ASSOC);
		$link = "<a href='${urlServer}modules/work/work.php?id=$work_id&amp;unit=$id'>";
                $exlink = $link . "$title</a>";
		$imagelink = $link .
                        "<img src='../../template/classic/img/assignments_" .
			($visibility == 'i'? 'off': 'on') . ".gif' /></a>";
	}
	$class_vis = ($visibility == 'v')? '': ' class="invisible"';
        if (!empty($comments)) {
                $comment_box = "<tr><td width='3%'>&nbsp;</td><td width='82%'>$comments</td>";
	}

	return "<tr$class_vis><td width='3%'>$imagelink</td><td width='82%'>$exlink</td>" .
		actions('lp', $resource_id, $visibility) .
		'</tr>' . $comment_box;
}


// display resource exercise
function show_exercise($title, $comments, $resource_id, $exercise_id, $visibility)
{
	global $id, $tool_content, $mysqlMainDb, $urlServer, $is_adminOfCourse,
               $langWasDeleted, $currentCourseID;

	$comment_box = $class_vis = $imagelink = $link = '';
        $title = htmlspecialchars($title);
	$r = db_query("SELECT * FROM exercices WHERE id = $exercise_id",
                      $currentCourseID);
	if (mysql_num_rows($r) == 0) { // check if it was deleted
		if (!$is_adminOfCourse) {
			return '';
		} else {
			$status = 'del';
			$imagelink = "<img src='../../template/classic/img/delete.gif' />";
			$exlink = "<span class='invisible'>$title ($langWasDeleted)</span>";
		}
	} else {
                $exercise = mysql_fetch_array($r, MYSQL_ASSOC);
		$link = "<a href='${urlServer}modules/exercice/exercice_submit.php?exerciseId=$exercise_id&amp;unit=$id'>";
                $exlink = $link . "$title</a>";
		$imagelink = $link .
                        "<img src='../../template/classic/img/exercise_" .
			($visibility == 'i'? 'off': 'on') . ".gif' /></a>";
	}
	$class_vis = ($visibility == 'v')? '': ' class="invisible"';
        if (!empty($comments)) {
                $comment_box = "<tr><td width='3%'>&nbsp;</td><td width='82%'>$comments</td>";
	}

	return "<tr$class_vis><td width='3%'>$imagelink</td><td width='82%'>$exlink</td>" .
		actions('lp', $resource_id, $visibility) .
		'</tr>' . $comment_box;
}


// display resource forum
function show_forum($type, $title, $comments, $resource_id, $ft_id, $visibility)
{
	global $id, $tool_content, $mysqlMainDb, $urlServer, $is_adminOfCourse, $currentCourseID;
	$comment_box = '';
	$class_vis = ($visibility == 'i')? ' class="invisible"': '';
        $title = htmlspecialchars($title);
	if ($type == 'forum') {
		$link = "<a href='${urlServer}modules/phpbb/viewforum.php?forum=$ft_id&amp;unit=$id'>";
                $forumlink = $link . "$title</a>";
	} else {
		$r = db_query("SELECT forum_id FROM topics WHERE topic_id = $ft_id", $currentCourseID);
		list($forum_id) = mysql_fetch_array($r);
		$link = "<a href='${urlServer}modules/phpbb/viewtopic.php?topic=$ft_id&amp;forum=$forum_id&amp;unit=$id'>";
                $forumlink = $link . "$title</a>";
	}

	$imagelink = $link . "<img src='../../template/classic/img/forum_" .
			($visibility == 'i'? 'off': 'on') . ".gif' /></a>";
        if (!empty($comments)) {
                $comment_box = "<tr><td width='3%'>&nbsp;</td><td width='82%'>$comments</td>";
	}

	return "<tr$class_vis><td width='3%'>$imagelink</td><td width='82%'>$forumlink</td>" .
		actions('forum', $resource_id, $visibility) .
		'</tr>' . $comment_box;
}


// display resource wiki
function show_wiki($title, $comments, $resource_id, $wiki_id, $visibility)
{
	global $id, $tool_content, $mysqlMainDb, $urlServer, $is_adminOfCourse,
               $langWasDeleted, $currentCourseID;

	$comment_box = $imagelink = $link = $class_vis = '';
	$class_vis = ($visibility == 'i')? ' class="invisible"': '';
        $title = htmlspecialchars($title);
	$r = db_query("SELECT * FROM wiki_properties WHERE id = $wiki_id",
                      $currentCourseID);
	if (mysql_num_rows($r) == 0) { // check if it was deleted
		if (!$is_adminOfCourse) {
			return '';
		} else {
			$status = 'del';
			$imagelink = "<img src='../../template/classic/img/delete.gif' />";
			$exlink = "<span class='invisible'>$title ($langWasDeleted)</span>";
		}
	} else {
                $wiki = mysql_fetch_array($r, MYSQL_ASSOC);
		$link = "<a href='${urlServer}modules/wiki/page.php?wikiId=$wiki_id&amp;action=show&amp;unit=$id'>";
                $wikilink = $link . "$title</a>";
		$imagelink = $link .
                        "<img src='../../template/classic/img/wiki_" .
			($visibility == 'i'? 'off': 'on') . ".gif' /></a>";
		
	}
        if (!empty($comments)) {
                $comment_box = "<tr><td width='3%'>&nbsp;</td><td width='82%'>$comments</td>";
	}

	return "<tr$class_vis><td width='3%'>$imagelink</td><td width='82%'>$wikilink</td>" .
		actions('wiki', $resource_id, $visibility) .
		'</tr>' . $comment_box;
}

// resource actions
function actions($res_type, $resource_id, $status, $res_id = false)
{
        global $is_adminOfCourse, $langEdit, $langDelete, $langVisibility, $langDown, $langUp, $mysqlMainDb;

        static $first = true;

	if (!$is_adminOfCourse) {
		return '';
	}

        if ($type = 'description') {
                $icon_vis = ($status == 'v')? 'checkbox_on.gif': 'checkbox_off.gif';
                $edit = "numBloc=$res_id";
        } else {
                $icon_vis = ($status == 'v')? 'visible.gif': 'invisible.gif';
                $edit = "edit=$resource_id";
        }

        if ($status != 'del') {
                $content = "<td width='3%'><a href='$_SERVER[PHP_SELF]?$edit'>" .
                "<img src='../../template/classic/img/edit.gif' title='$langEdit' /></a></td>";
        } else {
                $content = '<td width="3%">&nbsp;</td>';
        }
        $content .= "<td width='3%'><a href='$_SERVER[PHP_SELF]?del=$resource_id'" .
                                        " onClick=\"return confirmation();\">" .
                                        "<img src='../../template/classic/img/delete.gif' " .
                                        "title='$langDelete'></img></a></td>";
	 
	if ($status != 'del') {
		if (in_array($res_type, array('description', 'text', 'video', 'forum', 'topic'))) { 
			$content .= "<td width='3%'><a href='$_SERVER[PHP_SELF]?vis=$resource_id'>" .
                                        "<img src='../../template/classic/img/$icon_vis' " .
                                        "title='$langVisibility'></img></a></td>";
		} else {
			$content .= "<td width='3%'>&nbsp;</td>";
		}
        } else {
                $content .= '<td width="3%">&nbsp;</td>';
        }
        if ($resource_id != $GLOBALS['max_resource_id']) {
                $content .= "<td width='3%'><a href='$_SERVER[PHP_SELF]?down=$resource_id'>" .
                        "<img src='../../template/classic/img/down.gif' title='$langDown'></img></a></td>";
	} else {
		$content .= "<td width='3%'>&nbsp;</td>";
	}
        if (!$first) {
                $content .= "<td width='3%'><a href='$_SERVER[PHP_SELF]?up=$resource_id'>" .
                        "<img src='../../template/classic/img/up.gif' title='$langUp'></img></a></td>";
        } else {
                $content .= "<td width='3%'>&nbsp;</td>";
        }
        $first = false;
        return $content;
}


// edit resource
function edit_res($resource_id) 
{
	global $tool_content, $id, $urlServer, $langTitle, $langDescr, $langContents, $langModify;
	 
        $sql = db_query("SELECT id, title, comments, type FROM unit_resources WHERE id='$resource_id'");
        $ru = mysql_fetch_array($sql);
        $restitle = " value='" . htmlspecialchars($ru['title'], ENT_QUOTES) . "'";
        $rescomments = $ru['comments'];
        $resource_id = $ru['id'];
        $resource_type = $ru['type'];

	$tool_content .= "<form method='post' action='${urlServer}modules/units/'>";
	$tool_content .= "<input type='hidden' name='id' value='$id'>";
	$tool_content .= "<input type='hidden' name='resource_id' value='$resource_id'>";
	$tool_content .= "<table class='FormData'><tbody>";
	if ($resource_type != 'text') {
		$tool_content .= "<tr><th width='150' class='left'>$langTitle:</th>
		<td><input type='text' name='restitle' size='50' maxlength='255' $restitle class='FormData_InputText'></td></tr>";
		$message = $langDescr;
	} else {
		$message = $langContents;
	}
        $tool_content .= "<tr><th class='left'>$message:</th><td>
        <table class='xinha_editor'><tr><td>".
	rich_text_editor('rescomments', 4, 20, $rescomments)
	."</td></tr>
        </table></td></tr>
        <tr><th>&nbsp;</th>
	<td><input type='submit' name='edit_res_submit' value='$langModify'></td></tr>
	</tbody></table>
	</form>";
}
