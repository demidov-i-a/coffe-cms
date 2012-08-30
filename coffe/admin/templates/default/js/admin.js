var COFFE =
{
	frameDeltaHeight: 0,
	navFrameWidth: 240,
	prevWidth: 0,
	prevHeight: 0,
	gotoList: function(location){
		window.frames['list_frame'].document.location = location;
	},
	updateNavFrame: function(){
		window.frames['nav_frame'].document.location.reload();

	},
	resize: function(){

		var window_height = $(window).height();
		var window_width = $(window).width();
		var head_height = $('#coffe-module-head').height();

		//IE9 need 2px. why???
		window_width = Math.floor(window_width) - 2;
		window_height = Math.floor(window_height) - 2;
		head_height = Math.floor(head_height);
		window_height = window_height - head_height;
		$('#coffe-module-content').height(window_height);
		$('#coffe-module-content').width(window_width);

		if ($('iframe').length){
			$('iframe').each(function(){
				if (this.name == 'nav_frame'){
					$(this).width(COFFE.navFrameWidth);
					$(this).height(window_height);
					$('#coffe-module-content', $(this).contents()).height(window_height - $('#coffe-module-head', $(this).contents()).height());
					$('#coffe-module-content', $(this).contents()).width(COFFE.navFrameWidth);
				}
				if (this.name == 'list_frame'){
					$(this).width(window_width - COFFE.navFrameWidth);
					$(this).height(window_height);
					$('#coffe-module-content', $(this).contents()).height(window_height - $('#coffe-module-head', $(this).contents()).height());
					$('#coffe-module-content', $(this).contents()).width(window_width - COFFE.navFrameWidth);
				}
			});
		}
	}
}

$(document).ready(function(){
	if (self.name == '' || self.name == 'coffe_window_iframe'){
		COFFE.resize();
		if (self.name == ''){
			$(window).resize(COFFE.resize);
		}
	}
	else{
		parent.COFFE.resize();
	}

	$(document).keydown(function(e) {
		switch(e.keyCode){
			case 27:
				if (self.name == 'coffe_window_iframe'){
					parent.COFFE_PANEL.close();
				}
				if (self.name == 'nav_frame'){
					parent.parent.COFFE_PANEL.close();
				}
				if (self.name == 'list_frame'){
					parent.parent.COFFE_PANEL.close();
				}
				break;
		}
	});

	$('.coffe-form-tabs-menu li').click(function(){
		$('.form_tab',$(this).closest('form')).first().attr('value',$(this).data('tab'));
		$('li', $(this).closest('ul')).removeClass('active');
		$(this).addClass('active');
		$('.coffe-form-tab-item', $(this).closest('form')).hide();
		$('.tab-' + $(this).data('tab') + '-block', $(this).closest('form')).first().show();
	});

});