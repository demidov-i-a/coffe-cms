var COFFE_PANEL =
{
    parent: 'body',
    location: null,
    wrapper: null,
    wrapper_inner: null,
    closeButton: null,
    base_url: null,
    deltaWidth: 0.9,
    deltaHeight: 0.9,
    iframe: null,
    create_iframe : function(){
        var name = 'coffe_window_iframe';
        var src= this.base_url + 'coffe/includes/blank.php';
        $('<iframe frameborder="0" style="overflow: hidden;" id="coffe-window-iframe" name="' + name + '" src="' + src + '"></iframe>').appendTo(this.wrapper_inner);
        this.iframe = top.frames[name];
    },
    create_wrapper : function(){
        this.wrapper = $('<div id="coffe-window-wrapper"></div>').css({
            display: 'none',
            position: 'absolute',
            zIndex: '100000'
        }).appendTo(this.parent);

        this.wrapper_inner = $('<div id="coffe-window-wrapper-inner"></div>').css({
            position: 'absolute'
        }).appendTo(this.wrapper);

        this.closeButton = $('<div id="coffe-window-close"></div>').appendTo(this.wrapper_inner);

        $(this.closeButton).click(function(){
            COFFE_PANEL.close();
        });

        $(document).keydown(function(e) {
            switch(e.keyCode){
                case 27: COFFE_PANEL.close(); break;
            }
        });
    },
    init: function(){
        this.create_wrapper();
        this.create_iframe();
    },
    show: function(){
        $(COFFE_PANEL.wrapper).css('display', 'block');
        COFFE_PANEL.resize();
    },
    close: function(location){
        $(this.wrapper).css('display', 'none');
    },
    goto: function(location){
        this.show();
        this.iframe.document.location = decodeURIComponent(location);
    },
    doAjax: function(conf,success_cb){
        $.ajax({
            url: decodeURIComponent(conf.url),
            type: "POST",
            dataType:'html',
            cache: false,
            data:conf.data,
            success: success_cb,
            error: function(XMLHttpRequest, textStatus){

            }
        });
    },
    doAjaxOperation: function(url, reload, callback){
        var conf = {url: url, data: ''};
        var cb = null;
        if (callback){
            cb = callback;
        }
        else if (reload){
            cb = function(){
                window.location.reload();
            }
        }
        this.doAjax(conf, cb);
    }
    ,
    resize: function(){
        var w = $(window).width();
        var h = $(window).height();
        var nw = w*this.deltaWidth;
        var nh = (h*this.deltaHeight) - 10;
        var left = (w-nw)/2;
        var top = (h-nh)/2;
        $(this.wrapper).css({'width':nw,'height':nh,'top':50,'left':left});
        $(this.wrapper_inner).css({'width':nw,'height':nh,'top':0,'left':0});
        $('#coffe-window-iframe').height(nh);
        $('#coffe-window-iframe').width(nw);
        if ($('#coffe-window-iframe').get(0).contentWindow.COFFE){
            $('#coffe-window-iframe').get(0).contentWindow.COFFE.resize();
        }
    },
    gotoList: function(location){
        this.iframe.frames['list_frame'].document.location = location;
    },
    updateNavFrame: function(){
        this.iframe.frames['nav_frame'].document.location.reload();
    }
}
$(document).ready(function(){
    COFFE_PANEL.init();
    $(window).resize(function(){
        COFFE_PANEL.resize();
    });

});
