function monitisClientProductDialog(properties, callback) {

    function inArray(value, arr) {
        for(var i = 0; i < arr.length; i++) {
            if(arr[i] == value){
                return i;
            }
        }
        return -1;
    }
        
    properties = $.extend({
        settings: null,
        locations: null,
    }, properties);
    
    var settings = properties.settings;
    if(typeof settings === 'string') {
        var str = settings.replace(/~/g, '"');
        settings = JSON.parse(str);
    }

    var str = '<div class="monitis-dialog form-inline">';
    str += '<div class="monitis-block">';
    str += '<label class="monitis-title monitis-fixed">'+monitisLang.timeout+' <span class="not-ping">(1 — 50 sec.)</span><span class="ping" style="display: none;">(1 — 5000 ms.)</span>:</label>';
    str += '<input type="text" size="15" name="timeout" value="'+ settings.timeout +'"/>';
    str += '</div>';

    var locationIds = settings.locationIds;
    str += '<div class="monitis-block">';
    str += '<div class="monitis-title-large">'+monitisLang.checkLocations+' <span style="font-size: 12px;">(max. '+ settings.locationsMax +')</span></div>';
    str += '<div class="monitis-grid-collapse">';
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

    $dialog = $(str);
    $('#monitis_dialogs').append($dialog);
    
	if(settings.types == 'ping'){
		$dialog.find('.not-ping').hide();
		$dialog.find('.ping').show();
	}
    
    $dialog.find('[name=timeout]').change(function() {
        var value = parseInt($(this).val());
        var min, max;
        if(settings.types == 'ping') {
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
    
    $dialog.find('[name=location_ids]').click(function() {
        var locationsMax = parseInt(settings.locationsMax);
        if($dialog.find('[name=location_ids]:checked').length > locationsMax && $(this).attr('checked')) {
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
                    settings['timeout'] = parseInt($(this).find('[name=timeout]').val());
                    
                    settings['locationIds'] = [];
                    $(this).find('[name=location_ids]:checked').each(function(){
                        settings['locationIds'].push(parseInt($(this).val()));
                    });
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