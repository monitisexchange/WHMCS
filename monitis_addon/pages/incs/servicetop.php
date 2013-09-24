<?php
//require_once ('../modules/addons/monitis_addon/lib/product.class.php');

$locations = MonitisApiHelper::getExternalLocationsGroupedByCountry();
foreach ($locations as $key => $value) {
    if (empty($value))
        unset($locations[$key]);
}
$groupList = MonitisApi::getContactGroupList();


?>
<style>
.datatable {
	width:100%;
	min-width:1000px;
}
.datatable td {
	overflow:hidden;
}
.datatable th.title{
	text-align:left;
	padding-left:10px;
}
.datatable .customfields ul{
	list-style-type: none;
	width:130px;
	margin: 0px;
	padding: 2px 5px;
}
.datatable .customfields li{
	padding: 3px 0px;
	margin: auto 0px;
}
.datatable td {
	overflow:hidden;
}
.datatable .actions div {
	text-align:left;
	width:150px;
	margin-bottom: 10px;
	padding-left:30px;
}
.datatable .fieldlabel {
	text-align:right;
}
</style>
<script>
var countryname = <? echo json_encode($locations); ?>;
var groupsList = <? echo json_encode($groupList); ?>;

$(document).ready(function() {
	
	$('.monitisMultiselectTrigger').click(function(event) {
		var prefix = $(this).attr("element_prefix");
		var locationsMax = $("#locationsMax" + prefix).val();
		var loc_ids = $('#locationIDs'+prefix).val();
		var opt = {
			parentId:"monitisMultiselectInputs",
			max_loc: locationsMax,
			loc_ids: loc_ids
		}
		new monitisLocationDialogClass( opt, function(resp, count){
			$('#locationIDs'+prefix).val(resp);
			$('#locationsize'+prefix).html(count);
		});
	});
	
	$('.notificationRule').click(function(event) {
		var product = $(this).attr("product");
		var sets_json = $('.notificationRule_'+product).val();
		var groupid = $('.notificationGroupId_'+product).val();
		var groupname = $('.notificationGroupName_'+product).val();
//console.log(sets_json);
		var that = $(this);
		if( sets_json && sets_json != '') {
			var group = {
				id: groupid,
				name: groupname,
				list: groupsList
			}
			var obj = new monitisNotificationRuleClass( sets_json, group,  function(not_json, group){
				$('.notificationRule_'+product).attr( 'value', not_json );
				//$('.notificationRule_'+product).val( not_json );
				$('.notificationGroupId_'+product).val(group.id);
				$('.notificationGroupName_'+product).val(group.name);
				var title = ( group.id > 0) ? group.name : group.name;
				that.val(title);
			});
		}
	});
	
});
</script>
<div id="monitisMultiselectInputs" ></div>
<div id="monitis_notification_dialog_div"></div>