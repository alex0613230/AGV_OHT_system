<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="UTF-8">
	    <meta Name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	    <title>AGV 設備操作功能</title>
	    <style>
	    th
	    {font-size: 17px; }
	    p
	    {font-size: 23px;}
	    </style>
	</head>
<?
	header("Refresh:5;");
	include("sys_database.php");
	session_start();
	if($_POST["schedule"] == 1)
	{
		$car = $_POST["ch_car"];
		$crt  = $_POST["ch_rt"];
		
		//--------------------Check Car
		$sql = "select count(Name) from car_operate where Name ='$car'"; 
		$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
		list($check_IP) = mysqli_fetch_row($result);
		mysqli_free_result($result);

		if($check_IP == 0)
		{
			//--------------------Get IP
			$sql = "select IP from Wi_SUN_IP where Name='$car'";
			$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
			list($Carip) = mysqli_fetch_row($result);
			//echo $Carip;
			//--------------------INPUT car_operate
			$sql = "insert into car_operate values ('$car', '$Carip', '$crt')";
			$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
			//--------------------Get Initial_site Crane_site Task_site
			$sql = "select Isite,Csite,Tsite from Route where Name='$crt'"; 
			$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
			list($Isite, $Csite, $Tsite) = mysqli_fetch_row($result);
			// echo "Isite : ".$Isite."Csite :".$Csite."Tsite :".$Tsite."<br>";
			mysqli_free_result($result);
			//--------------------Get Initial_site RFID and Crane_site RFID
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
			//--------------------Send to Car_WISUN Initail_site and Crane_site
			$tr_rand = rand(0, 65535);
			$tr_rand = sprintf("%04X", $tr_rand);
			$message = "0000000B0110000A000204".$Isite_RFID."00";
			$sql = "insert into Command value(0, '$tr_rand', '$Carip', '$message', 0, NOW())";
			$result = mysqli_query($con, $sql);
			
			$tr_rand = rand(0, 65535);
			$tr_rand = sprintf("%04X", $tr_rand);
			$message = "0000000B0110000E000204".$Csite_RFID."00";
			$sql = "insert into Command value(0, '$tr_rand', '$Carip', '$message', 0, NOW())";
			$result = mysqli_query($con, $sql);
			
		}
		else
		{
				echo "<script>alert('重複出現 AGV 設備')</script>";
		}
	}	
		
	if($_POST["Car_Command"])
	{
		$Car_Command = $_POST["Car_Command"];
		$Car_Command = explode(" ",$Car_Command);
		//echo $Car_Command[0], $Car_Command[1];
		$sql = "select IP from Wi_SUN_IP where Name='$Car_Command[1]'"; 
		$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
		list($Command_CIP) = mysqli_fetch_row($result);
		mysqli_free_result($result);
	
		
		switch($Car_Command[0])
		{
			case 1: //go task
				$ch_site = $_POST["ch_site"];
				$sql = "select RFID from site where Name='$ch_site'"; 
				$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
				list($Command_Task_RFID) = mysqli_fetch_row($result);
				mysqli_free_result($result);
				
				$tr_rand = sprintf("%04X", rand(0, 65535));
		
				$message = "0000000B0110000C000204".$Command_Task_RFID."00";
				$message = strtoupper($message);
				$sql = "insert into Command value(0, '$tr_rand', '$Command_CIP', '$message', 0, NOW())";
				$result = mysqli_query($con, $sql);

				$tr_rand = rand(0, 65535);
				$tr_rand = sprintf("%04X", $tr_rand);

				$message = "0000000901100000000102000700";
				$sql = "insert into Command value(0, '$tr_rand', '$Command_CIP', '$message', 0, NOW())";
				$result = mysqli_query($con, $sql);
				
				break;
			case 2: //go initial
				$tr_rand = sprintf("%04X", rand(0, 65535));
				
				$message = "0000000901100000000102000600";
				$sql = "insert into Command value(0, '$tr_rand', '$Command_CIP', '$message', 0, NOW())";
				$result = mysqli_query($con, $sql);
				
				break;
			case 3: //go crane
				$tr_rand = sprintf("%04X", rand(0, 65535));
				
				$message = "0000000901100000000102000800";
				$sql = "insert into Command value(0, '$tr_rand', '$Command_CIP', '$message', 0, NOW())";
				$result = mysqli_query($con, $sql);
				
				break;
			case 4: // del
				$sql = "delete from car_operate where IP='$Command_CIP'"; 
				$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
				break;
		}
		
		
	}
	
?>
	<body>
				<form action="" method=post>
					<table border="1" width="1080" align="center">
						<tr height = "50" >
							<th colspan="5"  bgcolor="#97CBFF">
								<p style="font-size:20px">AGV 設備操作功能</p>
							</th>
						</tr><tr height = '50'>
							<th colspan="2">選擇設備: <select name='ch_car'>
							<?
								
								$sql = "select Name from Wi_SUN_IP where Category=1 order by Name"; 
								$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
								
								$i=0;
								while(list($Car[$i]) = mysqli_fetch_row($result))
								$i ++;
								
								for($j=0; $j<$i; $j++)
								{
									echo "<option value=".$Car[$j].">".$Car[$j]."</option>";
								}
								mysqli_free_result($result);
							?>
							</select></th>
							
							<th  colspan="2">選擇路線: <select name='ch_rt'>
							<?
								$sql = "select Name from Route"; 
								$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
								
								$i=0;
								while(list($rt[$i]) = mysqli_fetch_row($result))
								$i ++;
								
								for($j=0; $j<$i; $j++)
								{
									echo "<option value=".$rt[$j].">".$rt[$j]."</option>";
								}
								mysqli_free_result($result);
							?>
							</select></th>
							
							<th  colspan="1">
								<button name='schedule' value='1' >提交</button>
							</th >
						</tr>
					</table>
					<br>
					<table border="1" width="1080" align="center" cellpadding="10">
						<tr height = "50"><th  colspan='4' bgcolor="#97CBFF">AGV 設備資訊</th></tr>
						<?
							$sql = "select * from car_operate"; 
							$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
							$i=0;
							while(list($carname[$i], $carip[$i], $route[$i]) = mysqli_fetch_row($result))
								$i++;
							mysqli_free_result($result);
							
							for($j=0; $j<$i; $j++)
							{
								$sql = "select status,cargo,carwhere from Car_status where ip='$carip[$j]'"; 
								$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
								list($carstatus, $cargo, $carwhere) = mysqli_fetch_row($result);
								mysqli_free_result($result);
								$buuton = 0;								if($carstatus == 'NA')
								{
									$carstatus = '未回傳狀態';
									$carwhere = '未回傳位置';
									$cargo = '未回傳貨物狀態';
									$button = 1;
								}
								else
								{
									switch($carstatus)
									{
										case 0:	$carstatus = "閒置";	 	break;
										case 1:	$carstatus = "前往工作站點";	 break;
										case 2:	$carstatus = "抵達工作站點";	 break;
										case 3:	$carstatus = "前往天車";	 break;
										case 4:	$carstatus = "抵達天車";	 break;
										case 5:	$carstatus = "前往起始站點";	 break;
										case 6:	$carstatus = "抵達起始站點";	break;
										case 7:	$carstatus = "暫停";		 break;
										case 8:	$carstatus = "路線上有障礙物";	 break;	
										case 9:	$carstatus = "等待指令回傳";	 break;
									}
								
									switch($carwhere)
									{
										case 0:	$carwhere = "起始站點";	break;
										case 1:	$carwhere = "正在前往工作站點";	break;
										case 2:	$carwhere = "工作站點";	break;
										case 3:	$carwhere = "正在前往天車站點";	break;
										case 4:	$carwhere = "天車站點";	break;
										case 5:	$carwhere = "正在前往起始站點";	break;
										case 6: $carwhere = "等待指令回傳";	break;
									}
									
									
								}
								echo "<tr height = '50'><th rowspan='2' width='280'>AGV 設備名稱：$carname[$j]<br><br> 路線：$route[$j]</th>";	
								
								echo "<th width='300'>AGV 設備狀態：".$carstatus."</th>";	
								
								echo "<th width='500'>AGV 設備位置：".$carwhere."</th>";
								echo "<th width='200' rowspan='2' ><button name='Car_Command' value='4 $carname[$j]' style='width:130px;height:40px;'>移除</button></th></tr>";
								
								$message = "00000006010300010003" ;
								$sql = "select count(*) from Command where OT = '$message' and IP='$carip[$j]' and flag=0";
								$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
								$n = mysqli_fetch_row($result);
								
								if($n[0] == 0)
								{
									$tr_rand = sprintf("%04X", rand(0, 65535));
									$sql = "insert into Command value(0, '$tr_rand', '$carip[$j]', '$message', 0, NOW())";
									$result = mysqli_query($con, $sql);
								}
								mysqli_free_result($result);
								
								$sql = "select Tsite from Route where Name ='$route[$j]'"; 
								$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
								list($Tsite) = mysqli_fetch_row($result);
								mysqli_free_result($result);
								$Car_split =  explode(",",$Tsite);
								
								
								
								if($button == 0)
								{
									echo "<tr><th><select name='ch_site'>";
									for($e=0; $e<count($Car_split); $e++)
									{
										echo "<option value='$Car_split[$e]'>".$Car_split[$e]."</option>";
									}
									echo "</select>　<button name='Car_Command' value='1 $carname[$j]' style='width:120px;height:30px;'>前往工作站點</button></th>";
									echo "<th ><button name='Car_Command' value='2 $carname[$j]' style='width:120px;height:30px;'>前往起始站點</button>&nbsp;&nbsp;&nbsp;";
									echo "<button name='Car_Command' value='3 $carname[$j]' style='width:150px;height:30px;'>前往倉儲站點</button></th></tr>";	
								}
								else if($button == 1)
								{
									echo "<tr><th><select name='ch_site'>";
									for($e=0; $e<count($Car_split); $e++)
									{
										echo "<option value='$Car_split[$e]'>".$Car_split[$e]."</option>";
									}
									echo "</select>　<button name='Car_Command' value='1 $carname[$j]' style='width:120px;height:30px;' disabled>前往工作站點</button></th>";
									echo "<th ><button name='Car_Command' value='2 $carname[$j]' style='width:120px;height:30px;' disabled>前往起始站點</button>&nbsp;&nbsp;&nbsp;";
									echo "<button name='Car_Command' value='3 $carname[$j]' style='width:150px;height:30px;' disabled>前往倉儲站點</button></th></tr>";
										
								}
								
							}
							
						?>		
					</table>
					<br>
					<table border="1" width="1080" align="center">
						<tr><th height=60 bgcolor="#CCFFFF">
						<a href="./sys.php"><p style="font-size:20px">返回系統管理作功能</p></a>
						</th></tr>
					</table>
				</form>
	</body>
</html>