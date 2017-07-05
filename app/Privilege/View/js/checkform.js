function checkform(){
    var modItems = $("[mod]");
    var checkRes = true;
    modItems.each(function(){
        var val = $(this).val();
        var mods = $(this).attr('mod').split('|');  
        var msgs = $(this).attr('msg').split('|');
        for(var i=0; i<mods.length; i++){
            var mod = mods[i];
            var msg = msgs[i];
            if(mod == 'isEmpty'){
                var res = isEempty(val);
            }
            if(mod == 'isNumeric'){
                var res = isNumeric(val);    
            } 
            if(res == false){
                checkRes = res;
                $(this).parent().addClass("has-error");
                $(this).next().remove();
                $(this).after('<label class="control-label" for="inputError">'+ msg +'</label>'); 
                break;
            }else{
                $(this).parent().removeClass("has-error");
                $(this).next().remove();
            }
        }

    });
    return checkRes;
}

function isEempty(val){
    if(val == ''){
        return false;   
    }
    return true;
}
function isNumeric(val){
    return $.isNumeric(val);
}
function select_location(sid, val){
    $('#'+sid).val(val);
}

