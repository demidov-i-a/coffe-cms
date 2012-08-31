<?php

/**
 * ����� ��� ���������� ������������
 *
 * @package coffe_cms
 */
class Coffe_CpManager
{

    /**
     * ��� ������� � ������������
     *
     * @var string
     */
    private static $table = 'component';

    /**
     * ���������� � ������������ ������������ PATH_ROOT
     *
     * @var string
     */
    private static $component_dir = 'coffe/components/';

    /**
     * �������� ������ ���� �����������
     *
     * @static
     * @return array
     */
    public static function getAllComponents()
    {
        $components = array();
        $path = PATH_ROOT . self::$component_dir;
        $dh = @opendir($path);
        while (false !== ($sub_dir = @readdir($dh))) {
            if ($sub_dir !== '.' && $sub_dir !== '..' && is_dir($path . $sub_dir)) {
                $components[$sub_dir] = self::getOneComponent($sub_dir);
            }
        }
        @closedir($dh);
        return $components;
    }

    /**
     * ��������� ������ ����������
     *
     * @static
     * @param $key
     * @return array|bool
     */
    public static function getOneComponent($key)
    {
        $component['id'] = $key;
        $component['path'] = self::$component_dir . $key . '/';
        if (is_dir(PATH_ROOT . $component['path'])){
            $description_file_path = PATH_ROOT . $component['path'] . 'description.php';
            if (file_exists($description_file_path)){
                $component['description'] = require($description_file_path);
                if (is_array($component['description'])){
                    Coffe_Func::parseLangInArray($component['description']);
                }
            }
            if (!isset($component['description']['title'])){
                $component['description']['title'] = $component['id'];
            }
            return $component;
        }

        return false;
    }

    /**
     * ��������� ������ �������� ��� ����������
     *
     * @static
     * @param $component
     * @return array
     */
    public static function getComponentTemplates($component)
    {
        $templates = array();
        $component = self::getOneComponent($component);
        if ($component){
            //������� �� ����� ����������
            $path = PATH_ROOT . $component['path'] . 'templates/';
            if (is_dir($path)){
                $dh = @opendir($path);
                while (false !== ($sub_dir = @readdir($dh))) {
                    if ($sub_dir !== '.' && $sub_dir !== '..' && is_dir($path . $sub_dir)) {
                        $templates['coffe:' . $sub_dir] = 'coffe:' . $sub_dir;
                    }
                }
                @closedir($dh);
            }

            //���� ������� ���������� � ���������������� ��������
            $path = PATH_ROOT . Coffe_Template::getTemplatesDir();
            if (is_dir($path)){
                $dh = @opendir($path);
                while (false !== ($sub_dir = @readdir($dh))) {
                    if ($sub_dir !== '.' && $sub_dir !== '..' && is_dir($path . $sub_dir)) {
                        $path2 = $path . $sub_dir . '/components/' . $component['id'] . '/templates/';
                        if (is_dir($path2)){
                            $dh2 = @opendir($path2);
                            while (false !== ($sub_dir2 = @readdir($dh2))) {
                                if ($sub_dir2 !== '.' && $sub_dir2 !== '..' && is_dir($path2 . $sub_dir2)) {
                                    $templates[$sub_dir .':'. $sub_dir2] = $sub_dir .':'. $sub_dir2;
                                }
                            }
                            @closedir($dh2);
                        }
                    }
                }
                @closedir($dh);
            }
        }
        return $templates;
    }

    /**
     * ���������� ������ ���������� �� ����
     *
     * @static
     * @param int $uid
     * @param string $add_where �������������� �������
     * @return array|bool
     */
    public static function getRowById($uid, $add_where = 'NOT hidden')
    {
        GLOBAL $COFFE_DB;
        $where = 'uid = ' . intval($uid);
        $where .= (!empty($add_where)) ? (' AND ' . $add_where) : '';
        return $COFFE_DB->fetch($COFFE_DB->select('*', self::$table, $where));
    }

    /**
     * ���������� ���������� ��������
     *
     * @static
     * @param $pid
     * @param bool $hidden
     * @param null $position
     * @return mixed
     */
    public static function getRowsByPid($pid, $hidden = false, $position = null)
    {
        GLOBAL $COFFE_DB;
        $where = 'pid = ' . intval($pid);
        $where .= ($hidden) ? '' : ' AND NOT hidden';
        if ($position !== null && is_int($position)){
            $where .= ' AND position = ' . $position;
        }
        return $COFFE_DB->fetchAll($COFFE_DB->select('*', self::$table, $where, '', 'sorting'));
    }

    /**
     * ���������� ���������� �� ������ ������
     *
     * @static
     * @param $group
     * @param bool $hidden
     * @return mixed
     */
    public static function getRowsByGroup($group, $hidden = false)
    {
        GLOBAL $COFFE_DB;
        $where = 'cp_group = ' . $COFFE_DB->fullEscapeString($group);
        $where .= ($hidden) ? '' : ' AND NOT hidden';
        return $COFFE_DB->fetchAll($COFFE_DB->select('*', self::$table, $where, '', 'sorting'));
    }

    /**
     * ��������� ���� ����������� �� ����������� � ��������
     *
     * @static
     * @return mixed
     */
    public static function getRowsNoPage()
    {
        GLOBAL $COFFE_DB;
        return $COFFE_DB->fetchAll($COFFE_DB->select('*', self::$table, 'NOT pid', '', 'sorting'));
    }

    /**
     * ��������� ���������� �����������
     *
     * @static
     * @param $pid
     * @param bool $hidden
     * @param null $position
     * @return mixed
     */
    public static function getCountRows($pid, $hidden = false, $position = null)
    {
        GLOBAL $COFFE_DB;
        $where = 'pid = ' . intval($pid);
        $where .= ($hidden) ? '' : ' AND NOT hidden';
        if ($position !== null && is_int($position)){
            $where .= ' AND position = ' . $position;
        }
        $res = $COFFE_DB->select('count(*) as cnt', self::$table, $where);
        $row = $COFFE_DB->fetch($res);
        return $row['cnt'];
    }

    /**
     * ����������� ����������
     *
     * @static
     * @param $id
     * @param $template
     * @param $config
     * @param null $data
     * @return string
     */
    public static function includeComponent($id, $template, $config, $data)
    {
        $component = trim($id);
        $parts = explode('.', $component);

        $template_parts = Coffe_Func::trimExplode(':',$template);

        if (count($template_parts) != 2){
            return self::showError("The template of a component isn't found");
        }

        $component_class_prefix = implode('_',$parts);

        $cp_path = self::$component_dir . $id . '/';

        //���� � ������� ���������� � ���������������� ��������
        $template_cp_path = false;
        //���� ����� ���������������� ������
        if ($template_parts[0]  != 'coffe'){
            $template_cp_path = Coffe_Template::getTemplatesDir() . $template_parts[0] . '/components/' . $id . '/';
        }

        $obj = null;
        //��������� ������� ��������� ����� ����������
        if (!file_exists(PATH_ROOT . $cp_path . 'component.php')){
            return self::showError("The file of a component isn't found");
        }

        //���� ���������� ���������� ������, �.�. ��������������, ���
        //� ���������� ����� �������������� �� ����
        require_once(PATH_ROOT . $cp_path . 'component.php');

        //���� ���� � �������� ������������
        if ($template_cp_path && file_exists(PATH_ROOT . $template_cp_path . 'component.php')){
            require_once(PATH_ROOT . $template_cp_path . 'component.php');
            $class = 'User_' . $component_class_prefix .'_Component';
            if (class_exists($class)){
                $obj = new $class;
                if (!($obj instanceof Coffe_Component))
                    return self::showError('The component class must be instance of class Coffe_Component');
            }
            else{
                return self::showError("The class '. $class .' isn't found");
            }
        }

        //������ ���� � ����� � �����������
        if ($obj === null){
            $class = $component_class_prefix .'_Component';
            if (class_exists($class)){
                $obj = new $class;
                if (!($obj instanceof Coffe_Component))
                    return self::showError('The component class must be instance of class Coffe_Component');
            }
        }

        if (!is_object($obj)){
            return self::showError("The class of a component isn't found");
        }

        //���� � �������� ����������
        if (is_dir(PATH_ROOT . $cp_path . 'templates/' . $template_parts[1] . '/'))
            $obj->setTemplatePath($cp_path . 'templates/' . $template_parts[1] . '/');

        //���� � ���������������� �������� ����������
        if ($template_cp_path && is_dir(PATH_ROOT . $template_cp_path . 'templates/' . $template_parts[1] . '/')){
            $obj->setTemplateUserPath($template_cp_path . 'templates/' . $template_parts[1] . '/');
        }

        $obj->setComponentID($component);
        $obj->setConfig($config);
        $obj->setData($data);
        $obj->_init();
        return $obj->main();
    }

    /**
     * ������� ������ ��� ����������� ����������
     *
     * @static
     * @param $error
     * @return string
     */
    private static function showError($error)
    {
        return '<span style="color:red" class="coffe-error">' . $error .'</span>';
    }

    /**
     * ��������� ���� ����������
     *
     * @static
     * @param $menu
     * @return string
     */
    public static function getComponentMenu($menu)
    {

        $content = '';
        if (is_array($menu)){
            $content .= '<div class="cf-cp-menu">';
            foreach ($menu as $item){
                $content .= '<div class="cf-cp-menu-item">';
                $onclick = isset($item['onclick']) ? $item['onclick'] : '';
                $href = isset($item['href']) ? $item['href'] : '';;
                if ($onclick && !trim($href)){$href = 'javascript:void(0)';}
                $icon = '';
                if (isset($item['icon'])){
                    $icon = Coffe_Func::getIcon($item['icon']);
                }
                $content .= '<span><a href="' . $href . '" onclick="' . $onclick . '">'. $icon . '</a></span>';
                $content .= '</div>';
            }
            $content .= '<span class="cf-cp-menu-after"><span>';
            $content .= '</div>';
        }
        return $content;
    }

}