<?php
/**
 * Рисование административной панели в FE-режиме
 *
 */

function __panelMakeTree(&$tree, $rows)
{
	if (isset($tree['children'])){
		foreach ($tree['children'] as $key => $primary){
			$tree['children'][$key] = $rows[$primary];
			__panelMakeTree($tree['children'][$key], $rows);
		}
	}
}

function __panelRenderLine(&$content, $tree, $level = 1)
{
	$content .= '<ul class="coffe-panel-line coffe-panel-line-' . $level . '" >';
	$level++;
	foreach ($tree as $item){
		$content .= '<li>';
		$onclick = isset($item['onclick']) ? $item['onclick'] : '';
		$href = isset($item['href']) ? $item['href'] : '';;
		if ($onclick && !trim($href)){$href = 'javascript:void(0)';}
		$content .= '<span><a href="' . $href . '" onclick="' . $onclick . '">' . htmlspecialchars($item['label']) . '</a></span>';
		if (isset($item['children']) && is_array($item['children'])){
			__panelRenderLine($content , $item['children'] , $level);
		}
		$content .= '</li>';
	}
	$content .= '</ul>';
}

$menu['_top'] = array('id' => '_top');

$tmp = Coffe_ModuleManager::getMenu();

$template = Coffe::getConfig('adminTemplate','default');

Coffe::getHead()->addCssFile('_coffe_panel_css', Coffe::getUrlPrefix() . 'coffe/admin/templates/' . $template . '/css/panel.css');
Coffe::getHead()->addJsFile('_coffe_panel_js', Coffe::getUrlPrefix() . 'coffe/admin/templates/' . $template . '/js/panel.js');
Coffe::getHead()->addJsFile('_coffe_panel_init', '',"COFFE_PANEL.base_url = '" . Coffe::getUrlPrefix() . "';");

foreach ($tmp as $id => $item){
	$menu[$item['id']] = $item;
}

foreach ($menu as $id => $item){
	if (isset($item['position']) && isset($menu[$item['position']])){
		$menu[$item['position']]['children'][] = $id;
	}
}

$tree = $menu['_top'];
__panelMakeTree($tree, $menu);

$content = '<div id="coffe-panel">';
$content .= '<div class="coffe-panel-inner">';

$content .= '<div id="coffe-panel-menu">';
if (isset($tree['children']) && is_array($tree['children']))
	__panelRenderLine($content, $tree['children']);
$content .= '</div>';

$edit_mode = Coffe::isEditMode();

$content .= '<div class="coffe-icon-panel">';

$content .= '<div class="coffe-icon">';
$href = urldecode(Coffe::getUrlPrefix() . 'index.php?cfAjax=setUserKey&key=admin_edit_mode&data=' .( ($edit_mode) ? '0' : '1'));
$content .= '<a href="javascript:void(0)" onclick="COFFE_PANEL.doAjaxOperation(\''.$href.'\',true)">';
$content .= ($edit_mode) ? Coffe_Func::getIcon('coffe/admin/icons/;panel/edit') : Coffe_Func::getIcon('coffe/admin/icons/;panel/edit_off');
$content .= '</a>';
$content .= '</div>';

$content .= '</div>';

$user = Coffe_User::get();
Coffe_ModuleManager::loadModuleLang('main');

$content .= '<div class="coffe-panel-right">';
$content .= '<div class="coffe-panel-welcome">' . $GLOBALS['LANG']->get('welcome_message',array('USER' => $user['username'])) . '</div>';
$content .= '<div class="coffe-panel-exit"><a href="'.Coffe_ModuleManager::getBackendModuleUrl('_login',array('logout' => '1')).'">' . $GLOBALS['LANG']->get('exit_message') . '</a></div>';
$content .= '</div>';

$content .= '</div>';
$content .= '</div>';

echo $content;