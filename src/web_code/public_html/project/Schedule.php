<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="UTF-8">
	    <meta Name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	    <title>工單製作功能</title>
	</head>
<?	
	session_start();
	include("database.php");
	$web_status = $_SESSION["Web_status"];
	if(isset($_POST['addroute']))
	{
		$_SESSION["addroute"] = $_POST['addroute'];
	}
	
	if(isset($_POST['add']))
	{ 
		$addCar = $_POST['addCar'];
		$addWH = $_POST['addWH'];
		$addTsite = $_POST['addTsite'];
		$addroute = $_SESSION['addroute'];
		
		if( ($addCar == NULL) || ($addWH == NULL) || ($addroute == NULL) || ($addTsite == NULL) )
		{
			echo "<script>alert('資料輸入異常')</script>";
			$web_status = NULL;
			unset($_SESSION["Web_status"]);
			unset($_SESSION["addroute"]);
		}
		else
		{
			//-------------------INPUT schedule TABLE 
			$sql = "insert into schedule value('$addroute', '$addCar', '$addTsite', '$addWH', 0, 0)"; 
			$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
			mysqli_free_result($result);
			
			//--------------------Get Initial_site_RFID Crane_site_RFID
			$sql = "select Isite,Csite from Route where Name='$addroute'"; 
			$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
			list($Isite, $Csite) = mysqli_fetch_row($result);
			mysqli_free_result($result);
			
			$sql = "select RFID from site where Name='$Isite'"; 
			$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
			list($Isite_RFID) = mysqli_fetch_row($result);
			$Isite_RFID = sprintf("%08s", $Isite_RFID);
			// echo "Isite_RFID : ".$Isite_RFID;
			mysqli_free_result($result);
			
			$sql = "select RFID from site where Name='$Csite'"; 
			$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
			list($Csite_RFID) = mysqli_fetch_row($result);
			$Csite_RFID = sprintf("%08s", $Csite_RFID);
			// echo "  Csite_RFID : ".$Csite_RFID."<br>";
			mysqli_free_result($result);
			
			//---------------Tsite and another
			$sql = "select RFID from site where Name='$addTsite'"; 
			$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
			list($Tsite_RFID) = mysqli_fetch_row($result);
			$Tsite_RFID = sprintf("%08s", $Tsite_RFID);
			//echo "Tsite_RFID : ".$Tsite_RFID;
			mysqli_free_result($result);
			$sql = "SELECT IP FROM Wi_SUN_IP where Name = '$addCar'";
			$result = mysqli_query($con, $sql);
			list($Car_ip) = mysqli_fetch_row($result);
			
			$sql = "SELECT Name FROM site where RFID = '$Csite_RFID'";
			$result = mysqli_query($con, $sql);
			list($Csite_name) = mysqli_fetch_row($result);
			
			$sql = "SELECT WS FROM position where Csite = '$Csite_name'";
			$result = mysqli_query($con, $sql);
			list($Crane_name) = mysqli_fetch_row($result);
			
			$sql = "SELECT IP FROM Wi_SUN_IP where Name = '$Crane_name'";
			$result = mysqli_query($con, $sql);
			list($Crane_ip) = mysqli_fetch_row($result);
			
			$sql = "insert into car_order values('$Car_ip', '$Crane_ip', '$Tsite_RFID', '$Csite_RFID', '$Isite_RFID', '$addWH', 0, '$addCar',0)"; 
			$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
			mysqli_free_result($result);
		}
	}
	else if(isset($_POST['del']))
	{ 
		$del = $_POST['del'];
		echo $del;
		$sql = "delete from schedule where No = '$del'"; 
		$result = mysqli_query($con,$sql) or die("Error in query: $sql. ". mysqli_error());
		
		$sql = "delete from car_order where No = '$del'"; 
		$result = mysqli_query($con,$sql) or die("Error in query: $sql. ". mysqli_error()); 
		$sql = "alter table schedule drop column No"; 
		$result = mysqli_query($con,$sql) or die("Error in query: $sql. ". mysqli_error()); 
		$sql = "alter table schedule add No int unsigned auto_increment primary key"; 
		$result = mysqli_query($con,$sql) or die("Error in query: $sql. ". mysqli_error()); 
		$sql = "alter table car_order drop column No"; 
		$result = mysqli_query($con,$sql) or die("Error in query: $sql. ". mysqli_error()); 
		$sql = "alter table car_order add No int unsigned auto_increment primary key"; 
		$result = mysqli_query($con,$sql) or die("Error in query: $sql. ". mysqli_error());  	
		mysqli_free_result($result);
		
		header("refresh: 0;");
	}
	else if(isset($_POST['finish']))
	{
		$web_status = NULL;
		unset($_SESSION["Web_status"]);
		unset($_SESSION["addroute"]);
	}
	include("database.php");
?>
	<body>
		<form action="" method=post>
			<table border="1" width="80%" align="center">
				<tr height = "50">
					<th colspan="4"  bgcolor="#97CBFF">
						<p ><font size=5>工單製作功能</font></p>
					</th>
				</tr>
				<tr bgcolor='#FCFCFC' height = '50'>
				<?
					echo "<th colspan='2' width='180'>選擇路線：<select Name='addroute'>";
					
					$sql = "SELECT Name FROM Route ORDER BY Name";
					$result = mysqli_query($con, $sql);

					$i=0;
					while(list($Name[$i]) = mysqli_fetch_row($result))
					$i ++;
				
					for($j = 0; $j < $i; $j++)
					{
						echo "<option value='".$Name[$j]."'>".$Name[$j];
					}
					mysqli_free_result($result);
					echo "</select>　<input type='submit' Name='send' size=6 value='提交'></th>";
					if(isset($_POST['send']) || $web_status == 1)
					{
						$web_status = 1;
					$_SESSION["Web_status"] = $web_status;
					$addroute = $_SESSION["addroute"];
					//-----------------------choose Car
					echo "<th colspan='2'>選擇 AGV 設備：<select Name='addCar'>";
				
					$sql = "SELECT Name FROM Wi_SUN_IP where Category = 1 ORDER BY Name";
					$result = mysqli_query($con, $sql);

					$i=0;
					while(list($Name[$i]) = mysqli_fetch_row($result))
					$i ++;
				
					for($j = 0; $j < $i; $j++)
					{
						echo "<option value='".$Name[$j]."'>".$Name[$j]."</option>";
					}
					mysqli_free_result($result);
					//-----------------------choose Tsite
					echo "</select>　工作站點：<select Name='addTsite'>";
					$sql = "SELECT Tsite FROM Route where Name = '$addroute'";
					$result = mysqli_query($con, $sql);

					list($Name) = mysqli_fetch_row($result);
					$Route_split =  explode(",",$Name);
					for($j = 0; $j < $i; $j++)
					{
						if($Route_split[$j] == NULL)
						{
							
						}
						else
						{
							echo "<option value='".$Route_split[$j]."'>".$Route_split[$j]."</option>";
						}
					}
					mysqli_free_result($result);
					
					echo "</select>　倉儲位置：";
					for($j = 0; $j < 4; $j++)
					{
						$k = $j +1;
						echo "<input type='radio' name='addWH' value='$j'>$k";
					}
					echo "　<input type='submit' Name='add' size=6 value='提交'>";
					//echo "　　<input type='submit' Name='finish' size=6 value='完成'></th>";
					}
					
				?>
				</tr>
			</table>
			<?php
				if($web_status == NULL)
				{
					echo "<table border='2' width='80%' align='center'><br><tr bgcolor='#97CBFF'>";
					echo "<th COLSPAN=6 ALIGN=CENTER><font size=5>路線資料顯示</font></th>";
					echo "</tr><tr  bgcolor='#FCFCFC'>";
					echo "<th width='160'>路線名稱</th><th width='160'>起始站點</th><th width='160'>倉儲站點</th><th width='160'>工作站點</th>";
					$sql = "SELECT * FROM Route ORDER BY Name";
					$result = mysqli_query($con, $sql);

					$i=0;
					while(list($Name_t[$i],$Isite_t[$i],$Csite_t[$i],$Tsite_t[$i]) = mysqli_fetch_row($result))
					$i ++;

					for($j = 0; $j < $i; $j++)
					{
						echo "<tr   bgcolor=\"#FCFCFC\"><th>" .$Name_t[$j]. "</th><th>" .$Isite_t[$j].  "</th><th>" .$Csite_t[$j].  "</th><th>" .$Tsite_t[$j]."</th></tr>";
					}
					mysqli_free_result($result);
					echo "</table>";
				}
			?>
			<table border="2" width="80%" align="center"><br>
				<tr bgcolor="#97CBFF">
					<th COLSPAN=7 ALIGN=CENTER>
						<font size=5>工單資料顯示</font>
					</th>
				</tr>

				<tr  bgcolor="#FCFCFC">
				<th width="80">工單編號</th><th width="160">路線名稱</th><th width="160">AGV 設備名稱</th><th width="160">工作站點</th><th width="160">放置儲位</th><th width="160">狀態</th><th width='160'>作業</th>
			
			<?php	
				$sql = "SELECT * FROM schedule where status != 2 ORDER BY No";
				$result = mysqli_query($con, $sql);

				$i=0;
				while(list($Mission_Route[$i], $Mission_Car[$i], $Mission_Tsite[$i], $Mission_Wh[$i], $status[$i], $NUM[$i]) = mysqli_fetch_row($result))
					$i ++;
				
				for($j = 0; $j < $i; $j++)
				{
				 	echo "<tr   bgcolor=\"#FCFCFC\"><th>".$NUM[$j]."</th><th>" .$Mission_Route[$j]. "</th><th>"
					.$Mission_Car[$j].  "</th><th>" .$Mission_Tsite[$j]. "</th><th>" .($Mission_Wh[$j] + 1). "</th>";
					switch($status[$j])
					{
						case 0:
							$status[$j] = "未處理";
							break;
						case 1:
							$status[$j] = "處理中";
							break;
						case 2:
							$status[$j] = "已完成";
							break;
					}
					echo "<th>" .$status[$j]. "</th><th><button type='submit' name='del' value='$NUM[$j]'>刪除</button></th></tr>";
				}
				mysqli_free_result($result);
				
			?>
			</table>
			
			<br>
			<table border="1" width="80%" align="center">
				<tr><th height=60 bgcolor="#CCFFFF">
				<a href="../user.php"><p style="font-size:20px">上一頁</p></a>
				</th></tr>
			</table>
		</form>	
	</body>
</html>