<?php
    
namespace App\Controller;

use App\Service\HelloService;
use App\Service\SoapService;
use Laminas\Server\Reflection;
use Laminas\Soap\Server;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class HelloController extends AbstractController
{
    #[Route('/soap')]
    public function index()
    {
        $serverUrl = "http://127.0.0.1:8000/soap";
        $options = [
            'uri' => $serverUrl,
        ];
        $server = new \Laminas\Soap\Server(null, $options);

        if (isset($_GET['wsdl'])) {
            $soapAutoDiscover = new \Laminas\Soap\AutoDiscover(
                new \Laminas\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence(),
                $serverUrl
            );
            $soapAutoDiscover->setBindingStyle(array('style' => 'rpc'));
            $soapAutoDiscover->setOperationBodyStyle(array('use' => 'encode'));
            $soapAutoDiscover->setClass(SoapService::class);

            header("Content-Type: text/xml");
            echo $soapAutoDiscover->generate()->toXml();
            
            exit();
        }
        
        $server->setClass(SoapService::class);
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=ISO-8859-1');

        ob_start();
        $server->setDebugMode(true);
        $server->handle();
        $response->setContent(ob_get_clean());

        return $response;
    }
}
