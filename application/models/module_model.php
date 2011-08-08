<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Ionize, creative CMS
 *
 * @package		Ionize
 * @author		Ionize Dev Team
 * @license		http://ionizecms.com/doc-license
 * @link		http://ionizecms.com
 * @since		Version 0.9.6
 */

// ------------------------------------------------------------------------

/**
 * Module Model
 *
 * @package		Ionize
 * @subpackage	Models
 * @category	Modules
 * @author		Ionize Dev Team
 */

class Module_Model extends Base_model 
{

	public $module_setting_table =	'module_settings';


	public function __construct()
	{
		parent::__construct();

		$this->set_table('setting');
		$this->set_pk_name('id_setting');
	}


	// ------------------------------------------------------------------------


	/** 
	 * Get languages from LANG table
	 *
	 * @return	The lang array
	 */
	function get_languages()
	{
		return $this->db->from('lang')->order_by('ordering', 'ASC')->get()->result_array();
	}
}
/* End of file module_model.php */
/* Location: ./application/models/module_model.php */