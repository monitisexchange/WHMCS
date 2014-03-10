<?php

$limit = MONITIS_LOG_PAGE_LIMIT;
define('MONITIS_LOG_EXPAND', false);

if($_POST['clean']) {
	monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_LOG_TABLE);
} 

$page = isset( $_REQUEST['page'] ) ? intval($_REQUEST['page']) : 1;
$start = isset( $_REQUEST['start'] ) ? intval($_REQUEST['start']) : 0;


$list = monitisSqlHelper::pageQuery('SELECT SQL_CALC_FOUND_ROWS * FROM '.MONITIS_LOG_TABLE.' ORDER BY `id` DESC LIMIT '.$start.', '.$limit);

$start = ($page-1)*$limit;
$total = $list[0]['total'];
$pages = intval($total/$limit);
if( $total % $limit )
	$pages++;
?>
<style type="text/css">
.datatable th,  .datatable td{
	word-wrap:break-word;
}
</style>
<script type="text/javascript">
$(document).ready(function(){
//	$(".description").tabs(  );
});
</script> 


<form method="post" action="" id="serversListId">
<table width="100%" border="0" cellpadding="3" cellspacing="0">
	<tr>
		<td align="left"><input type="submit" value="Clean" name="clean"></td>
		<td width="50%" align="right">
		Jump to Page:&nbsp;&nbsp; 
			<select name="page" onchange="submit()">
			<?php	for($i=1; $i<=$pages; $i++) {
					$selected = '';
					if($i == $page)
						$selected = 'selected="selected"';
					echo '<option value="'.($i).'" '.$selected.'>'.($i).'</option>';
				}
			?>
			</select>
			<input type="hidden" name="start" value="<?php echo $start?>">
			<input type="submit" value="Go">
			
		</td>
	</tr>
</table>
</form>
<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align:left; margin-right:10px;table-layout:fixed;">
	<tr>
		<th width="70px">Date</th>
		<th>Description</th>
		<th width="7px">&nbsp;</th>
	</tr>
<?php

if( $list && count($list) > 0) {

	for($k=0; $k<count($list); $k++) {
		$row = $list[$k];

		$desc = $row["description"];
		if(MONITIS_LOG_EXPAND && $row["type"] == 'json') {
			$desc = json_decode($row["description"], true);
			$desc = var_export($desc, true);
		}
?>
	<tr>
		<td><?php echo $row["date"]?></td>
		<td>
			<?php if(!empty($row["title"])) { ?>
				<h3><?php echo $row["title"]?></h3>
			<?php } ?>
			<?php if(MONITIS_LOG_EXPAND && $row["type"] == 'json') { ?>
				<pre><?php echo $desc; ?></pre>
			<?php } else {  echo $desc; } ?>
		</td>
		<td>&nbsp;</td>
	</tr>
<?php
	}
}
?>
</table>
