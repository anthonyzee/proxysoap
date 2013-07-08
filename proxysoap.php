<?php
//          FILE: proxysoap.php
//
// LAST MODIFIED: 2013-04-02
//
//        AUTHOR: Anthony Zee <kk.zee@mimos.my>
//
//   DESCRIPTION: Allow scripts to consume Soap Web Services. 
//								For example, $.ajax requests from a
//                client script are only allowed to make requests to the same
//                host that the script is served from. This is to prevent
//                "cross-domain" scripting. With proxysoap.php, the javascript
//                client can pass the requested URL in and get back the
//                response from the external server. 
//
//         USAGE: "proxy_url" required parameter. For example:
//                http://www.mydomain.com/proxysoap.php?proxy_url=http://www.xxx.com
//

if (!function_exists('http-chunked-decode')) { 
    /** 
     * dechunk an http 'transfer-encoding: chunked' message 
     * 
     * @param string $chunk the encoded message 
     * @return string the decoded message.  If $chunk wasn't encoded properly it will be returned unmodified. 
     */ 
    function http_chunked_decode($chunk) { 
        $pos = 0; 
        $len = strlen($chunk); 
        $dechunk = null; 

        while(($pos < $len) 
            && ($chunkLenHex = substr($chunk,$pos, ($newlineAt = strpos($chunk,"\n",$pos+1))-$pos))) 
        { 
            if (! is_hex($chunkLenHex)) { 
                trigger_error('Value is not properly chunk encoded', E_USER_WARNING); 
                return $chunk; 
            } 

            $pos = $newlineAt + 1; 
            $chunkLen = hexdec(rtrim($chunkLenHex,"\r\n")); 
            $dechunk .= substr($chunk, $pos, $chunkLen); 
            $pos = strpos($chunk, "\n", $pos + $chunkLen) + 1; 
        } 
        return $dechunk; 
    } 
} 

/** 
 * determine if a string can represent a number in hexadecimal 
 * 
 * @param string $hex 
 * @return boolean true if the string is a hex, otherwise false 
 */ 
function is_hex($hex) { 
		// regex is for weenies 
		$hex = strtolower(trim(ltrim($hex,"0"))); 
		if (empty($hex)) { $hex = 0; }; 
		$dec = hexdec($hex); 
		return ($hex == dechex($dec)); 
} 

$proxy_url = isset($_GET['proxy_url'])?$_GET['proxy_url']:false;
if (!$proxy_url) {
    header("HTTP/1.0 400 Bad Request");
    echo "proxy.php failed because proxy_url parameter is missing";
    exit();
}
preg_match("~([a-z]*://)?([^:^/]*)(:([0-9]{1,5}))?(/.*)?~i", $proxy_url, $parts);
$protocol = $parts[1];
$server = $parts[2];
$port = $parts[4];
$path = $parts[5];
if ($port == "") {
		if (strtolower($protocol) == "https://") {
				$port = "443";
		} else {
				$port = "80";
		}
}

$fp = fsockopen($server, $port, $errno, $errstr, 30);
if (!$fp) {
    echo "$errstr ($errno)<br />\n";
} else {
	$soapContent=$_POST["dataString"];	
	$out="POST ".$proxy_url." HTTP/1.1"."\r\n";
	$out.="Accept-Encoding: gzip,deflate"."\r\n";
	$out.="Content-Type: text/xml;charset=UTF-8"."\r\n";
	$out.='SOAPAction: "'.$_POST["soapAction"].'"'."\r\n";
	$out.="Content-Length: ".strlen($soapContent)."\r\n";
	$out.="Host: ".$server."\r\n";
	$out.="Connection: Keep-Alive"."\r\n";
	$out.="User-Agent: Apache-HttpClient/4.1.1 (java 1.5)"."\r\n";
	$out.="\r\n";
	$out.=$soapContent;

	
	fwrite($fp, $out);
	
	/*
	while (!feof($fp)) {
			echo fgets($fp, 128);
	}
	*/
	/* Get response header. */

	$header = fgets($fp, 128);

	$status = substr($header,9,3);

	while ((trim($line = fgets($fp, 128)) != "") && (!feof($fp))) {
			$header .= $line;
			if ($status=="401" and strpos($line,"WWW-Authenticate: Basic realm=\"")===0) {
					fclose($fp);
			}	
		if (strpos(strtoupper($line), "TRANSFER-ENCODING") !== FALSE){
			$tencode=explode(":", $line);
			$strtencode=strtoupper(trim($tencode[1]));
		}
		
		if (strpos(strtoupper($line), "CONTENT-LENGTH") !== FALSE){
			$clen=explode(":", $line);
			$intclen=intval($clen[1]);
		}
	}

	if ($strtencode != "CHUNKED"){
		while (!feof($fp)) {
			$getlen=$intclen-strlen($body)+1;
			$body.=fgets($fp, $getlen);
			
			if ($intclen<=strlen($body)){
				echo $body;
				fclose($fp);
				exit();
			}
		}
	}else{
		$line = fgets($fp, 128);
		$intclen=hexdec($line);
		$inttlen=$intclen;
		while (!feof($fp)) {
			$body.=fgets($fp, $intclen);
		
			if ($inttlen<=strlen($body)){
				$line = fgets($fp, 128);
				if ($line==""){
					$line = fgets($fp, 128);
				}
				$intclen=hexdec($line);	
				$inttlen+=$intclen;
				if ($intclen==0){
					$body=htmlspecialchars_decode($body);
					echo $body;
					fclose($fp);
					exit();
				}
			}
		}
	}
	echo $body;
	fclose($fp);
	exit();
}
?>