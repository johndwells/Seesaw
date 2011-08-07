<?php
/**
 * Class file for Seesaw.
 * 
 * This file must be placed in the
 * /{system}/extensions/ folder in your ExpressionEngine installation.
 * See accompanying README file for more.
 *
 * @package Seesaw
 * @version 0.5.0
 * @author John D Wells <http://johndwells.com>
 * @see http://johndwells.com/software
 * @copyright Copyright (c) 2009-2010 John D Wells
 * @license http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons Attribution-Share Alike 3.0 Unported
 */


if ( ! defined('EXT')) exit('Invalid file request');

define('Seesaw_version',			'0.5.0');
define('Seesaw_docs_url',			'http://johndwells.com/software');
define('Seesaw_addon_id',			'Seesaw');
define('Seesaw_extension_class',	'Seesaw');


/**
 * Seesaw
 *
 * Now you see 'em, now you don't. Seesaw allows you to configure the Edit Channel Entries page to show/hide columns on a channel by channel basis.
 *
 * @package Seesaw
 * @version 0.5.0
 * @author John D Wells <http://johndwells.com>
 * @see http://johndwells.com/software
 * @copyright Copyright (c) 2009-2010 John D Wells
 * @license http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons Attribution-Share Alike 3.0 Unported
 */
class Seesaw
{
	/**
	 * Extension settings
	 * @var array
	 */
	var $settings = array();


	/**
	 * Extension name
	 * @var string
	 */
	var $name = Seesaw_addon_id;


	/**
	 * Extension version
	 * @var string
	 */
	var $version = Seesaw_version;


	/**
	 * Extension description
	 * @var string
	 */
	var $description = 'Seesaw allows you to configure the Edit Channel Entries page to show/hide columns on a channel-by-channel basis.';


	/**
	 * If $settings_exist = 'y' then a settings page will be shown in the EE admin
	 * @var string
	 */
	var $settings_exist = 'y';


	/**
	 * Link to extension documentation
	 * @var string
	 */
	//var $docs_url = Seesaw_docs_url;


	/**
	 * Core fields are those always output by EE
	 * @var array
	 */
	var $available_core_fields = array();


	/**
	 * Used when creating the default fields to show
	 * @var array
	 */
	var $supported_field_types = array();


	/**
	 * DB prefix to be set by on __construct()
	 * @var string
	 */
	var $db_prefix = '';


	/**
	 * Array of installed modules
	 * @var array
	 */
	var $installed_modules = array();


	/**
	 * PHP4 Constructor
	 *
	 * @see __construct()
	 */
	function seesaw($settings = '')
	{
		$this->__construct($settings);
	}

	/**
	 * PHP 5 Constructor
	 *
	 * @param	array|string $settings Extension settings associative array or an empty string
	 */
	function __construct($settings = '')
	{
		global $PREFS, $DB;
		// Set db_prefix
		$this->db_prefix = $PREFS->ini('db_prefix');

		$this->supported_field_types = array(
			'text'
			,'timestamp'
			,'mx_ue_img'
			,'custom_html'
//			,'custom_php'
		);

		$query = $DB->query('SELECT LOWER(module_name) as name
							 FROM ' . $this->db_prefix . '_modules');

		if($query->num_rows > 0) {
			foreach($query->result as $row) {
				$this->installed_modules[$row['name']] = $row['name'];
			}
		}

		// build array of available_core_fields structure
		$this->available_core_fields = array(
			'field_entry_id' => array('value' => 'y','label' => 'ID', 'type' => 'text', 'format' => ''),
			'field_title' => array('value' => 'y','label' => 'Title', 'type' => 'text', 'format' => ''),
			'field_view' => array('value' => 'y','label' => 'View', 'type' => 'text', 'format' => '')
		);
		
		// Add comments column if installed
		if(isset($this->installed_modules['comment'])) {
			$this->available_core_fields['field_comments'] = array('value' => 'y','label' => 'Comments', 'type' => 'text', 'format' => '');
		}

		// Add trackback column if installed
		if(isset($this->installed_modules['trackback'])) {
			$this->available_core_fields['field_trackbacks'] = array('value' => 'y','label' => 'Trackbacks', 'type' => 'text', 'format' => '');
		}
		
		// finish available_core_fields structure
		$this->available_core_fields['field_author'] = array('value' => 'y','label' => 'Author', 'type' => 'text', 'format' => '');
		$this->available_core_fields['field_date'] = array('value' => 'y','label' => 'Date', 'type' => 'text', 'format' => '');
		$this->available_core_fields['field_channel'] = array('value' => 'y','label' => 'Channel', 'type' => 'text', 'format' => '');
		$this->available_core_fields['field_status'] = array('value' => 'y','label' => 'Status', 'type' => 'text', 'format' => '');
	}


	/**
	 * Activates the extension
	 *
	 * @return	bool Always TRUE
	 */
	function activate_extension()
	{
		global $DB;
    
		$hooks = array(
				'lg_addon_update_register_source' => 'lg_addon_update_register_source'
				,'lg_addon_update_register_addon' => 'lg_addon_update_register_addon'
				,'edit_entries_additional_tableheader' => 'edit_entries_additional_tableheader'
				,'edit_entries_modify_tableheader' => 'edit_entries_modify_tableheader'
				,'edit_entries_additional_celldata' => 'edit_entries_additional_celldata'
				,'edit_entries_modify_tablerow' => 'edit_entries_modify_tablerow'
        );

		foreach ($hooks as $hook => $method)
		{
			$sql[] = $DB->insert_string($this->db_prefix . '_extensions',
						array(
	                    'extension_id' => '',
	                    'class'   => Seesaw_extension_class,
	                    'method'  => $method,
	                    'hook'     =>$hook,
	                    'settings'  => '',
	                    'priority'  => 10,
	                    'version' => $this->version,
	                    'enabled' => "y"
	                    )
	                  );
		}
		
		// run all sql queries
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		
		// Set up & save default settings
		$default_settings = $this->_merge_settings(array());
		$this->_save_settings($default_settings);

		return TRUE;
	}


	/**
	 * Update Extension
	 *
	 * @param string   $current   Previous installed version of the extension
	 * @since version 0.4.6
	 */
	function update_extension($current = '')
	{
		global $DB;

		
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		
		if ($current < '0.4.6')
		{
			$settings = $this->_get_settings();
			if(! $settings) $settings = array();
			$settings = $this->_merge_settings($settings);
			$this->_save_settings($settings);
		}
		
		$DB->query('UPDATE ' . $this->db_prefix . '_extensions
		            SET version = "' . $this->version . '"
		            WHERE class = "' . Seesaw_extension_class . '"');
	}


	/**
	 * EE extension settings form
	 * @param array $current The current settings array
	 * @return array
	 */
	function settings_form($current)
	{
		global $DSP, $LANG, $IN, $DB;
		
		// in case structure has changed since last saving settings, merge
		$current = $this->_merge_settings($current);
		
		
		// Part 0: Breadcrumbs, disable  button, and open up form
		$DSP->crumbline = TRUE;

		$DSP->title  = $LANG->line('extension_settings');
		$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities'))
					 . $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager', $LANG->line('extensions_manager')))
					 . $DSP->crumb_item($this->name);

		$DSP->right_crumb($LANG->line('disable_extension'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=toggle_extension_confirm'.AMP.'which=disable'.AMP.'name='.$IN->GBL('name'));

	    $DSP->body = $DSP->form_open(
			array(
				'action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=save_extension_settings',
				'name'   => 'channel_edit_panels',
				'id'     => 'channel_edit_panels'
			),
			array('name' => get_class($this))
		);

		// PART 1: Table Header
		$DSP->body .= $DSP->table('tableBorder', '0', '', '100%');
		$DSP->body .= $DSP->tr();
		$DSP->body .= $DSP->td('tableHeading', '', '2');
		$DSP->body .= $this->name;
		$DSP->body .= $DSP->td_c();
		$DSP->body .= $DSP->tr_c();
		$DSP->body .= $DSP->tr();
		$DSP->body .= $DSP->td('tableCellTwo', '', '2');
		$DSP->body .= $DSP->qdiv('defaultBold', $LANG->line('instructions_header'));
		$DSP->body .= $DSP->qdiv('', $LANG->line('instructions'));
		$DSP->body .= $DSP->td_c();
		$DSP->body .= $DSP->tr_c();

		foreach($current as $channel_id => $channel_settings) {
			
			// skip if it's not actually channel info
			if(substr($channel_id, 0, 8) != 'channel_') continue;
			
			$DSP->body .= $DSP->tr();
			$DSP->body .= $DSP->td('tableCellOne');
			$DSP->body .= $DSP->qdiv('defaultBold', $channel_settings['title']);
			$DSP->body .= $DSP->td_c();
			$DSP->body .= $DSP->td('tableCellOne');
			$DSP->body .= $DSP->table('tableBorder', '0', '', '100%');
			$DSP->body .= '<col /><col width="150" /><col width="200" /><col width="100" /><col width="100" />';
			$DSP->body .= $DSP->td('tableHeadingAlt') . 'Field' . $DSP->td_c();
			$DSP->body .= $DSP->td('tableHeadingAlt') . 'Type' . $DSP->td_c();
			$DSP->body .= $DSP->td('tableHeadingAlt') . 'Format' . $DSP->td_c();
			$DSP->body .= $DSP->td('tableHeadingAlt', '', '', '', '', 'center') . 'Show' . $DSP->td_c();
			$DSP->body .= $DSP->td('tableHeadingAlt', '', '', '', '', 'center') . 'Hide' . $DSP->td_c();

			$count = 1; // count helps us toggle our tableCellOne / tableCellTwo styles
			
			// first cycle through core_fields
			foreach($channel_settings['core_fields'] as $field_id => $field_settings) {
				$style = ($count % 2 == 0) ? 'tableCellOne' : 'tableCellTwo';
				$count++;
				
				$DSP->body .= $this->_get_input_row($style, 'core_fields', $channel_id, $field_id, $field_settings);
			}

			// now cyle through any custom fields
			foreach($channel_settings['custom_fields'] as $field_id => $field_settings) {
				
				$style = ($count % 2 == 0) ? 'tableCellOne' : 'tableCellTwo';
				$count++;

				$DSP->body .= $this->_get_input_row($style, 'custom_fields', $channel_id, $field_id, $field_settings);
			}

			$DSP->body .= $DSP->tr_c();
			$DSP->body .=   $DSP->table_c();
			$DSP->body .= $DSP->td_c();
			$DSP->body .= $DSP->tr_c();
		}

		$DSP->body .=   $DSP->table_c();

	    // Updates Setting

		$lgau_query = $DB->query('SELECT class
		                          FROM ' . $this->db_prefix . '_extensions
		                          WHERE class = "Lg_addon_updater_ext"
		                            AND enabled = "y"
		                          LIMIT 1');
		$lgau_enabled = $lgau_query->num_rows ? TRUE : FALSE;
		$check_for_extension_updates = ($lgau_enabled AND $current['check_for_extension_updates'] == 'y') ? TRUE : FALSE;

		$DSP->body .= $DSP->table_open(
		                                   array(
		                                       'class'  => 'tableBorder',
		                                       'border' => '0',
		                                       'style' => 'margin-top:18px; width:100%'
		                                   )
		                               )

		            . $DSP->tr()
		            . $DSP->td('tableHeading', '', '2')
		            . $LANG->line("check_for_extension_updates_title")
		            . $DSP->td_c()
		            . $DSP->tr_c()

		            . $DSP->tr()
		            . $DSP->td('', '', '2')
		            . '<div class="box" style="border-width:0 0 1px 0; margin:0; padding:10px 5px"><p>'.$LANG->line('check_for_extension_updates_info').'</p></div>'
		            . $DSP->td_c()
		            . $DSP->tr_c()

		            . $DSP->tr()
		            . $DSP->td('tableCellOne', '60%')
		            . $DSP->qdiv('defaultBold', $LANG->line("check_for_extension_updates_label"))
		            . $DSP->td_c()

		            . $DSP->td('tableCellOne')
		            . '<select name="check_for_extension_updates"'.($lgau_enabled ? '' : ' disabled="disabled"').'>'
		            . $DSP->input_select_option('y', $LANG->line('yes'), ($current['check_for_extension_updates'] == 'y' ? 'y' : ''))
		            . $DSP->input_select_option('n', $LANG->line('no'),  ($current['check_for_extension_updates'] != 'y' ? 'y' : ''))
		            . $DSP->input_select_footer()
		            . ($lgau_enabled ? '' : NBS.NBS.NBS.$LANG->line('check_for_extension_updates_nolgau'))
		            . $DSP->td_c()
		            . $DSP->tr_c()

		            . $DSP->table_c();
	    
		
		$DSP->body .=   $DSP->qdiv('itemWrapperTop', $DSP->input_submit('Save Settings'));
		$DSP->body .=   $DSP->form_c();
	}


	/**
	 * Save settings
	 *
	 * @return	bool Always returns TRUE
	 */
	function save_settings()
	{
		global $IN;
		
		// Clean global input data		
		$settings = $IN->clean_input_data($_POST);
		
		// unset the name
		unset($settings['name']);

		// unset some mystery input additions
		foreach($settings as $key => $val) {
			if(strstr($key, '_core_fields')) unset($settings[$key]);
			if(strstr($key, '_custom_fields')) unset($settings[$key]);
		}

		return $this->_save_settings($settings);
	}


	/**
	 * Modify table headers for edit panel
	 *
	 * @param string $out Current table headers
	 * @return	string Modified table headers
	 */
	function edit_entries_modify_tableheader($out)
	{

		global $IN, $EXT;

		$out = ($EXT->last_call !== FALSE) ? $EXT->last_call : $out;
		
		$settings = $this->_get_settings();

		// if plugin has not yet been configured, abort
		if(! $settings) return $out;
		
		$supported_core_keys = array_keys($this->available_core_fields);

		// break output into array of <td>...</td> or <th>...</th>
		preg_match_all('#<t(h|d) [^>]*>.*?</t(h|d)[^>]*>#is', $out, $matches);
		$columns = $matches[0];
		
		// to avoid being destructive, let's replace all table cells with Seesaw, and then restore as we go
		$out = preg_replace('#<t(h|d) [^>]*>.*?</t(h|d)[^>]*>#is', 'Seesaw', $out);

		$channel_id  = 'channel_';
		$channel_id .= ($IN->GBL('weblog_id') && $IN->GBL('weblog_id') != 'null') ? $IN->GBL('weblog_id') : '0';
		
		$idx = 0; // $idx helps us keep track of any additional headers that have to be put back in at the end
		foreach($supported_core_keys as $core_field) {

			switch(true) {
				case( ! array_key_exists($core_field, $settings[$channel_id]['core_fields']) ) :
				case( $settings[$channel_id]['core_fields'][$core_field]['value'] == 'y' ) :
					$include = true;
					break;
				
				default :
					$include = false;
			}

			$out = ($include) ? preg_replace('/Seesaw/', $columns[$idx], $out, 1) : preg_replace('/Seesaw/', '', $out, 1);

			$idx++;
		}
		
		// add back in any columns that are not "supported" or "core"
		// first bring $idx down by 1 to simplify math
		if(count($columns) > $idx) {
			for($i = $idx; $i <= count($columns) - 1; $i++) {
				$out = preg_replace('/Seesaw/', $columns[$i], $out, 1 );
			}
		}
		
		return $out;
	}


	/**
	 * Modify table cells for edit panel
	 *
	 * @param string $out Current table row cells
	 * @return	string Modified table row cells
	 */
	function edit_entries_modify_tablerow($out)
	{

		global $IN, $EXT;

		$out = ($EXT->last_call !== FALSE) ? $EXT->last_call : $out;

		$settings = $this->_get_settings();

		// if plugin has not yet been configured, abort
		if(! $settings) return $out;
		
		$supported_core_keys = array_keys($this->available_core_fields);

		// break output into array of <td>...</td>
		preg_match_all('#<td[^>]*>.*?</td[^>]*>#is', $out, $matches);
		$columns = $matches[0];

		// to avoid being destructive, let's replace all table cells with Seesaw, and then restore as we go
		$out = preg_replace('#<td[^>]*>.*?</td[^>]*>#is', 'Seesaw', $out);

		$channel_id  = 'channel_';
		$channel_id .= ($IN->GBL('weblog_id') && $IN->GBL('weblog_id') != 'null') ? $IN->GBL('weblog_id') : '0';

		$idx = 0;
		foreach($supported_core_keys as $core_field) {
			switch(true) {
				case( ! array_key_exists($core_field, $settings[$channel_id]['core_fields']) ) :
				case( $settings[$channel_id]['core_fields'][$core_field]['value'] == 'y' ) :
					$include = true;
					break;
				
				default :
					$include = false;
			}

			$out = ($include) ? preg_replace('/Seesaw/', $columns[$idx], $out, 1) : preg_replace('/Seesaw/', '', $out, 1);

			$idx++;
		}
		
		// add back in any columns that are not "supported" or "core"
		// first bring $idx down by 1 to simplify math
		if(count($columns) > $idx) {
			for($i = $idx; $i <= count($columns) - 1; $i++) {
				$out = preg_replace('/Seesaw/', $columns[$i], $out, 1 );
			}
		}
		
		return $out;
	}


	/**
	 * Add table headers to edit panel
	 *
	 * @return	string Modified table row headers
	 */
	function edit_entries_additional_tableheader()
	{
		global $IN, $DB, $DSP, $EXT, $FNS;

		$extra = ($EXT->last_call !== FALSE) ? $EXT->last_call : '';

		$settings = $this->_get_settings();

		// if plugin has not yet been configured, abort
		if(! $settings) return $extra;

		// nothing to add if we're at main edit page?
		if(! $IN->GBL('weblog_id') || $IN->GBL('weblog_id') == 'null') {

			// Edge case: If user is only allowed to access one weblog, then the main edit page
			// will effectively be this view
			$allowed_blogs = $FNS->fetch_assigned_weblogs();
			if (count($allowed_blogs) == 1)
			{
				$channel_id = $allowed_blogs[0];
			} else {
				return $extra;
			}
		} 
		else
		{
			$channel_id = ($IN->GBL('weblog_id') && $IN->GBL('weblog_id') != 'null') ? $IN->GBL('weblog_id') : '0';
		}
		
		$channel_id = 'channel_' . $channel_id;

		$return = '';
		if(array_key_exists('custom_fields', $settings[$channel_id])) {
			foreach($settings[$channel_id]['custom_fields'] as $field_id => $field_settings) {
				if($field_settings['value'] != 'y') continue;
				$query = $DB->query('SELECT field_id, field_label
									 FROM ' . $this->db_prefix . '_weblog_fields
									 WHERE field_id="' . substr($field_id, 6) . '"
									 LIMIT 1');
	
				if($query->num_rows > 0) {
					$return .= $DSP->table_qcell('tableHeadingAlt', $query->row['field_label']);
				}
			}
		}

		return $return . $extra;
	}
	

	/**
	 * Add table cells for edit panel
	 *
	 * @param string $row Current table row cells
	 * @return	string Modified table row cells
	 */
	function edit_entries_additional_celldata($row)
	{
		global $IN, $DB, $row_count, $DSP, $EXT, $FNS;

		$extra = ($EXT->last_call !== FALSE) ? $EXT->last_call : '';

		$settings = $this->_get_settings();

		// if plugin has not yet been configured, abort
		if(! $settings) return $extra;
		
		// nothing to add if we're at main edit page?
		if(! $IN->GBL('weblog_id') || $IN->GBL('weblog_id') == 'null') {

			// Edge case: If user is only allowed to access one weblog, then the main edit page
			// will effectively be this view
			$allowed_blogs = $FNS->fetch_assigned_weblogs();
			if (count($allowed_blogs) == 1)
			{
				$channel_id = $allowed_blogs[0];
			} else {
				return $extra;
			}
		} 
		else
		{
			$channel_id = ($IN->GBL('weblog_id') && $IN->GBL('weblog_id') != 'null') ? $IN->GBL('weblog_id') : '0';
		}
		
		$channel_id = 'channel_' . $channel_id;

        if (empty($row_count)) $row_count= 1;
        $style = ($row_count % 2 == 0) ? 'tableCellOne' : 'tableCellTwo'; $row_count++;

		$fields = array();
		if(array_key_exists('custom_fields', $settings[$channel_id])) {
			foreach($settings[$channel_id]['custom_fields'] as $field_id => $field_settings) {
				if($field_settings['value'] === 'y') $fields['field_id_' . substr($field_id, 6)] = $field_settings;
			}
		}

		$return = '';
		if($fields) {
			$field_keys = array_keys($fields);
			$query = $DB->query('SELECT ' . implode(',', $field_keys) . '
								 FROM ' . $this->db_prefix . '_weblog_data
								 WHERE entry_id ="' . $row['entry_id'] . '"
								 LIMIT 1');
			
			$return = '';
			
			if($query->num_rows > 0) {
				foreach($fields as $field_key => $field_settings) {
					$formatted = $this->_display_celldata($query->result[0][$field_key], $field_settings['type'], $field_settings['format']);
					$return .= $DSP->table_qcell($style, $DSP->qdiv('smallNoWrap', $formatted));
				}
			}
		}

		$extra = ($EXT->last_call !== FALSE) ? $EXT->last_call : '';
		return $return . $extra;
	}
	

	/**
	* Disables the extension the extension and deletes settings from DB
	*/
	function disable_extension()
	{
		global $DB;
		$DB->query('DELETE FROM ' . $this->db_prefix . '_extensions WHERE class = "' . $DB->escape_str(Seesaw_extension_class) . '"');
	}


	/**
	* Register a new Addon Source
	*
	* @param array $sources The existing sources
	* @return array The new source list
	*/
	function lg_addon_update_register_source($sources)
	{
		global $EXT;
		// -- Check if we're not the only one using this hook
		if($EXT->last_call !== FALSE)
			$sources = $EXT->last_call;

		$settings = $this->_get_settings();

		if($settings['check_for_extension_updates'] == 'y')
		{
			$sources[] = 'http://johndwells.com/software/versions/';
		}

		return $sources;
	}


	/**
	* Register a new Addon
	*
	* @param array $addons The existing sources
	* @return array The new addon list
	*/
	function lg_addon_update_register_addon($addons)
	{
		global $EXT;
		// -- Check if we're not the only one using this hook
		if($EXT->last_call !== FALSE)
			$addons = $EXT->last_call;

		$settings = $this->_get_settings();

		// add a new addon
		// the key must match the id attribute in the source xml
		// the value must be the addons current version
		if($settings['check_for_extension_updates'] == 'y')
		{
			$addons[Seesaw_addon_id] = $this->version;
		}

		return $addons;
	}


	/**
	* Save settings to the DB
	*
	* @access	private
	* @param	array	$settings	The settings array
	* @return	bool
	*/
	function _save_settings($settings)
	{
		global $DB;

		// update the settings
		$query = $DB->query($sql = 'UPDATE ' . $this->db_prefix . '_extensions SET settings = "' . addslashes(serialize($settings)) . '" WHERE class = "' . Seesaw_extension_class . '"');
		
		return TRUE;
	}
	

	/**
	* Returns the extension settings from the DB
	*
	* @access	private
	* @return	array	The settings array
	*/
	function _get_settings()
	{
		global $DB, $REGX;

		// check the db for extension settings
		$query = $DB->query('SELECT settings
							 FROM '. $this->db_prefix . '_extensions
							 WHERE enabled = "y"
							 AND class = "' . Seesaw_extension_class . '"
							 LIMIT 1');

		return ($query->num_rows > 0 && $query->row['settings'] != '')
			? $REGX->array_stripslashes(unserialize($query->row['settings']))
			: array();
	}

	/**
	* Merge the current settings array with any structural amends since last saving
	*
	* @access	private
	* @param	array	$current	The current settings array
	* @return	array				The merged settings array
	*/
	function _merge_settings($current)
	{
		global $DSP, $LANG, $IN, $DB;
		
		// start off with the default edit view
		$settings = array(
			'channel_0' => array(
				'title' => $LANG->line('default_view'),
				'core_fields' => $this->available_core_fields,
				'custom_fields' => array()
			)
		);
		
		// Set up LG Addon Updater
		$settings['check_for_extension_updates'] = 'n';
		
		// look for custom columns (ff & gypsy for now)
		$custom_query = $DB->query('SELECT * FROM ' . $this->db_prefix . '_weblog_fields LIMIT 1');
		
		$ff_flag = (isset($custom_query->row['ff_settings'])) ? true : false;
		$gypsy_flag = (isset($custom_query->row['gypsy_weblogs'])) ? true : false;
		
		// fieldtypes support still a work in progress
		// if fieldframe is installed, let's see which ff types exist
/*
		if($ff_flag) {
			$ff_types_query = $DB->query('SELECT fieldtype_id, class FROM ' . $this->db_prefix . '_ff_fieldtypes');
		}
*/
		
		// query for channels
		$channels_query = $DB->query('SELECT weblog_id, blog_title, field_group
						     FROM ' . $this->db_prefix . '_weblogs');

		if ($channels_query->num_rows > 0) {

			foreach($channels_query->result as $channel) {
				$channel_id = 'channel_' . $channel['weblog_id'];
				$settings[$channel_id] = array(
					'title' => $channel['blog_title'],
					'core_fields' => $this->available_core_fields,
					'custom_fields' => array()
				);

				// create a bunch of empty arrays that we'll fill
				$settings[$channel_id]['custom_fields'] = $custom_fields = $gypsy_fields = array();

				// query field settings for each channel
				$custom_fields_query = $DB->query('SELECT field_id, field_label
									  FROM ' . $this->db_prefix . '_weblog_fields
									  WHERE group_id = "' . $channel['field_group'] . '"
									  ORDER BY field_order');

				if($custom_fields_query->num_rows > 0) {
					foreach($custom_fields_query->result as $field) {
						$custom_fields['field_' . $field['field_id']] = array(
							'value' => 'n',
							'label' => $field['field_label'],
							'type' => 'text',
							'format' => ''
						);
					}

					$channel_fields = $custom_fields;
				}
				
				// query gypsy field settings for each channel
				if ($gypsy_flag) {
					$gypsy_fields_query = $DB->query('SELECT field_id, field_label
											   FROM ' . $this->db_prefix . '_weblog_fields
											   WHERE field_is_gypsy = "y"
												 AND gypsy_weblogs LIKE "% ' . $channel['weblog_id'] . ' %"');
					
					if($gypsy_fields_query->num_rows > 0) {
						foreach($gypsy_fields_query->result as $field) {
							$gypsy_fields['field_' . $field['field_id']] = array(
								'value' => 'n',
								'label' => $field['field_label'],
								'type' => 'text',
								'format' => ''
							);
						}
					}
				}

				$settings[$channel_id]['custom_fields'] = array_merge($custom_fields , $gypsy_fields);
			}
		}

		// at this point, $settings is full of default values for any channels and fields that are available
		// now we need to merge the existing settings into this array, overwriting any previously-saved values
		foreach($settings as $channel_id => $channel_settings) {

			// skip non-channel info
			if(substr($channel_id, 0, 8) != 'channel_') continue;

			if(array_key_exists($channel_id, $current)) {
				// merge $current[$channel_id] into $settings[$channel_id]
				foreach($channel_settings['core_fields'] as $field_id => $field_settings) {
					if(array_key_exists($field_id, $current[$channel_id]['core_fields'])) {
						$settings[$channel_id]['core_fields'][$field_id]['value'] = $current[$channel_id]['core_fields'][$field_id]['value'];
					}
				}
				
				if(array_key_exists('custom_fields', $channel_settings)) {
					foreach($channel_settings['custom_fields'] as $field_id => $field_settings) {
						if(array_key_exists($field_id, $current[$channel_id]['custom_fields'])) {
							$settings[$channel_id]['custom_fields'][$field_id]['value'] = $current[$channel_id]['custom_fields'][$field_id]['value'];
							$settings[$channel_id]['custom_fields'][$field_id]['type'] = $current[$channel_id]['custom_fields'][$field_id]['type'];
							$settings[$channel_id]['custom_fields'][$field_id]['format'] = $current[$channel_id]['custom_fields'][$field_id]['format'];
						}
					}
				}
			}
		}
		
		// update LG Addon setting
		if(array_key_exists('check_for_extension_updates', $current)) {
			$settings['check_for_extension_updates'] = $current['check_for_extension_updates'];
		}

		return $settings;
	}

	/**
	* Build the field name
	*
	* @access	private
	* @param	string	$core_or_custom		Either "core_fields" or "custom_fields"
	* @param	string	$channel_id			The channel id
	* @param	string	$field_id			The field id
	* @param	string	$setting			The setting value
	* @return	string						The formatted field name
	*/
	function _implode_field_name($core_or_custom, $channel_id, $field_id, $setting)
	{
		return $channel_id . '[' . $core_or_custom . '][' . $field_id . '][' . $setting . ']';
	}
	

	/**
	* Build the settings form's input row
	*
	* @access	private
	* @param	string	$style				Either tableCellOne or tableCellTwo
	* @param	string	$core_or_custom		Either "core_fields" or "custom_fields"
	* @param	string	$channel_id			The channel id
	* @param	string	$field_id			The field id
	* @param	array	$field_settings		The field's settings
	* @return	string						The complete input row
	*/
	function _get_input_row($style, $core_or_custom, $channel_id, $field_id, $field_settings)
	{
		global $DSP, $LANG;
		$return  = $DSP->tr();
		
		$return .= $DSP->td($style) . $field_settings['label'] . $DSP->td_c();

		if($core_or_custom == 'core_fields') {
			$name = $this->_implode_field_name($core_or_custom, $channel_id, $field_id, 'type');
			$return .= $DSP->td($style) . '-' . $DSP->input_hidden($name, $field_settings['type']) . $DSP->td_c();

			$name = $this->_implode_field_name($core_or_custom, $channel_id, $field_id, 'format');
			$return .= $DSP->td($style) . '-' . $DSP->input_hidden($name, $field_settings['format']) . $DSP->td_c();

		} else {
	
			$name = $this->_implode_field_name($core_or_custom, $channel_id, $field_id, 'type');
			$return .= $DSP->td($style)
					.  '<select name="' . $name . '">';
			foreach($this->supported_field_types as $type) {
				$return .= '<option value="' . $type . '"';
				if($type == $field_settings['type']) $return .= ' selected="selected"';
				$return .= '>' . $LANG->line($type . '_select') . '</option>';
			}
			$return .= '</select>';
	
			$name = $this->_implode_field_name($core_or_custom, $channel_id, $field_id, 'format');
			$return .= $DSP->td($style) . $DSP->input_text($name, $field_settings['format']) . $DSP->td_c();
		}

		$name = $this->_implode_field_name($core_or_custom, $channel_id, $field_id, 'value');
		$return .= $DSP->td($style, '', '', '', '', 'center') .  $DSP->input_radio($name, 'y', ($field_settings['value'] == 'y') ? 1 : 0) . $DSP->td_c();
		$return .= $DSP->td($style, '', '', '', '', 'center') .  $DSP->input_radio($name, 'n', ($field_settings['value'] == 'n') ? 1 : 0) . $DSP->td_c();

		$return .= $DSP->tr_c();
		return $return;
	}


	/**
	* Build the edit table cell
	*
	* @access	private
	* @param	string	$value		The field value
	* @param	string	$type		What type of data it is
	* @param	string	$format		Any formatting
	* @return	string				The complete table cell
	*/
	function _display_celldata($value, $type = 'text', $format = '')
	{
		global $LOC, $LANG;

		$formatted = $value;
		$format = stripslashes($format);
		switch($type) {
			case('timestamp') :
				$formatted = $LOC->decode_date($format, $formatted);
				break;

			case('text') :
			
				$formatted = strip_tags($formatted);
				$format = (int) $format;
				if( $format > 0) {
					$formatted = substr($formatted, 0, $format);
					if(strlen($formatted) < strlen($value)) $formatted = $formatted . '...';
				}
				break;

			case('mx_ue_img') :
				$img_name = basename($formatted);
				$formatted = '<a href="' . $formatted . '" title="' . $LANG->line('mx_ue_img_view_large') . '" style="border: 0"><img src="' . dirname($formatted) . '/_thumbs/_' . $img_name . '" alt="' . $img_name . '" style="border: 0;" /></a>';
				break;

			case('custom_html') :
				$formatted = str_replace('{value}', $formatted, $format);
				break;

/*
			case('custom_php') :
				$php = str_replace('{value}', $formatted, $format);
				if(substr($php, -1) != ';') $php = $php . ';';
				if(substr($php, 0, 7) != 'return ') $php = 'return ' . $php;
				$formatted = eval($php . ';');
				if($formatted === FALSE) $formatted = 'syntax error';
				break;
*/
		}
		return $formatted;
	}

}
// END CLASS
?>