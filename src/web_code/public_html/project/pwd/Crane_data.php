<table border=3 width=80% cellspacing=2 cellpadding=10 bordercolor=Blue >
<tr>
    <th height=30 colspan=5 bgcolor='#66d9ff'>
        <font size=6>OHT　設　備　四　軸　目　前　位　置　與　電　磁　鐵　狀　態</font>
    </th>
</tr>
<tr>
        <th>W 軸</th>
        <th>X 軸</th>
        <th>Y 軸</th>
        <th>Z 軸</th>
        <th>電磁鐵狀態</th>
</tr>
<?

include("sys_database.php");
$ip = $_SESSION['operator_ip']; 
// 讀取目前位置
$sql = "select w_axis, x_axis, y_axis, z_axis,emt  from Crane_status where ip = '$ip' ";
$result = mysqli_query($con, $sql);


list($w, $x, $y, $z, $emt) = mysqli_fetch_row($result);

echo "<tr><th>$w</th><th>$x</th><th>$y</th><th>$z</th><th>";

if($emt == "0")
{
    echo "放下";
}
else if($emt == "2")
{
    echo "吸住";
}
else
{
    echo "NA";
}
echo "</th></tr>";
mysqli_free_result($result);
?>