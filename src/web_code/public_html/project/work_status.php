<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="UTF-8">
	    <meta Name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	    <title>作業管理</title>
	</head>

	<body>
				<form action="" method=post>
					<table border="1" width="960" align="center">
						<tr height = "50">
							<th colspan="4"  bgcolor="#97CBFF">
								<p style="font-size:20px">作業管理</p>
							</th>
						</tr>
						<tr height = "50">
							<th colspan="2" >
								<a href="./work.php">新增作業</a>
							</th>
							<th colspan="2" >
								<p style="font-size:20px">處理作業</p>
							</th>
						</tr>
					</table>
					<br>
					<br>
					<table border="1" width="960" height="175" align="center">
						<tr>
						<th>
							車子狀態
						</th>
						<th>
							
						</th>
						<th>
							天車狀態
						</th>
						<th>
						
						</th>
						</tr>
						<tr>
						<th colspan="2">
							車子 : <select name="Car">
								<?php
									include("database.php");
									$sql = "SELECT Car FROM work where Status =0";
									$result = mysqli_query($con, $sql);

									$i=0;
									while(list($Name[$i]) = mysqli_fetch_row($result))
									$i ++;
									for($j = 0; $j < $i; $j++)
									{
										$result = mysqli_query($con, $sql);
										echo "<option value='".$Name[$j]."'>".$Name[$j]."</option>";
									}
									echo "</select>&nbsp&nbsp";
									mysqli_free_result($result);
									mysqli_close($con);
								?>
							命令 : <select name="Command_C">
							<option value='0'>開始</option>
							<option value='1'>回總站</option>
							<option value='2'>卸貨</option>
							<option value='3'>重置</option>
							</select>
								
						</th>
						<th colspan="2">
							天車 : <select name="Warehouse">
								<?php
									include("database.php");
									$sql = "SELECT Warehouse FROM work where Status =1";
									$result = mysqli_query($con, $sql);

									$i=0;
									while(list($Name[$i]) = mysqli_fetch_row($result))
									$i ++;
									for($j = 0; $j < $i; $j++)
									{
										$result = mysqli_query($con, $sql);
										echo "<option value='".$Name[$j]."'>".$Name[$j]."</option>";
									}
									echo "</select>&nbsp&nbsp";
									mysqli_free_result($result);
									mysqli_close($con);
								?>
							命令 : <select name="Command_W">
							<option value='0'>開始</option>
							<option value='1'>重置並歸位</option>
							<option value='2'>回邏輯零點</option>
							<option value='3'>電磁鐵工作</option>
							<option value='4'>電磁鐵不工作</option>
							<option value='5'>斷電恢復</option>
								</select>
						</th>
							
						</tr>
							
						
						
						<tr  bgcolor="#FCFCFC" height = "50" >
							<th colspan="4">
								<input type="button" value="返回主頁" onclick="location.href='./index.php'" style="align;"></button>
								<input type="submit" Name='start' value="開始" >
								<input type="submit" Name='stop' value="停止" >
								<input type="submit" Name='clear' value="清空已完成" /> 
							</th>
						
						</tr>
					</table>
					
					<? 
					
					?>			
					<table border="2" width="960" align="center"><br>
						<tr bgcolor="#97CBFF">
							<th COLSPAN=8 ALIGN=CENTER>
								<font size=5>資料顯示</font>
							</th>
						</tr>

						<tr  bgcolor="#FCFCFC">
						<th width="240">識別碼</th><th width="300">負責車子</th><th width="300">負責天車</th><th width="240">狀態</th><th width="300">初始站點</th><th width="300">取貨站點</th><th width="300">天車取貨點</th><th width="300">天車存貨點</th>
						</tr>
					
					<?php
						include("database.php");
						if(isset($_POST['onedel']))
						{
							$del = $_POST['del'];
							$sql = "delete from work where ID = '$del'";
							$result = mysqli_query($con, $sql);
							mysqli_free_result($result);
							
							$sql = "ALTER TABLE work DROP ID";
							$result = mysqli_query($con, $sql);
							mysqli_free_result($result);
							
							$sql = "ALTER TABLE work ADD  ID INT NOT NULL AUTO_INCREMENT FIRST ,ADD PRIMARY KEY (ID)";
							$result = mysqli_query($con, $sql);
							mysqli_free_result($result);
						}
						else if(isset($_POST['clear']))
						{
							$sql = "delete from work where Status = 2";
							$result = mysqli_query($con, $sql);
							mysqli_free_result($result);
						}
						$sql = "SELECT * FROM work";
						$result = mysqli_query($con, $sql);

						$i=0;
						while(list($db_ID[$i],$db_Car[$i],$db_Warehouse[$i],$db_Status[$i],$db_Initial_site[$i],$db_Task_site[$i],$db_Initial_Warehouse[$i],$db_Task_Warehouse[$i]) = mysqli_fetch_row($result))
						$i ++;
						
						
						for($j = 0; $j < $i; $j++)
						{
							switch($db_Status[$j])
							{
								case 0:
									$db_Status[$j] = "待處理";
								break;
								case 1:
									$db_Status[$j] = "處理中";
								break;
								case 2:
									$db_Status[$j] = "已完成";
								break;
							}
							echo "<tr bgcolor=\"#FCFCFC\"><th>".$db_ID[$j]."</th><th>".$db_Car[$j]."</th><th>".$db_Warehouse[$j]."</th>";
							switch($db_Status[$j])
							{
								case "待處理":
									echo "<th bgcolor='#a5dff8'>".$db_Status[$j];
								break;
								case "處理中":
									echo "<th bgcolor='#f3f8a5'>".$db_Status[$j];
								break;
								case "已完成":
									echo "<th bgcolor='#60e161'>".$db_Status[$j];
								break;
							}
							
							echo "</th><th>".$db_Initial_site[$j]."</th><th>".$db_Task_site[$j]."</th><th>".$db_Initial_Warehouse[$j]."</th><th>".$db_Task_Warehouse[$j]."</th></tr>";
						}
						mysqli_free_result($result);
						mysqli_close($con);
					?>
					
					</table>
				</form>
	</body>
</html>