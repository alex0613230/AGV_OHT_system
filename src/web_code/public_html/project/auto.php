<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>派車作業功能</title>
</head>
<center>
<?	
	include("database.php");
	session_start();
	
	if($_SESSION["timer"] == 1)
	{
		header("Refresh:10;");
	}
	else if($_SESSION["timer"] == 0)
	{
		header("Refresh:5;");
	}
	if(isset($_POST['send'])) // order
	{
		$choose_No = $_POST['choNo'];
		$query = "select Who from car_order where No = '$choose_No'"; 
		$result = mysqli_query($con,$query) or die("Error in query: $query.". mysqli_error());
		list($check_car) = mysqli_fetch_row($result);
		//ECHO $check_car;
		
		$query = "select count(Who) from car_order where Who = '$check_car' && Status = 1"; 
		$result = mysqli_query($con,$query) or die("Error in query: $query.". mysqli_error());
		list($check) = mysqli_fetch_row($result);

		if($check == 0)
		{
			$query = "update car_order set Status = 1 where No = '$choose_No'"; 
			$result = mysqli_query($con,$query) or die("Error in query: $query.". mysqli_error());	
			$query = "update schedule set Status = 1 where No = '$choose_No'"; 
			$result = mysqli_query($con,$query) or die("Error in query: $query.". mysqli_error());	
		}
		else
		{
			echo "<script>alert('AGV 設備工作中')</script>";
		}
		$_SESSION["Car_go"][$choose_No] = 0;
		$_SESSION["Car_check"][$choose_No] = 0;
		$_SESSION["Clock"][$choose_No] = 0;	//Crane work
		$_SESSION["start_button"][$choose_No] = 0;
		$_SESSION["upload"][$choose_No] = 0;	//uploading 
		
	}
	if(isset($_POST['Button_auto']))
	{
		
		$control = $_POST['Button_auto'];
		
		$control = explode(" ",$control);
		
		//echo $control[0],$control[1]; 0 = values 1 = order_num
		
		$order_num = $control[1];
		if($control[0] == 5) // del order
		{
			$query = "update car_order set Status = 0 where No = '$control[1]'"; 
			$result = mysqli_query($con,$query) or die("Error in query: $query.". mysqli_error());
			$query = "update schedule set Status = 0 where No = '$control[1]'"; 
			$result = mysqli_query($con,$query) or die("Error in query: $query.". mysqli_error());
		}
		else
		{
			switch($control[0])
			{
				case 2:	//-ing
					
					$sql = "SELECT Car_ip, Crane_ip, Csite, Isite, Tsite FROM car_order where No = '$order_num'";
					$result = mysqli_query($con, $sql);
					
					list($Car_ip_send, $Crane_ip_send, $Csite_RFID, $Isite_RFID, $Tsite_RFID) = mysqli_fetch_row($result);
					
					//Send Car
					$tr_rand = rand(0, 65535);
					$tr_rand = sprintf("%04X", $tr_rand);
					$message = "0000000B0110000A000204".$Isite_RFID."00";
					$sql = "insert into Command value(0, '$tr_rand', '$Car_ip_send', '$message', 0, NOW())";
					$result = mysqli_query($con, $sql);
					
					$tr_rand = rand(0, 65535);
					$tr_rand = sprintf("%04X", $tr_rand);
					$message = "0000000B0110000E000204".$Csite_RFID."00";
					$sql = "insert into Command value(0, '$tr_rand', '$Car_ip_send', '$message', 0, NOW())";
					$result = mysqli_query($con, $sql);
					
					$tr_rand = rand(0, 65535);
					$tr_rand = sprintf("%04X", $tr_rand);
					$message = "0000000B0110000C000204".$Tsite_RFID."00";
					$sql = "insert into Command value(0, '$tr_rand', '$Car_ip_send', '$message', 0, NOW())";
					$result = mysqli_query($con, $sql);
					
					//Send Crane
					$sql = "select Name from site where RFID = '$Csite_RFID'"; 
					$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
					list($Csite_name) = mysqli_fetch_row($result);
					
					$sql = "select WS,CWa,CXa,CYa,CZa,WZa from position where Csite = '$Csite_name'"; 
					$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
					list($WS,$cw,$cx,$cy,$cz,$wz) = mysqli_fetch_row($result);
					
					$cw = sprintf("%04s", dechex($cw));
					$cx = sprintf("%04s", dechex($cx));
					$cy = sprintf("%04s",dechex($cy));
					//-------------依據高度修改
					if($cz >= $wz)
					{
						$czo = $wz;
					}
					else
					{
						$czo = $cz;
					}
					
					$czo -= 35;
					
					if($czo < 0)
						$czo = 0;
					
					$cz = sprintf("%04s",dechex($cz));
					$czo = sprintf("%04s",dechex($czo));
			
					//echo $WS." ".$cw." ".$cx." ".$cy." ".$cz." ".$ww." ".$wx." ".$wy." ".$wz."<br>";
					mysqli_free_result($result);
					
					$tr_rand = sprintf("%04X", rand(0, 65535));
					$message_crane10 = "0000000B011000070002040004000000";
					$sql = "insert into Command value(0, '$tr_rand', '$Crane_ip_send', '$message_crane10', 0, NOW())";
					$result = mysqli_query($con, $sql);
					
					$tr_rand = rand(0, 65535);
					$tr_rand = sprintf("%04X", $tr_rand);
					$message = "0000000F0110000A000408".$cw.$cx.$cy.$cz."00";
					$message = strtoupper($message);
					$sql = "insert into Command value(0, '$tr_rand', '$Crane_ip_send', '$message', 0, NOW())";
					$result = mysqli_query($con, $sql);
					
					$tr_rand = rand(0, 65535);
					$tr_rand = sprintf("%04X", $tr_rand);
					$message = "0000000901100012000102".$czo."00";
					$message = strtoupper($message);
					$sql = "insert into Command value(0, '$tr_rand', '$Crane_ip_send', '$message', 0, NOW())";
					$result = mysqli_query($con, $sql);
					
					$message = "00000006010300010006" ;
					$sql = "select count(*) from Command where OT = '$message' and IP='$Crane_ip_send' and flag=0";
					$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
					$n = mysqli_fetch_row($result);
					$i = 0;
					if($n[0] == 0)
					{
						$tr_rand = sprintf("%04X", rand(0, 65535));
						$sql = "insert into Command value(0, '$tr_rand', '$Crane_ip_send', '$message', 0, NOW())";
						$result = mysqli_query($con, $sql);
					}
					mysqli_free_result($result);
					
					
					
			
					$_SESSION["order_num"] = 0;
					$_SESSION["upload"][$control[1]] = 1;
					$_SESSION["timer"] = 1;
					$_SESSION["start_button"][$control[1]] = 1;
					break;
				case 3:	//pasuse
					$sql = "SELECT Car_ip FROM car_order where No = '$order_num'";
					$result = mysqli_query($con, $sql);
					list($Car_ip_send) = mysqli_fetch_row($result);
					
					$tr_rand = sprintf("%04X", rand(0, 65535));
					$message = "0000000901100000000102000300";
					$sql = "insert into Command value(0, '$tr_rand', '$Car_ip_send', '$message', 0, NOW())";
					$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
					
					$message = "00000006010300010003" ;
					$sql = "select count(*) from Command where OT = '$message' and IP='$Car_ip_send' and flag=0";
					$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
					$n = mysqli_fetch_row($result);
					$i = 0;
					if($n[0] == 0)
					{
						$tr_rand = sprintf("%04X", rand(0, 65535));
						$sql = "insert into Command value(0, '$tr_rand', '$Car_ip_send', '$message', 0, NOW())";
						$result = mysqli_query($con, $sql);
					}
					mysqli_free_result($result);
					
					
					$_SESSION["order_num"] = 0;
					break;
				case 4: //Continue
					$sql = "SELECT Car_ip_send FROM car_order where No = '$order_num'";
					$result = mysqli_query($con, $sql);
					list($Car_ip_send) = mysqli_fetch_row($result);
					
					for($i=0; $i<count($re_ip); $i++)
					{
						$tr_rand = sprintf("%04X", rand(0, 65535));
						$message = "0000000901100000000102000400";
						$sql = "insert into Command value(0, '$tr_rand', '$Car_ip_send', '$message', 0, NOW())";
						$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
					
						$message = "00000006010300010003" ;
						$sql = "select count(*) from Command where OT = '$message' and IP='$Car_ip_send' and flag=0";
						$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
						$n = mysqli_fetch_row($result);
						if($n[0] == 0)
						{
							$tr_rand = sprintf("%04X", rand(0, 65535));
							$sql = "insert into Command value(0, '$tr_rand', '$Car_ip_send', '$message', 0, NOW())";
							$result = mysqli_query($con, $sql);
						}
						mysqli_free_result($result);
					}
					$_SESSION["auto_op"] = 2;
					$_SESSION["order_num"] = 0;
					break;
				case 5:	//reset
					$sql = "SELECT Car_ip,Crane_ip FROM car_order where No = '$order_num'";
					$result = mysqli_query($con, $sql);
					list($Car_ip_send, $Crane_ip_send) = mysqli_fetch_row($result);
					
					$tr_rand = sprintf("%04X", rand(0, 65535));
					$message = "0000000901100000000102000A00";
					$sql = "insert into Command value(0, '$tr_rand', '$Car_ip_send', '$message', 0, NOW())";
					$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
				
					$message = "00000006010300010003" ;
					$sql = "select count(*) from Command where OT = '$message' and IP='$Car_ip_send' and flag=0";
					$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
					$n = mysqli_fetch_row($result);
					if($n[0] == 0)
					{
						$tr_rand = sprintf("%04X", rand(0, 65535));
						$sql = "insert into Command value(0, '$tr_rand', '$Car_ip_send', '$message', 0, NOW())";
						$result = mysqli_query($con, $sql);
					}
					mysqli_free_result($result);
					
					
					$tr_rand = sprintf("%04X", rand(0, 65535));
					$message = "0000000901100000000102000A00";
					$sql = "insert into Command value(0, '$tr_rand', '$Crane_ip_send', '$message', 0, NOW())";
					$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());			
					
					$message = "00000006010300010006" ;
					$sql = "select count(*) from Command where OT = '$message' and IP='$Crane_ip_send' and flag=0";
					$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
					$n = mysqli_fetch_row($result);
					if($n[0] == 0)
					{
						$tr_rand = sprintf("%04X", rand(0, 65535));
						$sql = "insert into Command value(0, '$tr_rand', '$Crane_ip_send', '$message', 0, NOW())";
						$result = mysqli_query($con, $sql);
					}
					mysqli_free_result($result);			
								
					unset($_SESSION["auto_op"]); 
					break;
					
				case 6:
					$sql = "SELECT Car_ip FROM car_order where No = '$order_num'";
					$result = mysqli_query($con, $sql);
					list($Car_ip_send) = mysqli_fetch_row($result);
						
					$tr_rand = sprintf("%04X", rand(0, 65535));
					$message = "0000000901100000000102000500";
					$sql = "insert into Command value(0, '$tr_rand', '$Car_ip_send', '$message', 0, NOW())";
					$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
					
					$message = "00000006010300010003" ;
					$sql = "select count(*) from Command where OT = '$message' and IP='$Car_ip_send' and flag=0";
					$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
					$n = mysqli_fetch_row($result);
					if($n[0] == 0)
					{
						$tr_rand = sprintf("%04X", rand(0, 65535));
						$sql = "insert into Command value(0, '$tr_rand', '$Car_ip_send', '$message', 0, NOW())";
						$result = mysqli_query($con, $sql);
					}
					mysqli_free_result($result);
					
					$_SESSION["upload"][$order_num] = 0;
					$_SESSION["start_button"][$order_num] = 0;
					$_SESSION["button"] = 1;
					break;
			}
		}
	}
?>
<body>
				<form action="" method=post>
					<table border="1" width="80%" align="center">
						<tr height = "50">
							<th colspan="2"  bgcolor="#97CBFF">
								<p ><font size=5>派車作業功能</font></p>
							</th>
						</tr>
					
					
					<tr  bgcolor="#FCFCFC"><th COLSPAN=7 ALIGN=CENTER height="40">
						<font size=3>選擇工單編號 ：</font>
						<select  Name='choNo'>
						<?
						$sql = "SELECT No FROM car_order where status != 2 ORDER BY No";
						$result = mysqli_query($con, $sql);

						$i=0;
						while(list($No[$i]) = mysqli_fetch_row($result))
						$i ++;
					
						for($j = 0; $j < $i; $j++)
						{
							echo "<option value='".$No[$j]."'>".$No[$j]."</option>";
						}
						mysqli_free_result($result);
						?>
						</select>　
						<input type='submit' Name='send' size=6 value='提交'>　　
						<input type='submit' Name='order_on' size=6 value='顯示工單資料顯示表'>　　
						<input type='submit' Name='order_off' size=6 value='隱藏工單資料顯示表'>
					</th></tr>
				</tr>
				</table>
				
				
			<?php	
			if(isset($_POST['order_off']))
			{
				unset($_SESSION["order_see"]);
			}
			else if(isset($_POST['order_on']) || ($_SESSION["order_see"] == 1))
			{
				$_SESSION["order_see"] = 1;
				echo "<table border=\"2\" width=\"80%\" align=\"center\"><br>";
				echo "<tr bgcolor='#97CBFF'>";
				echo "<th COLSPAN=7 ALIGN=CENTER><font size=5>工單資料顯示</font></th></tr><tr  bgcolor='#FCFCFC'> ";
				echo "<th width='80'>工單編號</th><th width='160'>路線名稱</th><th width='160'>執行 AGV 設備</th><th width='160'>工作站點</th><th width='160'>放置儲位</th><th width='160'>狀態</th>";

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
					}
					echo "<th>" .$status[$j]. "</th></tr>";
				}
				mysqli_free_result($result);
				echo "</table>";
			}
			?>
			<br><table border="2" width="80%" align="center">
					<tr bgcolor="#97CBFF"><th COLSPAN=7 ALIGN=CENTER>
						<font size=5>工單狀態</font>
					</th></tr>
					<?
					
						$sql = "SELECT Car_ip, Crane_ip, No, Who, Csite FROM car_order where status = 1";
						$result = mysqli_query($con, $sql);
						$i = 0;
						
						while( list($Car_ip[$i], $Crane_ip[$i], $work_table[$i], $do_car[$i], $do_Crane[$i]) = mysqli_fetch_row($result) )
							$i++;
						for($j = 0; $j < $i; $j++)
						{
							if($_SESSION["upload"][$work_table[$j]] == 1)
							{
								
								$query = "select count(flag) from Command where IP = '$Car_ip[$j]' && flag = 0"; 
								$result = mysqli_query($con,$query) or die("Error in query: $query.". mysqli_error());
								list($FNUM_1) = mysqli_fetch_row($result);
								$query = "select count(flag) from Command where IP = '$Crane_ip[$j]' && flag = 0"; 
								$result = mysqli_query($con,$query) or die("Error in query: $query.". mysqli_error());
								list($FNUM_2) = mysqli_fetch_row($result);
								$FNUM = $FNUM_1 + $FNUM_2;
								if($FNUM == 0)
								{
									$_SESSION["upload"][$work_table[$j]] = 0;
									//-------------Car_GO
									$tr_rand = rand(0, 65535);
									$tr_rand = sprintf("%04X", $tr_rand);
									$message = "0000000901100000000102000100";
									$sql = "insert into Command value(0, '$tr_rand', '$Car_ip[$j]', '$message', 0, NOW())";
									$result = mysqli_query($con, $sql);
									$_SESSION["timer"] = 0;
									$_SESSION["start_button"][$work_table[$j]] = 1;
								}	
							}
							$get_crane = 0;
							for($check_crane = 0; $check_crane<$i; $check_crane++)
							{
								if($_SESSION["upload"][$work_table[$check_crane]] == 1)
									$get_crane++;
							}
							
							$message = "00000006010300010003" ;
							$sql = "select count(*) from Command where OT = '$message' and IP='$Car_ip[$j]' and flag=0";
							$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
							$n = mysqli_fetch_row($result);
							if($n[0] == 0)
							{
								$tr_rand = sprintf("%04X", rand(0, 65535));
								$sql = "insert into Command value(0, '$tr_rand', '$Car_ip[$j]', '$message', 0, NOW())";
								$result = mysqli_query($con, $sql);
							}
							mysqli_free_result($result);
							
							if($get_crane == 0)
							{
								$message = "00000006010300010006" ;
								$sql = "select count(*) from Command where OT = '$message' and IP='$Crane_ip[$j]' and flag=0";
								$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
								$n = mysqli_fetch_row($result);
								if($n[0] == 0)
								{
									$tr_rand = sprintf("%04X", rand(0, 65535));
									$sql = "insert into Command value(0, '$tr_rand', '$Crane_ip[$j]', '$message', 0, NOW())";
									$result = mysqli_query($con, $sql);
								}
								mysqli_free_result($result);
							}
							
							
							
							
							//---------------Car status
							$sql = "SELECT status, carwhere FROM Car_status where ip = '$Car_ip[$j]'";
							$result = mysqli_query($con, $sql);
							list($Car_status, $Car_where) = mysqli_fetch_row($result);
							
							//---------------Crane status
							$sql = "SELECT status FROM Crane_status where ip = '$Crane_ip[$j]'";
							$result = mysqli_query($con, $sql);
							list($Crane_status) = mysqli_fetch_row($result);
							
							echo "<tr>";
							echo "<th width = '80' rowspan = '2'>編號 : $work_table[$j]</th>";
							echo "<th rowspan = '1' height = '50'>$do_car[$j] AGV 設備狀態：";
							if(($Car_status) == "NA")
							{
								$Car_status = "未連線";
								$Car_where = "未連線";
								$_SESSION["button"] = 0;
							}
							else
							{
								switch($Car_status)
								{
									case 0:	$Car_status = "閒置";	
										if($_SESSION["Car_go"][$work_table[$j]] == 1)
										{
											$query = "update car_order set status = 2 where No = '$work_table[$j]'"; 
											$result = mysqli_query($con,$query) or die("Error in query: $query.". mysqli_error());
											$query = "update schedule set status = 2 where No = '$work_table[$j]'"; 
											$result = mysqli_query($con,$query) or die("Error in query: $query.". mysqli_error());
											$_SESSION["Car_go"][$work_table[$j]] = 0;
											unset($_SESSION["Car_go"][$work_table[$j]]);
											unset($_SESSION["upload"][$work_table[$j]]);
											unset($_SESSION["Car_check"][$work_table[$j]]);
											unset($_SESSION["start_button"][$work_table[$j]]);
											unset($_SESSION["Clock"][$work_table[$j]]);
										}
										else if($_SESSION["upload"][$work_table[$j]] == 0 && $_SESSION["start_button"][$work_table[$j]] == 0)
										{
											$_SESSION["button"] = 1; 
										}
										else if($_SESSION["start_button"][$work_table[$j]] == 1)
										{
											$_SESSION["button"] = 2;
										}
										break;
									case 1:	$Car_status = "前往工作站點";	$_SESSION["button"] = 2; break;
									case 2:	$Car_status = "抵達工作站點";	$_SESSION["button"] = 2; break;
									case 3:	$Car_status = "前往 倉儲站點";	$_SESSION["button"] = 2; break;
									case 4:	$Car_status = "等待貨物離開";	
										$sql = "SELECT status from Crane_status where ip = '$Crane_ip[$j]'";
										$result = mysqli_query($con, $sql);
										list($Crane_status) = mysqli_fetch_row($result);
										mysqli_free_result($result);
										
										if($Crane_status == 0 && $_SESSION["Clock"][$work_table[$j]] == 0)
										{
											//----------Send Warehouse_4_axis
											$sql = "select Name from Wi_SUN_IP where IP='$Crane_ip[$j]'"; 
											$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
											list($Crane_name) = mysqli_fetch_row($result);
											
											$sql = "select WWa,WXa,WYa,WZa from position where WS='$Crane_name'"; 
											$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
											list($ww,$wx,$wy,$wz) = mysqli_fetch_row($result);
											
											$sql = "select house from car_order where No = '$work_table[$j]'"; 
											$result = mysqli_query($con,$sql) or die("Error in query: $sql.". mysqli_error());
											list($Wh_num) = mysqli_fetch_row($result);
											
											switch($Wh_num)
											{
												case 0:
													break;
												case 1:
													$ww = $ww + 40;
													$wy = $wy + 60;
													break;
												case 2:
													$wx = $wx + 80;
													break;
												case 3:
													$ww = $ww + 40;
													$wy = $wy + 60;
													$wx = $wx + 80;
													break;
											}
											$ww = sprintf("%04s",dechex($ww));
											$wx = sprintf("%04s",dechex($wx));
											$wy = sprintf("%04s",dechex($wy));
											$wz = sprintf("%04s",dechex($wz));
											
											$tr_rand = sprintf("%04X", rand(0, 65535));
											$message = "0000000F0110000E000408".$ww.$wx.$wy.$wz."00";
											$message = strtoupper($message);
											$sql = "insert into Command value(0, '$tr_rand', '$Crane_ip[$j]', '$message', 0, NOW())";
											$result = mysqli_query($con, $sql);
											
											$tr_rand = sprintf("%04X", rand(0, 65535));
											$message = "0000000901100007000102000100";
											$sql = "insert into Command value(0, '$tr_rand', '$Crane_ip[$j]', '$message', 0, NOW())";
											$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
											
											
											$message = "00000006010300010006" ;
											$sql = "select count(*) from Command where OT = '$message' and IP='$Crane_ip[$j]' and flag=0";
											$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
											$n = mysqli_fetch_row($result);
											if($n[0] == 0)
											{
												$tr_rand = sprintf("%04X", rand(0, 65535));
												$sql = "insert into Command value(0, '$tr_rand', '$Crane_ip[$j]', '$message', 0, NOW())";
												$result = mysqli_query($con, $sql);
											}
											mysqli_free_result($result);
											
											$query = "update Crane_status set status = 6 where ip = '$Crane_ip[$j]'"; 
											$result = mysqli_query($con,$query) or die("Error in query: $query.". mysqli_error());
											$_SESSION["Clock"][$work_table[$j]] = 1;
											$_SESSION["button"] = 2;
											$_SESSION["Car_check"][$work_table[$j]] = 1;
										}
										break;
									case 5:	$Car_status = "前往起始站點"; $_SESSION["Clock"][$work_table[$j]] = 0;	$_SESSION["button"] = 2; break;
									case 6:	$Car_status = "抵達起始站點";	$_SESSION["button"] = 2; break;
									case 7:	$Car_status = "暫停";	$_SESSION["button"] = 3; break;
									case 8:	$Car_status = "路線上有障礙物";	$_SESSION["button"] = 1; break;	
									case 9:	$Car_status = "等待指令回傳";	$_SESSION["button"] = 0; break;
								}
								switch($Car_where)
								{
									case 0:	$Car_where = "起始站點";	break;
									case 1:	$Car_where = "正在前往工作站點";	break;
									case 2:	$Car_where = "工作站點";	break;
									case 3:	$Car_where = "正在前往 倉儲站點";	break;
									case 4:	$Car_where = "倉儲站點";	break;
									case 5:	$Car_where = "正在前往起始站點";	break;
									case 6: $Car_where = "等待指令回傳";	break;
								}
							}
							if($Crane_status == "NA")
							{
								$Crane_status = "未連線";
							}
							else
							{
								switch($Crane_status)
								{
									case 0:	$Crane_status = "閒置";	break;
									case 1:	$Crane_status = "工作中";	break;
									case 2:	$Crane_status = "警告";	break;
									case 3:	$Crane_status = "電磁鐵異常 貨物需人工檢查";	break;
									case 5:	//完成
									
										//$_SESSION["Car_check"][$work_table[$j]] = $_SESSION["Car_check"][$work_table[$j]] + 1 ;
										
										if($_SESSION["Car_check"][$work_table[$j]] == 1)
										{
											$_SESSION["Car_go"][$work_table[$j]] = 1;
											
											$tr_rand = sprintf("%04X", rand(0, 65535));
											$message = "0000000901100007000102001000";
											$sql = "insert into Command value(0, '$tr_rand', '$Crane_ip[$j]', '$message', 0, NOW())";
											$result = mysqli_query($con, $sql);
											
											$message = "00000006010300010006" ;
											$sql = "select count(*) from Command where OT = '$message' and IP='$Crane_ip[$j]' and flag=0";
											$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
											$n = mysqli_fetch_row($result);
											if($n[0] == 0)
											{
												$tr_rand = sprintf("%04X", rand(0, 65535));
												$sql = "insert into Command value(0, '$tr_rand', '$Crane_ip[$j]', '$message', 0, NOW())";
												$result = mysqli_query($con, $sql);
											}
											mysqli_free_result($result);
											
											$query = "update Crane_status set status = 0 where ip = '$Crane_ip[$j]'"; 
											$result = mysqli_query($con,$query) or die("Error in query: $query.". mysqli_error());
										
											$tr_rand = sprintf("%04X", rand(0, 65535));
											$message = "0000000901100000000102000200";
											$sql = "insert into Command value(0, '$tr_rand', '$Car_ip[$j]', '$message', 0, NOW())";
											$result = mysqli_query($con, $sql);
											
											$message = "00000006010300010003" ;
											$sql = "select count(*) from Command where OT = '$message' and IP='$Car_ip[$j]' and flag=0";
											$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
											$n = mysqli_fetch_row($result);
											if($n[0] == 0)
											{
												$tr_rand = sprintf("%04X", rand(0, 65535));
												$sql = "insert into Command value(0, '$tr_rand', '$Car_ip[$j]', '$message', 0, NOW())";
												$result = mysqli_query($con, $sql);
											}
											mysqli_free_result($result);$_SESSION["Car_check"][$work_table[$j]] = 0;
										}
										$Crane_status = "閒置";
									break;
									case 6:
										$Crane_status = "等待指令回傳";
										break;
								}
							}
							echo "$Car_status </th>";
							echo "<th rowspan = '1'>AGV 設備位置：$Car_where </th>";
							
							echo "<th rowspan = '1'>OHT 設備狀態：$Crane_status</th>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th height = '50' rowspan = '1' colspan='3'>";
							
							if($_SESSION["upload"][$work_table[$j]] == 1)
							{
								echo "UPLOADING";
							}
							else if($_SESSION["upload"][$work_table[$j]] == 0)
							{
								switch($_SESSION["button"])
								{
									case 0: //未連線
										echo "<button type='submit' name='Button_auto' value='2 $work_table[$j]' style='width:120px;height:40px;' disabled>開始</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='3 $work_table[$j]' style='width:120px;height:40px;' disabled>暫停</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='4 $work_table[$j]' style='width:120px;height:40px;' disabled>繼續</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='5 $work_table[$j]' style='width:120px;height:40px;' >刪除工單</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='6 $work_table[$j]' style='width:120px;height:40px;background-color:#FF0000;' disabled>停止</button>";
										break;
									case 1:	//已連線 no work
										echo "<button type='submit' name='Button_auto' value='2 $work_table[$j]' style='width:120px;height:40px;' >開始</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='3 $work_table[$j]' style='width:120px;height:40px;' disabled>暫停</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='4 $work_table[$j]' style='width:120px;height:40px;' disabled>繼續</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='5 $work_table[$j]' style='width:120px;height:40px;' >刪除工單</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='6 $work_table[$j]' style='width:120px;height:40px;background-color:#FF0000;' disabled>停止</button>";
										break;
									case 2:		//status -ing (able pause stop)
										echo "<button type='submit' name='Button_auto' value='2 $work_table[$j]' style='width:120px;height:40px;' disabled>開始</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='3 $work_table[$j]' style='width:120px;height:40px;' >暫停</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='4 $work_table[$j]' style='width:120px;height:40px;' disabled>繼續</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='5 $work_table[$j]' style='width:120px;height:40px;' disabled>刪除工單</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='6 $work_table[$j]' style='width:120px;height:40px;background-color:#FF0000;' >停止</button>";
										break;
									case 3:		//status pause 
										echo "<button type='submit' name='Button_auto' value='2 $work_table[$j]' style='width:120px;height:40px;' disabled>開始</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='3 $work_table[$j]' style='width:120px;height:40px;' disabled>暫停</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='4 $work_table[$j]' style='width:120px;height:40px;' >繼續</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='5 $work_table[$j]' style='width:120px;height:40px;' >刪除工單</button>&nbsp&nbsp";
										echo "<button type='submit' name='Button_auto' value='6 $work_table[$j]' style='width:120px;height:40px;background-color:#FF0000;' >停止</button>";
										break;
								}	
							}
							echo "</th></tr>";
						}
						
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
	</center>
</html>