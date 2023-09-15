<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="UTF-8">
	    <meta Name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	    <title>路線管理功能</title>
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
					<table border="1" width="1080" align="center">
						<tr height = "50">
							<th colspan="4"  bgcolor="#97CBFF">
								<p ><font size=5>路線管理功能</font></p>
							</th>
						</tr>
						<tr  bgcolor="#FCFCFC" height = "50">
							<th colspan=4>
								路線名稱：<input type="text" Name="Name" size="4" maxlength="4">&nbsp&nbsp
								起始站點：
								<select name="Isite">
								<?php
									$sql = "SELECT Name FROM site where Sort=0 ORDER BY Name";
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
								?>
								倉儲站點：
								<select name="Csite">
								<?php
									$sql = "SELECT Name FROM site  where Sort=1 ORDER BY Name";
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
								?>
						</tr>
						<tr height = "50">	
							<th colspan="1" width="270">
								工作站點
							</th>
							<th colspan="3" align="left">
									
									<?php
										$sql = "SELECT Name FROM site where Sort = 2 ORDER BY Name";
										$result = mysqli_query($con, $sql);

										$i=0;
										while(list($Name[$i]) = mysqli_fetch_row($result))
										$i ++;
									
										$k=0;
										for($j = 0; $j < $i; $j++)
										{
											$k++;
											$result = mysqli_query($con, $sql);

											echo "<input type='checkbox' name='Tsite[]' value='".$Name[$j]."'>".$Name[$j];
											if(($k % 19)== 0)
											{
												echo "<br>";
											}
										}
										mysqli_free_result($result);
									?>
							</th>
						</tr>

						<tr  bgcolor="#FCFCFC" height = "50">
							<th colspan="4">
								<input type="submit" Name="add" value="提交" /> 
								<a href = "./sys.php">
								<input type="button" value="返回系統管理介面" style="align;"></button></a>
							</th>
						
						</tr>
					</table>
					<table border="2" width="1080" align="center"><br>
						<tr bgcolor="#97CBFF">
							<th>
								<font size=5>資料清除</font>
							</th>
						</tr>
						<tr  bgcolor="#FCFCFC">
							<th>
								路線名稱：<input type="text" Name="delpatientcard" size="8"/ >&nbsp&nbsp
								<input type="submit" Name="onedel" value="清除" />
							</th>
						</tr>
					</table>
					
					<?php
						include("sys_database.php");
						if(isset($_POST['add']))
						{ 
							
							$addName = $_POST['Name'];
							$addIsite = $_POST['Isite'];
							$addCsite = $_POST['Csite'];
							$addTsite = $_POST['Tsite'];
							
							$i = 0;
							
							if($addName == NULL)
							{
								echo "<script>alert('請輸入路線名稱')</script>";
								$i++;
							}
							if(count($addTsite) == 0)
							{
								echo "<script>alert('請選擇站點')</script>";
								$i++;
							}
							
							for($j=0;$j<count($addTsite);$j++)
							{
								if($j==0)
									$str_tag = $addTsite[$j];
								else
									$str_tag =$str_tag.",".$addTsite[$j];
							}
							
							$sql = "select count(*) from Route where Name = '$addName'";
							$result = mysqli_query($con,$sql) or die("Error in query: $query.". mysqli_error());
							$n = mysqli_fetch_row($result);
							if($n[0] != 0)
							{
								$i++;
								echo "<script>alert('重複出現Name')</script>";
							}
							
							if($i == 0)
							{
								$query = "INSERT INTO Route VALUE('$addName','$addIsite','$addCsite','$str_tag')"; 
								$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error());
								header("refresh: 0;");
							}
							mysqli_free_result($result);
							mysqli_close($con);						
							
						} 
						else if(isset($_POST['onedel']))
						{ 
							$delpatientcard = $_POST['delpatientcard'];

							$query = "delete from Route where Name='$delpatientcard'"; 
							$result = mysqli_query($con,$query) or die("Error in query: $query. ". mysqli_error()); 
							mysqli_close($con); 
							header("refresh: 0;");
						} 
					?>
					<table border="2" width="1080" align="center"><br>
						<tr bgcolor="#97CBFF">
							<th COLSPAN=6 ALIGN=CENTER>
								<font size=5>資料顯示</font>
							</th>
						</tr>

						<tr  bgcolor="#FCFCFC">
						<th width="160">路線名稱</th><th width="160">起始站點</th><th width="160">倉儲站點</th><th width="160">工作站點</th>
					
					<?php	
					
						$sql = "SELECT * FROM Route ORDER BY Name";
						$result = mysqli_query($con, $sql);

						$i=0;
						while(list($Name[$i],$Isite[$i],$Csite[$i],$Tsite[$i]) = mysqli_fetch_row($result))
						$i ++;

						for($j = 0; $j < $i; $j++)
						{
							echo "<tr   bgcolor=\"#FCFCFC\"><th>" .$Name[$j]. "</th><th>" .$Isite[$j].  "</th><th>" .$Csite[$j].  "</th><th>" .$Tsite[$j]."</th></tr>";
						}
						mysqli_free_result($result);
						mysqli_close($con);
					?>
					</table>
				</form>
	</body>
</html>