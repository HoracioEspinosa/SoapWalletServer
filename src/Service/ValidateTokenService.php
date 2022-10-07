<?php
namespace App\Service;

use Exception;
use App\Entity\Token;
use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;

class ValidateTokenService
{
    /** @var EntityManagerInterface $entityManager */
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager ) {
        $this->entityManager = $entityManager;
    }

    public function validateToken($token,$customer): array {
        $customerRepository = $this->entityManager->getRepository(Customer::class);
        $tokenRepository = $this->entityManager->getRepository(Token::class);

        try{
            $query = $tokenRepository->createQueryBuilder("t")
                ->select('t.id, t.activate', 't.expiration_date')
                ->where("t.token = :token")
                ->andWhere("t.customer = :customer")
                ->setParameter("token", $token)
                ->setParameter("customer", $customer)
                ->setMaxResults(1)
                ->getQuery();
            
            $token_validate = $query->getResult();

            if($token_validate){
                $now = new \DateTime("now", new \DateTimeZone('America/Caracas') );
                if($token_validate[0]['activate'] == "enable" && $token_validate[0]['expiration_date'] >= $now->format('Y-m-d H:i:s')) {
                    return [
                        'success' => true,
                    ];
                } else {
                    $token = $tokenRepository->find($token_validate[0]['id']);
                    $token->setActivate('disable');
                    $this->entityManager->persist($token);
                    $this->entityManager->flush();

                    return [
                        'success' => false,
                        'message' => 'Your session has expired',
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid Token',
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Invalid Token',
            ];
        }
    }
}