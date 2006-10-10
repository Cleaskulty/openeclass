<?
/**=============================================================================
       	GUnet e-Class 2.0 
        E-learning and Course Management Program  
================================================================================
       	Copyright(c) 2003-2006  Greek Universities Network - GUnet
        � full copyright notice can be read in "/info/copyright.txt".
        
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

/*===========================================================================
	search_loggedin.php
	@last update: 05-10-2006 by Sakis Agorastos
	@authors list: Agorastos Sakis <th_agorastos@hotmail.com>
==============================================================================        
    @Description: Search function that searches data within public access
    courses and courses to which the student enrolled. In case the user is
    an administrator the script shows all the courses the user manages
    This script is intened to be used by logged-in users (students/adminsitrators)
==============================================================================*/

$require_current_course = FALSE;
$langFiles = array('course_info', 'create_course', 'opencours', 'search');
//include '../../include/baseTheme.php';

$nameTools = $langSearch;
$tool_content = "";




//elegxos ean *yparxoun* oroi anazhthshs
if(empty($search_terms_title) && empty($search_terms_keywords) && empty($search_terms_instructor) && empty($search_terms_coursecode)) {
/**********************************************************************************************
				emfanish formas anahzthshs ean oi oroi anazhthshs einai kenoi 
***********************************************************************************************/
	
		$tool_content .= "
			<form method=\"post\" action=\"$_SERVER[PHP_SELF]\">
			<table width=\"99%\">
			<thead>
				<tr>
					<th colspan=\"2\">$langSearchWith</th>
				</tr>
			</thead>
				<tr>
				<td valign=\"middle\" align=\"right\">
					<b>$langTitle</b>
				</td>				
				<td valign=\"middle\">
					<input name=\"search_terms_title\" type=\"text\" size=\"80\" />
				</td>
				</tr>
				<tr>
				<td valign=\"middle\" align=\"right\">
					<b>$langKeywords</b>
				</td>				
				<td valign=\"middle\">
					<input name=\"search_terms_keywords\" type=\"text\" size=\"80\" />
				</td>
				</tr>
				<tr>
				<td valign=\"middle\" align=\"right\">
					<b>$langInstructor</b>
				</td>				
				<td valign=\"middle\">
					<input name=\"search_terms_instructor\" type=\"text\" size=\"80\" />
				</td>
				</tr>
				<tr>
				<td valign=\"middle\" align=\"right\">
					<b>$langCourseCode</b>
				</td>				
				<td valign=\"middle\">
					<input name=\"search_terms_coursecode\" type=\"text\" size=\"80\" />
				</td>
				</tr>
			</tr>								
			<tr>
				<td colspan=\"2\" align=\"center\">
					<input type=\"Submit\" name=\"submit\" value=\"$langDoSearch\" />
					&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
					<input type=\"Reset\" name=\"reset\" value=\"$langNewSearch\" />
				</td>
			</tr>
			</table>
			</form>
		";
	
}else 
{
/**********************************************************************************************
					ektelesh anazhthshs afou yparxoun oroi anazhthshs
						 emfanish arikown mhnymatwn anazhthshs
***********************************************************************************************/
	
	//ektypwsh mhnymatos anazhthshs
	$tool_content .= "<h2>$langSearchingFor</h2>";

	
	//elegxos gia ton typo xrhsth
	if($is_admin == TRUE)
	{
		$result = mysql_query("SELECT * FROM cours, cours_user WHERE cours.code=cours_user.code_cours AND cours_user.user_id='".$uid."' AND (cours_user.statut='5' OR cours_user.statut='10' OR cours_user.statut='1')");
	}else
	{
		$result = mysql_query("SELECT * FROM cours, cours_user WHERE cours.code=cours_user.code_cours AND cours_user.user_id='".$uid."' AND (cours_user.statut='5' OR cours_user.statut='10')");
	}
	
	
	
	$results_found = 0; //arithmos apotelesmatwn pou exoun emfanistei (ena gia kathe grammh tou $mycours)
	
	
	//*****************************************************************************************************
	//vrogxos gia na diatreksoume ola ta mathimata sta opoia enai anoixta (public OR open for registration)
    while ($mycours = mysql_fetch_row($result)) 
    {
    	
		$show_entry = FALSE; //flag gia emfanish apotelesmatwn se mia grammh tou array efoson entopistoun apotelesmata				
				
		$show_entry = match_arrays($search_terms_title, $mycours[3]);
		if($show_entry == FALSE) $show_entry = match_arrays($search_terms_keywords, $mycours[7]);
		if($show_entry == FALSE) $show_entry = match_arrays($search_terms_instructor, $mycours[13]);
		if($show_entry == FALSE) $show_entry = match_arrays($search_terms_coursecode, $mycours[1]);
		
		
		//EMFANISH APOTELESMATOS:
		//ean to flag $show_entry exei allaxtei se TRUE (ara kapoios apo tous orous anazhthshs entopistike sto
		//$mycours, emfanise thn eggrafh
		if($show_entry)
		{			
			$tool_content .= "<br><table width=\"90%\"><tr><td>".$langTitle.": <strong>".$mycours[3]."</strong><br>";
			$tool_content .= $langCourseCode.": <strong>".$mycours[1]."</strong><br>";
			$tool_content .= $langKeywords.": <strong>".$mycours[7]."</strong><br>";
			$tool_content .= $langInstructor.": <strong>".$mycours[13]."</strong><br>";			
			$tool_content .= "<a href=\"../../courses/".$mycours[1]."/\">".$langEnter."</a>";
			$tool_content .= "</td></tr></table><br>";

			
			$tool_content .= "";
			
			
			
			//afkhsh tou arithmou apotelesmatwn
			$results_found++;			
		}
		
    }
    
    //elegxos tou arithmou twn apotelesmatwn pou exoun emfanistei. ean den emfanistike kanena apotelesma, ektypwsh analogou mhnymatos
    if($results_found == 0) $tool_content .= "<br>".$langNoResult;
    
    //ektypwsh syndesmou gia nea anazhthsh
    $tool_content .= "<p align=\"center\"><a href=\"search.php\">$langNewSearch</a></p>";
    
}

draw($tool_content, 0);


//katharisma twn orwn anazhthshs gia apofygh lathwn
$search_terms_title = "";
$search_terms_keywords = "";
$search_terms_instructor = "";
$search_terms_coursecode ="";





//voithitiki function gia ton entopismo strings se array mesa se string
function match_arrays($search_terms_array, $mycours_string)
{
	//elegxos gia to an yparxoun apotelesmata sthn trexousa grammh toy $mycours_array
		if(!empty($term) || !empty($mycours_string))
		{
			//echo "compare: ".$search_terms_array." == ".$mycours_string;
			$ret = strcmp($search_terms_array, $mycours_string);
			//if($ret == 0) echo " MATCH!<br>";
			if($ret == 0) return TRUE;
			
			//echo "<br>";
		}
		
	return FALSE;
}
?>
