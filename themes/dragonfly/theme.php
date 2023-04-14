<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/themes/dragonfly/theme.php,v $
  $Revision: 9.19 $
  $Author: nanocaiordo $
  $Date: 2007/09/03 11:26:31 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
define('THEME_VERSION', '9.1.0.0');

$gfxcolor = '#C0C000';
$bgcolor1 = '#FFFFFF';
$bgcolor2 = '#D3DFE3';
$bgcolor3 = '#EFEFEF';
$bgcolor4 = '#FFC53A';
$textcolor1 = '#009900';
$textcolor2 = '#000000';
$hr = 1; # 1 to have horizonal rule in comments instead of table bgcolor

function OpenTable() {
	echo '<div class="table1">';
}
function OpenTable2() {
	echo '<div class="table2">';
}

function CloseTable() {
	echo '</div>';
}
function CloseTable2() {
	echo '</div>';
}

function themeheader() {
	global $slogan, $sitename, $mainindex, $adminindex, $cpgtpl, $site_logo, $userinfo, $CPG_SESS, $MAIN_CFG, $Blocks;
	if ($MAIN_CFG['global']['admingraphic'] & 4) {
		include('includes/cssmainmenu.php');
	}
	$imgr = $imgl = '';
	// left blocks ?
	if ($Blocks->l && ($Blocks->showblocks & 1)) {
		$img = $Blocks->hideblock('600') ? 'plus.png' : 'minus.png';
		$imgl = '<img style="float:left; cursor:pointer;" alt="'._TOGGLE.'" title="'._TOGGLE.'" id="pic600" src="themes/'.$CPG_SESS['theme'].'/images/'.$img.'" onclick="blockswitch(\'600\');" />';
	}
	// right blocks ?
	if ($Blocks->r && ($Blocks->showblocks & 2)) {
		$img = $Blocks->hideblock('601') ? 'plus.png' : 'minus.png';
		$imgr = '<img style="float:right; cursor:pointer;" alt="'._TOGGLE.'" title="'._TOGGLE.'" id="pic601" src="themes/'.$CPG_SESS['theme'].'/images/'.$img.'" onclick="blockswitch(\'601\');" />';
	}
	$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
	$css_ie = preg_match('#MSIE 7.0#m', $user_agent) ? 'ie7' : (preg_match('#MSIE ([0-6].[0-9]{1,2})#m', $user_agent) ? 'ie6' : ((isset($_SESSION['SECURITY']['UA']) && $_SESSION['SECURITY']['UA'] == 'Safari') ? 'safari' : ((isset($_SESSION['SECURITY']['UA']) && $_SESSION['SECURITY']['UA'] == 'Opera') ? 'opera' : '')));
	$cpgtpl->assign_vars(array(
		'CSS_IE'		=> ($css_ie) ? '<link rel="stylesheet" type="text/css" href="themes/'.$CPG_SESS['theme'].'/style/'.$css_ie.'.css" />' : '',
		'PUBLIC_HEADER' => !defined('ADMIN_PAGES'),
		'B_L_VISIBLE'  => $Blocks->hideblock('600') ? 'style="display: none"' : '',
		'G_LEFTIMAGE'  => $imgl,
		'G_RIGHTIMAGE' => $imgr,
		'S_IS_ADMIN'   => is_admin(),
		'S_IS_USER'    => is_user(),
		'S_USER_INFO'  => _BWEL.' '.(is_user() ? $userinfo['username'] : _ANONYMOUS),
		'S_LOGO'       => $site_logo,
		'S_SITENAME'   => $sitename,
		'S_HOME'       => _HOME,
		'S_DOWNLOADS'  => is_active('Downloads') ? _DownloadsLANG : false,
		'S_FORUMS'     => _ForumsLANG,
		'S_MY_ACCOUNT' => is_user() ? _Your_AccountLANG : _BREG,
		'S_ADMIN'      => _ADMINISTRATION,
		'U_MAININDEX'  => $mainindex,
		'U_DOWNLOADS'  => getlink('Downloads'),
		'U_FORUMS'     => getlink('Forums'),
		'U_MY_ACCOUNT' => getlink(is_user() ? 'Your_Account' : 'Your_Account&amp;file=register'),
		'U_ADMININDEX' => $adminindex,
		'S_MAIN_MENU'  => $mmcontent ?? false
	));
	$Blocks->display('l');
}

function themefooter() {
    global $banners, $cpgtpl, $Blocks;
	$Blocks->display('r');
	$cpgtpl->assign_vars(array(
		'B_R_VISIBLE'  => $Blocks->hideblock('601') ? 'style="display: none"' : '',
		'S_FOOTER'     => footmsg(),
		'S_BANNER'     => ($banners) ? viewbanner() : ''
	));
	$cpgtpl->set_filenames(array('footer' => 'footer.html'));
	$cpgtpl->display('footer');
}

/***********************************************************************************

 string theme_open_form

 Creates start tag for form
	$get_link : link for action default blank
	$form_name : useful for styling and nbbcode
	$legend: optional string value is used in form lagend tag
	$border: optional use 1 to not show border on fieldset from stylesheet
************************************************************************************/
function theme_open_form($link, $form_name=false, $legend=false,$tborder=false) {
	$leg = $legend ? "<legend>$legend</legend>" : '';
	$bord = $tborder ? $tborder : '';
	$form_name	= $form_name ? ' name="'.$form_name.'" id="'.$form_name.'"' :'';
	return '<form method="post" action="'.$link.'"'.$form_name.' enctype="multipart/form-data" accept-charset="utf-8"><fieldset '.$bord.'>'.$leg;
}
function theme_close_form() {
	return '</fieldset></form>';
}
/***********************************************************************************

 string theme_yesno_option

 Creates 2 radio buttons with a Yes and No option
	$name : name for the <input>
	$value: current value, 1 = yes, 0 = no

************************************************************************************/
function theme_yesno_option($name, $value=0) {
	$sel = [];
 $sel[(!$value)] = '';
	$sel[$value] = ' selected="selected"';
	$select = '<select class="set" name="'.$name.'" id="'.$name."\">\n";
	$select .= '<option value="1"'.$sel[1].">"._YES."</option>\n";
	$select .= '<option value="0"'.$sel[0].">"._NO."</option>\n";
	return $select.'</select>';
}
/***********************************************************************************

 string theme_select_option

 Creates a selection dropdown box of all given variables in the array
	$name : name for the <select>
	$value: current/default value
	$array: array like array("value1","value2")

************************************************************************************/
function theme_select_option($name, $value, $array) {
	$sel = [];
 $sel[$value] = ' selected="selected"';
	$select = '<select class="set" name="'.$name.'" id="'.$name."\">\n";
	foreach($array as $var) {
		$select .= '<option'.($sel[$var] ?? '').">$var</option>\n";
	}
	return $select.'</select>';
}
/***********************************************************************************

 string theme_select_box

 Creates a selection dropdown box of all given variables in the multi array
	$name : name for the <select>
	$value: current/default value
	$array: array like array("value1 => title1","value2 => title2")

************************************************************************************/
function theme_select_box($name, $value, $array) {
	$select = '<select class="set" name="'.$name.'" id="'.$name."\">\n";
	foreach($array as $val => $title) {
		$select .= "<option value=\"$val\"".(($val==$value) ? ' selected="selected"' : '').">$title</option>\n";
	}
	return $select.'</select>';
}