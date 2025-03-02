<?php
/**
* Functions for exporting data
*
* phpGedView: Genealogy Viewer
* Copyright (C) 2002 to 2009 PGV Development Team.  All rights reserved.
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*
* @package PhpGedView
* @subpackage Admin
* @version $Id: functions_export.php 6481 2009-11-28 20:17:25Z fisharebest $
*/

define('PGV_FUNCTIONS_EXPORT_PHP', '');

require_once '../components/com_manager/php/gedcom/Parser/includes/classes/class_gedownloadgedcom.php';

// Tidy up a gedcom record on export, for compatibility/portability
function reformat_record_export($rec) {
	global $WORD_WRAPPED_NOTES;

	$newrec='';
	foreach (preg_split('/[\r\n]+/', $rec, -1, PREG_SPLIT_NO_EMPTY) as $line) {
		// Escape @ characters
		// TODO:
		// Need to replace '@' with '@@', unless it is either
		// a) an xref, such as @I123@
		// b) an escape, such as @#D FRENCH R@
		if (false) {
			$line=str_replace('@', '@@', $line);
		}
		// Split long lines
		// The total length of a GEDCOM line, including level number, cross-reference number,
		// tag, value, delimiters, and terminator, must not exceed 255 (wide) characters.
		// Use quick strlen() check before using slower UTF8_strlen() check
		if (strlen($line)>PGV_GEDCOM_LINE_LENGTH && UTF8_strlen($line)>PGV_GEDCOM_LINE_LENGTH) {
			list($level, $tag)=explode(' ', $line, 3);
			if ($tag!='CONT' && $tag!='CONC') {
				$level++;
			}
			do {
				// Split after $pos chars
				$pos=PGV_GEDCOM_LINE_LENGTH;
				if ($WORD_WRAPPED_NOTES) {
					// Split on a space, and remove it (for compatibility with some desktop apps)
					while ($pos && UTF8_substr($line, $pos-1, 1)!=' ') {
						--$pos;
					}
					if ($pos==strpos($line, ' ', 3)+1) {
						// No spaces in the data! Can't split it :-(
						break;
					} else {
						$newrec.=UTF8_substr($line, 0, $pos-1).PGV_EOL;
						$line=$level.' CONC '.UTF8_substr($line, $pos);
					}
				} else {
					// Split on a non-space (standard gedcom behaviour)
					while ($pos && UTF8_substr($line, $pos-1, 1)==' ') {
						--$pos;
					}
					if ($pos==strpos($line, ' ', 3)) {
						// No non-spaces in the data! Can't split it :-(
						break;
					}
					$newrec.=UTF8_substr($line, 0, $pos).PGV_EOL;
					$line=$level.' CONC '.UTF8_substr($line, $pos);
				}
			} while (UTF8_strlen($line)>PGV_GEDCOM_LINE_LENGTH);
		}
		$newrec.=$line.PGV_EOL;
	}
	return $newrec;
}

/*
* Create a header for a (newly-created or already-imported) gedcom file.
*/
function gedcom_header($gedfile) {
	global $CHARACTER_SET, $pgv_lang, $TBLPREFIX;

	$ged_id=get_id_from_gedcom($gedfile);

	// Default values for a new header
	$HEAD="0 HEAD";
	$SOUR="\n1 SOUR ".PGV_PHPGEDVIEW."\n2 NAME ".PGV_PHPGEDVIEW."\n2 VERS ".PGV_VERSION_TEXT;
	$DEST="\n1 DEST DISKETTE";
	$DATE="\n1 DATE ".strtoupper(date("d M Y"))."\n2 TIME ".date("H:i:s");
	$GEDC="\n1 GEDC\n2 VERS 5.5.1\n2 FORM Lineage-Linked";
	$CHAR="\n1 CHAR {$CHARACTER_SET}";
	$FILE="\n1 FILE {$gedfile}";
	$LANG="";
	$PLAC="\n1 PLAC\n2 FORM {$pgv_lang['default_form']}";
	$COPR="";
	$SUBN="";
	$SUBM="\n1 SUBM @SUBM@\n0 @SUBM@ SUBM\n1 NAME ".PGV_USER_NAME; // The SUBM record is mandatory

	// Preserve some values from the original header
	if (get_gedcom_setting($ged_id, 'imported')) {
		$head=find_gedcom_record("HEAD", $ged_id);
		if (preg_match("/\n1 CHAR .+/", $head, $match)) {
			$CHAR=$match[0];
		}
		if (preg_match("/\n1 PLAC\n2 FORM .+/", $head, $match)) {
			$PLAC=$match[0];
		}
		if (preg_match("/\n1 LANG .+/", $head, $match)) {
			$LANG=$match[0];
		}
		if (preg_match("/\n1 SUBN .+/", $head, $match)) {
			$SUBN=$match[0];
		}
		if (preg_match("/\n1 COPR .+/", $head, $match)) {
			$COPR=$match[0];
		}
		// Link to SUBM/SUBN records, if they exist
		$subn=
			PGV_DB::prepare("SELECT o_id FROM ${TBLPREFIX}other WHERE o_type=? AND o_file=?")
			->execute(array('SUBN', $ged_id))
			->fetchOne();
		if ($subn) {
			$SUBN="\n1 SUBN @{$subn}@";
		}
		$subm=
			PGV_DB::prepare("SELECT o_id FROM ${TBLPREFIX}other WHERE o_type=? AND o_file=?")
			->execute(array('SUBM', $ged_id))
			->fetchOne();
		if ($subm) {
			$SUBM="\n1 SUBM @{$subm}@";
		}
	}

	return $HEAD.$SOUR.$DEST.$DATE.$GEDC.$CHAR.$FILE.$COPR.$LANG.$PLAC.$SUBN.$SUBM."\n";
}

/**
 * Create a temporary user, and assign rights as specified
 */
function createTempUser($userID, $rights, $gedcom) {
	if ($tempUserID=get_user_id($userID)) {
		delete_user($tempUserID);
		AddToLog("deleted dummy user -> {$userID} <-, which was not deleted in a previous session");
	}

	$tempUserID=create_user($userID, md5(rand()));
	if (!$tempUserID) return false;

	set_user_setting($tempUserID, 'relationship_privacy', 'N');
	set_user_setting($tempUserID, 'max_relation_path', '0');
	set_user_setting($tempUserID, 'visibleonline', 'N');
	set_user_setting($tempUserID, 'contactmethod', 'none');
	switch ($rights) {
	case 'admin':
		set_user_setting($tempUserID, 'canadmin', 'Y');
		set_user_gedcom_setting($tempUserID, $gedcom, 'canedit', 'admin');
	case 'gedadmin':
		set_user_setting($tempUserID, 'canadmin', 'N');
		set_user_gedcom_setting($tempUserID, $gedcom, 'canedit', 'admin');
		break;
	case 'user':
		set_user_setting($tempUserID, 'canadmin', 'N');
		set_user_gedcom_setting($tempUserID, $gedcom, 'canedit', 'access');
		break;
	case 'visitor':
	default:
		set_user_setting($tempUserID, 'canadmin', 'N');
		set_user_gedcom_setting($tempUserID, $gedcom, 'canedit', 'none');
		break;
	}
	AddToLog("created dummy user -> {$userID} <- with level {$rights} to GEDCOM {$gedcom}");

	// Save things in cache
	$_SESSION["pgv_GEDCOM"]				= $gedcom;
	$_SESSION["pgv_GED_ID"]				= get_id_from_gedcom($_SESSION["pgv_GEDCOM"]);
	$_SESSION["pgv_USER_ID"]			= $userID;
	$_SESSION["pgv_USER_NAME"]			= 'Not Relevant';
	$_SESSION["pgv_USER_GEDCOM_ADMIN"]	= userGedcomAdmin   ($_SESSION["pgv_USER_ID"], $_SESSION["pgv_GED_ID"]);
	$_SESSION["pgv_USER_CAN_ACCESS"]	= userCanAccess     ($_SESSION["pgv_USER_ID"], $_SESSION["pgv_GED_ID"]);
	$_SESSION["pgv_USER_ACCESS_LEVEL"]	= getUserAccessLevel($_SESSION["pgv_USER_ID"], $_SESSION["pgv_GED_ID"]);
	$_SESSION["pgv_USER_GEDCOM_ID"]		= get_user_gedcom_setting($_SESSION["pgv_USER_ID"], $_SESSION["pgv_GED_ID"], 'gedcomid');

	return $tempUserID;
}

/**
 * remove any custom PGV tags from the given gedcom record
 * custom tags include _PGVU and _THUM
 * @param string $gedrec	the raw gedcom record
 * @return string		the updated gedcom record
 */
function remove_custom_tags($gedrec, $remove="no") {
	if ($remove=="yes") {
		//-- remove _PGVU
		$gedrec = preg_replace("/\d _PGVU .*/", "", $gedrec);
		//-- remove _THUM
		$gedrec = preg_replace("/\d _THUM .*/", "", $gedrec);
	}
	//-- cleanup so there are not any empty lines
	$gedrec = preg_replace(array("/(\r\n)+/", "/\r+/", "/\n+/"), array("\r\n", "\r", "\n"), $gedrec);
	//-- make downloaded file DOS formatted
	$gedrec = preg_replace("/([^\r])\n/", "$1\n", $gedrec);
	return $gedrec;
}

/**
 * Convert media path by:
 *	- removing current media directory
 *	- adding a new prefix
 *	- making directory name separators consistent
 */
function convert_media_path($rec, $path, $slashes) {
	global $MEDIA_DIRECTORY;

	$file = get_gedcom_value("FILE", 1, $rec);
	if (preg_match("~^https?://~i", $file)) return $rec;	// don't modify URLs

	$rec = str_replace('FILE '.$MEDIA_DIRECTORY, 'FILE '.trim($path).'/', $rec);
	$rec = str_replace('\\', '/', $rec);
	$rec = str_replace('//', '/', $rec);
	if ($slashes=='backward') $rec = str_replace('/', '\\', $rec);
	return $rec;
}

/*
 *	Export the database in GEDCOM format
 *
 *  input parameters:
 *		$gedcom:	GEDCOM to be exported
 *		$gedout:	Handle of output file
 *		$exportOptions:	array of options for this Export operation as follows:
 *			'privatize':	which Privacy rules apply?  (none, visitor, user, GEDCOM admin, site admin)
 *			'toANSI':		should the output be produced in ANSI instead of UTF-8?  (yes, no)
 *			'noCustomTags':	should custom tags be removed?  (yes, no)
 *			'path':			what constant should prefix all media file paths?  (eg: media/  or c:\my pictures\my family
 *			'slashes':		what folder separators apply to media file paths?  (forward, backward)
 */
function export_gedcom($gedcom, $gedout, $exportOptions) {
	global $GEDCOM, $pgv_lang, $CHARACTER_SET;
	global $TBLPREFIX;

	// Temporarily switch to the specified GEDCOM
	$oldGEDCOM = $GEDCOM;
	$GEDCOM = $gedcom;
	$ged_id=get_id_from_gedcom($gedcom);

	$tempUserID = '#ExPoRt#';
	if ($exportOptions['privatize']!='none') {
		// Create a temporary userid
		$export_user_id = createTempUser($tempUserID, $exportOptions['privatize'], $gedcom);	// Create a temporary userid

		// Temporarily become this user
		$_SESSION["org_user"]=$_SESSION["pgv_user"];
		$_SESSION["pgv_user"]=$tempUserID;
	}

	$head=gedcom_header($gedcom);
	if ($exportOptions['toANSI']=="yes") {
		$head=str_replace("UTF-8", "ANSI", $head);
		$head=utf8_decode($head);
	}
	$head=remove_custom_tags($head, $exportOptions['noCustomTags']);

	// Buffer the output.  Lots of small fwrite() calls can be very slow when writing large gedcoms.
	$buffer=reformat_record_export($head);

	$recs=
		PGV_DB::prepare("SELECT i_gedcom FROM {$TBLPREFIX}individuals WHERE i_file=? AND i_id NOT LIKE ? ORDER BY i_id")
		->execute(array($ged_id, '%:%'))
		->fetchOneColumn();
	foreach ($recs as $rec) {
		$rec=remove_custom_tags($rec, $exportOptions['noCustomTags']);
		if ($exportOptions['privatize']!='none') $rec=privatize_gedcom($rec);
		if ($exportOptions['toANSI']=="yes") $rec=utf8_decode($rec);
		$buffer.=reformat_record_export($rec);
		if (strlen($buffer)>65536) {
			fwrite($gedout, $buffer);
			$buffer='';
		}
	}

	$recs=
		PGV_DB::prepare("SELECT f_gedcom FROM {$TBLPREFIX}families WHERE f_file=? AND f_id NOT LIKE ? ORDER BY f_id")
		->execute(array($ged_id, '%:%'))
		->fetchOneColumn();
	foreach ($recs as $rec) {
		$rec=remove_custom_tags($rec, $exportOptions['noCustomTags']);
		if ($exportOptions['privatize']!='none') $rec=privatize_gedcom($rec);
		if ($exportOptions['toANSI']=="yes") $rec=utf8_decode($rec);
		$buffer.=reformat_record_export($rec);
		if (strlen($buffer)>65536) {
			fwrite($gedout, $buffer);
			$buffer='';
		}
	}

	$recs=
		PGV_DB::prepare("SELECT s_gedcom FROM {$TBLPREFIX}sources WHERE s_file=? AND s_id NOT LIKE ? ORDER BY s_id")
		->execute(array($ged_id, '%:%'))
		->fetchOneColumn();
	foreach ($recs as $rec) {
		$rec=remove_custom_tags($rec, $exportOptions['noCustomTags']);
		if ($exportOptions['privatize']!='none') $rec=privatize_gedcom($rec);
		if ($exportOptions['toANSI']=="yes") $rec=utf8_decode($rec);
		$buffer.=reformat_record_export($rec);
		if (strlen($buffer)>65536) {
			fwrite($gedout, $buffer);
			$buffer='';
		}
	}

	$recs=
		PGV_DB::prepare("SELECT o_gedcom FROM {$TBLPREFIX}other WHERE o_file=? AND o_id NOT LIKE ? AND o_type!=? AND o_type!=? ORDER BY o_id")
		->execute(array($ged_id, '%:%', 'HEAD', 'TRLR'))
		->fetchOneColumn();
	foreach ($recs as $rec) {
		$rec=remove_custom_tags($rec, $exportOptions['noCustomTags']);
		if ($exportOptions['privatize']!='none') $rec=privatize_gedcom($rec);
		if ($exportOptions['toANSI']=="yes") $rec=utf8_decode($rec);
		$buffer.=reformat_record_export($rec);
		if (strlen($buffer)>65536) {
			fwrite($gedout, $buffer);
			$buffer='';
		}
	}

	$recs=
		PGV_DB::prepare("SELECT m_gedrec FROM {$TBLPREFIX}media WHERE m_gedfile=? AND m_media NOT LIKE ? ORDER BY m_media")
		->execute(array($ged_id, '%:%'))
		->fetchOneColumn();
	foreach ($recs as $rec) {
		$rec = convert_media_path($rec, $exportOptions['path'], $exportOptions['slashes']);
		$rec=remove_custom_tags($rec, $exportOptions['noCustomTags']);
		if ($exportOptions['privatize']!='none') $rec=privatize_gedcom($rec);
		if ($exportOptions['toANSI']=="yes") $rec=utf8_decode($rec);
		$buffer.=reformat_record_export($rec);
		if (strlen($buffer)>65536) {
			fwrite($gedout, $buffer);
			$buffer='';
		}
	}

	fwrite($gedout, $buffer."0 TRLR".PGV_EOL);

	if ($exportOptions['privatize']!='none') {
		$_SESSION["pgv_user"]=$_SESSION["org_user"];
		delete_user($export_user_id);
		AddToLog("deleted dummy user -> {$tempUserID} <-");
	}

	$GEDCOM = $oldGEDCOM;
}

/*
 *	Export the database in GRAMPS XML format
 *
 *  input parameters:
 *		$gedcom:	GEDCOM to be exported
 *		$gedout:	Handle of output file
 *		$exportOptions:	array of options for this Export operation as follows:
 *			'privatize':	which Privacy rules apply?  (none, visitor, user, GEDCOM admin, site admin)
 *			'toANSI':		should the output be produced in ANSI instead of UTF-8?  (yes, no)
 *			'noCustomTags':	should custom tags be removed?  (yes, no)
 *			'path':			what constant should prefix all media file paths?  (eg: media/  or c:\my pictures\my family
 *			'slashes':		what folder separators apply to media file paths?  (forward, backward)
 */
function export_gramps($gedcom, $gedout, $exportOptions) {
	global $GEDCOM, $pgv_lang;
	global $TBLPREFIX;

	// Temporarily switch to the specified GEDCOM
	$oldGEDCOM = $GEDCOM;
	$GEDCOM = $gedcom;
	$ged_id=get_id_from_gedcom($gedcom);

	$tempUserID = '#ExPoRt#';
	if ($exportOptions['privatize']!='none') {

		$export_user_id = createTempUser($tempUserID, $exportOptions['privatize'], $gedcom);	// Create a temporary userid

		// Temporarily become this user
		$_SESSION["org_user"]=$_SESSION["pgv_user"];
		$_SESSION["pgv_user"]=$tempUserID;
	}

	$geDownloadGedcom=new GEDownloadGedcom();
	$geDownloadGedcom->begin_xml();

	$recs=
		PGV_DB::prepare("SELECT i_id, i_gedcom FROM {$TBLPREFIX}individuals WHERE i_file=? AND i_id NOT LIKE ? ORDER BY i_id")
		->execute(array($ged_id, '%:%'))
		->fetchAssoc();
	foreach ($recs as $id=>$rec) {
		$rec = remove_custom_tags($rec, $exportOptions['noCustomTags']);
		if ($exportOptions['privatize']!='none') $rec=privatize_gedcom($rec);
		$geDownloadGedcom->create_person($rec, $id);
	}

	$recs=
		PGV_DB::prepare("SELECT f_id, f_gedcom FROM {$TBLPREFIX}families WHERE f_file=? AND f_id NOT LIKE ? ORDER BY f_id")
		->execute(array($ged_id, '%:%'))
		->fetchAssoc();
	foreach ($recs as $id=>$rec) {
		$rec = remove_custom_tags($rec, $exportOptions['noCustomTags']);
		if ($exportOptions['privatize']!='none') $rec=privatize_gedcom($rec);
		$geDownloadGedcom->create_family($rec, $id);
	}

	$recs=
		PGV_DB::prepare("SELECT s_id, s_gedcom FROM {$TBLPREFIX}sources WHERE s_file=? AND s_id NOT LIKE ? ORDER BY s_id")
		->execute(array($ged_id, '%:%'))
		->fetchAssoc();
	foreach ($recs as $id=>$rec) {
		$rec = remove_custom_tags($rec, $exportOptions['noCustomTags']);
		if ($exportOptions['privatize']!='none') $rec=privatize_gedcom($rec);
		$geDownloadGedcom->create_source($rec, $id);
	}

	$recs=
		PGV_DB::prepare("SELECT m_media, m_gedrec FROM {$TBLPREFIX}media WHERE m_gedfile=? AND m_media NOT LIKE ? ORDER BY m_media")
		->execute(array($ged_id, '%:%'))
		->fetchAssoc();
	foreach ($recs as $id=>$rec) {
		$rec = convert_media_path($rec, $exportOptions['path'], $exportOptions['slashes']);
		$rec = remove_custom_tags($rec, $exportOptions['noCustomTags']);
		if ($exportOptions['privatize']!='none') $rec=privatize_gedcom($rec);
		$geDownloadGedcom->create_media($rec, $id);
	}
	fwrite($gedout,$geDownloadGedcom->dom->saveXML());

	if ($exportOptions['privatize']!='none') {
		$_SESSION["pgv_user"]=$_SESSION["org_user"];
		delete_user($export_user_id);
		AddToLog("deleted dummy user -> {$tempUserID} <-");
	}

	$GEDCOM = $oldGEDCOM;
}

function um_export($proceed) {
	global $INDEX_DIRECTORY, $TBLPREFIX, $pgv_lang;

	// Get user array and create authenticate.php
	if (($proceed=="export") || ($proceed=="exportovr")) {
		print $pgv_lang["um_creating"]." \"authenticate.php\"<br /><br />";
	}
	$authtext="<?php\n\n\$users=array();\n\n";
	foreach (get_all_users() as $user_id=>$username) {
		$authtext .="\$user=array();\n";
		foreach (array('username', 'firstname', 'lastname', 'gedcomid', 'rootid', 'password','canadmin', 'canedit', 'email', 'verified','verified_by_admin', 'language', 'pwrequested', 'reg_timestamp','reg_hashcode', 'theme', 'loggedin', 'sessiontime', 'contactmethod', 'visibleonline', 'editaccount', 'defaulttab','comment', 'comment_exp', 'sync_gedcom', 'relationship_privacy', 'max_relation_path', 'auto_accept') as $ukey) {
			$value=get_user_setting($user_id, $ukey);
			// Convert Y/N/yes/no to bools
			if (in_array($ukey, array('canadmin', 'loggedin', 'visibleonline', 'editaccount', 'sync_gedcom', 'relationship_privacy', 'auto_accept'))) {
				$value=($value=='Y');
			}
			if (in_array($ukey, array('verified', 'verified_by_admin'))) {
				$value=($value=='yes');
			}
			if (!is_array($value)) {
				$value=str_replace('"', '\\"', $value);
				$authtext .="\$user[\"$ukey\"]='$value';\n";
			} else {
				$authtext .="\$user[\"$ukey\"]=array();\n";
				foreach ($value as $subkey=>$subvalue) {
					$subvalue=str_replace('"', '\\"', $subvalue);
					$authtext .="\$user[\"$ukey\"][\"$subkey\"]='$subvalue';\n";
				}
			}
		}
		$authtext .="\$users[\"$username\"]=\$user;\n\n";
	}
	$authtext .="?".">\n";
	if (file_exists($INDEX_DIRECTORY."authenticate.php")) {
		print $pgv_lang["um_file_create_fail1"]." ".$INDEX_DIRECTORY."authenticate.php<br /><br />";
	} else {
		$fp=fopen($INDEX_DIRECTORY."authenticate.php", "w");
		if ($fp) {
			fwrite($fp, $authtext);
			fclose($fp);
			$logline=AddToLog("authenticate.php updated");
			check_in($logline, "authenticate.php", $INDEX_DIRECTORY);
			if (($proceed=="export") || ($proceed=="exportovr")) {
				print $pgv_lang["um_file_create_succ1"]." authenticate.php<br /><br />";
			}
		} else
			print $pgv_lang["um_file_create_fail2"]." ".$INDEX_DIRECTORY."authenticate.php. ".$pgv_lang["um_file_create_fail3"]."<br /><br />";
	}

	// Get messages and create messages.dat
	if (($proceed=="export") || ($proceed=="exportovr")) {
		print $pgv_lang["um_creating"]." \"messages.dat\"<br /><br />";
	}
	$messages=array();
	$mesid=1;
	$rows=
		PGV_DB::prepare("SELECT * FROM {$TBLPREFIX}messages ORDER BY m_id DESC")
		->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rows as $row){
		$message=array();
		$message["id"]=$mesid;
		$mesid=$mesid + 1;
		$message["to"]=$row["m_to"];
		$message["from"]=$row["m_from"];
		$message["subject"]=$row["m_subject"];
		$message["body"]=$row["m_body"];
		$message["created"]=$row["m_created"];
		$messages[]=$message;
	}
	if ($mesid > 1) {
		$mstring=serialize($messages);
			if (file_exists($INDEX_DIRECTORY."messages.dat")) {
			print $pgv_lang["um_file_create_fail1"]." ".$INDEX_DIRECTORY."messages.dat<br /><br />";
		} else {
			$fp=fopen($INDEX_DIRECTORY."messages.dat", "wb");
			if ($fp) {
				fwrite($fp, $mstring);
				fclose($fp);
				$logline=AddToLog("messages.dat updated");
				check_in($logline, "messages.dat", $INDEX_DIRECTORY);
				if (($proceed=="export") || ($proceed=="exportovr")) {
					print $pgv_lang["um_file_create_succ1"]." messages.dat<br /><br />";
				}
			} else
				print $pgv_lang["um_file_create_fail2"]." ".$INDEX_DIRECTORY."messages.dat. ".$pgv_lang["um_file_create_fail3"]."<br /><br />";
		}
	} else {
		if (($proceed=="export") || ($proceed=="exportovr")) {
			print $pgv_lang["um_nomsg"]." ".$pgv_lang["um_file_not_created"]."<br /><br />";
		}
	}

	// Get favorites and create favorites.dat
	if (($proceed=="export") || ($proceed=="exportovr")) {
		print $pgv_lang["um_creating"]." \"favorites.dat\"<br /><br />";
	}
	$favorites=array();
	$rows=
		PGV_DB::prepare("SELECT * FROM {$TBLPREFIX}favorites")
		->fetchAll(PDO::FETCH_ASSOC);
	$favid=1;
	foreach ($rows as $row){
		$favorite=array();
		$favorite["id"]=$favid;
		$favid=$favid + 1;
		$favorite["username"]=$row["fv_username"];
		$favorite["gid"]=$row["fv_gid"];
		$favorite["type"]=$row["fv_type"];
		$favorite["file"]=$row["fv_file"];
		$favorite["title"]=$row["fv_title"];
		$favorite["note"]=$row["fv_note"];
		$favorite["url"]=$row["fv_url"];
		$favorites[]=$favorite;
	}
	if ($favid > 1) {
		$mstring=serialize($favorites);
		if (file_exists($INDEX_DIRECTORY."favorites.dat")) {
			print $pgv_lang["um_file_create_fail1"]." ".$INDEX_DIRECTORY."favorites.dat<br /><br />";
		} else {
			$fp=fopen($INDEX_DIRECTORY."favorites.dat", "wb");
			if ($fp) {
				fwrite($fp, $mstring);
				fclose($fp);
				$logline=AddToLog("favorites.dat updated");
				check_in($logline, "favorites.dat", $INDEX_DIRECTORY);
				if (($proceed=="export") || ($proceed=="exportovr")) {
					print $pgv_lang["um_file_create_succ1"]." favorites.dat<br /><br />";
				}
			} else
				print $pgv_lang["um_file_create_fail2"]." ".$INDEX_DIRECTORY."favorites.dat. ".$pgv_lang["um_file_create_fail3"]."<br /><br />";
		}
	} else {
		if (($proceed=="export") || ($proceed=="exportovr")) {
			print $pgv_lang["um_nofav"]." ".$pgv_lang["um_file_not_created"]."<br /><br />";
		}
	}

	// Get news and create news.dat
	if (($proceed=="export") || ($proceed=="exportovr")) {
		print $pgv_lang["um_creating"]." \"news.dat\"<br /><br />";
	}
	$allnews=array();
	$rows=
		PGV_DB::prepare("SELECT * FROM {$TBLPREFIX}news ORDER BY n_date DESC")
		->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rows as $row){
		$news=array();
		$news["id"]=$row["n_id"];
		$news["username"]=$row["n_username"];
		$news["date"]=$row["n_date"];
		$news["title"]=$row["n_title"];
		$news["text"]=$row["n_text"];
		$allnews[$row["n_id"]]=$news;
	}
	if (count($allnews) > 0) {
		$mstring=serialize($allnews);
		if (file_exists($INDEX_DIRECTORY."news.dat")) {
			print $pgv_lang["um_file_create_fail1"].$INDEX_DIRECTORY."news.dat<br /><br />";
		} else {
			$fp=fopen($INDEX_DIRECTORY."news.dat", "wb");
			if ($fp) {
				fwrite($fp, $mstring);
				fclose($fp);
				$logline=AddToLog("news.dat updated");
				check_in($logline, "news.dat", $INDEX_DIRECTORY);
				if (($proceed=="export") || ($proceed=="exportovr")) {
					print $pgv_lang["um_file_create_succ1"]." news.dat<br /><br />";
				}
			} else
				print $pgv_lang["um_file_create_fail2"]." ".$INDEX_DIRECTORY."news.dat. ".$pgv_lang["um_file_create_fail3"]."<br /><br />";
		}
	} else {
		if (($proceed=="export") || ($proceed=="exportovr")) {
			print $pgv_lang["um_nonews"]." ".$pgv_lang["um_file_not_created"]."<br /><br />";
		}
	}

	// Get blocks and create blocks.dat
	if (($proceed=="export") || ($proceed=="exportovr")) {
		print $pgv_lang["um_creating"]." \"blocks.dat\"<br /><br />";
	}
	$allblocks=array();
	$blocks["main"]=array();
	$blocks["right"]=array();
	$rows=
		PGV_DB::prepare("SELECT * FROM {$TBLPREFIX}blocks ORDER BY b_location, b_order")
		->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rows as $row){
		$blocks=array();
		$blocks["username"]=$row["b_username"];
		$blocks["location"]=$row["b_location"];
		$blocks["order"]=$row["b_order"];
		$blocks["name"]=$row["b_name"];
		$blocks["config"]=unserialize($row["b_config"]);
		$allblocks[]=$blocks;
	}
	if (count($allblocks) > 0) {
		$mstring=serialize($allblocks);
		if (file_exists($INDEX_DIRECTORY."blocks.dat")) {
			print $pgv_lang["um_file_create_fail1"]." ".$INDEX_DIRECTORY."blocks.dat<br /><br />";
		} else {
			$fp=fopen($INDEX_DIRECTORY."blocks.dat", "wb");
			if ($fp) {
				fwrite($fp, $mstring);
				fclose($fp);
				$logline=AddToLog("blocks.dat updated");
				check_in($logline, "blocks.dat", $INDEX_DIRECTORY);
				if (($proceed=="export") || ($proceed=="exportovr")) {
					print $pgv_lang["um_file_create_succ1"]." blocks.dat<br /><br />";
				}
			} else
				print $pgv_lang["um_file_create_fail2"]." ".$INDEX_DIRECTORY."blocks.dat. ".$pgv_lang["um_file_create_fail3"]."<br /><br />";
		}
	} else {
		if ($proceed=="export" || $proceed=="exportovr") {
			print $pgv_lang["um_noblocks"]." ".$pgv_lang["um_file_not_created"]."<br /><br />";
		}
	}
}
?>
