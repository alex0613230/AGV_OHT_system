<!DOCTYPE html>
<html lang="en">
	<head>
	</head>

	<body>
	
	<form action="" method=post>
	姓名
	<?
		echo $_POST['name'];
	?>
	<br>
	性別
	<?
		echo $_POST['SEX'];
	?>
	<br>
	生日
	<?
		echo $_POST['bir']."月".$_POST['bir_1']."日";
	?>
	<br>
	國籍
	<?
		echo $_POST['lo'];
	?>
	<br>
	興趣
	<?
						$addsite = $_POST['sites'];
							
							for($j=0;$j<count($addsite);$j++)
							{
								if($j==0)
									$str_tag = $addsite[$j];
								else
									$str_tag =$str_tag.",".$addsite[$j];
							}
		echo $str_tag;
	?>
	</body>
</html>