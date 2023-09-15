<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="UTF-8">
	    <meta Name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	    <title>手動操作功能</title>
	</head>

	<body>
				<form action="" method=post>
					<table border="1" width="1080" align="center">
					
					
						<tr height = "50" >
							<th colspan="4"  bgcolor="#97CBFF">
								<p style="font-size:20px">手動操作功能</p>
							</th>
						</tr>
						<tr  bgcolor="#FCFCFC" height = "50">
							<th width="270">
								<a href="./Manual_Car.php">車子</a>
							</th>
							<th width="270">
								<p>天車</p>
							</th>
						</tr>
						<tr  bgcolor="#FCFCFC" height = "50">
							<th colspan="4">
							<a href = "/~Wi_SUN/">
								<input type="button" value="返回系統操作介面" style="align;"></button></a>
							</th>
						
						</tr>
							
					</table>
						<table border="2" width="1080" align="center"><br>
						<tr bgcolor="#97CBFF">
							<th COLSPAN=3 ALIGN=CENTER>
								<font size=5>資料顯示</font>
							</th>
						</tr>

						<tr  bgcolor="#FCFCFC">
						<th width="540">站點名稱</th><th width="540">RFID</th>
					
					<?php
						include("database.php");
						$sql = "SELECT * FROM  CarSite_RFID ORDER BY Name";
						$result = mysqli_query($con, $sql);

						$i=0;
						while(list($Name[$i],$RFID[$i]) = mysqli_fetch_row($result))
						$i ++;

						for($j = 0; $j < $i; $j++)
						{

							$sql = "select hex('$RFID[$j]')";
							$result = mysqli_query($con, $sql);
							echo "<tr   bgcolor=\"#FCFCFC\"><th>" .$Name[$j]. "</th><th>" .$RFID[$j]. "</th></tr>";
						}
						mysqli_free_result($result);
						mysqli_close($con);
					?>
					</table>

				</form>
	</body>
</html>