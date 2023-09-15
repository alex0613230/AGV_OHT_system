<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>系統操作選項</title>
<style>
	.chi {font-family:"標楷體";font-size:20px;}
	th, button, input {font-size:24px;}
</style>

</head>
<?
	include("sys_database.php");
?>

<body bgcolor="#FFFFFF"><form action="/~Wi_SUN/index.php" method=post>
<center>
<table border=5 cellspacing=0 bordercolor="#6363FF" cellpadding=10 width=80%>
<tr>
	<th height=20 colspan=3><font size=7>系　統　管　理</font></th>
</tr>
<?
	if($_SESSION['priority'] == 1)
	{
		echo "<tr><th colspan='3'>";
		echo "<a href='./Register_member.php'><img src='./pic/user.png' style='width:160px;height:150px;'></a><br>使用者管理功能";
		echo "</th></tr>";
	}

?>
<tr>
	<th>
		<a href="./Register.php"><img src="./pic/Wi.png" style="width:160px;height:150px;"></a><br>受控設備管理功能
	</th>
	<th>
		<a href="./CarSite_RFID.php"><img src="./pic/truck.png" style="width:160px;height:150px;"></a><br>站點管理功能

	</th>
	<th>
		<a href="./Warehouse.php"><img src="./pic/locate.png" style="width:160px;height:150px;"></a><br>倉儲位置管理功能
	</th>
</tr>
<tr>
	<th>
		<a href="./Route.php"><img src="./pic/Route.png" style="width:160px;height:150px;"></a><br>路線管理功能
	</th>
	<th>
		<a href="./Manual.php"><img src="./pic/manual.png" style="width:160px;height:150px;"></a><br>AGV 設備操作功能
	</th>
	<th>
		<a href="./Crane.php"><img src="./pic/crane.png" style="width:160px;height:150px;"></a><br>OHT 設備操作功能
	</th>
</tr>


</table>
<br>
<table border=5 cellspacing=0 bordercolor="#6363FF" cellpadding=10 width=80%>
<tr>

	<th bgcolor=red>
		<input type="submit" name="bye" value="登出" style="width:180px;height:40px;"></button>
	</th>

</tr>



</table>
</center></form>
</body>
</html>
