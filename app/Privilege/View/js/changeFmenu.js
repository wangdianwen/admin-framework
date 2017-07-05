function changeFmenu(obj,finalChild) {
    var fid = obj.value;
    var num = obj.getAttribute('selectNum');
        $("[selectNum="+ num +"]").attr('name', 'fid');
        $("div[num]").each(function(){
            var eNum = $(this).attr('num');
            if(eNum > num) {
                $("div[num='"+eNum+"']").remove();
            }
        });
    if(fid == 0)  return;
    num = parseInt(num) + 1;
    $.post("/Privilege/Menu/addMenu/", { fid: fid, finalChild:finalChild, 'ajaxAct': 'getChildMenu'},
        function(data){
            if(data == 'null') return;
            var menus = jQuery.parseJSON(data);
             var preHtml = '<div class="col-xs-2" num="'+ num +'">' +
                        '<div class="form-group" id="fmenu_div">' +
                            '<label>&nbsp;</label>' +
                            '<select name="" onchange="changeFmenu(this);" selectNum="' + num + '" class="form-control">' +
                                '<option value=""></option>';
            var mHtml = '';
            $.each(menus, function(i, obj){
                mHtml = mHtml + '<option value="'+ obj.pk_menu_id  +'">'+ obj.name +'</option>';
            });
            var subHtml = '</select></div></div>';

            var totalHtml = preHtml + mHtml + subHtml;
            $("#fmenu").append(totalHtml);
            
        });
    return true;
};
