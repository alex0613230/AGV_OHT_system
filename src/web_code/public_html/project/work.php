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
							<th colspan="3"  bgcolor="#97CBFF">
								<p style="font-size:20px">作業管理</p>
							</th>
						</tr>
						<tr height = "50">
							<th colspan="2" >
								<p style="font-size:20px">新增作業</p>
							</th>
							<th colspan="2" >
								<a href="./work_status.php">處理作業</a>
							</th>
						</tr>
					</table>
					<br>
					<br>
					<table border="1" width="960"  align="center">	
						<tr  bgcolor="#FCFCFC" height = "50">
								<th width="250">
									
									<?php session_start();
										
										if(isset($_POST['F_send']))
										{
											$use_car = $_POST['Car'];
											if(count($use_car)==0)
											{
												echo "可用車子：&nbsp";
												include("database.php");
												$sql = "SELECT Name FROM Wi_SUN_IP where Category= '1' and Status ='2' ORDER BY Name";
												$result = mysqli_query($con, $sql);

												$i=0;
												while(list($Name[$i]) = mysqli_fetch_row($result))
												$i ++;
											
												if($i == 0)
												{
													echo "Car no Ready";
													header("refresh: 10;");
												}
												else
												{
													for($j = 0; $j < $i; $j++)
													{
														echo "<input type='checkbox' name='Car[]' value='".$Name[$j]."'>".$Name[$j];
													}
													echo "</select>&nbsp&nbsp";
												}
												
												mysqli_free_result($result);
												mysqli_close($con);
											}
											else
											{
												
												for($j=0;$j<count($use_car);$j++)
												{
													if($j==0)
														$str_tag = $use_car[$j];
													else
														$str_tag =$str_tag.",".$use_car[$j];
												}
												echo "車子：&nbsp".$str_tag;
												$_SESSION['car']=$str_tag;
												$_SESSION['car_num']=count($use_car);
											}
										}
										else if(isset($_POST['S_send']))
										{
											$use_car = $_SESSION['car'];
											echo "車子：&nbsp".$_SESSION['car'];
											
										}
										else
										{
											echo "可用車子：&nbsp";
											include("database.php");
											$sql = "SELECT Name FROM Wi_SUN_IP where Category= '1' and Status ='2' ORDER BY Name";
											$result = mysqli_query($con, $sql);

											$i=0;
											while(list($Name[$i]) = mysqli_fetch_row($result))
											$i ++;
										
											if($i == 0)
											{
												echo "No Car Ready";
											}
											else
											{
												for($j = 0; $j < $i; $j++)
												{
													echo "<input type='checkbox' name='Car[]' value='".$Name[$j]."'>".$Name[$j];
												}
												echo "</select>&nbsp&nbsp";
											}
											
											mysqli_free_result($result);
											mysqli_close($con);
										}
									?>
								</th>
								<th>
									
									
									<?php session_start();
										if(isset($_POST['F_send']))
										{
											$route = $_POST['Route'];
											echo "路線 : ".$route;
											$_SESSION['route']=$route;
										}
										else if(isset($_POST['S_send']))
										{
											$route = $_SESSION['route'];
											echo "路線 : ".$route;
											
										}
										else
										{
											echo "選擇路線 : ";
											echo "<select name='Route'>";
											include("database.php");
											
											$sql = "SELECT Name FROM Car_Route";
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
											echo "</select>&nbsp&nbsp";
										}
									?>
									
								</th>
								<th>
								
									<? 
									session_start();
									if(isset($_POST['F_send']))
									{
										$Warehouse_group = $_POST['Warehouse_group'];
											
										echo "倉儲群組 : ".$Warehouse_group;
										$_SESSION['Warehouse_group'] = $Warehouse_group;
									}
									else if(isset($_POST['S_send']))
									{

										$Warehouse_group = $_SESSION['Warehouse_group'];
										echo "倉儲群組 : ".$Warehouse_group;
									}
									else
									{
										echo "選擇倉儲群組 : <select name='Warehouse_group'>";
										include("database.php");
										
										$sql = "SELECT Name FROM Warehouse";
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
									}
									?>
								</th>
						</tr>
						<tr>
							
							<?	session_start();
									
									if(isset($_POST['F_send']))
									{
										
										echo "<th colspan='1' height = '50'>";
										echo "</th>";
										
										echo "<th colspan='1' height = '50'>";
										
										include("database.php");
										
										echo "站點選擇 : <select name='site'>";
										$sql = "SELECT Other_Car_Site FROM Car_Route where Name = '$route'";
										$result = mysqli_query($con, $sql);

										$i=0;
										while(list($Name[$i]) = mysqli_fetch_row($result))
										$i ++;
										
										$site =  explode(",",$Name[0]);
										
										mysqli_free_result($result);
										mysqli_close($con);
										for($i=0;$i<count($site);$i++)
										{
											echo "<option value='".$site[$i]."'>".$site[$i]."</option>";
										}
										
										echo "</select>&nbsp&nbsp";
										//------------------------------------
										echo "<th colspan='1' height = '50'>";
										include("database.php");
										
										echo "倉儲選擇 : <select name='Warehouse'>";
										$sql = "SELECT house_group FROM Warehouse where Name = '$Warehouse_group'";
										$result = mysqli_query($con, $sql);

										$i=0;
										while(list($Name[$i]) = mysqli_fetch_row($result))
										$i ++;
										
										$site =  explode(",",$Name[0]);
										
										mysqli_free_result($result);
										mysqli_close($con);
										for($i=0;$i<count($site);$i++)
										{
											echo "<option value='".$site[$i]."'>".$site[$i]."</option>";
										}
										
										echo "</select>&nbsp&nbsp";
										echo "</th>";
										
										
									}
									if(isset($_POST['S_send']))
									{
										echo "<th colspan='1' height = '50'>";
										echo "</th>";
										
										echo "<th colspan='1' height = '50'>";
										
										include("database.php");
										
										echo "站點選擇 : <select name='site'>";
										$sql = "SELECT Other_Car_Site FROM Car_Route where Name = '$route'";
										$result = mysqli_query($con, $sql);

										$i=0;
										while(list($Name[$i]) = mysqli_fetch_row($result))
										$i ++;
										
										$site =  explode(",",$Name[0]);
										
										mysqli_free_result($result);
										mysqli_close($con);
										for($i=0;$i<count($site);$i++)
										{
											echo "<option value='".$site[$i]."'>".$site[$i]."</option>";
										}
										
										echo "</select>&nbsp&nbsp";
										//------------------------------------
										echo "<th colspan='1' height = '50'>";
										include("database.php");
										
										echo "倉儲選擇 : <select name='Warehouse'>";
										$sql = "SELECT house_group FROM Warehouse where Name = '$Warehouse_group'";
										$result = mysqli_query($con, $sql);

										$i=0;
										while(list($Name[$i]) = mysqli_fetch_row($result))
										$i ++;
										
										$site =  explode(",",$Name[0]);
										
										mysqli_free_result($result);
										mysqli_close($con);
										for($i=1;$i<count($site);$i++)
										{
											echo "<option value='".$site[$i]."'>".$site[$i]."</option>";
										}
										
										echo "</select>&nbsp&nbsp";
										echo "</th>";
									}
							?>
						</tr>
						<tr  bgcolor="#FCFCFC" height = "50" >
							<th colspan="3">
								<input type="button" value="返回主頁" onclick="location.href='./index.php'" style="align;"></button>
								<?
									session_start();
									if(isset($_POST['F_send']))
									{
										$_SESSION['clock'] = 0;
										echo "<input type='submit' Name='S_send' value='提交' />";
										echo "&nbsp";
										echo "<input type='submit' onclick='location.href='./work.php'' value='重選' />";
									}
									else if(isset($_POST['S_send']))
									{
										echo "<input type='submit' Name='S_send' value='提交' />";
										echo "&nbsp";
										echo "<input type='submit' onclick='location.href='./work.php'' value='重選' />";
										echo "&nbsp";
										echo "<input type='submit' Name='Finish' value='派發完畢' />";
									}
									else
									{
										echo "<input type='submit' Name='F_send' value='提交' />";
									}
								
								?>
								
								<input type="submit" Name='clear' value="清空已完成" /> 
							</th>
						
						</tr>
					</table>
					
					<? 
						session_start();
						if(isset($_POST['F_send']))
						{
							$use_car = $_POST['Car'];
							if($use_car == NULL)
							{
								echo "<center><h1 style='color:red'>請選定車子</h1></center>";
								header("refresh: 1;");
							}
						}
						if(isset($_POST['S_send']))
						{
							$car = $_SESSION['car'];
							$site = $_POST['site'];
							$Warehouse = $_POST['Warehouse'];
							$car_num = $_SESSION['car_num'];
							include("database.php");
							
							$sql = "SELECT count('ID') FROM work";
							$result = mysqli_query($con, $sql);
							$id = mysqli_fetch_row($result);
							mysqli_free_result($result);
							
							$sql = "SELECT Initial_Site_Name FROM Car_Route where Name ='$route'";
							$result = mysqli_query($con, $sql);
							$Initial_Site = mysqli_fetch_row($result);
							mysqli_free_result($result);
							
							$sql = "SELECT Initial FROM Warehouse where Name = '$Warehouse_group'";
							$result = mysqli_query($con, $sql);
							$Initial_Warehouse = mysqli_fetch_row($result);
							mysqli_free_result($result);
							
							$sql = "SELECT Wi_SUN FROM Warehouse where Name = '$Warehouse_group'";
							$result = mysqli_query($con, $sql);

							$i=0;
							while(list($Name[$i]) = mysqli_fetch_row($result))
							$i ++;
							$Warehouse_group_Wi_SUN =  $Name[0];
							mysqli_free_result($result);
							
							if($car_num > 1)
							{
								$clock = $_SESSION['clock'];
								$loop_car =  explode(",",$car);
								$sql = "insert into work (Car,Warehouse,Status,Initial_site,Task_site,Initial_Warehouse,Task_Warehouse) value('$loop_car[$clock]','$Warehouse_group_Wi_SUN',0,'$Initial_Site[0]','$site','$Initial_Warehouse[0]','$Warehouse')";
								$result = mysqli_query($con, $sql);
								mysqli_free_result($result);
								$clock++;
								if($clock > ($car_num-1))
								{
									$clock = 0;
								}
								$_SESSION['clock'] = $clock;
							}
							else if($car_num == 1)
							{
								//Initial 第一個當車子的點
								$sql = "insert into work (Car,Warehouse,Status,Initial_site,Task_site,Initial_Warehouse,Task_Warehouse) value('$car','$Warehouse_group_Wi_SUN',0,'$Initial_Site[0]','$site','$Initial_Warehouse[0]','$Warehouse')";
								$result = mysqli_query($con, $sql);
								mysqli_free_result($result);
								mysqli_close($con);
							}
						}
						if(isset($_POST['Finish']))	
						{
							unset($_SESSION['car']);
							unset($_SESSION['Warehouse_group']);
							unset($_SESSION['route']);
							//session_destroy();
						}							
					?>			
					<table border="2" width="960" align="center"><br>
						<tr bgcolor="#97CBFF">
							<th>
								<font size=5>取消作業</font>
							</th>
						</tr>
						<tr  bgcolor="#FCFCFC">
							<th>
								識別碼 :<input type="text" Name="del" size="8"/ >&nbsp&nbsp
								<input type="submit" Name="onedel" value="清除" />
							</th>
						</tr>
					</table>
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