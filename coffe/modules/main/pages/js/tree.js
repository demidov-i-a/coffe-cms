$(document).ready(function(){
    $('#page-tree .item').click(function(){

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


        $('.children',$(this).parent()).first().slideToggle(200);

    });
});
