<?php
/***************************************************************************
*				   functions_search.php
*				   -------------------
*	  begin		   : Wed Sep 05 2001
*	  copyright		   : (C) 2002 The phpBB Group
*	  email		   : support@phpbb.com
*
*	  Modifications made by CPG Dev Team http://cpgnuke.com
*	  Last modification notes:
*
*	  $Id: functions_search.php,v 9.6 2005/10/11 12:31:52 djmaze Exp $
*
****************************************************************************/

/***************************************************************************
 *
 *	 This program is free software; you can redistribute it and/or modify
 *	 it under the terms of the GNU General Public License as published by
 *	 the Free Software Foundation; either version 2 of the License, or
 *	 (at your option) any later version.
 *
 ***************************************************************************/

if (!defined('IN_PHPBB')) {
	die('Hacking attempt');
}

function clean_words($mode, &$entry, &$stopword_list, &$synonym_list)
{
	static $drop_char_match =	array('^', '$', '&', '(', ')', '<', '>', '`', '\'', '"', '|', ',', '@', '_', '?', '%', '-', '~', '+', '.', '[', ']', '{', '}', ':', '\\', '/', '=', '#', '\'', ';', '!');
	static $drop_char_replace = array(' ', ' ', ' ', ' ', ' ', ' ', ' ', '',  '',	' ', ' ', ' ', ' ', '',	 ' ', ' ', '',	' ',  ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ' , ' ', ' ', ' ', ' ',	' ', ' ');

	$entry = ' ' . strip_tags(strtolower($entry)) . ' ';

	if ( $mode == 'post' )
	{
		// Replace line endings by a space
		$entry = preg_replace('/[\n\r]/is', ' ', $entry);
		// HTML entities like &nbsp;
		$entry = preg_replace('/\b&[a-z]+;\b/', ' ', $entry);
		// Remove URL's
		$entry = preg_replace('/\b[a-z0-9]+:\/\/[a-z0-9\.\-]+(\/[a-z0-9\?\.%_\-\+=&\/]+)?/', ' ', $entry);
		// Quickly remove BBcode.
		$entry = preg_replace('/\[img:[a-z0-9]{10,}\].*?\[\/img:[a-z0-9]{10,}\]/', ' ', $entry);
		$entry = preg_replace('/\[\/?url(=.*?)?\]/', ' ', $entry);
		$entry = preg_replace('/\[\/?[a-z\*=\+\-]+(\:?[0-9a-z]+)?:[a-z0-9]{10,}(\:[a-z0-9]+)?=?.*?\]/', ' ', $entry);
	}
	else if ( $mode == 'search' )
	{
		$entry = str_replace(' +', ' and ', $entry);
		$entry = str_replace(' -', ' not ', $entry);
	}

	//
	// Filter out strange characters like ^, $, &, change "it's" to "its"
	//
	for ($i = 0; $i < count($drop_char_match); $i++)
	{
		$entry = str_replace($drop_char_match[$i], $drop_char_replace[$i], $entry);
	}

	if ($mode == 'post')
	{
		$entry = str_replace('*', ' ', $entry);
		// 'words' that consist of <3 or >20 characters are removed.
		$entry = preg_replace('/[ ]([\S]{1,2}|[\S]{21,})[ ]/',' ', $entry);
	}

	if (!empty($stopword_list))
	{
		for ($j = 0; $j < (is_countable($stopword_list) ? count($stopword_list) : 0); $j++)
		{
			$stopword = trim($stopword_list[$j]);
			if ($mode == 'post' || ($stopword != 'not' && $stopword != 'and' && $stopword != 'or'))
			{
				$entry = str_replace(' ' . trim($stopword) . ' ', ' ', $entry);
			}
		}
	}

	if (!empty($synonym_list))
	{
		for ($j = 0; $j < (is_countable($synonym_list) ? count($synonym_list) : 0); $j++)
		{
			list($replace_synonym, $match_synonym) = explode(' ', trim(strtolower($synonym_list[$j])));
			if ( $mode == 'post' || ( $match_synonym != 'not' && $match_synonym != 'and' && $match_synonym != 'or' ) )
			{
				$entry =  str_replace(' ' . trim($match_synonym) . ' ', ' ' . trim($replace_synonym) . ' ', $entry);
			}
		}
	}

	return $entry;
}

function split_words($entry)
{
	// Trim 1+ spaces to one space and split this trimmed string into words.
	return explode(' ', trim(preg_replace('#\s+#', ' ', $entry)));
}

function add_search_words($mode, $post_id, $post_text, $post_title = '')
{
	$sql = null;
 global $db, $phpbb_root_path, $board_config, $lang;

	$stopword_array = file('language/' . $board_config['default_lang'] . "/Forums/search_stopwords.txt");
	$synonym_array = file('language/' . $board_config['default_lang'] . "/Forums/search_synonyms.txt");

	$search_raw_words = array();
	$search_raw_words['text'] = split_words(clean_words('post', $post_text, $stopword_array, $synonym_array));
	$search_raw_words['title'] = split_words(clean_words('post', $post_title, $stopword_array, $synonym_array));
	set_time_limit(0);
	$word = array();
	$word_insert_sql = array();
	foreach ($search_raw_words as $word_in => $search_matches) {
     $word_insert_sql[$word_in] = '';
     if (!empty($search_matches))
   		{
   			for ($i = 0; $i < (is_countable($search_matches) ? count($search_matches) : 0); $i++)
   			{
   				$search_matches[$i] = trim($search_matches[$i]);
   
   				if( $search_matches[$i] != '' )
   				{
   					$word[] = $search_matches[$i];
   					if ( !strstr($word_insert_sql[$word_in], "'" . $search_matches[$i] . "'") )
   					{
   						$word_insert_sql[$word_in] .= ( $word_insert_sql[$word_in] != "" ) ? ", '" . $search_matches[$i] . "'" : "'" . $search_matches[$i] . "'";
   					}
   				}
   			}
   		}
 }

	if (count($word))
	{
		sort($word);

		$prev_word = '';
		$word_text_sql = '';
		$temp_word = array();
		for($i = 0; $i < count($word); $i++)
		{
			if ( $word[$i] != $prev_word )
			{
				$temp_word[] = $word[$i];
				$word_text_sql .= ( ( $word_text_sql != '' ) ? ', ' : '' ) . "'" . $word[$i] . "'";
			}
			$prev_word = $word[$i];
		}
		$word = $temp_word;

		$check_words = array();
		switch (SQL_LAYER)
		{
			case 'postgresql':
			case 'msaccess':
			case 'mssql-odbc':
			case 'oracle':
			case 'db2':
				$sql = "SELECT word_id, word_text
					FROM " . SEARCH_WORD_TABLE . "
					WHERE word_text IN ($word_text_sql)";
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
				{
					$check_words[$row['word_text']] = $row['word_id'];
				}
				break;
		}

		$value_sql = '';
		$match_word = array();
		for ($i = 0; $i < count($word); $i++)
		{
			$new_match = true;
			if (isset($check_words[$word[$i]])) { $new_match = false; }

			if ($new_match) {
				$word[$i] = Fix_Quotes($word[$i]);
				switch (SQL_LAYER)
				{
					case 'mysql':
					case 'mysql4':
						$value_sql .= ( ( $value_sql != '' ) ? ', ' : '' ) . '(\'' . $word[$i] . '\', 0)';
						break;
					case 'mssql':
					case 'mssql-odbc':
						$value_sql .= ( ( $value_sql != '' ) ? ' UNION ALL ' : '' ) . "SELECT '" . $word[$i] . "', 0";
						break;
					default:
						$sql = "INSERT INTO " . SEARCH_WORD_TABLE . " (word_text, word_common)
							VALUES ('" . $word[$i] . "', 0)";
						$db->sql_query($sql);
						break;
				}
			}
		}

		if ($value_sql != '') {
			switch (SQL_LAYER)
			{
				case 'mysql':
				case 'mysql4':
					$sql = "INSERT IGNORE INTO " . SEARCH_WORD_TABLE . " (word_text, word_common)
						VALUES $value_sql";
					break;
				case 'mssql':
				case 'mssql-odbc':
					$sql = "INSERT INTO " . SEARCH_WORD_TABLE . " (word_text, word_common)
						$value_sql";
					break;
			}
			$db->sql_query($sql);
		}
	}

	foreach ($word_insert_sql as $word_in => $match_sql) {
     $title_match = ( $word_in == 'title' ) ? 1 : 0;
     if ($match_sql != '')
   		{
   			$sql = "INSERT IGNORE INTO " . SEARCH_MATCH_TABLE . " (post_id, word_id, title_match)
				SELECT $post_id, word_id, $title_match
					FROM " . SEARCH_WORD_TABLE . "
					WHERE word_text IN ($match_sql)";
   			$db->sql_query($sql);
   		}
 }

	if ($mode == 'single') {
		remove_common('single', 4/10, $word);
	}

	return;
}

//
// Check if specified words are too common now
//
function remove_common($mode, $fraction, $word_id_list = array())
{
	global $db;
	$row = $db->sql_ufetchrow("SELECT COUNT(post_id) AS total_posts FROM ".POSTS_TABLE);
	if ($row['total_posts'] >= 100)
	{
		$common_threshold = floor($row['total_posts'] * $fraction);
		if ($mode == 'single' && (is_countable($word_id_list) ? count($word_id_list) : 0))
		{
			$word_id_sql = '';
			for($i = 0; $i < (is_countable($word_id_list) ? count($word_id_list) : 0); $i++)
			{
				$word_id_sql .= ( ( $word_id_sql != '' ) ? ', ' : '' ) . "'" . $word_id_list[$i] . "'";
			}
			$sql = "SELECT m.word_id
				FROM " . SEARCH_MATCH_TABLE . " m, " . SEARCH_WORD_TABLE . " w
				WHERE w.word_text IN ($word_id_sql)
					AND m.word_id = w.word_id
				GROUP BY m.word_id
				HAVING COUNT(m.word_id) > $common_threshold";
		}
		else
		{
			$sql = "SELECT word_id
				FROM " . SEARCH_MATCH_TABLE . "
				GROUP BY word_id
				HAVING COUNT(word_id) > $common_threshold";
		}
		$result = $db->sql_query($sql);
		$common_word_id = '';
		while ($row = $db->sql_fetchrow($result))
		{
			$common_word_id .= ( ( $common_word_id != '' ) ? ', ' : '' ) . $row['word_id'];
		}
		$db->sql_freeresult($result);
		if ($common_word_id != '')
		{
			$db->sql_query("UPDATE ".SEARCH_WORD_TABLE." SET word_common = ".TRUE." WHERE word_id IN ($common_word_id)");
			$db->sql_query("DELETE FROM ".SEARCH_MATCH_TABLE." WHERE word_id IN ($common_word_id)");
		}
	}

	return;
}

function remove_search_post($post_id_sql)
{
	global $db;
	$words_removed = false;
	switch (SQL_LAYER)
	{
	case 'mysql':
	case 'mysql4':
		$sql = "SELECT word_id
			FROM " . SEARCH_MATCH_TABLE . "
			WHERE post_id IN ($post_id_sql)
			GROUP BY word_id";
		if ($result = $db->sql_query($sql)) {
		$word_id_sql = '';
		while ($row = $db->sql_fetchrow($result)) {
			$word_id_sql .= ( $word_id_sql != '' ) ? ', ' . $row['word_id'] : $row['word_id'];
		}
		if ($word_id_sql != '') {
			$sql = "SELECT word_id FROM " . SEARCH_MATCH_TABLE . "
				WHERE word_id IN ($word_id_sql)
				GROUP BY word_id HAVING COUNT(word_id) = 1";
			if ($result = $db->sql_query($sql)) {
				$word_id_sql = '';
				while ( $row = $db->sql_fetchrow($result) ) {
					$word_id_sql .= ( $word_id_sql != '' ) ? ', ' . $row['word_id'] : $row['word_id'];
				}
				if ( $word_id_sql != '' ) {
					$db->sql_query("DELETE FROM ".SEARCH_WORD_TABLE." WHERE word_id IN ($word_id_sql)");
					$words_removed = $db->sql_affectedrows();
				}
			}
		}
		}
		break;

	default:
		$sql = "DELETE FROM " . SEARCH_WORD_TABLE . "
			WHERE word_id IN (
			SELECT word_id
			FROM " . SEARCH_MATCH_TABLE . "
			WHERE word_id IN (
				SELECT word_id
				FROM " . SEARCH_MATCH_TABLE . "
				WHERE post_id IN ($post_id_sql)
				GROUP BY word_id
			)
			GROUP BY word_id
			HAVING COUNT(word_id) = 1
			)";
		$db->sql_query($sql);
		$words_removed = $db->sql_affectedrows();
		break;
	}
	$db->sql_query("DELETE FROM ".SEARCH_MATCH_TABLE." WHERE post_id IN ($post_id_sql)");
	return $words_removed;
}

//
// Username search
//
function username_search($search_match)
{
	global $db, $board_config, $template, $lang, $images, $theme, $phpbb_root_path;

	$gen_simple_header = TRUE;

	$username_list = '';
	if ( !empty($search_match) ) {
		$username_search = preg_replace('/\*/', '%', trim(strip_tags($search_match)));

		// akamu 12/26/2004 10:28PM additional fields to help match email from members to list
		$sql = "SELECT username FROM " . USERS_TABLE . "
			WHERE username LIKE '" . Fix_Quotes($username_search) . "'	
			OR user_email LIKE '" . Fix_Quotes($username_search) . "'
			OR name	 LIKE '" . Fix_Quotes($username_search) . "'
			OR user_website LIKE '" . Fix_Quotes($username_search) . "'
			OR user_aim	 LIKE '" . Fix_Quotes($username_search) . "'
			OR user_msnm LIKE '" . Fix_Quotes($username_search) . "'
			OR user_yim	 LIKE '" . Fix_Quotes($username_search) . "' 
			AND user_id <> " . ANONYMOUS . "
			ORDER BY username";
		$result = $db->sql_query($sql);
		if ($row = $db->sql_fetchrow($result)) {
			do {
				$username_list .= '<option value="' . $row['username'] . '">' . $row['username'] . '</option>';
			}
			while ($row = $db->sql_fetchrow($result));
		} else {
			$username_list .= '<option>' . $lang['No_match']. '</option>';
		}
		$db->sql_freeresult($result);
	}

	$page_title = $lang['Search'];
	include('includes/phpBB/page_header.php');
	$template->assign_vars(array(
		'USERNAME' => ( !empty($search_match) ) ? strip_tags($search_match) : '',

		'L_CLOSE_WINDOW' => $lang['Close_window'],
		'L_SEARCH_USERNAME' => $lang['Find_username'],
		'L_UPDATE_USERNAME' => '&nbsp;',
		'L_SELECT' => $lang['Select'],
		'L_SEARCH' => $lang['Search'],
		'L_SEARCH_EXPLAIN' => $lang['Search_author_explain'],
		'L_CLOSE_WINDOW' => $lang['Close_window'],

		'S_USERNAME_OPTIONS' => $username_list,
		'S_SEARCH_ACTION' => getlink("&file=search&mode=searchuser&popup=1"))
	);
	if ( $username_list != '' ) {
		$template->assign_block_vars('switch_select_name', array());
	}
	$template->set_filenames(array('body' => 'forums/search_username.html'));
	include('includes/phpBB/page_tail.php');
	return;
}