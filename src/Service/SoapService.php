<?php
namespace App\Service;

use App\Entity\Token;
use App\Entity\Customer;
use App\Service\ValidateTokenService;
use Doctrine\ORM\EntityManagerInterface;

class SoapService
{

    /** @var EntityManagerInterface $entityManager */
    private EntityManagerInterface $entityManager;
    /** @var \App\Service\ValidateTokenService $validateToken */
    private \App\Service\ValidateTokenService $validateToken;

    public function __construct(EntityManagerInterface $entityManager, ValidateTokenService $ValidateTokenService)
    {
        $this->entityManager = $entityManager;
        $this->validateToken = $ValidateTokenService;
    }

    /**
     * @param string|null $email
     * @param string|null $password
     * @param string|null $expiration_date
     * @return object
     */
    public function login(string $email=null, string $password=null, string $expiration_date=null): object {
        $customerRepository = $this->entityManager->getRepository(Customer::class);
        $customer = $customerRepository->findOneBy(['email' => $email, 'password' => $password]);

        if($customer){
            try {
                $sessionId = $this->generateRandomString();
                $token = random_int(100000, 999999);
                $tokenEntity = new Token();
                $tokenEntity->setToken($token);
                $tokenEntity->setExpirationDate($expiration_date ?? date('Y-m-d H:i:s'));
                $tokenEntity->setActivate('enable');
                $tokenEntity->setCustomer($customer);
                $this->entityManager->persist($tokenEntity);
                $this->entityManager->flush();
                
                $customer->setSessionId($sessionId);
                $this->entityManager->persist($customer);
                $this->entityManager->flush();

                return (object) [
                    'success' => true,
                    'message' => 'Logged in!',
                    'token' => $token,
                    'session_id' => $sessionId
                ];
            } catch (\Exception $e) {
                return (object) [
                    'success' => false,
                    'message' => 'Option not available at the moment' . $e->getMessage()
                ];
            }
        } else{
            return (object) [
                'success' => false,
                'message' => 'Customer Not Registered'
            ];
        }
    }

    public function registerCustomer($email,$password,$dni,$name,$last_name,$phone): object {
        $customerRepository = $this->entityManager->getRepository(Customer::class);
        $query = $customerRepository->createQueryBuilder("customer")
            ->where("customer.email = :email")
            ->orWhere("customer.dni = :dni")
            ->setParameter("email", $email)
            ->setParameter("dni", $dni)
            ->getQuery();
        $existingCustomer = $query->getResult();

        if($existingCustomer){
            return  (object) [
                'success' => false,
                'message' => 'Customer already exists'
            ];
        } else{
            $customer = new Customer();

            try {
                $customer->setEmail($email);
                $customer->setPassword($password);
                $customer->setDni($dni);
                $customer->setName($name);
                $customer->setLastName($last_name);
                $customer->setPhone($phone);

                $this->entityManager->persist($customer);
                $this->entityManager->flush();

                return (object) [
                    'success' => true,
                    'message' => 'Customer Successfully Registered'
                ];

            } catch (\Exception $e) {
                return (object) [
                    'success' => false,
                    'message' => 'Option not available at the moment'
                ];
            }
        }  
    }

    public function rechargeWallet($dni,$phone,$balance,$token): object {
        $customerRepository = $this->entityManager->getRepository(Customer::class);
        $customer = $customerRepository->findOneBy(['dni' => $dni, 'phone' => $phone]);

        if(!$customer) {
            return (object) [
                'success' => false,
                'message' => 'Customer not registered with these credentials'
            ];
        }

        $token_validate = $this->validateToken->validateToken($token, $customer);

        if($token_validate['success']) {
            try {
                $balanceUpdate = is_null($customer->getBalance()) ? $balance : $customer->getBalance() + $balance;
                $customer->setBalance($balanceUpdate);
                $this->entityManager->persist($customer);
                $this->entityManager->flush();

                return (object) [
                    'success' => true,
                    'message' => 'Recharge wallet done successfully'
                ];
            } catch (\Exception $e) {
                return (object) [
                    'success' => false,
                    'message' => 'Option not available at the moment'
                ];
            }
        } else {
            return (object) [
                'success' => false,
                'message' => 'Unprocessable entity'
            ];
        }
    }

    private function generateRandomString($length = 10) {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }

    public function confirmPayment($token_email=false,$session_id=false): object {
        $customerRepository = $this->entityManager->getRepository(Customer::class);
        $customer = $customerRepository->findOneBy(['session_id' => $session_id, 'token_email' => $token_email]);

        if(!$customer) {
            return (object) [
                'success' => false,
                'message' => 'Bad credentials or token expired'
            ];
        } else {
            try {
                $customer->setBalance($customer->getBalance() - $customer->getPendingBalance());
                $customer->setTokenEmail('');
                $customer->setPendingBalance(0);

                $this->entityManager->persist($customer);
                $this->entityManager->flush();

                return (object) [
                    'success' => true,
                    'message' => 'Payment made successfully'
                ];
            } catch (\Exception $exception) {
                return (object) [
                    'success' => true,
                    'message' => 'Payment could not be confirmed'
                ];
            }
        }
    }

    public function checkBalance($dni,$phone,$token): object {
        $customerRepository = $this->entityManager->getRepository(Customer::class);
        $tokenRepository = $this->entityManager->getRepository(Token::class);
        $customer = $customerRepository->findOneBy(['dni' => $dni, 'phone' => $phone]);

        if($customer) {
            $token_validate = $this->validateToken->validateToken($token, $customer);
            if($token_validate['success']) {
                return (object) [
                    'success' => true,
                    'balance' => $customer->getBalance(),
                    'pending_to_approve' => $customer->getPendingBalance()
                ];
            } else { 
                return (object) $token_validate;
            }
        } else {
            return (object) [
                'success' => false,
                'message' => 'Customer not registered with these credentials'
            ];
        }
    }

    public function payment($token, $session_id=null,$amount_payable=null): object {
        $customerRepository = $this->entityManager->getRepository(Customer::class);
        $tokenEmail = uniqid();

        if($session_id && $token){
            $customer = $customerRepository->findOneBy(['session_id' => $session_id]);
            $token_validate = $this->validateToken->validateToken($token, $customer);

            if($customer && $token_validate['success']) {
                try {
                    // TODO: Send token to customer email
                    
                    $customer->setTokenEmail($tokenEmail);
                    $customer->setSessionId($session_id);
                    $customer->setPendingBalance($amount_payable);
                    $this->entityManager->persist($customer);
                    $this->entityManager->flush();

                    return (object) [
                        'success' => true,
                        'message' => 'Payment was pending to approval'
                    ];
                } catch (\Exception $e) {
                    return (object) [
                        'success' => false,
                        'message' => 'Option not available at the moment'
                    ];
                }
            }
        }

        return (object) [
            'success' => false,
            'message' => 'Option not available at the moment'
        ];
    }

    public function logout($id,$token,$expiration_date): object {
        $customerRepository = $this->entityManager->getRepository(Customer::class);
        $tokenRepository = $this->entityManager->getRepository(Token::class);

        if($id && $token && $expiration_date){
            $customer = $customerRepository->find($id);
            $token = $tokenRepository->findOneBy(['customer' => $customer, 'token' => $token, 'expiration_date' => $expiration_date]);
    
            if($token){
                try {
                    $token->setActivate('disable');
                    $this->entityManager->persist($token);
                    $this->entityManager->flush();

                    return (object) [
                        'success' => true,
                        'message' => 'Log out successfully'
                    ];
                } catch (\Exception $e) {
                    return (object) [
                        'success' => false,
                        'message' => 'Option not available at the moment'
                    ];
                }
            } else{
                return (object) [
                    'success' => false,
                    'message' => 'The session could not be closed'
                ];
            }
        } else {
            return (object) [
                'success' => false,
                'message' => 'Option not available at the moment'
            ];
        }
    }
}