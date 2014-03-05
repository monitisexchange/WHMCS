/*
 * type = product | addon | option | server-ping | server-cpu | server-memory | server-drive
 */
function monitisProductDialog(properties, callback) {
    function inArray(value, arr) {
        for(var i = 0; i < arr.length; i++) {
            if(arr[i] == value){
                return i;
            }
        }
        return -1;
    }
        
    properties = $.extend({
        type: 'product',
        settings: null,
        locations: null,
        optionGrups: null
    }, properties);
    
    var settings = properties.settings;
    if(typeof settings === 'string') {
        var str = settings.replace(/~/g, '"');
        settings = JSON.parse(str);
    }

    var str = '<div class="monitis-dialog">';
    
    if(properties.type == 'option'){
        str += '<div class="monitis-block">';
        str += '<div class="monitis-title">Select Configurable Option:</div>';
        str += '<select name="group">';
        for(groupId in properties.optionGrups) {
                var group = properties.optionGrups[groupId];
                str += '<option value="' + groupId + '">' + group['name'] + '</option>';
        }
        str += '</select>';
        str += '<select name="option"></select>';
        str += '<select name="sub"></select>';
        str += '</div>';
    }
    
    
    if(properties.type == 'product') {
        str += '<div class="monitis-block">';
        str += '<div class="monitis-title">Monitor types:</div>';
        var mtypes = new Array();
        var monitorsList = new Array("http", "https", "ping");
        var mtypes = settings.types.split(",");
        for(var i = 0; i < monitorsList.length; i++) {
            var checked = ($.inArray(monitorsList[i], mtypes) !== -1) ? "checked" : "";
            str += ' <input type="checkbox" id="type_' + monitorsList[i] + '" name="type" value="' + monitorsList[i] + '" ' + checked + ' />';
            str += '<label for="type_' + monitorsList[i] + '">' + monitorsList[i].toUpperCase() + '</label>';
        }
        str += '</div>';
    }
    else if(properties.type == 'option' || properties.type == 'addon') {
        str += '<div class="monitis-block-mini">';
        str += '<div class="monitis-title">Monitor type:</div>';
        var mtypes = new Array();
        var monitorsList = new Array("http", "https", "ping");
        var mtypes = settings.types.split(",");
        for(var i = 0; i < monitorsList.length; i++) {
            var checked = ($.inArray(monitorsList[i], mtypes) !== -1) ? 'checked="checked"' : '';
            str += ' <input type="radio" id="type_' + monitorsList[i] + '" name="type" value="' + monitorsList[i] + '" ' + checked + ' />';
            str += '<label for="type_' + monitorsList[i] + '">' + monitorsList[i].toUpperCase() + '</label>';
        }
        str += '</div>';
    }
    
    if(properties.type == 'product' || properties.type == 'option' || properties.type == 'addon' || properties.type == 'server-ping') {
        str += '<div class="monitis-block">';
        str += '<div class="monitis-block-mini">';
        var intervalsList = [1,3,5,10,15,20,30,40,60];
        str += '<label class="monitis-title monitis-fixed">Interval (min.):</label>';
        str += '<select name="interval">';
        for(var i = 0; i < intervalsList.length; i++) {
            var selected = (intervalsList[i] == parseInt(settings.interval)) ? 'selected="selected"' : '';
            str += '<option  ' + selected + ' value="'+ intervalsList[i] +'" >'+intervalsList[i]+'</option>';
        }
        str += '</select>';
        str += '</div>';
        str += '<div class="monitis-block-mini">';
        str += '<label class="monitis-title monitis-fixed">Timeout <span class="not-ping">(1 — 50 sec.)</span><span class="ping" style="display: none;">(1 — 5000 ms.)</span>:</label>';
        str += '<input type="text" size="15" name="timeout" value="'+ settings.timeout +'"/><br/>';
        str += '</div>';
        if(properties.type == 'product') {
            str += '<div class="monitis-block-mini">';
            str += '<label class="monitis-title monitis-fixed">Ping timeout (1 — 5000 ms.):</label>';
            str += '<input type="text" size="15" name="timeout_ping" value="'+ settings.timeoutPing +'"/></br>';
            str += '</div>';
        }
        
        if(properties.type == 'product' || properties.type == 'option' || properties.type == 'addon') {
            str += '<div class="monitis-block-mini">';
            str += '<label class="monitis-title monitis-fixed">Max locations:</label>';
            str += '<input type="text" size="15" name="locations_max" value="'+ settings.locationsMax +'" /></br>';
            str += '</div>';
        }
        str += '</div>';
    }    
    
    if(properties.type == 'product' || properties.type == 'option' || properties.type == 'addon' || properties.type == 'server-ping') {
        var locationIds = settings.locationIds;
        str += '<div class="monitis-block">';
        str += '<div class="monitis-title-large">Check locations</div>';
        str += '<div class="monitis-grid">';
        for (var name in properties.locations) {
            str += '<div class="monitis-grid-item-25">';
            str += '<div class="monitis-title">' + name + '</div>';
            var country = properties.locations[name];
            for (var id in country) {
                var location = country[id];
                var checked = (inArray(id, locationIds) >= 0) ? 'checked="checked"' : '';
                str += '<div class="item">';
                str += '<input type="checkbox" name="location_ids" id="location_ids_' + id + '" ' + checked + ' value="' + id + '" /> ';
                str += '<label for="location_ids_' + id + '">' + location.fullName + '</label>';
                str += '</div>';
            }
            str += '</div>';
        }
        str += '</div>';
        str += '</div>';
    }
	

    if(properties.type == 'server-cpu') {
        str += '<div class="monitis-block-mini">';
        str += '<label class="monitis-title monitis-fixed-mini">Kernel Max:</label>';
        str += '<input type="text" size="15" name="kernel_max" value="'+ settings.kernelMax +'" />';
        str += '</div>';
        str += '<div class="monitis-block-mini">';
        str += '<label class="monitis-title monitis-fixed-mini">Used Max:</label>';
        str += '<input type="text" size="15" name="used_max" value="'+ settings.usedMax +'" />';
        str += '</div>';
        str += '<div class="monitis-block-mini">';
        str += '<label class="monitis-title monitis-fixed-mini">Idle Min:</label>';
        str += '<input type="text" size="15" name="idle_min" value="'+ settings.idleMin +'" />';
        str += '</div>';
        str += '<div class="monitis-block-mini">';
        str += '<label class="monitis-title monitis-fixed-mini">IOWait Max:</label>';
        str += '<input type="text" size="15" name="io_wait_max" value="'+ settings.ioWaitMax +'" />';
        str += '</div>';
        str += '<div class="monitis-block-mini">';
        str += '<label class="monitis-title monitis-fixed-mini">Nice Max:</label>';
        str += '<input type="text" size="15" name="nice_max" value="'+ settings.niceMax +'" />';
        str += '</div>';
    }
    
    if(properties.type == 'server-memory') {
        str += '<div class="monitis-block-mini">';
        str += '<label class="monitis-title monitis-fixed-mini">Free Limit:</label>';
        str += '<input type="text" size="15" name="free_limit" value="' + settings.freeLimit + '" />&nbsp;MB';
        str += '</div>';
        str += '<div class="monitis-block-mini">';
        str += '<label class="monitis-title monitis-fixed-mini">Swap Limit:</label>';
        str += '<input type="text" size="15" name="free_swap_limit" value="' + settings.freeSwapLimit + '" />&nbsp;MB';
        str += '</div>';
    }
    
    if(properties.type == 'server-drive') {
        str += '<div class="monitis-block">';
        if(properties.drives != undefined) {
            str += '<label class="monitis-title monitis-fixed-mini">Name:</label>';
            str += '<select name="drive_letter" style="width: 300px">';
            for(key in properties.drives){
                var val = properties.drives[key];
                str += '<option value="' + val + '">' + val + '</option>';
            }
            str += '</select>';
        }
        str += '</div>';
        str += '<div class="monitis-block-mini">';
        str += '<label class="monitis-title monitis-fixed-mini">Free Limit:</label>';
        str += '<input type="text" size="15" name="free_limit" value="' + settings.freeLimit + '" />&nbsp;GB</br>';
        str += '</div>';
    }

    $dialog = $(str);
    $('#monitis_dialogs').append($dialog);
    
    if(properties.type != 'product') {
        $dialog.find('[name=type]').click(function() {
            if($(this).val() == 'ping') {
                $dialog.find('.not-ping').hide();
                $dialog.find('.ping').show();
            }
            else {
                $dialog.find('.not-ping').show();
                $dialog.find('.ping').hide();
            }
            $dialog.find('[name=timeout]').change();
        });
        $dialog.find('[name=type]:checked').click();
    }
    
    if(properties.type === 'server-ping') {
        $dialog.find('.ping').show();
        $dialog.find('.not-ping').hide();
    }
        
    $dialog.find('[name=group]').click(function(){
        var groupId = $(this).val();
        var options = properties.optionGrups[groupId]['options'];
        $dialog.find('[name=option]').html('');
        for(optionId in options){
            $dialog.find('[name=option]').append('<option value="' + optionId + '">' + options[optionId]['name'] + '</option>');
        }
        $dialog.find('[name=option]').click();
    });
    
    $dialog.find('[name=option]').click(function(){
        var groupId = $dialog.find('[name=group]').val();
        var optionId = $(this).val();
        var subs = properties.optionGrups[groupId]['options'][optionId]['subs'];
        $dialog.find('[name=sub]').html('');
        for(subId in subs){
            $dialog.find('[name=sub]').append('<option value="' + subId + '" ' + (subs[subId]['hasMonitor'] ? 'disabled="disabled"' : '' ) + '>' + subs[subId]['name'] + '</option>');
        }
    });
    
    $dialog.find('[name=group]').click();
    
    $dialog.find('[name=timeout]').change(function() {
        var value = parseInt($(this).val());
        var min, max;
        if((properties.type !== 'product' && $dialog.find('[name=type]:checked').val() === 'ping') || properties.type === 'server-ping') {
            min = 1;
            max = 5000;
        }
        else{
            min = 1;
            max = 50;
        }
        
        if(value < min) {
            $(this).val(min);
        }
        else if(value > max) {
            $(this).val(max);
        }
        else{
            $(this).val(value);
        }
    });
    
    $dialog.find('[name=timeout_ping]').change(function() {
        var value = parseInt($(this).val());
        var min = 1;
        var max = 5000;
        if(value < min) {
            $(this).val(min);
        }
        else if(value > max) {
            $(this).val(max);
        }
        else{
            $(this).val(value);
        }
    });
    
    $dialog.find('[name=locations_max]').change(function() {
        var value = parseInt($(this).val());
        var min = 1;
        var max = 255;
        if(value < min) {
            $(this).val(min);
        }
        else if(value > max) {
            $(this).val(max);
        }
        else{
            $(this).val(value);
        }
    });
    
    $dialog.find('[name=location_ids]').click(function() {
        var locationsMax = parseInt($dialog.find('[name=locations_max]').val());
        var checkedCount = $dialog.find('[name=location_ids]:checked').length;
        if(checkedCount > locationsMax && $(this).attr('checked') || checkedCount == 0) {
            return false;
        }
        return true;
    });
        
    $dialog.dialog({
        width: 640,
        zIndex: 1100,
        modal: true,
        title: (settings.name || properties.name),
        buttons: {
            'save': {
                text: 'Save',
                class: 'btn',
                click: function() {
                    if(properties.type === 'product') {
                        settings['types'] = '';
                        $(this).find('[name=type]:checked').each(function(){
                            if(settings['types']){
                                settings['types'] += ',';
                            }
                            settings['types'] += $(this).val();
                        });
                        
                        settings['timeoutPing'] = parseInt($(this).find('[name=timeout_ping]').val());
                    }
                    else if(properties.type === 'addon' || properties.type === 'options') {
                        settings['types'] = $(this).find('[name=type]:checked').val();
                    }
                    
                    if(properties.type === 'product' || properties.type === 'addon' || properties.type === 'options' || properties.type === 'server-ping'){
			$(this).find('[name=timeout]').change();
                        settings['interval'] = parseInt($(this).find('[name=interval]').val());
                        settings['timeout'] = parseInt($(this).find('[name=timeout]').val());
                        
                        settings['locationIds'] = [];
                        $(this).find('[name=location_ids]:checked').each(function(){
                            settings['locationIds'].push(parseInt($(this).val()));
                        });
                    }
                    
                    if(properties.type === 'product' || properties.type === 'addon' || properties.type === 'options') {
                        settings['locationsMax'] = parseInt($(this).find('[name=locations_max]').val());
                    }
                    
                    if(properties.type === 'option'){
                        settings['subId'] = parseInt($(this).find('[name=sub]').val());
                    }
                    
                    if(properties.type === 'server-cpu'){
                        settings['kernelMax'] = parseInt($(this).find('[name=kernel_max]').val());
                        settings['usedMax'] = parseInt($(this).find('[name=used_max]').val());
                        settings['idleMin'] = parseInt($(this).find('[name=idle_min]').val());
                        settings['ioWaitMax'] = parseInt($(this).find('[name=io_wait_max]').val());
                        settings['niceMax'] = parseInt($(this).find('[name=nice_max]').val());
                    }
                    
                    if(properties.type === 'server-memory'){
                        settings['freeLimit'] = parseInt($(this).find('[name=free_limit]').val());
                        settings['freeSwapLimit'] = parseInt($(this).find('[name=free_swap_limit]').val());
                    }
                    
                    if(properties.type === 'server-drive'){
                        settings['freeLimit'] = parseInt($(this).find('[name=free_limit]').val());
                        if($(this).find('[name=drive_letter]').length) settings['driveLetter'] = $(this).find('[name=drive_letter]').val();
                    }
                    
                    monitisModal.open({content: null});
                    callback(settings);
                    $(this).dialog("close");
                }
            },
            'close': {
                    text: 'Close',
                    class: 'btn',
                    click: function() {
                            $(this).dialog("close");
                    }
            }
        },
        close: function() {
                $(this).remove();
        }
    });
    return $dialog;
}