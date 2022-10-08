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
    
    /**
     * Register a new customer
     * 
     * @param string|null $email
     * @param string|null $password
     * @param int|null $dni
     * @param string|null $name
     * @param string|null $last_name
     * @param int|null $phone
     * @return object
     */
    public function registerCustomer(string $email = null, string $password = null, int $dni = null, string $name = null, string $last_name = null, int $phone = null): object {
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

    /**
     * Recharge Wallet for customer
     * 
     * @param int|null $dni
     * @param int|null $phone
     * @param float|null $balance
     * @param string|null $token
     * @return object
     */
    public function RechargeWallet(int $dni = null,int $phone = null, float $balance = null, string $token = null): object {
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

    /**
     * Create payment confirmation
     * 
     * @param string|null $token_email
     * @param string|null $session_id
     * @return object
     */
    public function ConfirmPayment(string $token_email=null, string $session_id=null): object {
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

    /**
     * Get Balance
     * @param string|null $dni
     * @param string|null $phone
     * @param int|null $token
     * @return object
     */
    public function checkBalance(string $dni = null,string $phone = null,int $token = null): object {
        $customerRepository = $this->entityManager->getRepository(Customer::class);
        $tokenRepository = $this->entityManager->getRepository(Token::class);
        echo "asdasdasd";
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

    /**
     * Create payment
     * @param string $token
     * @param string|null $session_id
     * @param float|null $amount_payable
     * @return object
     */
    public function CreatePayment(string $token, string $session_id=null, float $amount_payable=null): object {
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
                        // TODO: Remove when email was sent
                        'token_email_tmp' => $tokenEmail,
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
}