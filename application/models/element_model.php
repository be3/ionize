<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Element_model extends Base_model 
{

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	public function __construct()
	{
		parent::__construct();

		$this->table = 'element';
		$this->pk_name = 'id_element';

		$this->definition_table = 'element_definition';
		$this->definition_pk_name = 'id_element_definition';

		$this->fields_table = 'extend_fields';
		
		
	}
	
	
	// ------------------------------------------------------------------------
	

	/**
	 * Get all elements
	 *
	 */
	function get_elements($where)
	{
		$data = array();
		
		// Perform conditions from the $where array
		foreach(array('limit', 'offset', 'order_by', 'like') as $key)
		{
			if(isset($where[$key]))
			{
				call_user_func(array($this->db, $key), $where[$key]);
				unset($where[$key]);
			}
		}

		$where = $this->correct_ambiguous_conditions($where, $this->table);

		if ( !empty ($where) )
			$this->db->where($where);

		
		$this->db->join($this->table, $this->table.'.'.$this->definition_pk_name.'='.$this->definition_table.'.'.$this->definition_pk_name );
		
		$query = $this->db->get($this->definition_table);
		
		if ( $query->num_rows() > 0 )
			$data = $query->result_array();

		$query->free_result();
		
		return $data;
	}
	
	
	// ------------------------------------------------------------------------
	
	
	/**
	 * Returns all the element fields from one element instance
	 *
	 */ 
	function get_element_fields($id_element)
	{
		// Loads the element model if it isn't loaded
		$CI =& get_instance();
		if (!isset($CI->element_definition_model)) $CI->load->model('element_definition_model');
		
		// Get the element
		$element = $this->get(array('id_element' => $id_element) );


		// Get Element fields definition 
		$cond = array(
			'id_element_definition' => $element['id_element_definition'],
			'order_by' => 'ordering ASC'
		);
		$definitions_fields = $CI->element_definition_model->get_list($cond, 'extend_field');


		// Get fields instances
		$sql = 'select extend_field.*, extend_fields.*
				from extend_fields
				join extend_field on extend_field.id_extend_field = extend_fields.id_extend_field
				where extend_fields.id_element = \''.$id_element.'\'
				order by extend_field.ordering ASC';

		$query = $this->db->query($sql);

		$result = array();
		if ( $query->num_rows() > 0)
			$result = $query->result_array();
		$query->free_result();

		// Feed each field with content for the element fields
		$langs = Settings::get_languages();
		$extend_fields_fields = $this->db->list_fields('extend_fields');
		
		foreach($definitions_fields as $key => &$df)
		{
			if ($df['translated'] == '1')
			{
				foreach($langs as $language)
				{
					$df[$language['lang']]['content'] = '';
				}
			}
					
			foreach($result as $row)
			{
				if ($row['id_extend_field'] == $df['id_extend_field'])
				{
					$df = array_merge($df, $row);
					
					if ($df['translated'] == '1')
					{
						foreach($langs as $language)
						{
							$lang = $language['lang'];
							
							if($row['lang'] == $lang)
							{
								$df[$lang]['content'] = $row['content'];
							}
						}
					}
				}
			}
		}

		return $definitions_fields;
	}



	
	// ------------------------------------------------------------------------
	

	function get_fields_from_parent($parent, $id_parent, $id_definition = FALSE, $id_element = FALSE)
	{
		// Loads the element model if it isn't loaded
		$CI =& get_instance();
		if (!isset($CI->element_definition_model)) $CI->load->model('element_definition_model');

		// Get definitions
		$cond = array();

		if ($id_definition != FALSE)
			$cond['id_element_definition'] = $id_definition;
		
		$definitions = $CI->element_definition_model->get_list($cond);
		

		// Get definitions fields
		$cond = array(
			'id_element_definition <>' => '0',
			'order_by' => 'ordering ASC'
		);
		$definitions_fields = $CI->element_definition_model->get_list($cond, 'extend_field');


		// Get Elements
		$cond = array('order_by' => 'element.ordering ASC' );
		
		if ($id_element) {
			$cond['id_element'] = $id_element;
		}
		else
		{
			$cond['parent'] = $parent;
			$cond['id_parent'] = $id_parent;
		}
		
		$elements = $this->get_elements($cond);


		// Get fields instances
		$where = ' where extend_fields.id_element in (
					select id_element from element
					where parent= \''.$parent.'\'
					and id_parent= \''.$id_parent.'\'
				)';

		if ($id_element)
			$where = ' where extend_fields.id_element = \''.$id_element.'\'';
		
		$sql = 'select extend_field.*, extend_fields.*
				from extend_fields
				join extend_field on extend_field.id_extend_field = extend_fields.id_extend_field'
				.$where;
		
		$query = $this->db->query($sql);

		$result = array();
		if ( $query->num_rows() > 0)
			$result = $query->result_array();
		$query->free_result();


		$langs = Settings::get_languages();
		$extend_field_fields = $this->db->list_fields('extend_field');
		$extend_fields_fields = $this->db->list_fields('extend_fields');
		
		foreach($definitions as $key => &$definition)
		{
			$definition['elements'] = array();
			
			foreach($elements as $element)
			{
				// The element match a definition
				if ($element['id_element_definition'] == $definition['id_element_definition'])
				{
					$element['fields'] = array();
					
					foreach($definitions_fields as $df)
					{
						if ($df['id_element_definition'] == $definition['id_element_definition'])
						{
							$el = array_merge(array_fill_keys($extend_fields_fields, ''), $df);

							foreach($result as $row)
							{
								if ($row['id_element'] == $element['id_element'] && $row['id_extend_field'] == $df['id_extend_field'])
								{
									$el = array_merge($el, $row);
									
									if ($df['translated'] == '1')
									{
										foreach($langs as $language)
										{
											$lang = $language['lang'];
											
											if($row['lang'] == $lang)
											{
												$el[$lang] = $row;
											}
										}
									}
								}
							}
							$element['fields'][$df['name']] = $el;
						}
					}
					$definition['elements'][] = $element;
				}
			}
			
			if (empty($definition['elements']))
				unset($definitions[$key]);
		}
		
		if (count($definitions) == 1)
			$definitions = array_shift($definitions);
		
		return $definitions;
	}


	

	/**
	 * Returns the whole tree of fields 
	 *
	function get_fields_from_parent($parent, $id_parent)
	{
		$data = array();
		
		// Get definitions
		$definitions = $this->get_list();
		
		// Get definitions fields
		$definitions_fields = $this->get_list(array('id_element_definition <>' => '0', 'order_by' => 'ordering ASC'), 'extend_field');

		// Get Elements
		$elements = $this->get_elements(array('parent'=>$parent, 'id_parent' => $id_parent, 'order_by' => 'element.ordering ASC'));

		// Get fields instances
		$sql = 'select extend_field.*, extend_fields.*
				from extend_fields
				join extend_field on extend_field.id_extend_field = extend_fields.id_extend_field
				where extend_fields.id_element in (
					select id_element from element
					where parent= \''.$parent.'\'
					and id_parent= \''.$id_parent.'\'
				)';
		
		$query = $this->db->query($sql);

		$result = array();
		if ( $query->num_rows() > 0)
			$result = $query->result_array();
		$query->free_result();


		$langs = Settings::get_languages();

		$element_fields = $this->db->list_fields('extend_fields');
		
		foreach($definitions as $key => &$definition)
		{
			$definition['elements'] = array();
			
			foreach($elements as $element)
			{
				// The element match a definition
				if ($element['id_element_definition'] == $definition['id_element_definition'])
				{
					$element['fields'] = array();
					
					foreach($definitions_fields as $df)
					{
						if ($df['id_element_definition'] == $definition['id_element_definition'])
						{
							$el = array_fill_keys($element_fields, '');
							
							foreach($result as $row)
							{
								if ($row['id_element'] == $element['id_element'] && $row['id_extend_field'] == $df['id_extend_field'])
								{
									$el = array_merge($el, $row);
									
									if ($df['translated'] == '1')
									{
										foreach($langs as $language)
										{
											$lang = $language['lang'];
											
											if($row['lang'] == $lang)
											{
												$el[$lang] = $row;
											}
										}
									}
								}
							}
							$element['fields'][$df['name']] = $el;
						}
					}
					$definition['elements'][] = $element;
				}
			}
			
			if (empty($definition['elements']))
				unset($definitions[$key]);
		}

		return $definitions;
	}
	
*/	
	// ------------------------------------------------------------------------
	
	
	/**
	 * Save one element instance
	 *
	 */
	function save($parent, $id_parent, $id_element = FALSE, $id_element_definition, $post)
	{
		// Insert the element, if needed
		if ( ! $id_element OR $this->exists(array('id_element' => $id_element), $this->table) == FALSE)
		{
			$ordering = $this->_get_ordering($post['ordering'], $parent, $id_parent, $id_element_definition);
		
			$element = array
			(
				'id_element_definition' => $id_element_definition,
				'parent' => $parent,
				'id_parent' => $id_parent,
				'ordering' => $ordering
			);
			$this->db->insert('element', $element);
			$id_element = $this->db->insert_id();
		}
		
		// Save fields
		$extend_fields = $this->get_list(array('id_element_definition' => $id_element_definition), 'extend_field');

		foreach ($extend_fields as $extend_field)
		{
			// Link between extend_field, current parent and element
			$where = array(
				'id_extend_field' => $extend_field['id_extend_field'],
				'id_parent' => $id_parent,
				'id_element' => $id_element
			);
			
			// Checkboxes : first clear values from DB as the var isn't in $_POST if no value is checked
			if ($extend_field['type'] == '4')
			{
				$this->db->where($where);
				$this->db->delete('extend_fields');			
			}
			
			// Get the value from _POST values ($data) and feed the data array
			foreach ($post as $k => $value)
			{
				if (substr($k, 0, 2) == 'cf')
				{
					// Fill the extend field value with nothing : safe for checkboxes
					$data = array();
					$data['content'] = '';
					$data['lang'] = '';
					$data['id_parent'] = $id_parent;
					$data['id_element'] = $id_element;

					// id of the extend field
					$key = explode('_', $k);

					// if language code is set, use it in the query
					if (isset($key[2]))
					{
						$where['lang'] = $data['lang'] = $key[2];
					}
					
					// If the extend field ID is set, we can safelly save...
					if (isset($key[1]) && $key[1] == $extend_field['id_extend_field'])
					{
						// if value is an array...
						if (is_array($value)) {	$value = implode(',', $value); }

						$data['content'] = $value;	

						// Update
						if( $this->exists($where, 'extend_fields'))
						{
							$this->db->where($where);
							$this->db->update('extend_fields', $data);
						}
						// Insert
						else
						{
							// Set the extend field element field ID
							$data['id_extend_field'] = $key[1];
							
							$this->db->insert('extend_fields', $data);
						}
					}
				}
			} // foreach ($post as $k => $value)
		} // foreach ($extend_fields as $extend_field)
		
		return $id_element;	
	}


	// ------------------------------------------------------------------------


	function delete($id)
	{
		$affected_rows = 0;
		
		// Check if exists
		if( $this->exists(array($this->pk_name => $id)) )
		{
			// Element delete
			$affected_rows += $this->db->where($this->pk_name, $id)->delete($this->table);
			
			// Extend fields content delete
			$affected_rows += $this->db->where($this->pk_name, $id)->delete($this->fields_table);
		}
		
		return $affected_rows;
	}


	// ------------------------------------------------------------------------
	

	function copy($data)
	{
		$return = FALSE;
		
		// Get the existing element
		$element = $this->get(array('id_element' => $data['id_element']));

		// TODO : For each parent, setup if element is pur at first / last when copying
		$ordering = $this->_get_ordering('first', $data['parent'], $data['id_parent'], $element['id_element_definition']);
		
		// Alter and save a copy of the element
		$element['id_parent'] = $data['id_parent'];
		$element['parent'] = $data['parent'];
		$element['ordering'] = $ordering;
		
		unset($element['id_element']);

		$this->db->insert('element', $element);
		$return = $id_element = $this->db->insert_id();
		
		if ($id_element)
		{
			// Copy all fields
			$sql = 	'insert into extend_fields 
					 (
					 	id_extend_field,
					 	id_parent,
					 	lang,
					 	content,
					 	ordering,
					 	id_element
					)
					select
						id_extend_field, 
						'.$data['id_parent'].',
						lang,
						content,
						ordering,
						'.$id_element.'
					from extend_fields 
					where id_element = '.$data['id_element'];

			$return = $this->db->query($sql);
		}
		return $return;
	}


	// ------------------------------------------------------------------------
	

	function move($data)
	{
		$where = array
		(
			'id_element' => $data['id_element']
		);
		$data = array
		(
			'parent' => $data['parent'],
			'id_parent' => $data['id_parent']
		);

		return $this->update($where, $data);
	}


	// ------------------------------------------------------------------------
	

 	/**
 	 * Gets the element's ordering
 	 *
 	 */
	function _get_ordering($place, $parent, $id_parent, $id_element_definition)
	{

		$ordering = '0';

		switch($place)
		{
			case 'first' :
			
				break;
			
			case 'last' :
			
				$cond = array
				(
					'id_element_definition' => $id_element_definition,
					'id_parent' => $id_parent,
					'parent' => $parent
				);
				$ordering = count($this->get_elements($cond));
				
				break;
		}
		return $ordering;
	}


}

/* End of file element_model.php */
/* Location: ./application/models/element_model.php */