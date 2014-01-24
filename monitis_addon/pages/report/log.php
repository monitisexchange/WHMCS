<?php

$limit = MONITIS_LOG_PAGE_LIMIT;
define('MONITIS_LOG_EXPAND', false);

if($_POST['clean']) {
//monitisLog($_POST, 'POST dump');
	monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_LOG_TABLE);
} 

//$limit = 3;
$page = isset( $_REQUEST['page'] ) ? intval($_REQUEST['page']) : 1;
$start = isset( $_REQUEST['start'] ) ? intval($_REQUEST['start']) : 0;


$list = monitisSqlHelper::pageQuery('SELECT SQL_CALC_FOUND_ROWS * FROM '.MONITIS_LOG_TABLE.' ORDER BY `id` DESC LIMIT '.$start.', '.$limit);

$start = ($page-1)*$limit;
$total = $list[0]['total'];
$pages = intval($total/$limit);
if( $total % $limit )
	$pages++;
//echo "****** start=$start ******* page=$page *********************** <br>";
//_dump($page);
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
			<?	for($i=1; $i<=$pages; $i++) {
					$selected = '';
					if($i == $page)
						$selected = 'selected="selected"';
					echo '<option value="'.($i).'" '.$selected.'>'.($i).'</option>';
				}
			?>
			</select>
			<input type="hidden" name="start" value="<?=$start?>">
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
<?

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
		<td><?=$row["date"]?></td>
		<td>
			<? if(!empty($row["title"])) { ?>
				<h3><?=$row["title"]?></h3>
			<? } ?>
			<? if(MONITIS_LOG_EXPAND && $row["type"] == 'json') { ?>
				<pre><? echo $desc; ?></pre>
			<? } else {  echo $desc; } ?>
		</td>
		<td>&nbsp;</td>
	</tr>
<?		
	}
}
?>
</table>
