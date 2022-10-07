<?php
namespace App\Service;

use App\Service\ValidateTokenService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Customer;
use App\Entity\Token;

class SoapService
{

    private $em;
    private $validateToken;

    public function __construct(EntityManagerInterface $entityManager, ValidateTokenService $ValidateTokenService)
    {
        $this->em = $entityManager;
        $this->validateToken = $ValidateTokenService;
    }

    public function login($email=false,$id=false,$token=false,$expiration_date=false){

        $customerRepository = $this->em->getRepository(Customer::class);

        if($email){

            $customer = $customerRepository->findOneBy(
                array(
                    'email' => $email
                )
            );
    
            if($customer){
                try {
                    $data = [
                        'id' => $customer->getId(),
                        'password' => $customer->getPassword()
                    ];
        
                    $response = [
                        'status' => 'success',
                        'response' => $data
                    ];
                    return json_encode($response);
    
                } catch (Exception $e) {
                    $response = [
                        'status' => 'err',
                        'message' => 'Option Not Available At The Moment'
                    ];
                    return json_encode($response);
                }
    
            } else{
                $response = [
                    'status' => 'err',
                    'message' => 'Customer Not Registered'
                ];
                return json_encode($response);
            }

        } else {

            try {

                $customerEntity = $customerRepository->find($id);
                $tokenEntity = new Token();

                $tokenEntity->setToken($token);
                $tokenEntity->setExpirationDate($expiration_date); 
                $tokenEntity->setActivate('enable');
                $tokenEntity->setCustomer($customerEntity);
                $this->em->persist($tokenEntity);
                $this->em->flush();

                $response = [
                    'status' => 'success',
                    'message' => 'Logged in!',
                    'token' => $token
                ];
                return json_encode($response);

            } catch (Exception $e) {
                $response = [
                    'status' => 'err',
                    'message' => 'Option Not Available At The Moment'
                ];
                return json_encode($response);
            }
        }
    }

    public function registerCustomer($email,$password,$dni,$name,$last_name,$phone) {

        $customerRepository = $this->em->getRepository(Customer::class);
        $query = $customerRepository->createQueryBuilder("customer")
            ->where("customer.email = :email")
            ->orWhere("customer.dni = :dni")
            ->setParameter("email", $email)
            ->setParameter("dni", $dni)
            ->getQuery();
        $existingCustomer = $query->getResult();

        if($existingCustomer){
            $response = [
                'status' => 'err',
                'message' => 'Customer Is Already Registered'
            ];
            return json_encode($response);

        } else{
            $customer = new Customer();
            try {
                $customer->setEmail($email);
                $customer->setPassword($password);
                $customer->setDni($dni);
                $customer->setName($name);
                $customer->setLastName($last_name);
                $customer->setPhone($phone);

                $this->em->persist($customer);
                $this->em->flush();

                $response = [
                    'status' => 'success',
                    'message' => 'Customer Successfully Registered'
                ];
                return json_encode($response);

            } catch (Exception $e) {
                $response = [
                    'status' => 'err',
                    'message' => 'Option Not Available At The Moment'
                ];
                return json_encode($response);
            }
        }        
    }

    public function rechargeWallet($dni,$phone,$balance,$token) {

        $customerRepository = $this->em->getRepository(Customer::class);
        $customer = $customerRepository->findOneBy(
            array(
                'dni' => $dni,
                'phone' => $phone
            )
        );

        if(!$customer) {
            $response = [
                'status' => 'err',
                'message' => 'Customer Not Registered with these credentials'
            ];
            return json_encode($response);
        }

        $token_validate = $this->validateToken->validateToken($token, $customer);

        if($token_validate['status'] == 'success') {

            try {

                $balanceUpdate = is_null($customer->getBalance()) ? $balance : $customer->getBalance() + $balance;

                $customer->setBalance($balanceUpdate);
                $this->em->persist($customer);
                $this->em->flush();

                $response = [
                    'status' => 'success',
                    'message' => 'Recharge Wallet Done Successfully'
                ];
                return json_encode($response);


            } catch (Exception $e) {
                $response = [
                    'status' => 'err',
                    'message' => 'Option Not Available At The Moment'
                ];
                return json_encode($response);
            }

        } else {

            return json_encode($token_validate);
        }        
    }

    public function payment($dni=false,$phone=false,$amount_payable=false,$id=false,$token_email=false,$session_id=false,$token) {

        $customerRepository = $this->em->getRepository(Customer::class);

        if($id && $token_email && $session_id){

            $customer = $customerRepository->find($id);
            $token_validate = $this->validateToken->validateToken($token, $customer);

            if($token_validate['status'] == 'success') {

                try {

                    $customer->setTokenEmail($token_email);
                    $customer->setSessionId($session_id);
                    $this->em->persist($customer);
                    $this->em->flush();

                    $response = [
                        'status' => 'success',
                        'message' => 'Token and session_id successfully registered'
                    ];
                    return json_encode($response);

                } catch (Exception $e) {

                    $response = [
                        'status' => 'err',
                        'message' => 'Option Not Available At The Moment'
                    ];

                    return json_encode($response);

                }
            } else {

                return json_encode($token_validate);
            }
            
        } else {

            $customer = $customerRepository->findOneBy(
                array(
                    'dni' => $dni,
                    'phone' => $phone
                )
            );
    
            if(!$customer) {
                $response = [
                    'status' => 'err',
                    'message' => 'Customer Not Registered'
                ];
                return json_encode($response);
            }

            $token_validate = $this->validateToken->validateToken($token, $customer);
            if($token_validate['status'] == 'success') {

                try {

                    $data = [
                        'id' => $customer->getId(),
                        'email' => $customer->getEmail(),
                        'balance' => $customer->getBalance()
                    ];
        
                    $response = [
                        'status' => 'success',
                        'response' => $data
                    ];
                    return json_encode($response);

                } catch (Exception $e) {

                    $response = [
                        'status' => 'err',
                        'message' => 'Option Not Available At The Moment'
                    ];

                    return json_encode($response);

                }

            } else {

                return json_encode($token_validate);
            }
            
        }
        
    }

    public function confirmPayment($id,$token,$balance=false,$token_email=false,$session_id=false){

        $customerRepository = $this->em->getRepository(Customer::class);
        $customer = $customerRepository->find($id);

        if(!$customer) {
            $response = [
                'status' => 'err',
                'message' => 'Customer Not Registered with these credentials'
            ];
            return json_encode($response);
        }

        $token_validate = $this->validateToken->validateToken($token, $customer);

        if($token_validate['status'] == 'success') {

            if($id && $balance != null && $token_email == null && $session_id == null){

                try {

                    $customer->setBalance($balance);
                    $customer->setTokenEmail(null);
                    $customer->setSessionId(null);
                    $this->em->persist($customer);
                    $this->em->flush();

                    $response = [
                        'status' => 'success',
                        'message' => 'Payment Made Successfully'
                    ];
                    return json_encode($response);

                } catch (Exception $e) {

                    $response = [
                        'status' => 'err',
                        'message' => 'Your Payment Could Not Be Confirmed'
                    ];

                    return json_encode($response);
                }

            } else {

                try {

                    $data = [
                        'id' => $customer->getId(),
                        'balance' => $customer->getBalance(),
                        'token_email' => $customer->getTokenEmail(),
                        'session_id' => $customer->getSessionId()
                    ];
        
                    $response = [
                        'status' => 'success',
                        'response' => $data
                    ];
                    return json_encode($response);
        
                } catch (Exception $e) {
        
                    $response = [
                        'status' => 'err',
                        'message' => 'Option Not Available At The Moment'
                    ];
        
                    return json_encode($response);
        
                }

            }
        } else {

            return json_encode($token_validate);
        }

    }

    public function checkBalance($dni,$phone,$token) {

        $customerRepository = $this->em->getRepository(Customer::class);
        $tokenRepository = $this->em->getRepository(Token::class);
        $customer = $customerRepository->findOneBy(
            array(
                'dni' => $dni,
                'phone' => $phone
            )
        );

        if($customer) {

            $token_validate = $this->validateToken->validateToken($token, $customer);
            if($token_validate['status'] == 'success') {

                $response = [
                    'status' => 'success',
                    'balance' => $customer->getBalance(),
                ];

                return json_encode($response);

            } else { 
                return json_encode($token_validate);
            }

        } else {

            $response = [
                'status' => 'err',
                'message' => 'Customer Not Registered with these credentials'
            ];
            return json_encode($response);

        }
       
    }

    public function logout($id,$token,$expiration_date){
        $customerRepository = $this->em->getRepository(Customer::class);
        $tokenRepository = $this->em->getRepository(Token::class);

        if($id && $token && $expiration_date){

            $customer = $customerRepository->find($id);

            $token = $tokenRepository->findOneBy(
                array(
                    'customer' => $customer,
                    'token' => $token,
                    'expiration_date' => $expiration_date
                )
            );
    
            if($token){
                try {
   
                    $token->setActivate('disable');
                    $this->em->persist($token);
                    $this->em->flush();

                    $response = [
                        'status' => 'success',
                        'message' => 'Log out successfully'
                    ];
                    return json_encode($response);
    
                } catch (Exception $e) {
                    $response = [
                        'status' => 'err',
                        'message' => 'Option Not Available At The Moment'
                    ];
                    return json_encode($response);
                }
    
            } else{
                $response = [
                    'status' => 'err',
                    'message' => 'The session could not be closed'
                ];
                return json_encode($response);
            }

        } else {

            $response = [
                'status' => 'err',
                'message' => 'Option Not Available At The Moment'
            ];
            return json_encode($response);
        }
    }

}