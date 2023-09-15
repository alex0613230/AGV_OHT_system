<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="UTF-8">
	    <meta Name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	    <title>受控設備管理功能</title>
	     <style>
	    th
	    {
		 font-size: 22px;  
	    }
	    p
	    {font-size: 24px;  }
	    </style>
	</head>
<?	
	include("sys_database.php");
?>
	<body>
				<form action="" method=post>
					<table border="1" width="80%" align="center">
						<tr height = "50">
							<th colspan="2"  bgcolor="#97CBFF">
								<p><font size=5>受控設備管理功能</font></p>
							</th>
						</tr>
						<tr height = "50">
							<th width = "50%">
								<p>Wi-SUN 資料管理</p>
							</th>
							<th width = "50%">
								<a href="Connect.php" >連線狀態查詢</a>
							</th>
						</tr>
						<tr  bgcolor="#FCFCFC" height = "50">
							<th colspan="2">
								受控設備名稱：<input type="text" Name="Name" size="4" maxlength="4">&nbsp&nbsp
								IPv6 位址：<input type="text" Name="IP"   size="40" maxlength="29">&nbsp&nbsp
								<select name="Category">
									<option value="">選擇類別</option>
									<option value="1">AGV 設備</option>
									<option value="2">OHT 設備</option>
								</select>
								&nbsp&nbsp&nbsp
								<input type="submit" Name="add" value="提交" /> 
							</th>
						</tr>

						<tr  bgcolor="#FCFCFC" height = "50" >
							<th colspan="2">
							<a href = "./sys.php">
								<input type="button" value="返回系統管理介面" style="align;"></button></a>
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
								受控設備名稱：<input type="text" Name="delpatientcard" size="8"/ >&nbsp&nbsp
								<input type="submit" Name="onedel" value="清除" />
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
						<th width="240">受控設備名稱</th><th width="300">IPv6 位址</th><th width="300">類別</th>
					
					<?php
						$sql = "SELECT * FROM Wi_SUN_IP where Category = 1 ORDER BY Name";
						$result = mysqli_query($con, $sql);

						$i=0;
						while(list($Name[$i],$IP[$i],$Category[$i]) = mysqli_fetch_row($result))
						$i ++;

						for($j = 0; $j < $i; $j++)
						{
							switch($Category[$j])
							{
								case 1:
									$Category[$j] = "AGV 設備";
									break;
								case 2:
									$Category[$j] = "OHT 設備";
									break;
							}
							$sql = "select hex('$IP[$j]')";
							$result = mysqli_query($con, $sql);
							echo "<tr   bgcolor=\"#FCFCFC\"><th>" .$Name[$j]. "</th><th>" .$IP[$j]. "</th><th>" .$Category[$j]."</th></tr>";
						}
						mysqli_free_result($result);
						$sql = "SELECT * FROM Wi_SUN_IP where Category = 2";
						$result = mysqli_query($con, $sql);

						$i=0;
						while(list($Name[$i],$IP[$i],$Category[$i]) = mysqli_fetch_row($result))
						$i ++;

						for($j = 0; $j < $i; $j++)
						{

							$Category[$j] = "OHT 設備";

							$sql = "select hex('$IP[$j]')";
							$result = mysqli_query($con, $sql);
							echo "<tr   bgcolor=\"#FCFCFC\"><th>" .$Name[$j]. "</th><th>" .$IP[$j]. "</th><th>" .$Category[$j]."</th></tr>";
						}
						mysqli_free_result($result);
						mysqli_close($con);
					?>
					</table>
					<?php
						include("sys_database.php");
						if(isset($_POST['add']))
						{ 
							$addName = $_POST['Name'];
							$addIP = $_POST['IP'];
							$addCg = $_POST['Category'];
							$sql = "select count(*) from Wi_SUN_IP where IP = '$addIP'";
							$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
							$n = mysqli_fetch_row($result);
							$i = 0;
							if($n[0] != 0)
							{
								$i++;
								echo "<script>alert('重複出現IP')</script>";
							}
							mysqli_free_result($result);
							
							$sql = "select count(*) from Wi_SUN_IP where Name = '$addName'";
							$result = mysqli_query($con,$sql) or die("Error in query: $query. 456". mysqli_error());
							$n = mysqli_fetch_row($result);
							if($n[0] != 0)
							{
								$i++;
								echo "<script>alert('重複出現Name')</script>";
								
							}
							mysqli_free_result($result);
							if($addCg == NULL)
							{
								echo "<script>alert('請選擇新增類別')</script>";
							}
							mysqli_free_result($result);
							if($i == 0 && $addName != NULL && $addIP != NULL && $addCg != NULL)
							{
								$query = "INSERT INTO Wi_SUN_IP(Name,IP,Category,Status) VALUE('$addName','$addIP','$addCg',2)"; 
								$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error());
								mysqli_free_result($result);
								switch($addCg)
								{
									case "1":
										$query = "INSERT INTO Car_status VALUE('$addIP','NA','NA','NA')"; 
										$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error());
										mysqli_free_result($result);
										break;
									case "2":
										$query = "INSERT INTO Crane_status VALUE('$addIP','NA','NA','NA','NA','NA','NA')"; 
										$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error());
										mysqli_free_result($result);
										break;
								}
								header("refresh: 0;");
							}
							mysqli_close($con);						
							
						} 
						if(isset($_POST['onedel']))
						{ 
							$delpatientcard = $_POST['delpatientcard'];
							$sql = "SELECT IP,Category FROM Wi_SUN_IP where Name = '$delpatientcard'";
							$result = mysqli_query($con, $sql);
							list($IP_de,$Category_de) = mysqli_fetch_row($result);
							switch ($Category_de) 
							{
								case "1":
									$query = "delete from Car_status where ip='$IP_de'"; 
									$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error()); 
									break;
								
								case "2":
									$query = "delete from Crane_status where ip='$IP_de'"; 
									$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error()); 
									break;
							}
							$query = "delete from Wi_SUN_IP where Name='$delpatientcard'"; 
							$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error()); 
							mysqli_close($con); 
							header("refresh: 0;");
						}
					?>
				</form>
	</body>
</html>