<?php

namespace App\Controller\Backend\Soap;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QWCController
{

    protected $container;
    protected $logger;

    public function __construct(ContainerInterface $container,LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }


    /**
     * @param string $strUserName
     * @param string $strPassword
     * @return array
     */
    public function authenticate(string $strUserName, string $strPassword)
    {
        $evLogTxt = "WebMethod: authenticate() has been called by QBWebconnector evLogTxt=evLogTxt";

        $evLogTxt .= $evLogTxt . " string strUserName = " . $strUserName;
        $evLogTxt .= $evLogTxt . " string strPassword = " . $strPassword;

        $authReturn = ['', '','', ''];
// Code below uses a random GUID to use as session ticket
// An example of a GUID is {85B41BEE-5CD9-427a-A61B-83964F1EB426}

       $authReturn[0] = "kjhkhkh";
// For simplicity of sample, a hardcoded username/password is used.
// In real world, you should handle authentication in using a standard way.
// For example, you could validate the username/password against an LDAP
// or a directory server
        //authenticate user
        if (true) {
// An empty string for authReturn[1] means asking QBWebConnector
// to connect to the company file that is currently openned in QB
            $authReturn[1] = "";
        } else {
            $authReturn[1] = "nvu";
        }
// You could also return "none" to indicate there is no work to do
// or a company filename in the format C:\full\path o\company.qbw
// based on your program logic and requirements.";



        return $authReturn;
    }

    /**
     * @param string $strVersion
     * @return string
     */
    public function clientVersion(string $strVersion)
    {

        return "ok";
    }

    /**
     * @param string $ticket
     * @return string
     */
    public function closeConnection(string $ticket)
    {

        $retVal = "";

        //close connection


        $retVal = "OK";

        return $retVal;
    }

    /**
     * @param string $ticket
     * @param string $hresult
     * @param string $message
     * @return string|null
     */
    public function connectionError(string $ticket, string $hresult, string $message)
    {
        $retVal = null;
// 0x80040400 - QuickBooks found an error when parsing the provided XML text stream.
        $QB_ERROR_WHEN_PARSING = "0x80040400";
// 0x80040401 - Could not access QuickBooks.
        $QB_COULDNT_ACCESS_QB = "0x80040401";
// 0x80040402 - Unexpected error. Check the qbsdklog.txt file

        $QB_UNEXPECTED_ERROR = "0x80040402";
// Add more as you need...
        if ($hresult == $QB_ERROR_WHEN_PARSING) {
            $retVal = "DONE";
        } else if ($hresult == $QB_COULDNT_ACCESS_QB) {
            $retVal = "DONE";
        } else if ($hresult == $QB_UNEXPECTED_ERROR) {
            $retVal = "DONE";
        } else {
// Depending on various hresults return different value
// Try again with this company file
            $retVal = "";
        }
        return $retVal;
    }

    /**
     * @param string $wcTicket
     * @param string $sessionID
     * @return string
     */
    public function getInteractiveURL(string $wcTicket, string $sessionID)
    {
        return $this->generateUrl('homepage_backend', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }


    /**
     * @param string $ticket
     * @return string|null
     */
    public function getLastError(string $ticket)
    {
        $errorCode = 0;
        $retVal = null;
        if ($errorCode == -101) {
            $retVal = "QuickBooks was not running!"; // just an example of custom user errors
        } else {
            $retVal = "Error!";
        }
        return $retVal;
    }


    /**
     * @return string
     */
    public function serverVersion()
    {
        return "2.1";
    }

    /**
     * @param string $ticket
     * @param string $response
     * @param string $hresult
     * @param string $message
     * @return float|int
     */
    public function receiveResponseXML(string $ticket, string $response, string $hresult, string $message)
    {

        $retVal = 0;
        if ($hresult != "") {
// if error in the response, web service should return a negative int
            $retVal = -101;
        } else {
            $req = buildRequest();
            $total = $req . Count;
            $count = Session["counter"];
            $percentage = ($count * 100) / $total;
            if ($percentage >= 100) {
                $count = 0;
                $session["counter"] = 0;
            }
            $retVal = $percentage;
        }
        return $retVal;
    }


    /**
     * @param string $ticket
     * @param string $strHCPResponse
     * @param string $strCompanyFileName
     * @param string $qbXMLCountry
     * @param int $qbXMLMajorVers
     * @param int $qbXMLMinorVers
     * @return string
     */
    public function sendRequestXML(string $ticket,
                                   string $strHCPResponse,
                                   string $strCompanyFileName,
                                   string $qbXMLCountry,
                                   int $qbXMLMajorVers,
                                   int $qbXMLMinorVers)
    {

        if ($session["counter"] == null) {
            $session["counter"] = 0;
        }
        $req = buildRequest();
        $request = "";
        $total = count($req);
        $count = $session["counter"];
        if ($count < $total) {
            $request = $req[$count];
            $session["counter"] = $session["counter"] + 1;
        } else {
            $count = 0;
            $session["counter"] = 0;
            $request = "";
        }
        return $request;
    }
}
