<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="UTF-8">
	    <meta Name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	    <title>Wi-SUN 連線狀態</title>
	</head>

	<body>
				<form action="" method=post>
					<table border="1" width="80%" align="center">
					
					
						<tr height = "50" >
							<th colspan="5"  bgcolor="#97CBFF">
							<p ><font size=5>資料與狀態查詢功能</font></p>
							</th>
						</tr>
						<tr  bgcolor="#FCFCFC" height = "50">
							<th width="270">
								<p>受控設備連線狀態</p>
							</th>
							<th width="270">
								<a href="./Check_RFID.php">站點資料</a>
							</th>
							<th width="270">
								<a href="./Check_WH.php">倉儲位址資料</a>
							</th>
							<th width="270">
								<a href="./Check_Route.php">路線與站點資料</a>	
							</th>
							<th width="270">
								<a href="./Check_schedule.php">工單資料</a>	
							</th>
						</tr>
							
					</table>
					<table border="2" width="80%" align="center"><br>
						<tr bgcolor="#97CBFF">
							<th COLSPAN=3 ALIGN=CENTER>
								<font size=5>資料顯示</font>
							</th>
						</tr>

						<tr  bgcolor="#FCFCFC">
						<th width="360">節點名稱</th><th width="360">類別</th><th width="360">狀態</th>
					
					<?php	
						include("database.php");
						$sql = "SELECT Name,Category,Status FROM Wi_SUN_IP where Category = 1 ORDER BY Name";
						$result = mysqli_query($con, $sql);
						
						$i=0;
						while(list($Name[$i],$Category[$i],$Status[$i]) = mysqli_fetch_row($result))
						$i ++;

						for($j = 0; $j < $i; $j++)
						{
					
							$Category[$j] = "Car";
							
							switch($Status[$j])
							{
								case 1:
									$Status[$j] = "已連線";
									echo "<tr   bgcolor=\"#28FF28\"><th>" .$Name[$j]. "</th><th>" .$Category[$j]. "</th><th>" .$Status[$j]."</th></tr>";
									break;
								case 2:
									$Status[$j] = "未連線";
									echo "<tr   bgcolor=\"#FFFF37\"><th>" .$Name[$j]. "</th><th>" .$Category[$j]. "</th><th>" .$Status[$j]."</th></tr>";
									break;
								default:
									$Status[$j] = "未連線";
									echo "<tr   bgcolor=\"#FFFF37\"><th>" .$Name[$j]. "</th><th>" .$Category[$j]. "</th><th>" .$Status[$j]."</th></tr>";
									break;
							}
							
							
						}
						mysqli_free_result($result);
						$sql = "SELECT Name,Category,Status FROM Wi_SUN_IP where Category = 2 ORDER BY Name";
						$result = mysqli_query($con, $sql);
						
						$i=0;
						while(list($Name[$i],$Category[$i],$Status[$i]) = mysqli_fetch_row($result))
						$i ++;

						for($j = 0; $j < $i; $j++)
						{
					
							$Category[$j] = "Warehouse";
							
							switch($Status[$j])
							{
								case 1:
									$Status[$j] = "已連線";
									echo "<tr   bgcolor=\"#28FF28\"><th>" .$Name[$j]. "</th><th>" .$Category[$j]. "</th><th>" .$Status[$j]."</th></tr>";
									break;
								case 2:
									$Status[$j] = "未連線";
									echo "<tr   bgcolor=\"#FFFF37\"><th>" .$Name[$j]. "</th><th>" .$Category[$j]. "</th><th>" .$Status[$j]."</th></tr>";
									break;
								default:
									$Status[$j] = "未連線";
									echo "<tr   bgcolor=\"#FFFF37\"><th>" .$Name[$j]. "</th><th>" .$Category[$j]. "</th><th>" .$Status[$j]."</th></tr>";
									break;
							}
							
							
						}
						mysqli_free_result($result);
						mysqli_close($con);
					?>
					
					<?php
						header("refresh: 30;");
					?>
					</table>
					<br>
					<table border="1" width="80%" align="center" >
						<tr><th height=60 bgcolor="#CCFFFF">
						<a href="../user.php"><p style="font-size:20px">上一頁</p></a>
						</th></tr>
					</table>
				</form>
	</body>
</html>