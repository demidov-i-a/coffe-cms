<?php
require_once 'init.inc.php';
if (Coffe::isAdmin()){
	Coffe::getHead()->addJsFile('jquery ',Coffe::getUrlPrefix() . 'coffe/admin/js/jquery.js');
	$template = Coffe::getConfig('adminTemplate','default');
	Coffe::getHead()->addCssFile('_coffe_panel_css', Coffe::getUrlPrefix() . 'coffe/admin/templates/' . $template . '/css/panel.css');
	Coffe::getHead()->addJsFile('_coffe_panel_js', Coffe::getUrlPrefix() . 'coffe/admin/templates/' . $template . '/js/panel.js');
	Coffe::getHead()->addJsFile('_coffe_panel_init', '',"COFFE_PANEL.base_url = '" . Coffe::getUrlPrefix() . "';");
}
?>
<!doctype html>
<html>
<head>
	<meta name="robots" content="noindex,nofollow">
	<title>Oooops</title>
	<meta http-equiv="content-type" content="text/html; charset=<?php echo Coffe::getConfig('charset') ?>" />
	<?php echo Coffe::getHead()->renderHead(); ?>
</head>
<body>
<noindex>
	<?php if (Coffe::isAdmin()) echo Coffe_ModuleManager::printPanel() ?>
	<div class="coffe-error-block">
		<?php echo $error ?>
		<?php if (Coffe::getConfig('display_errors') == '-1'): ?>
		<pre>
<?php print_r($e->getTraceAsString()); ?>
</pre>
		<?php endif ?>
	</div>
</noindex>
</body>
</html>