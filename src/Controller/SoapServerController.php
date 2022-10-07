<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Service\SoapService;

class SoapServerController extends AbstractController
{
    public function guardarOrdenDeCompra($request) {
        return [
            'NumeroDeAutorizacion' => 'La orden de compra ' . $request['NumeroDeOrden'] . ' ha sido autorizada con el número ' . rand(1000, 100000),
            'Resultado' => true
        ];
    }
    
    /**
     * @Route("/soap/server", name="soap_server")
     */
    public function soapServer(SoapService $soapService)
    {
        // NuSOAP implementation
        $namespace = 'Controller.asv';
        $server = new \soap_server();
        $server->configureWSDL("MiSOAP", $namespace);
        $server->wsdl->schemaTargetNamespace = $namespace;
        
        
        $server->wsdl->addComplexType(
            "OrdenDeCompra",
            "complexType",
            "struct",
            "all",
            '',
            [
                'NumeroDeOrden' => ['name' => 'NumeroDeOrden', 'type' => 'xsd:string'],
                'Ordenante' => ['name' => 'Ordenante', 'type' => 'xsd:string'],
                'Moneda' => ['name' => 'Moneda', 'type' => 'xsd:string'],
                'TipoCambio' => ['name' => 'TipoCambio', 'type' => 'xsd:decimal'],
            ]
        );
        
        $server->wsdl->addComplexType(
            'response',
            'complexType',
            'struct',
            'all',
            '',
            [
                'NumeroDeAutorizacion' => ['name' => 'NumeroDeAutorizacion', 'type' => 'xsd:string'],
                'Resultado' => ['name' => 'Resultado', 'type' => 'xsd:boolean'],
            ]
        );
        
        $server->register(
            'SoapServerController.guardarOrdenDeCompra',
            ['name' => 'tns:ordenDeCompra'],
            ['name' => 'tns:response'],
            $namespace,
            false,
            'rpc',
            'encoded',
            'Recibe una orden de compra y regresa un numero de autorizacion'
        );
        
       // $server->debug_flag = true;
        $POST_DATA = file_get_contents('php://input');
        $server->service($POST_DATA);
        exit();
    }
}
