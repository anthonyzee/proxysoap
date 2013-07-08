# proxysoap.php

proxysoap.php is a server-side proxy script to allow scripts to consume SOAP based web services in other domain.

## Installation

Download proxysoap.php and put it in the web server that has the same domain with the script your want to allow cross domain SOAP web services call.

## Usage

### POST to the proxysoap.php with the following:

For example: http://www.mydomain.com/proxysoap.php?proxy_url=http://www.myotherdomain:81/xyzwebservice/service1.asmx

Data: 
{
	soapMessage: "",
	soapAction: "" 
}

## Example

```html
<!DOCTYPE html>
<html>
<head>
<title>Soap Client</title>
<style>
</style>
</head>
<body>
<div>
  <button id="btnRequest">Request cross domain SOAP</button>	
</div>
<script id="widgetScript">
	// Todo 1: Modify the webServiceURL below to the full http url to the SOAP web service
    var webServiceURL = 'http://www.myotherdomain:81/xyzwebservice/service1.asmx';	
	// Todo 2: Modify the soapMessage below by cut and paste here the soap message from SOAP-UI
	var soapMessage='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/"> <soapenv:Header/> <soapenv:Body> <tem:getXYZ> <tem:kontinjenId>9793</tem:kontinjenId> </tem:getXYZ> </soapenv:Body> </soapenv:Envelope>';
	var soapAction='http://tempuri.org/getXYZ';
	webekms.html5.widgetDiv.find("#btnRequest").click(function(e) {
		$.post("http://www.mydomain.com/proxysoap.php?proxy_url="+webServiceURL, {"dataString":soapMessage, "soapAction":soapAction}, function(xml){
			console.log(xml);
		});
    });
</script>
</body>
</html>
```

## License

proxysoap.php is covered by the MIT License. See LICENSE for more information.