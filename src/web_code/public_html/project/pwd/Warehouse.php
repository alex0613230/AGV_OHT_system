<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="UTF-8">
	    <meta Name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	    <title>站點與倉儲位置管理功能</title>
	    <style>
	    th
	    {font-size: 22px; }
	    p
	    {font-size: 25px;}
	    </style>
	</head>
<?	
	include("sys_database.php");
?>
	<body>
				<form action="" method=post>
					<table border="1" width="80%" align="center">
						<tr height = "50">
							<th colspan="5"  bgcolor="#97CBFF">
								<p ><font size=5>倉儲位置管理功能</font></p>
							</th>
						</tr>
						<tr><th  height="50" colspan="5">
								
								倉儲站點：
								<select Name="Csite">
								<?
									$sql = "select Name from site where Sort=1 ORDER BY NAME";
									$result = mysqli_query($con, $sql);
									$i=0;
									while(list($Csite[$i]) = mysqli_fetch_row($result))
									$i ++;

									for($j = 0; $j < $i; $j++)
									{
										echo "<option value='".$Csite[$j]."'>".$Csite[$j]."</option>";
									}
								?></select>&nbsp&nbsp
								倉儲 Wi-SUN 名稱：<select Name="WS">
								<?
									$sql = "select Name from Wi_SUN_IP where Category = 2";
									$result = mysqli_query($con, $sql);
									$i=0;
									while(list($Name[$i]) = mysqli_fetch_row($result))
									$i ++;
									for($j = 0; $j < $i; $j++)
									{
										echo "<option value='".$Name[$j]."'>".$Name[$j]."</option>";
									}
								?></select>&nbsp&nbsp<br>
							</th>
							</tr>
						<tr height = "50">
							<th width="300">取貨位置</th>
							<th>
								 w  :<input type="text" Name="CW"   size="4" maxlength="4">&nbsp  
								 x  :<input type="text" Name="CX"   size="4" maxlength="4">&nbsp 
								 y  :<input type="text" Name="CY"   size="4" maxlength="4">&nbsp 
								 z  :<input type="text" Name="CZ"   size="4" maxlength="4">&nbsp </th>
						</tr>
						<tr height = "50">
							<th width="300">倉儲位置</th>
							<th>	 w  :<input type="text" Name="WW"   size="4" maxlength="4">&nbsp  
								 x  :<input type="text" Name="WX"   size="4" maxlength="4">&nbsp 
								 y  :<input type="text" Name="WY"   size="4" maxlength="4">&nbsp 
								 z  :<input type="text" Name="WZ"   size="4" maxlength="4">&nbsp </th>
						</tr>
						<tr  bgcolor="#FCFCFC" height = "50">
							<th  colspan="5">
							<a href = "./sys.php">
							<input type="button" value="返回系統管理介面" style="align;"></button></a>
							<input type="submit" Name="add" value="提交" /> 
							</th>
						
						</tr>
					</table>
					<table border="2" width="80%" align="center"><br>
						
						<tr bgcolor="#97CBFF">
							<th>
								<font size=5>資料清除</font>
							</th>
						</tr>
						<tr  bgcolor="#FCFCFC">
							<th>
								倉儲站點&nbsp&nbsp:&nbsp&nbsp<input type="text" Name="del" size="8"/ >&nbsp&nbsp
								<input type="submit" Name="onedel" value="清除" />
							</th>
						</tr>
					</table>
					<?php
						include("sys_database.php");

						if(isset($_POST['add']))
						{ 
							$addCsite = $_POST['Csite'];
							$WS = $_POST['WS'];
							$addCA = array($_POST['CX'],$_POST['CY'],$_POST['CZ'],$_POST['CW']);
							$addWA = array($_POST['WX'],$_POST['WY'],$_POST['WZ'],$_POST['WW']);
							
							$x = 0;
							$y = 1;
							$z = 2;
							$w = 3;
							
							if(preg_match("/^\d*$/",$addCA[$x]) 
								&& preg_match("/^\d*$/",$addCA[$y]) 
								&& preg_match("/^\d*$/",$addCA[$z]) 
								&& preg_match("/^\d*$/",$addCA[$w])
								&& preg_match("/^\d*$/",$addWA[$y]) 
								&& preg_match("/^\d*$/",$addWA[$z]) 
								&& preg_match("/^\d*$/",$addWA[$w])
								&& preg_match("/^\d*$/",$addWA[$x]))   
							{
								$sql = "select count(*) from position where Csite = '$addCsite'";
								$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
								$n = mysqli_fetch_row($result);
								$check = 0;
								if($n[0] != 0)
								{
									$check++;
									echo "<script>alert('重複出現 Name :  ".$addCsite." ');</script>";
								}
								mysqli_free_result($result);
								
								$sql = "select count(*) from position where WS = '$WS'";
								$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
								$n = mysqli_fetch_row($result);
								$check = 0;
								if($n[0] != 0)
								{
									$check++;
									echo "<script>alert('重複出現 Wi-SUN :  ".$WS." ');</script>";
								}
								mysqli_free_result($result);
								
								for($j = 0; $j<count($addWA);$j++)
								{
									if($addWA[$j] == NULL)
									{
										$check++;
									}
								}
								
								for($j = 0; $j<count($addWA);$j++)
								{
									if($addWA[$j] == NULL)
									{
										$check++;
									}
								}
								
								if($addCsite != NULL && $check == 0)
								{
									$query = "INSERT INTO position VALUE('$addCsite','$WS',$addCA[$w],$addCA[$x],$addCA[$y],$addCA[$z],$addWA[$w],$addWA[$x],$addWA[$y],$addWA[$z])"; 
									$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error());
									
								}
								mysqli_free_result($result);header("refresh: 0;");
							}
							else
								echo "<script>alert('請輸入數字');</script>";
							
							mysqli_close($con);
							
						} 
						if(isset($_POST['onedel']))
						{ 
							$del = $_POST['del'];
							$query = "select count(*) from Route where Csite = '$del'"; 
							$result = mysqli_query($con,$query) or die("Error in query: $query.". mysqli_error());
							$n = mysqli_fetch_row($result);
							
							$check = 0;
							if($n[0] != 0)
							{
								$check++;
								echo "<script>alert('請先刪除路徑');</script>";
							}
							
							if($check == 0)
							{
								$query = "delete from position where Csite='$del'"; 
								$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error()); 
								mysqli_free_result($result);
								
							}
							mysqli_free_result($result);
							mysqli_close($con); header("refresh: 0;");
							
						} 
						
					?>
					<table border="2" width="80%" align="center"><br>
						<tr bgcolor="#97CBFF">
							<th COLSPAN=7 ALIGN=CENTER>
								<font size=5>資料顯示</font>
							</th>
						</tr>

						<tr  bgcolor="#FCFCFC">
						<th width="300">倉儲站點</th><th width="240">Wi-SUN 名稱</th><th width="240">位置</th><th width="300">w軸</th><th width="300">x軸</th><th width="300">y軸</th><th width="300">z軸</th></tr>
					
					<?php
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
				</form>
	</body>
</html>