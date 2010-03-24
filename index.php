<?php
  
  ////
  // vpn_config/index.php
  // Web app for creating OpenVPN client configurations
  // @author Filipp Lepalaan <filipp@mcare.fi>
  // @copyright (c) 2010 Filipp Lepalaan
  
  session_start();
  
  // Set some defaults
  $port = ($_SESSION['port']) ? $_SESSION['port'] : '1194';
  $name = ($_SESSION['name']) ? $_SESSION['name'] : 'example.com';
  $remote = ($_SESSION['remote']) ? $_SESSION['remote'] : 'vpn.example.com';
  
  if ($_POST['reset']) {
    session_destroy();
  }
  
  if (!empty($_POST['cert']) && !$_POST['reset'])
  {
    // Save some settings for later use
    $_SESSION['ca'] = $_POST['ca'];
    $_SESSION['port'] = $_POST['port'];
    $_SESSION['name'] = $_POST['name'];
    $_SESSION['remote'] = $_POST['remote'];
    $_SESSION['advanced'] = $_POST['advanced'];
    
    $tmpdir = '/tmp/'.uniqid();
    mkdir($tmpdir);
    file_put_contents("$tmpdir/ca.crt", $_POST['ca']);
    file_put_contents("$tmpdir/cert.crt", $_POST['cert']);
    file_put_contents("$tmpdir/key.key", $_POST['key']);
    $dns_support = ($_POST['use_dns']) ? 'true' : 'false';
    
    $config =<<<EOT
#viscosity startonopen false
#viscosity dhcp true
#viscosity dnssupport $dns_support
#viscosity name ${_POST['name']}
remote {$_POST['remote']} {$_POST['port']}
persist-key
persist-tun
tls-client
proto udp
ca ca.crt
key key.key
cert cert.crt
dev tun
nobind
pull
resolv-retry infinite
{$_POST['use_lzo']}
{$_POST['advanced']}
EOT;
    
    $cf = 'config';
    
    if ($_POST['client'] == 'Tunnelblick') {
      // Tunnelblick uses the name of the file
      $trans = array('&' => '_', '/' => '_', ' ' => '');
      $cf = strtr($_POST['name'], $trans);
    }
    
    file_put_contents("${tmpdir}/{$cf}.conf", $config);
    
    $filename = ($_POST['user'] != 'username') ? $_POST['user'] : time();
    $outfile = '/tmp/openvpn_'.$filename.'.tgz';
    
    // "Pack it up, pack it in..."
    `tar -czvf {$outfile} {$tmpdir}`;
    
    // "... let me begin"
    header('Expires: 0');
    header('Pragma: public');
    header('Content-Transfer-Encoding: binary');
    header('Content-Description: File Transfer');
    header('Content-Length: '.filesize($outfile));
    header('Content-Type: application/octet-stream');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Disposition: attachment; filename='.basename($outfile));
    
    ob_clean();
    flush();
    readfile($outfile);
    
    `srm -rs $tmpdir $outfile`;
    exit();
  }
  
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

	<title>vpn_config - Define OpenVPN Connection</title>
	<style type="text/css" media="screen">
	 body {
	   margin:0;
	   padding:0;
	   font:75% "Lucida Grande", "Trebuchet MS", Verdana, sans-serif;
	 }
	 #page {
	   width:800px;
     margin:20px auto 20px auto;
	 }
   fieldset {
     padding:10px;
     margin-bottom:15px;
     border:1px solid #999;
     -moz-border-radius:8px;
     -webkit-border-radius:8px;
   }
   label {
     float:left;
     width:100px;
     display:block;
     margin-right:10px;
   }
	 form {
	   text-align:top;
	 }
	 textarea, input[type="text"] {
	   padding:5px;
	   width:635px;
	   resize:none;
	   font-size:12px;
	 }
	 legend {
	   color:#555;
	   font-weight:bold;
	 }
	 #use_lzo {
	   margin-left:110px;
	 }
	 #footer {
     font-size:8pt;
	   text-align:center;
	 }
	</style>
</head>

<body>
<div id="page">
  <form action="#" method="post" accept-charset="utf-8" target="_blank">
    <fieldset id="general">
      <legend>General</legend>
      <label>Connection:</label>
      <input
        id="name"
        type="text"
        name="name"
        style="width:534px"
        value="<?php echo $name ?>" />
      &nbsp;
      <input
        id="user"
        type="text"
        name="user"
        value="username"
        style="width:75px" />
      <br/>
      <label>Remote Server:</label>
      <input
        id="remote"
        type="text"
        name="remote"
        style="width:534px"
        value="<?php echo $remote ?>" />
      :
      <input
        id="port"
        type="text"
        name="port"
        style="width:75px"
        value="<?php echo $port ?>" />
    <br/>
    <label>VPN Software:</label>
    <select name="client" id="client">
      <option>Viscosity</option>
      <option>Tunnelblick</option>
    </select>
    </fieldset>
    
    <fieldset id="certificates">
      <legend>Certificates</legend>
      <label>Certificate auth.:</label>
      <textarea name="ca" rows="8" cols="40"><?php echo $_SESSION['ca']; ?></textarea>
      <br/>
      <label>Certificate:</label>
      <textarea name="cert" rows="8" cols="40"></textarea>
      <br/>
      <label>Private Key:</label>
      <textarea name="key" rows="8" cols="40"></textarea>
    </fieldset>
    
    <fieldset id="options">
      <legend>Options</legend>
      <label>Advanced:</label>
      <textarea
        name="advanced"
        rows="8" cols="40"><?php echo $_SESSION['advanced'] ?></textarea>
      <br/>
      <input
        type="checkbox"
        name="use_lzo"
        id="use_lzo"
        checked="checked"
        value="comp-lzo"/>&nbsp;enable LZO compression
      <input
        type="checkbox"
        name="use_dns"
        id="use_dns"
        checked="checked" />&nbsp;enable DNS support
      <p>
        <input type="submit" style="float:right" value="Submit"/>
        <input type="submit" name="reset" style="float:right" value="Reset"/>
      </p>
    </fieldset>
  </form>
  <div id="footer">Made by Filipp | <a href="http://github.com/filipp/vpn_config" target="_blank">source@github</a></div>
</div>
<script type="text/javascript" charset="utf-8">
  document.getElementById('name').focus();
</script>
</body>
</html>
