<?php
    
namespace App\Controller;

use Laminas\Soap\Server;
use App\Service\SoapService;
use Laminas\Server\Reflection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SoapServerController extends AbstractController
{
    #[Route('/soap')]
    public function index(SoapService $soapService): Response {
        $serverUrl = "http://127.0.0.1:8000/soap";
        $options = ['uri' => $serverUrl];
        $server = new \Laminas\Soap\Server(null, $options);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=ISO-8859-1');
        
        if (isset($_GET['wsdl'])) {
            $soapAutoDiscover = new \Laminas\Soap\AutoDiscover(
                new \Laminas\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence(),
                $serverUrl
            );
            $soapAutoDiscover->setBindingStyle(array('style' => 'rpc'));
            $soapAutoDiscover->setOperationBodyStyle(array('use' => 'encode'));
            $soapAutoDiscover->setClass($soapService);
            $response->setContent($soapAutoDiscover->generate()->toXml());
        } else {
            $server->setClass($soapService);
            ob_start();
            $server->setDebugMode(true);
            $server->handle();
            $response->setContent(ob_get_clean());
        }

        return $response;
    }
}
