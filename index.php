<?php
/*

  filedump
  v1

  by jwc
  http://jwcxz.com/

*/

define('PASS', '');						# put the _md5_ sum of the password you want...
								#   you can find md5 generators all over the internet
								#   or you could do : printf 'pass' | md5sum on a *NIX terminal
define('SALT', "mmms0diumchlor1de");				# throw a pinch of salt in, just for kicks (set to something random)
define('MAXSIZE', 20*pow(1024,2));				# maximum file size, in bytes
define('CHMOD', 0644);						# what should the files be chmodded to?  (default is fine)
define('SELF', array_pop(explode('/', $_SERVER['PHP_SELF'])));	# this is probably fine on its own, but it's the name of the script
$GLOBALS['blklst'] = array( SELF , '.htaccess' );		# files to disregard

##### end config #####

session_start();

function getsize($file) {
	$size = filesize($file);
	if ($size>=pow(1024,2))	{$amt = round($size/pow(1024,2),2);	$unt = "MiB";}
	elseif ($size>=1024)	{$amt = round($size/1024,2);		$unt = "KiB";}
	else			{$amt = $size;				$unt = "B";}
	return array($amt,$unt);
}

function chkauth() {
	if ( $_SESSION['pwd'] == md5(PASS.$_REQUEST['REMOTE_ADDR'].SALT ) )
		return true;
	else	return false;
}

function sanitize($txt) {
	# if there are any slashes, return false immediately
	if ( preg_match('/[\\\\\\/\\:]/', $txt) )	return false;
	if ( substr($txt, 0, 1) == '.' )		return false;	# disallow anything beginning with .
									# if this is unwanted behavior (i.e. you
									# want to upload hidden files), make sure
									# to replace it with something to check
									# for . and .. !
	# if the file IS blacklisted, return false
	if ( in_array($txt, $GLOBALS['blklst']) )	return false;
	if ( is_dir($txt) )				return false;
	return $txt;
}	

function lst($adm=false) {
	clearstatcache();
	$lst = scandir(".");
	for ($i=2;$i<count($lst);$i++) {
		$alt = ( $j % 2 == 0 ) ? ' class="alt"' : "" ;
		$sz = getsize($lst[$i]);
		if ( !($nm =sanitize($lst[$i])) ) continue;
		$a .= '<tr id="f'.$i.'"'.$alt.'><td><a href="'.$nm.'">'.$nm.'</a></td><td>'.$sz[0].'</td><td>'.$sz[1].'</td>';
		$a .= ( $adm ) ? '<td><a href="#" onclick="if ( this.style.backgroundColor != \'red\' ) {this.style.backgroundColor = \'red\';} else {del(\''.$nm.'\');}">del</a></td></tr>' : "</tr>" ;
		$b .= 'jlist['.$i.'] = "'.$nm.'";';
		$j++;
	}
	return array($a, $b);
}
	

###   MAIN LOOP   ###

# decide what to do
if ( count($_GET) ) {
	if ( $_GET['doDEL'] ) {				# delete a file and return result
		chkauth() or die("fail auth");
		$fnme = sanitize($_GET['doDEL']) or die("fail sanity");
		@unlink($fnme) or die("fail del : $fnme");
		$data = lst(true);
		die($data[0]."{|}".$data[1]);
	}
	else
		$fltr = key($_GET);			# create search filter
}

if ( $_POST['addfile'] ) {				# upload file
	chkauth() or die("fail auth");
	if ( $_FILES['upld']['name'] &&						# via file
	     ( $nm = stripslashes(sanitize($_FILES['upld']['name'])) ) ) {	# upload
		if ( defined('MAXSIZE') && filesize($_FILES['upld']['tmp_name']) > MAXSIZE ) die("fail sizecheck");
		if ( move_uploaded_file(stripslashes($_FILES['upld']['tmp_name']), $nm) !=1 ) die("fail upload");
		chmod($nm, CHMOD);
	}
	if ( $_POST['fname'] && $_POST['txt'] &&				# via file name
	     ( $nm = sanitize($_POST['fname']) ) ) {				# and content
		$f = fopen($nm, 'w');
		fwrite($f, $_POST['txt']);
		fclose($f);
	}
	define('ADMIN', true);
}

if ( md5($_POST['pass']) == PASS ) {
	$_SESSION['pwd'] = md5(PASS.$_REQUEST['REMOTE_ADDR'].SALT );
	# activate admin mode
	define('ADMIN', true);
}

# display interface ...
$flst = lst( defined('ADMIN') );
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>dmp</title>
<style>
* {
	font-family:	verdana, sans-serif;
	font-size:	10px;
	color:		white;
	font-weight:	bold;
}

body {
	background-color: black;
}

h1,h2 {
	text-align:	center;
	margin:		0px;
}
	h1	{font-size:	15px;}

.box {
	width:		300px;
	margin: 	15px auto;
	border: 	1px solid white;
	padding:	15px;
	text-align:	center;
}

table {
	width: 100%
}

tr {
	text-align:	left;
	background:	#222;
}
	tr.alt		{background:	#444;}
	td:first-child	{width:		90%;}
	tr:hover	{background:	white;}
	tr:hover td, tr:hover td *	{color:	black;}

a {
	display:	block;
	text-decoration:	none;
}

input, textarea {
	margin:		2px auto;
	background:	black;
	border:		1px solid white;
}
	textarea	{width:	100%;}

#adm {
	position: absolute;
	top: 0px;
	right: 0px;
}
	#adm *		{ color: black }
	#adm:hover *	{ color: white }

#pwd {
	position:	absolute;
	top:		0px;
	right:		0px;
	padding:	5px;
	background:	#777;
	border:		white;
	z-index:	2;
	text-align:	center;
}

#pbg {
	position:	absolute;
	top:		0px;
	left:		0px;
	width:		100%;
	height:		100%;
	background:	white;
	z-index:	1;
	opacity:	.75
	filter:		alpha(opacity=75);
	-moz-opacity:	.75
}
</style>
<script type="text/javascript" language="javascript">
var jlist = new Array();
<?	echo $flst[1]	?>

function fltr() {
	txt = document.getElementById('fltr').value.toLowerCase();
	for ( f in jlist ) {
		if ( !jlist[f].match(txt) )	{ document.getElementById("f"+f).style.display = 'none'; }
		else				{ document.getElementById("f"+f).style.display = '';     }
	}
}

<? if ( !defined('ADMIN') ) : ?>
function pwd() {
	if ( document.getElementById('pwd').style.display == "none" )	{ val = 'block'	}
	else								{ val = 'none'	}

	document.getElementById('pwd').style.display = val;
	document.getElementById('pbg').style.display = val;
}
<? endif; ?>

<? if ( defined('ADMIN') ) : ?>
// Somewhat related to  http://mikeoncode.blogspot.com/2006/02/ajax-project-to-get-you-going.html
var ro;
if ( navigator.appName == "Microsoft Internet Explorer")
	{ ro = new ActiveXObject("Microsoft.XMLHTTP"); }
else	{ ro = new XMLHttpRequest(); }

function del(fname) {
	ro.open('GET', '?doDEL=' + fname);
	ro.onreadystatechange = rldlst;	
	ro.send(null);
}

function rldlst() {
	if ( ro.readyState == 4 ) {
		if ( ro.responseText.substr(0,4) != "fail" ) { 
			dta = ro.responseText.split('{|}');
			document.getElementById('flst').innerHTML = dta[0]; 
			jlist = new Array();
			eval(dta[1]);
		}
		else	{ alert(ro.responseText); }
	}
}
<? endif; ?>

</script>
</head>

<body onload="fltr();">
	<h1>filedump</h1>
	<h2>
<? if ( !defined('ADMIN') ) : ?>
		<a href="http://jwcxz.com/">by jwc [ http://jwcxz.com/ ]</a>
<? else : ?>
		<a href="?">back</a>
<? endif; ?>
	</h2>

	<div id="adm">
<? if ( !defined('ADMIN') ) : ?>
		<a href="#" onclick="pwd();document.getElementById('pass').focus();">adm</a>
<? else : ?>
		<a href="?">bck</a>
<? endif; ?>
	</div>
	
<? if ( !defined('ADMIN') ) : ?>
	<div id="pwd" style="display: none">
		<form name="admin" method="post" action="index.php">
			<input type="password" name="pass" id="pass" />
			<input type="submit" name="go" value="&raquo;" />
		</form>
	</div>
	<div id="pbg" style="display: none" onclick="pwd();"></div>
<? endif; ?>

<? if ( defined('ADMIN') ) : ?>
	<div class="box">
                <form name="add" method="post" action="index.php" enctype="multipart/form-data">
			<? if ( defined('MAXSIZE') ) : ?><input type="hidden" name="MAX_FILE_SIZE" value="<? echo MAXSIZE; ?>" /><? endif; ?>
                        <input type="file" name="upld" style="width: 100%" />
                        <input type="text" name="fname" style="width: 100%" onmouseover="this.focus();" onclick="this.select();" onkeyup="if (this.value != '') {val='block'} else {val='none'} document.getElementById('txt').style.display=val">
                        <textarea name="txt" id="txt" style="display: none" onmouseover="this.focus();"></textarea>
                        <input type="submit" name="addfile" value=":: go ::" />
                </form>
        </div>
<? endif; ?>

	<div id="main" class="box">
		<input type="text" name="fltr" id="fltr" value="<? echo $fltr; ?>" onmouseover="this.focus();" onclick="this.select();" onkeyup="fltr();" />
		<input type="button" name="fbtn" id="fbtn" value=":: clr ::" onclick="document.getElementById('fltr').value=''; fltr();" />
		<div id="fbox">
			<table id="flst" name="flst">
				<?php echo $flst[0] ?>
			</table>
		</div>
	</div>
</body>
