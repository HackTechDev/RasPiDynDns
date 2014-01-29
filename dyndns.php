#!/usr/bin/php
<?php

error_reporting(0);

$htmlPage = '
<HTML>
 <HEAD>
  <TITLE>%serverName%</title>
  <meta http-equiv="refresh" content="0; URL=http://%remoteIP%">
 </HEAD>
 <BODY>
  Si votre navigateur ne vous redirige pas automatiquement, utilisez ce lien <br/>
  If your browser does not automatically redirect you, use that link <br/> 
  <br/>
  <a href="%remoteIP%">%serverName%</a>
 </BODY>
</HTML> 
';

function getRemoteIpBehindBox() {
    // The internet box is a Livebox
    $internetBoxHomePage = file_get_contents("http://192.168.1.1");

    // s = multiline
    // (.*?) = no greedy
    preg_match('/Adresse IP WAN :(.*?)<td class="value">(.*?)<\/td>/s', $internetBoxHomePage, $matches);

    return $matches[2];
}

function createHtmlPage($htmlPage, $serverName, $remoteIpBehindBox) {
    $htmlPage = str_replace("%serverName%", $serverName, $htmlPage);
    $htmlPage = str_replace("%remoteIP%", $remoteIpBehindBox, $htmlPage);
    file_put_contents("/home/pi/DYNDNS/" . $serverName . ".html", $htmlPage);
    return $htmlPage;
}

// Ssh2 support:
//  sudo apt-get install libssh2-php

function uploadFileWithSSH($sshServer, $username, $password, $serverName, $remoteFile) {
    $connection = ssh2_connect($sshServer, 22);
    ssh2_auth_password($connection, $username, $password);
    ssh2_scp_send($connection, "/home/pi/DYNDNS/" . $serverName . ".html", $remoteFile, 0644);
}

/*

Installation:

On the server panel configuration:
 - Create a sub-domain that point to the ip address of the main server

On the main server:
 - Create a remote directory: $remoteDirectory
 - Create a apache virtual host that point to $remoteDirectory

*/

// Dynamic DNS paramaters 

$serverName = "Longwy";
$remoteIpBehindBox = getRemoteIpBehindBox();

$sshServer = "remoteserveur.com";
$username = "username";
$password = "mot2passe";
$remoteDirectory = "/home/nekrofage/framboisepi/serveur/";
$remoteFile = $remoteDirectory . strtolower($serverName) . "/index.html";

// Main application

if($argv[1] == "start") {
    // Html page creation
    $htmlPage = createHtmlPage($htmlPage, $serverName, $remoteIpBehindBox);
    // Upload the html file to remote server
    uploadFileWithSSH($sshServer, $username, $password, $serverName, $remoteFile);
} else if($argv[1] == "stop") {
    uploadFileWithSSH($sshServer, $username, $password, "notAvailable", $remoteFile);
} else {
    echo "Usage: \r\n  ./dyndns.php start\r\nor\r\n  ./dyndns.php stop\r\n";

}

?>
