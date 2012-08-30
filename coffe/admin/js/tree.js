function COFFE_TREE(selector,url_plus, url_minus)
{
    this.url_plus = url_plus;
    this.url_minus = url_minus;
    this.selector = selector;
    this.handler = null;
    var self = this;

    this.start = function(){
        $(this.selector + ' .item-button').live('click',function(){
            var target_element = this;
            if ($(this).hasClass('minus')){
                if ($(this).hasClass('plus')){
                    $(this).removeClass('plus');
                }
                else{
                    $(this).addClass('plus');
                }
            }
            if ($(this).hasClass('minus-bottom')){
                if ($(this).hasClass('plus-bottom')){
                    $(this).removeClass('plus-bottom');
                }
                else{
                    $(this).addClass('plus-bottom');
                }
            }
            var children = $('.children',$(this).closest('.tree-line')).first();
            if (children.length > 0 && $('*', children).length > 0){
                self.saveOpenedID();
                $(children).slideToggle(200);
            }
            else{
                if (self.url_plus){
                    $.ajax({
                        dataType:'html',
                        url: self.url_plus,
                        cache: false,
                        async: true,
                        type: "POST",
                        data:'id='+$(this).data('id')+ '&level=' + $(this).data('level'),
                        success: function(data){
                            if (data != 'Access denied'){
                                $(target_element).closest('.tree-line').append(data);
                                self.saveOpenedID();
                            }
                            else{
                                alert('Access denied');
                            }
                        },
                        error :function (jqXHR, textStatus, errorThrown){
                            alert(textStatus);
                        }
                    });
                }
            }
        });
    }


    this.saveOpenedID = function(){
        if (!this.url_minus) return false;
        var all = '';
        $(this.selector + ' .item-button').each(function(){
            if (!$(this).hasClass('plus')){
                all = all + $(this).data('id') + ',';
            }
        });
        if (this.handler){
            clearTimeout(this.handler);
        }
        this.handler = setTimeout(function(){
            $.ajax({
                dataType:'html',
                url: self.url_minus,
                cache: false,
                async: true,
                type: "POST",
                data:'open=' + all,
                error :function (jqXHR, textStatus, errorThrown){
                    alert(textStatus);
                }
            });
        },200);
    }
}

