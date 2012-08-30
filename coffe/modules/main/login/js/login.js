$(document).ready(function(){
    if (self.name == 'list_frame' || self.name == 'nav_frame'){
        parent.window.location = window.location;
    }
    if (self.name == ''){
        $('#redirect_url').attr('');
    }
});