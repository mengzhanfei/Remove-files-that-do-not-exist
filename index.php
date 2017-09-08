<?php
require 'Config.php';
header ( "Content-Type: text/html; charset=utf-8" );
/*数据库名*/
$database="lvyang";
/*有文件表字段名*/
$tablelist="
  'tb_news','tb_admin','tb_config'
";

set_time_limit(0);
$starttime=microtime(true);
$rs=$conn->Execute("
		DROP view if exists tb_self
		");//删除视图
$conn->Execute("
		CREATE or replace VIEW tb_self as select `TABLE_NAME` as t, `COLUMN_NAME` as f from `information_schema`.`COLUMNS` where `TABLE_SCHEMA`='$database' and TABLE_NAME not in($tablelist) and DATA_TYPE in('VARCHAR',\"LONGTEXT\")
		 ");//创建视图(有就删了替换，没有就创建)



$_SESSION['list']=array();

getList("Upload");//获取该目录下所有的图片地址



foreach ($_SESSION['list'] as $v)
{
	run($v);
}

echo "耗时：";
echo microtime(true)-$starttime;
echo "，程序执行完成:".date("Y-m-d H:i:s");


//递归遍历目录
//第二个参数是否显示全名
//第三个默认层级一般不用修改
function getList($dirname){
	//获取目录下的所有文件
	$fileArr=scandir($dirname);

	foreach($fileArr as $file){
		if($file == "." || $file == "..")
		{
			continue;
		}

		$dirfile=$dirname.DIRECTORY_SEPARATOR.$file;

		//如果子目录是目录的话就继续找下面的文件
		if(is_dir($dirfile))
		{
			getList($dirfile);
		}
		else
		{
			$ext=pathinfo($dirfile,PATHINFO_EXTENSION );
			if(stripos('|jpg|gif|png|', "|$ext|")!==false)//不区分大小写
			{
				$dirfile=str_replace('\\', "/", $dirfile);
				$dirfile=mb_convert_encoding($dirfile, "UTF-8", "GBK");
				// 				$dirfile=iconv('GBK','utf-8',$dirfile);//gbk转为utf-8
				$_SESSION['list'][]=$dirfile;
			}
		}
	}
}

//检查移动
function run($file)
{
	global $conn;

	$oldDir="Upload";//旧目录
	$newDir="Upload_Temp/";//临时目录
	$t=$conn->getAll("select * from tb_self");

	$isexist=false;//默认不存在
	foreach ($t as $k=>$v)
	{
		$sql = "select count(*) from $v[t] where $v[f] like '%$file%'";

		if($conn->getOne($sql))
		{
			$isexist=true;	//存在跳出
			break;
		}
	}

	if($isexist)
	{
		echo "文件{$file}存在<br/>";
	}
	else
	{
		// 		$file=iconv("GBK",'utf-8',$file);//gbk转为utf-8
		$file=mb_convert_encoding($file, "GBK", "UTF-8");
		$newFile=$newDir.$file;
		if(nameFile($file,$newFile,1))
		{
			$file=mb_convert_encoding($file, "UTF-8", "GBK");
			echo "<span style='color:#f00; font-size:15px;'>移动文件到{$newDir}{$file}成功</span><br/>";
		}
	}

}

function nameFile($oldname, $newname,$isBuildDir=false){
	if(!file_exists($oldname)){
		die('无法移动或重命名，文件或目录不存在');
	}
	$dirname=dirname($newname);
	if(strpos($dirname, '.')===false && !is_dir($dirname) && $isBuildDir){
		mkdir($dirname,0777,true);
	}
	if(!is_dir($dirname)){
		die( $dirname.'目录不存在，可以修改第三个参数为true，强制创建并移动文件，注意此操作有风险');
	}
	if(rename($oldname, $newname)){
		return true;
	}
}
?>
