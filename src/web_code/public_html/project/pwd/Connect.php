<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	    <title>Wi_SUN 資料管理功能</title>
	</head>

	<body>	
	<?	
		include("sys_database.php");
	?>
				<form action="" method=post>
					<table border="1" width="60%" align="center">
						<tr height = "50">
							<th colspan="2"  bgcolor="#97CBFF">
								<p><font size=5>受控設備管理功能</font></p>
							</th>
						</tr>
						<tr height = "50">
							<th width = "50%">
								<a href="Register.php" style="text-decoration:none;">Wi-SUN 資料管理</a>
							</th>
							<th width = "50%">
								<p>連線狀態查詢</p>
							</th>
						</tr>
						<tr  bgcolor="#FCFCFC" height = "50">
							<th colspan="2" >
							<a href = "./sys.php">
								<input type="button" value="返回系統管理介面" style="align;"></button></a>
							</th>
						
						</tr>
							
					</table>
					<table border="2" width="60%" align="center"><br>
						<tr bgcolor="#97CBFF">
							<th COLSPAN=3 ALIGN=CENTER>
								<font size=5>資料顯示</font>
							</th>
						</tr>

						<tr  bgcolor="#FCFCFC">
						<th width="33%">受控設備名稱</th><th width="33%">類別</th><th width="33%">狀態</th>
					
					<?php
						$sql = "SELECT Name, Category, Status FROM Wi_SUN_IP where Category = 1 ORDER BY Name";
						$result = mysqli_query($con, $sql);
						
						$i=0;
						while(list($Name[$i],$Category[$i],$Status[$i]) = mysqli_fetch_row($result))
						$i ++;

						for($j = 0; $j < $i; $j++)
						{
							$Category[$j] = "AGV 設備";	
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
						
								$Category[$j] = "OHT 設備";
								
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
								}	
							}
							mysqli_free_result($result);
							mysqli_close($con);
						
					?>
					
					<?php
						header("refresh: 30;");
					?>
					</table>
				</form>
	</body>
</html>