<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<html>
<head>
	<meta name="robots" content="noindex,nofollow">
	<?php echo Coffe::getHead()->renderHead() ?>
</head>
<body class="coffe-background1">
<noindex>
	<?php if ($this->navigation):?>
	<div id="coffe-module-head">
		<div id="coffe-module-head-inner">
			<div class="title">
				<h1>
					<?php if ($this->module_title):?>
					<?php echo $this->module_title ?>
					<?php endif?>
					<div class="update-frame clearfix">
						<?php echo $this->icon('common/update','','',null,null,array('onclick' => 'window.location.reload(true)'))?>
					</div>
				</h1>
			</div>
		</div>

		<?php if (count($this->module_menu)):?>
		<div id="coffe-module-menu">
				<?php
				foreach($this->module_menu as $key => $item){
					$atr = isset($item['href']) ? 'href="' . $item['href'] . '" ' : 'href="javascript:void(0)" ';
					$atr .= isset($item['onclick']) ? 'onclick="' . $item['onclick'] . '" ' : ' ';
					echo '<a class="btn btn-theme" ' . $atr . '>' . (isset($item['icon']) ? $item['icon'] :  ($item['title'])) . '</a>';
				}
				?>

			<div class="clearfix"></div>
		</div>
		<?php endif?>
	</div>
	<?php endif?>
	<div id="coffe-module-content">
		<div id="coffe-module-content-inner">
			<div id="coffe-module-content-inner-inner">
				<?if ($this->flashObj->count()): ?>
				<div id="module-messages-block">
					<?php
					$mes = '';
					$messages = $this->flashObj->getAll();
					foreach ($messages as $type => $items){
						foreach ($items as $item){
							$mes .= '<div class="alert alert-'. $type .'">' . $item . '</div>';
						}
					}
					$this->flashObj->clearAll();
					echo $mes;
					?>
				</div>
				<?endif?>
				<?php echo $this->content ?>
			</div>
		</div>
	</div>
</noindex>
</body>
</html>