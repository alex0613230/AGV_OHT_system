<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>系統管理功能</title>
<style>
	.chi {font-family:"標楷體";font-size:20px;}
	th, button, input {font-size:24px;}
</style>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
</head>
<?	
	include("./project/database.php");
	
	
	if (isset($_POST["bye"]))
	{
		session_start();
		unset($_SESSION['user']);
	}
	if (isset($_POST["reset"]))
	{
		session_destroy();
		$message_car10 = "0000000901100000000102000A00";
		$message_crane10 = "0000000B011000070002040004000000";
		$message_car03 = "00000006010300010003";
		$message_crane03 = "00000006010300010006";
		switch($_POST["reset"])
		{
			
			case 1:
				$ch = $_POST["ch"];
				
				$tr_rand = rand(0, 65535);
				$tr_rand = sprintf("%04X", $tr_rand);

				$sql = "SELECT ip,Category FROM  Wi_SUN_IP where Name = '$ch'";
				$result = mysqli_query($con, $sql);
				list($ip,$Category) = mysqli_fetch_row($result);
				
				if($Category == "1")
				{
					$sql = "delete from Command where flag = 0 && IP = '$ip'";
					$result = mysqli_query($con, $sql);

					$tr_rand = rand(0, 65535);
					$tr_rand = sprintf("%04X", $tr_rand);
					$sql = "insert into Command value(0, '$tr_rand', '$ip', '$message_car10', 0, NOW())";
					$result = mysqli_query($con, $sql);

					$tr_rand = rand(0, 65535);
					$tr_rand = sprintf("%04X", $tr_rand);
					$sql = "insert into Command value(0, '$tr_rand', '$ip', '$message_car03', 0, NOW())";
					
					$sql = "DELETE FROM manual";
					$result = mysqli_query($con, $sql);
				}
				else if($Category == "2")
				{
					$sql = "delete from Command where flag = 0 && IP = '$ip'";
					$result = mysqli_query($con, $sql);

					$tr_rand = rand(0, 65535);
					$tr_rand = sprintf("%04X", $tr_rand);
					$sql = "insert into Command value(0, '$tr_rand', '$ip', '$message_crane10', 0, NOW())";
					$result = mysqli_query($con, $sql);
					
					$tr_rand = rand(0, 65535);
					$tr_rand = sprintf("%04X", $tr_rand);
					$sql = "insert into Command value(0, '$tr_rand', '$ip', '$message_crane03', 0, NOW())";
				}
				$result = mysqli_query($con, $sql);
				mysqli_free_result($result);
				break;
			case 2:
				$sql = "delete from Command where flag = 0";
				$result = mysqli_query($con, $sql);
				
				$sql = "DELETE FROM manual";
				$result = mysqli_query($con, $sql);
				
				$sql = "select IP,Category from Wi_SUN_IP where Status = 1";
				$result = mysqli_query($con, $sql);
					
				$i = 0;
				while(list($ip[$i],$Category[$i]) = mysqli_fetch_row($result))
				$i++;

				for($j = 0; $j < $i; $j++)
				{
					$Car_rand = rand(0, 65535);
					$Car_rand = sprintf("%04X", $Car_rand);

					$Crane_rand = rand(0, 65535);
					$Crane_rand = sprintf("%04X", $Crane_rand);
					if($Category[$j] == 1)
					{
						$sql = "insert into Command value(0, '$Car_rand', '$ip[$j]', '$message_car10', 0, NOW())";
						$result = mysqli_query($con, $sql);

						$Car_rand = rand(0, 65535);
						$Car_rand = sprintf("%04X", $Car_rand);
						$sql = "insert into Command value(0, '$Car_rand', '$ip[$j]', '$message_car03', 0, NOW())";
						$result = mysqli_query($con, $sql);
					}
					else if($Category[$j] == 2)
					{
						$sql = "insert into Command value(0, '$Crane_rand', '$ip[$j]', '$message_crane10', 0, NOW())";
						$result = mysqli_query($con, $sql);

						$Crane_rand = rand(0, 65535);
						$Crane_rand = sprintf("%04X", $Crane_rand);
						$sql = "insert into Command value(0, '$Crane_rand', '$ip[$j]', '$message_crane03', 0, NOW())";
						$result = mysqli_query($con, $sql);
					}
					
					mysqli_free_result($result);
				}
				break;
		}
	}
?>

<body bgcolor="#FFFFFF">
<center>
<table border=5 cellspacing=0 bordercolor="#6363FF" cellpadding=10 width=80%>
<tr>
	<th height=20 colspan=3><font size=7>操　作　人　員</font></th>
</tr>

<tr>
	<th>
		<a href="./project/Schedule.php"><img src="./project/pic/schedule.png" style="width:160px;height:150px;"></a><br>工單製作功能
	</th>
	<th>
		<a href="./project/auto.php"><img src="./project/pic/auto.png" style="width:160px;height:150px;"></a><br>派車作業功能
	</th>
	
</tr>
<tr>
	<th colspan='2'>
		<a href="./project/Check.php"><img src="./project/pic/Check.png" style="width:160px;height:150px;"></a><br>資料與狀態查詢功能
	</th>
</tr>
</table><p>

<table border=5 cellspacing=0 bordercolor="#6363FF" cellpadding=10 width=80%>
<tr>
<form action="" method="POST">
	<th colspan="2">設備重置　：　
		<select name = "ch" style="width:90px;height:40px;font-size:30px;">
			<?
			$sql = "SELECT Name FROM  Wi_SUN_IP where Status = 1";
			$result = mysqli_query($con, $sql);

			$i=0;
			while(list($Name[$i]) = mysqli_fetch_row($result))
			$i ++;

			for($j = 0;$j < $i ; $j++)
				echo "<option value='$Name[$j]' > $Name[$j] </option>"
			?>
		</select>
		　<button type="submit" name="reset" value = "1" style="width:90px;height:40px;">重置</button>
	</th>
	<th bgcolor=red>
		<button type="submit" name="reset" value = "2" style="width:180px;height:40px;">全部設備重置</button>
	</th>
</form>
	
</tr>
</table ><br><table border=5 cellspacing=0 bordercolor="#6363FF" cellpadding=10 width=80%><th>
<a href="./index.php"><p style="font-size:20px">登出</p></a></th></table>
</center>
</body>
</html>
