<?php
/**=============================================================================
       	GUnet e-Class 2.0 
        E-learning and Course Management Program  
================================================================================
       	Copyright(c) 2003-2006  Greek Universities Network - GUnet
        A full copyright notice can be read in "/info/copyright.txt".
        
       	Authors:    Costas Tsibanis <k.tsibanis@noc.uoa.gr>
        	    Yannis Exidaridis <jexi@noc.uoa.gr> 
      		    Alexandros Diamantidis <adia@noc.uoa.gr> 

        For a full list of contributors, see "credits.txt".  
     
        This program is a free software under the terms of the GNU 
        (General Public License) as published by the Free Software 
        Foundation. See the GNU License for more details. 
        The full license can be read in "license.txt".
     
       	Contact address: GUnet Asynchronous Teleteaching Group, 
        Network Operations Center, University of Athens, 
        Panepistimiopolis Ilissia, 15784, Athens, Greece
        eMail: eclassadmin@gunet.gr
==============================================================================*/

/**===========================================================================
        phpbb/newtopic.php
        @last update: 2006-07-15 by Artemios G. Voyiatzis
        @authors list: Artemios G. Voyiatzis <bogart@upnet.gr>

        based on Claroline version 1.7 licensed under GPL
              copyright (c) 2001, 2006 Universite catholique de Louvain (UCL)

        Claroline authors: Piraux Sébastien <pir@cerdecam.be>
                      Lederer Guillaume <led@cerdecam.be>

	based on phpBB version 1.4.1 licensed under GPL
		copyright (c) 2001, The phpBB Group
==============================================================================
    @Description: This module implements a per course forum for supporting
	discussions between teachers and students or group of students.
	It is a heavily modified adaptation of phpBB for (initially) Claroline
	and (later) eclass. In the future, a new forum should be developed.
	Currently we use only a fraction of phpBB tables and functionality
	(viewforum, viewtopic, post_reply, newtopic); the time cost is
	enormous for both core phpBB code upgrades and migration from an
	existing (phpBB-based) to a new eclass forum :-(

    @Comments:

    @todo:
==============================================================================
*/

error_reporting(E_ALL);
/*
 * GUNET eclass 2.0 standard stuff
 */
$require_current_course = TRUE;
$require_login = TRUE;
$langFiles = 'phpbb';
$require_help = FALSE;
include '../../include/baseTheme.php';
$nameTools = $l_forums;
$tool_content = "";

/*
 * Tool-specific includes
 */
include_once("./config.php");
include("functions.php"); // application logic for phpBB

/******************************************************************************
 * Actual code starts here
 *****************************************************************************/

$sql = "SELECT forum_name, forum_access, forum_type
	FROM forums
	WHERE (forum_id = '$forum')";
if (!$result = db_query($sql, $currentCourseID)) {
	//XXX: Error message in specified language
	$tool_content .= "Can't get forum data.";
	draw($tool_content,2);
	exit;
}
$myrow = mysql_fetch_array($result);
$forum_name = $myrow["forum_name"];
$forum_access = $myrow["forum_access"];
$forum_type = $myrow["forum_type"];
$forum_id = $forum;

if (!does_exists($forum, $currentCourseID, "forum")) {
	//XXX: Error message in specified language
	$tool_content .= "The forum you are attempting to post to does not exist. Please try again.";
}

if (isset($submit) && $submit) {
	$subject = strip_tags($subject);
	if (trim($message) == '' || trim($subject) == '') {
		$tool_content .= $l_emptymsg;
		draw($tool_content, 2);
		exit;
	}
	if ( !isset($username) ) {
		$username = "";
	}
	$userdata = get_userdata($username, $db);
	if($forum_access == 3 && $userdata["user_level"] < 2) {
		$tool_content .= $l_nopost;
		draw($tool_content, 2);
		exit;
	}
	// Either valid user/pass, or valid session. continue with post.. but first:
	// Check that, if this is a private forum, the current user can post here.
	      
	if ($forum_type == 1) {
		if (!check_priv_forum_auth($userdata['user_id'], $forum, TRUE, $currentCourseID)) {
			$tool_content .= "$l_privateforum $l_nopost";
			draw($tool_content, 2);
			exit();
		}
	}
	$is_html_disabled = false;
	if ( (isset($allow_html) && $allow_html == 0) || isset($html)) {
		$message = htmlspecialchars($message);
		$is_html_disabled = true;
	}
	if ( (isset($allow_bbcode) && $allow_bbcode == 1) && !($_POST['bbcode'])) {
		$message = bbencode($message, $is_html_disabled);
	}
	// MUST do make_clickable() and smile() before changing \n into <br>.
	$message = make_clickable($message);
	if (isset($smile) && !$smile) {
		$message = smile($message);
	}
	$message = str_replace("\n", "<BR>", $message);
	$message = str_replace("<w>", "<s><font color=red>", $message);
	$message = str_replace("</w>", "</font color></s>", $message);
	$message = str_replace("<r>", "<font color=#0000FF>", $message);
	$message = str_replace("</r>", "</font color>", $message);
	$message = censor_string($message, $currentCourseID);
	$message = addslashes($message);
	$subject = strip_tags($subject);
	$subject = censor_string($subject, $currentCourseID);
	$subject = addslashes($subject);
	$poster_ip = $REMOTE_ADDR;
	$time = date("Y-m-d H:i");
	// ADDED BY Thomas 20.2.2002
	$nom = addslashes($nom);
	$prenom = addslashes($prenom);
	// END ADDED BY THOMAS

	//to prevent [addsig] from getting in the way, let's put the sig insert down here.
	if (isset($sig) && $sig && $userdata['user_id'] != -1) {
		$message .= "\n[addsig]";
	}
	$sql = "INSERT INTO topics (topic_title, topic_poster, forum_id, topic_time, topic_notify, nom, prenom)
			VALUES ('$subject', '" . $userdata["user_id"] . "', '$forum', '$time', 1, '$nom', '$prenom')";
	if (!$result = db_query($sql, $currentCourseID)) {
		$tool_content .= "Couldn't enter topic in database.";
		draw($tool_content, 2);
		exit();
	}

	$topic_id = mysql_insert_id();
	$sql = "INSERT INTO posts (topic_id, forum_id, poster_id, post_time, poster_ip, nom, prenom)
			VALUES ('$topic_id', '$forum', '$userdata[user_id]', '$time', '$poster_ip', '$nom', '$prenom')";
	if (!$result = db_query($sql, $currentCourseID)) {
		$tool_content .= "Couldn't enter post in database.";
		draw($tool_content, 2);
		exit();
	} else {
		$post_id = mysql_insert_id();
		if ($post_id) {
			$sql = "INSERT INTO posts_text (post_id, post_text)
					VALUES ($post_id, '$message')";
			if (!$result = db_query($sql, $currentCourseID)) {
				$tool_content .= "Could not enter post text!";
				draw($tool_content, 2);
				exit();
			}
			$sql = "UPDATE topics
				SET topic_last_post_id = $post_id
				WHERE topic_id = '$topic_id'";
			if (!$result = db_query($sql, $currentCourseID)) {
				$tool_content .= "Could not update topics table!";
				draw($tool_content, 2);
				exit();
			}
		}
	}
	if ($userdata["user_id"] != -1 && $userdata["user_id"] != "") {
		$sql = "UPDATE users
			SET user_posts=user_posts+1
			WHERE (user_id = " . $userdata["user_id"] . ")";
		$result = db_query($sql, $currentCourseID);
		if (!$result) {
			$tool_content .= "Couldn't update users post count.";
			draw($tool_content, 2);
			exit();
		}
	}

	$sql = "UPDATE forums
		SET forum_posts = forum_posts+1, forum_topics = forum_topics+1, forum_last_post_id = $post_id
		WHERE forum_id = '$forum'";
	$result = db_query($sql, $currentCourseID);
	if (!$result) {
		$tool_content .= "Couldn't update forums post count.";
		draw($tool_content, 2);
		exit();
	}                              
	$topic = $topic_id;
	$total_forum = get_total_topics($forum, $currentCourseID);
	$total_topic = get_total_posts($topic, $currentCourseID, "topic")-1;  
	// Subtract 1 because we want the nr of replies, not the nr of posts.
	$forward = 1;

	$tool_content .= "<br>
		<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACEING=\"0\" ALIGN=\"CENTER\" VALIGN=\"TOP\" WIDTH=\"99%\">
		<TR><TD>
			<TABLE BORDER=\"0\" CALLPADDING=\"1\" CELLSPACEING=\"1\" WIDTH=\"100%\">
			<TR><TD>
				<P>
				<P>
				<BR>
				<center>
				$l_stored<P>$l_click
				<a href=\"viewtopic.php?topic=$topic_id&forum=$forum&$total_topic\">$l_here</a>$l_viewmsg
				<p>
				$l_click <a href=\"viewforum.php?forum=$forum_id&total_forum\">$l_here</a> $l_returntopic
				</center>
				<P>
			</TD></TR>
			</TABLE>
		</TD></TR>
		</TABLE>
		<br>"; 
} else {
	// ADDED BY CLAROLINE: exclude non identified visitors
	if (!$uid AND !$fakeUid) {
		$tool_content .= "
				<center>
				<br>
				<br>
				$langLoginBeforePost1<br>$langLoginBeforePost2
				<a href=../../index.php>$langLoginBeforePost3.</a>
				</center>";
		draw($tool_content, 0);
		exit();
	}
	// END ADDED BY CLAROLINE exclude visitors unidentified
	$tool_content .= "
		<FORM ACTION=\"$PHP_SELF\" METHOD=\"POST\">
		<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"0\" ALIGN=\"CENTER\" VALIGN=\"TOP\" WIDTH=\"99%\">
		<TR><TD>
			<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"1\" WIDTH=\"99%\">
			<TR><TD width=\"20%\">
				<b>$l_subject:</b>
			    </TD>
			    <TD>
				<INPUT TYPE=\"TEXT\" NAME=\"subject\" SIZE=\"50\" MAXLENGTH=\"100\">
			    </TD>
			</TR>
			<TR><TD width=\"20%\">
				<b>$l_body:</b>
				<br>
				<br>
			    </TD>
			    <TD>
				<TEXTAREA NAME=\"message\" ROWS=14 COLS=50 WRAP=\"VIRTUAL\"></TEXTAREA>
			    </TD>
			</TR>
			<TR><TD colspan=2 ALIGN=\"CENTER\">
				<INPUT TYPE=\"HIDDEN\" NAME=\"forum\" VALUE=\"$forum\">
				<INPUT TYPE=\"SUBMIT\" NAME=\"submit\" VALUE=\"$l_submit\">&nbsp;
				<INPUT TYPE=\"SUBMIT\" NAME=\"cancel\" VALUE=\"$l_cancelpost\">
			</TD></TR>
			</TABLE>
		</TD></TR>
		</TABLE>
		</FORM>";
}
draw($tool_content, 2);
?>
