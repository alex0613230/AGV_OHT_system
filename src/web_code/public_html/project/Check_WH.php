<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="UTF-8">
	    <meta Name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	    <title>取貨與倉儲位址資料</title>
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
								<a href="./Check_RFID.php">站點資料</a>
							</th>
							<th width="270">
								<p>倉儲位址資料</p>
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
							<th COLSPAN=7 ALIGN=CENTER>
								<font size=5>資料顯示</font>
							</th>
						</tr>

						<tr  bgcolor="#FCFCFC">
						<th width="240">OHT 設備站點</th><th width="240">Wi-SUN 名稱</th><th width="240">位置</th><th width="300">w軸</th><th width="300">x軸</th><th width="300">y軸</th><th width="300">z軸</th></tr>
					
					<?php
						include("database.php");
						$sql = "SELECT * FROM  position ORDER BY Csite";
						$result = mysqli_query($con, $sql);

						$i=0;
						while(list($Csite[$i], $WS[$i], $CW[$i], $CX[$i], $CY[$i], $CZ[$i], $WW[$i], $WX[$i], $WY[$i], $WZ[$i]) = mysqli_fetch_row($result))
						$i ++;

						for($j = 0; $j < $i; $j++)
						{
							echo "<tr  bgcolor='#FCFCFC'>";
							echo "<th rowspan = 2>".$Csite[$j]."</th>";
							echo "<th rowspan = 2>".$WS[$j]."</th>";
							echo "<th rowspan = 1>取貨</th>";
							
							echo "<th rowspan = 1>".$CW[$j]."</th>";
							echo "<th rowspan = 1>".$CX[$j]."</th>";
							echo "<th rowspan = 1>".$CY[$j]."</th>";
							echo "<th rowspan = 1>".$CZ[$j]."</th>";
							echo "</tr>";
							
							echo "<tr>";
							
							echo "<th rowspan = 1>倉儲</th>";
							echo "<th rowspan = 1>".$WW[$j]."</th>";
							echo "<th rowspan = 1>".$WX[$j]."</th>";
							echo "<th rowspan = 1>".$WY[$j]."</th>";
							echo "<th rowspan = 1>".$WZ[$j]."</th>";
							echo "</tr>";
							
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