<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="UTF-8">
	    <meta Name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	    <title>工單資料</title>
	</head>
<?
	include("database.php");
	if(isset($_POST['del']))
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
	
?>
	<body>
				<form action="" method=post>
					<table border="1" width="80%" align="center">
					
					
						<tr height = "50" >
							<th colspan="5"  bgcolor="#97CBFF">
								<p ><font size=5>資料與狀態查詢功能</font></p>
							</th>
						</tr>
						<tr  bgcolor="#FCFCFC" height = "50">
							<th width="270">
								<a href="./Check.php">受控設備連線狀態</a>
							</th>
							<th width="270">
								<a href="./Check_RFID.php">站點資料</a>
							</th>
							<th width="270">
								<a href="./Check_WH.php">倉儲位置資料</a>
							</th>
							<th width="270">
								<a href="./Check_Route.php">路線資料</a>	
							</th>
							<th width="270">
								<p>工單資料</p>
							</th>
						</tr>
							
					</table><br>
					<table border="1" width="80%" align="center">
						<tr><th height=60 bgcolor="#FFFFFF">
						顯示狀態：
						
						<input type="radio" name="show" value="0">全部
						<input type="radio" name="show" value="1">未處理
						<input type="radio" name="show" value="2">已完成　
						<button type="submit"  name="submit">提交</button>
						</th></tr>
					</table>
					<table border="2" width="80%" align="center"><br>
				<tr bgcolor="#97CBFF">
					<th COLSPAN=7 ALIGN=CENTER>
						<font size=5>工單資料顯示</font>
					</th>
				</tr>

				<tr  bgcolor="#FCFCFC">
				<th width="80">工單編號</th><th width="160">路線名稱</th><th width="160">執行 AGV 設備</th><th width="160">工作站點</th><th width="160">放置儲位</th><th width="160">狀態</th><th width='160'>作業</th>
			
				<?php	
					
					if(isset($_POST["submit"]))
					{ 	
						$show = $_POST['show'];
						switch($show)
						{
							case 0:
								$sql = "SELECT * FROM schedule";
								break;
							case 1:
								$sql = "SELECT * FROM schedule where status != 2";
								break;
							case 2:
								$sql = "SELECT * FROM schedule where status = 2";
								break;
						}
					}
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
					<table border="1" width="80%" align="center" >
						<tr><th height=60 bgcolor="#CCFFFF">
						<a href="../user.php"><p style="font-size:20px">上一頁</p></a>
						</th></tr>
					</table>
				</form>
	</body>
</html>