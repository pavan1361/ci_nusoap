<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * https://www.agustinvillalba.com/using-nusoap-in-codeigniter/
 * https://sourceforge.net/projects/nusoap/?source=typ_redirect
 * https://www.agustinvillalba.com/creating-a-soap-server-in-codeigniter/

*/
      class nuSoap_lib{
          function Nusoap_lib(){
               require_once(APPPATH.'libraries/lib/nusoap.php'); //If we are executing this script on a Windows server
          }
      }
?>

