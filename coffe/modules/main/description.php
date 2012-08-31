<?php

return array(
	'title' =>  'LANG:MODULE:main/lang.xml;module_title',
	'description' => 'LANG:module_description',
	'version' => '0.7',
	'state' => 'alpha',
	'tableEditor' => array(
		'page' => array(
			'primary' => 'uid',
			'sorting_field' => 'sorting',
			'hidden_field' => 'hidden',
			'sorting_field_group' => 'pid',
			'update_nav_frame' => true,
			'record_title' => 'LANG:table_page_record_title',
			'elements' => array(
				'pid' => array(
					'type' => 'hidden',
				),
				'hidden' => array(
					'type' => 'checkbox',
					'config' => array(
						'label' => 'LANG:table_page_hidden'
					)
				),
				'type' => array(
					'type' => 'select',
					'config' => array(
						'label' => 'LANG:table_page_type',
						'options' => array(
							'10' => 'LANG:table_page_type10',
							'20' => 'LANG:table_page_type20',
						)
					)
				),
				'title' => array(
					'type' => 'text',
					'config' => array(
						'label' => 'LANG:table_page_title',
						'require' => true,
					)
				),
				'nav_title' => array(
					'type' => 'text',
					'config' => array(
						'label' => 'LANG:table_page_nav_title'
					)
				),
				'nav_hide' => array(
					'type' => 'checkbox',
					'config' => array(
						'label' => 'LANG:table_page_nav_hide'
					)
				),
				'link' => array(
					'type' => 'text',
					'config' => array(
						'label' => 'LANG:table_page_link'
					)
				),
				'alias' => array(
					'type' => 'text',
					'config' => array(
						'label' => 'LANG:table_page_alias'
					)
				),
				'config' => array(
					'type' => 'textarea',
					'config' => array(
						'label' => 'LANG:table_page_config'
					)
				),
				'description' => array(
					'type' => 'textarea',
					'config' => array(
						'label' => 'LANG:table_page_description'
					)
				),
				'keywords' => array(
					'type' => 'textarea',
					'config' => array(
						'label' => 'LANG:table_page_keywords'
					)
				),
			),
			'groups' => array(
				'main' => array(
					'elements' => array('pid', 'hidden','type','title','nav_title','nav_hide','link','alias'),
					'label' => 'LANG:table_page_tab_main',
				),
				'config' => array(
					'elements' => array('config'),
					'label' => 'LANG:table_page_tab_config',
				),
				'meta' => array(
					'elements' => array('description','keywords'),
					'label' => 'LANG:table_page_tab_meta',
				),
			),
			'readonly' => array('pid','hidden','type', 'config')
		),
		'component' => array(
			'primary' => 'uid',
			'sorting_field' => 'sorting',
			'sorting_field_group' => 'pid',
			'record_title' => 'LANG:table_component_record_title',
			'hidden_field' => 'hidden',
			'elements' => array(
				'position' => array(
					'type' => 'select',
					'config' => array(
						'label' => 'LANG:table_component_position',
						'options' => array(
							0 => 'LANG:table_component_position0',
							1 => 'LANG:table_component_position1',
							2 => 'LANG:table_component_position2',
							3 => 'LANG:table_component_position3',
						)
					)
				),
				'cp_group' => array(
					'type' => 'text',
					'config' => array(
						'label' => 'LANG:table_component_cp_group',
					)
				),
				'hidden' => array(
					'type' => 'checkbox',
					'config' => array(
						'label' => 'LANG:table_component_hidden'
					)
				),
				'title' => array(
					'type' => 'text',
					'config' => array(
						'label' => 'LANG:table_component_title',
					)
				),
				'content' => array(
					'type' => 'textarea',
					'config' => array(
						'label' => 'LANG:table_component_content'
					)
				) ,
				'cache' => array(
					'type' => 'checkbox',
					'config' => array(
						'label' => 'LANG:table_component_cache'
					)
				),
				'config' => array(
					'type' => 'textarea',
					'config' => array(
						'label' => 'LANG:table_component_config'
					)
				) ,
				'template' => array(
					'type' => 'select',
					'config' => array(
						'label' => 'LANG:table_component_template',
					)
				) ,
				'pid' => array(
					'type' => 'hidden',
				),
				'id' => array(
					'type' => 'hidden',
					'require' => true,
				),
			),
			'groups' => array(
				'main' => array(
					'elements' => array('position', 'cp_group', 'template', 'hidden', 'cache','title', 'content', 'pid','id'),
					'label' => 'LANG:table_component_tab_main'
				),
				'default' => array(
					'label' => 'LANG:table_component_tab_default'
				),
				'config' => array(
					'label' => 'LANG:table_component_tab_config',
					'elements' => array('config'),
				),
			),
			'readonly' => array('position','template','hidden', 'id', 'pid')
		),
		'user' => array(
			'primary' => 'uid',
			'record_title' => 'LANG:table_user_record_title',
			'hidden_field' => 'disable',
			'elements' => array(
				'disable' => array(
					'type' => 'checkbox',
					'config' => array(
						'label' => 'LANG:table_user_disable'
					)
				),
				'username' => array(
					'type' => 'text',
					'config' => array(
						'label' => 'LANG:table_user_username',
						'require' => true,
					)
				),
				'admin' => array(
					'type' => 'checkbox',
					'config' => array(
						'label' => 'LANG:table_user_admin'
					)
				),
				'last_name' => array(
					'type' => 'text',
					'config' => array(
						'label' => 'LANG:table_user_last_name'
					)
				),
				'first_name' => array(
					'type' => 'text',
					'config' => array(
						'label' => 'LANG:table_user_first_name'
					)
				),
				'middle_name' => array(
					'type' => 'text',
					'config' => array(
						'label' => 'LANG:table_user_middle_name'
					)
				),
				'external' => array(
					'type' => 'text',
					'config' => array(
						'label' => 'LANG:table_user_external'
					)
				),
				'user_group' => array(
					'type' => 'selectDB',
					'config' => array(
						'label' => 'LANG:table_user_group',
						'table' => 'user_group',
						'primary' => 'uid',
						'title' => 'title',
						'multiple' => true,
						'size' => 5,
						'sql_sorting' => 'title',
					)
				),
				'password' => array(
					'type' => 'password',
					'config' => array(
						'label' => 'LANG:table_user_password',
						'require' => true,
					)
				),
			),
			'groups' => array(
				'main' => array(
					'label' => 'LANG:table_user_tab_main',
					'elements' => array('disable', 'username', 'admin', 'last_name', 'first_name', 'middle_name', 'external', 'password' ,'user_group'),
				),
				'default' => array(
					'label' => 'LANG:table_user_tab_default'
				),
			),
			'readonly' => array()
		),
	)
);