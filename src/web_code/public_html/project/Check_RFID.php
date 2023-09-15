<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="UTF-8">
	    <meta Name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	    <title>站點資料</title>
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
								<a href="./Check.php">Wi-SUN 連線狀態</a>
							</th>
							<th width="270">
								<p>站點資料</p>
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
						<th width="240">站點名稱</th><th width="300">RFID</th><th width="300">站點類型</th>
					
					<?php
						include("database.php");
						$sql = "SELECT * FROM  site ORDER BY Name";
						$result = mysqli_query($con, $sql);

						$i=0;
						while(list($Name[$i],$RFID[$i],$sort[$i]) = mysqli_fetch_row($result))
						$i ++;

						for($j = 0; $j < $i; $j++)
						{

							$sql = "select hex('$RFID[$j]')";
							$result = mysqli_query($con, $sql);
							echo "<tr   bgcolor=\"#FCFCFC\"><th>" .$Name[$j]. "</th><th>".$RFID[$j]."</th><th>";
							switch($sort[$j])
							{
								case 0:
									echo "起始站點";
									break;
								case 1:
									echo "倉儲站點";
									break;
								case 2:
									echo "工作站點";
									break;
							}
							echo "</th></tr>";
						}
						mysqli_free_result($result);
						mysqli_close($con);
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