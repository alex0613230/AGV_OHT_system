<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="UTF-8">
	    <meta Name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	    <title>站點與倉儲位置管理功能</title>
	    <style>
	    th
	    {
		 font-size: 20px;  
	    }
	    hi,p
	    {font-size: 35px;  }
	    </style>
	</head>
<?	
	include("sys_database.php");
?>
	<body>
				<form action="" method=post>
					<table border="1" width=70% align="center">
						<tr height = "50">
							<th colspan="2"  bgcolor="#97CBFF">
								<p><hi>站點管理功能</hi></p>
							</th>
						</tr>
						
						<tr  bgcolor="#FCFCFC" height = "50">
							<th  colspan="2">
								站點名稱：<input type="text" Name="Name" size="10" maxlength="4">&nbsp&nbsp
								RFID：<input type="text" Name="RFID"   size="20" maxlength="8">&nbsp&nbsp
								站點類型：
								<select name="sort">
									<option value="0">起始站點</option>
									<option value="1">倉儲站點</option>
									<option value="2">工作站點</option>
								</select>　
								<input type="submit" Name="add" value="提交" />
							</th>
						</tr>

						<tr  bgcolor="#FCFCFC" height = "50">
							<th  colspan="2">
								
								<a href = "./sys.php">
								<input type="button" value="返回系統管理介面" style="align;"></button>
								</a>
							</th>
						
						</tr>
					</table>
					<table border="2" width="70%" align="center"><br>
						<tr bgcolor="#97CBFF">
							<th>
								<hi>資料清除</hi>
							</th>
						</tr>
						<tr  bgcolor="#FCFCFC">
							<th>
								站點名稱&nbsp&nbsp:&nbsp&nbsp<input type="text" Name="del" size="8"/ >&nbsp&nbsp
								<input type="submit" Name="onedel" value="清除" />
							</th>
						</tr>
					</table>
					
					<?php
						include("sys_database.php");
						if(isset($_POST['add']))
						{ 
							$addName = $_POST['Name'];
							$addRFID = $_POST['RFID'];
							$addsort = $_POST['sort'];
							$sql = "select count(*) from site where RFID = '$addRFID'";
							$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
							$n = mysqli_fetch_row($result);
							$i = 0;
							if($n[0] != 0)
							{
								$i++;
								echo "<script>alert('RFID 重複')</script>";
							}
							mysqli_free_result($result);
							
							$sql = "select count(*) from site where Name = '$addName'";
							$result = mysqli_query($con,$sql) or die("Error in query: $query. 456". mysqli_error());
							$n = mysqli_fetch_row($result);
							if($n[0] != 0)
							{
								$i++;
								echo "<script>alert('名字重複')</script>";
							}
							mysqli_free_result($result);
							
							
							if($i == 0 && $addName != NULL && $addRFID != NULL)
							{
								$query = "INSERT INTO site VALUE('$addName','$addRFID',$addsort)"; 
								$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error());
								mysqli_free_result($result);
								header("refresh: 0;");
							}
							mysqli_close($con);						
						} 
						
						if(isset($_POST['onedel']))
						{ 
							$del = $_POST['del'];
							$query = "select count(*) from Route where Isite = '$del' || Csite = '$del'"; 
							$result = mysqli_query($con,$query) or die("Error in query: $query.". mysqli_error());
							$n = mysqli_fetch_row($result);
							
							$check = 0;
							if($n[0] != 0)
							{
								$check++;
							}
							mysqli_free_result($result);
							
							$query = "select Tsite from Route";
							$result = mysqli_query($con,$query) or die("Error in query: $query.". mysqli_error());
							
							$i=0;
							while(list($Tsite_str[$i]) = mysqli_fetch_row($result))
							$i ++;

							for($j = 0; $j < count($Tsite_str); $j++)
							{
								$match =  explode(",",$Tsite_str[$j]);
								for($k = 0; $k < count($match); $k++)
								{
									if($match[$k] == $del)
										$check++;
								}
							}
							if($check == 0)
							{
								$query = "delete from site where Name='$del'"; 
								$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error()); 
								mysqli_free_result($result);
								header("refresh: 0;");
							}
							else if($del == NULL)
							{
								mysqli_free_result($result);
								header("refresh: 0;");
							}
							else if($check != 0)
							{
								echo "<script>alert('請先刪除路徑');</script>";
							}
							mysqli_free_result($result);
							mysqli_close($con); 
						} 
					?>
					<table border="2" width="70%" align="center"><br>
						<tr bgcolor="#97CBFF">
							<th COLSPAN=3 ALIGN=CENTER>
								<hi>資料顯示</hi>
							</th>
						</tr>

						<tr  bgcolor="#FCFCFC">
						<th width="240">站點名稱</th><th width="300">RFID</th><th width="300">站點類型</th>
					
					<?php
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

				</form>
	</body>
</html>