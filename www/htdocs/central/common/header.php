<?php
/* modifications
2021-02-25 fho4abcd moved profile and favicon to standard location. Favicon to png (see index.php).Non functional mods for readability
*/
	if (isset($charset))
        $content_charset=$charset;
	else
        $content_charset=$meta_encoding;

	if (!isset($css_name))
		$css_name="";
	else
		$css_name.="/";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head profile="http://www.w3.org/2005/10/profile">
		<link rel="icon" type="image/png" href="/favicon.png"/>
		<title>ABCD<?php if (isset($subtitle))  echo $subtitle?></title>
		<meta http-equiv="Expires" content="-1" />
		<meta http-equiv="pragma" content="no-cache" />
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $content_charset?>" />
		<meta http-equiv="X-Content-Type-Options" content="nosniff">
		<meta name="robots" CONTENT="NONE" />
		<meta http-equiv="keywords" content="" />
		<meta http-equiv="description" content="" />
		<!-- Stylesheets -->
		<link rel="stylesheet" rev="stylesheet" href="../css/<?php echo $css_name?>template.css?<?php echo time(); ?>" type="text/css" media="screen"/>
		<link href="../../assets/css/all.min.css" rel="stylesheet"> <!--load all styles -->
		<!--[if IE]>
			<link rel="stylesheet" rev="stylesheet" href="../css/bugfixes_ie.css" type="text/css" media="screen"/>
		<![endif]-->
		<!--[if IE 6]>
			<link rel="stylesheet" rev="stylesheet" href="../css/bugfixes_ie6.css" type="text/css" media="screen"/>
		<![endif]-->
		<style>
			#loading {
			   width: 100%;
			   height: 100%;
			   top: 0px;
			   left: 0px;
			   position: fixed;
			   display: none;
			   opacity: 0.7;
			   background-color: #fff;
			   z-index: 99;
			   text-align: center;
			}

			#loading-image {
			  position: absolute;
			  top:50%;
     		  left:50%;
     		  margin:-100px 0 0 -150px;
			  z-index: 100;
			}
		</style>
<?php
include ("css_settings.php");
?>
</head>
