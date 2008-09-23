<?
/*===========================================================================
*   Open eClass 2.1
*   E-learning and Course Management System
* ===========================================================================
*	Copyright(c) 2003-2008  Greek Universities Network - GUnet
*	A full copyright notice can be read in "/info/copyright.txt".
*
*  	Authors:	Costas Tsibanis <k.tsibanis@noc.uoa.gr>
*				Yannis Exidaridis <jexi@noc.uoa.gr>
*				Alexandros Diamantidis <adia@noc.uoa.gr>
*
*	For a full list of contributors, see "credits.txt".
*
*	This program is a free software under the terms of the GNU
*	(General Public License) as published by the Free Software
*	Foundation. See the GNU License for more details.
*	The full license can be read in "license.txt".
*
*	Contact address: 	GUnet Asynchronous Teleteaching Group,
*				Network Operations Center, University of Athens,
*				Panepistimiopolis Ilissia, 15784, Athens, Greece
*				eMail: eclassadmin@gunet.gr
============================================================================*/

/*===========================================================================
	ldapsearch.php
	@authors list: Karatzidis Stratos <kstratos@uom.gr>
		       Vagelis Pitsioygas <vagpits@uom.gr>
==============================================================================
  @Description: This script/file tries to authenticate the user, using
  his user/pass pair and the authentication method defined by the admin

==============================================================================
*/

$require_admin = TRUE;

include '../../include/baseTheme.php';
include '../../include/sendMail.inc.php';
require_once 'auth.inc.php';

$msg = "$langProfReg (".(get_auth_info($auth)).")";
$nameTools = $msg;
$navigation[] = array("url" => "../admin/index.php", "name" => $langAdmin);
$navigation[] = array("url" => "../admin/listreq.php", "name" => $langOpenProfessorRequests);
$tool_content = "";

// -----------------------------------------
// 		professor registration
// -----------------------------------------

if (isset($submit))  {
      $auth = $_POST['auth'];
      $pn = $_POST['pn'];
      $ps = $_POST['ps'];
      $pu = $_POST['pu'];
      $pe = $_POST['pe'];
      $department = $_POST['department'];
	
	$localize = isset($_POST['localize'])?$_POST['localize']:'';
	if ($localize == 'greek')
		$lang = 'el';
	elseif ($localize == 'english')
		$lang = 'en';

	// check if user name exists
    	$username_check=mysql_query("SELECT username FROM `$mysqlMainDb`.user WHERE username='".escapeSimple($pu)."'");
	 while ($myusername = mysql_fetch_array($username_check))
  	  {
    	 	 $user_exist=$myusername[0];
	  }
	
	if(isset($user_exist) and $pu == $user_exist) {
	     $tool_content .= "<p class=\"caution_small\">$langUserFree</p><br><br><p align=\"right\"><a href='../admin/listreq.php'>$langBackReq</a></p>";
		 draw($tool_content,0,'auth');
	     exit();
	}

        switch($auth)
        {
          case '2': $password = "pop3";
            break;
          case '3': $password = "imap";
            break;
          case '4': $password = "ldap";
            break;
          case '5': $password = "db";
            break;
          default:  $password = "";
            break;
        }

	$registered_at = time();
        $expires_at = time() + $durationAccount;

	$sql=db_query("INSERT INTO user (user_id, nom, prenom, username, password, email, statut, department, registered_at, expires_at, lang)
       VALUES ('NULL', '$pn', '$ps', '$pu', '$password', '$pe','1','$department', '$registered_at', '$expires_at', '$lang')", $mysqlMainDb);

	// close request
      //  Update table prof_request ------------------------------
      $rid = intval($_POST['rid']);
      db_query("UPDATE prof_request set status = '2',date_closed = NOW() WHERE rid = '$rid'");
		$emailbody = "$langDestination $pu $ps\n" .
                                "$langYouAreReg $siteName $langSettings $pu\n" .
                                "$langPass: $password\n$langAddress $siteName: " .
                                "$urlServer\n$langProblem\n$langFormula" .
                                "$administratorName $administratorSurname" .
                                "$langManager $siteName \n$langTel $telephone \n" .
                                "$langEmail: $emailAdministrator";

    if (!send_mail($gunet, $emailAdministrator, '', $emailhelpdesk, $mailsubject, $emailbody, $charset))  {
		      $tool_content .= "<table width=\"99%\"><tbody><tr>
    	    	<td class=\"caution\" height='60'>
	    	    <p>$langMailErrorMessage &nbsp; <a href=\"mailto:$emailhelpdesk\">$emailhelpdesk</a></p>
  	    	  </td>
    	    	</tr></tbody></table>";
      	  draw($tool_content,0);
        	exit();
      }

      //------------------------------------User Message ----------------------------------------
    $tool_content .= "<table width=\"99%\"><tbody>
      <tr>
      <td class=\"well-done\" height='60'>
	<p>$profsuccess</p><br><br>
	<center><p><a href='../admin/listreq.php'>$langBackReq</a></p></center>
      </td>
      </tr></tbody></table>";

} else {  // display the form

// if not submit then display the form
if (isset($_GET['lang'])) {
	$lang = $_GET['lang'];
	if ($lang == 'el')
		$language = 'greek';
	elseif ($lang == 'en')
		$language = 'english';
}

	$tool_content .= "<form action=\"$_SERVER[PHP_SELF]\" method=\"post\">
  <table width=\"99%\" class=\"FormData\">
  <tbody>
  <tr>
    <th width=\"220\">&nbsp;</th>
    <td><b>$langNewProf</b></td>
  </tr>
  <tr>
    <th class='left'><b>".$langSurname."</b></th>
    <td>$ps</td>
  </tr>
  <input type=\"hidden\" name=\"ps\" value=\"$ps\">
  <tr>
    <th class='left'><b>".$langName."</b></th>
    <td>$pn</td>
  </tr>
  <input type=\"hidden\" name=\"pn\" value=\"$pn\">
  <tr>
    <th class='left'><b>".$langUsername."</b></th>
    <td>$pu</td>
  <input type=\"hidden\" name=\"pu\" value=\"$pu\">
  </tr>
  <tr>
    <th class='left'><b>".$langEmail."</b></th>
    <td>$pe</b></td>
    <input type=\"hidden\" name=\"pe\" value=\"$pe\" >
  </tr>
  <tr>
    <th class='left'>".$langDepartment.":</th>
    <td><select name=\"department\">";
		$deps=mysql_query("SELECT name, id FROM faculte ORDER BY id");
		while ($dep = mysql_fetch_array($deps))
			  $tool_content .= "\n      <option value=\"".$dep[1]."\">".$dep[0]."</option>";
        $tool_content .= "</select>
    </td>
  </tr>
	<tr>
      <th class='left'>$langLanguage</th>
      <td>";
	$tool_content .= lang_select_options('localize');
	$tool_content .= "</td>
    </tr>
  <tr>
    <th>&nbsp;</th>
    <td><input type=\"submit\" name=\"submit\" value=\"".$langOk."\" >
        <input type=\"hidden\" name=\"auth\" value=\"$auth\" >
    </td>
  </tr>
  <input type='hidden' name='rid' value='".@$id."'>
  </tbody>
  </table>
</form>";
 }
draw($tool_content,0,'auth');
?>
