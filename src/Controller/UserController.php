<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use PDO;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class UserController extends AbstractController
{
    #[Route('/users', name: 'liste_des_users', method:['GET'])]
    public function getListeDesUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $entityManager->getRepository(User::class)->findAll();
        
        return $this->json(
            $data,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/users', name: 'users', method:['POST'])]
    public function createUser(Request $request,EntityManagerInterface $entityManager): JsonResponse
    {
        if($request->getMethod() === 'POST'){
            $data = json_decode($request->getContent(), true);
            $form = $this->createFormBuilder()
                ->add('nom', TextType::class, [
                    'constraints'=>[
                        new Assert\NotBlank(),
                        new Assert\Length(['min'=>1, 'max'=>255])
                    ]
                ])
                ->add('age', NumberType::class, [
                    'constraints'=>[
                        new Assert\NotBlank(),
                    ]
                ])
                ->getForm();

            $form->submit($data);
            
            if(!($form->isValid())){
                return new JsonResponse('Wrong method', 405);
            }

            if(!($data['age'] > 21)){
                $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
                return new JsonResponse('Wrong age', 400);
                
            }


                if($data['age'] > 21){
                    $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
                    
                    if(count($user) === 0){
                        $player = new User();
                        $player->setName($data['nom']);
                        $player->setAge($data['age']);
                        $entityManager->persist($player);
                        $entityManager->flush();

                        return $this->json(
                                    $player,
                                    201,
                                  headers: ['Content-Type' => 'application/json;charset=UTF-8']
                                );                    
                    }else{
                        return new JsonResponse('Name already exists', 400);
                    }
                }else{
                    return new JsonResponse('Wrong age', 400);
                }
            }else{
                return new JsonResponse('Invalid form', 400);
            }

    }

    #[Route('/user/{id}', name: 'get_user_by_id', method:['GET'])]
    public function getUserWithIdentifiant($identifiant, EntityManagerInterface $entityManager): JsonResponse
    {
        if(ctype_digit($identifiant)){
            $player = $entityManager->getRepository(User::class)->findBy(['id'=>$identifiant]);
            if(count($player) == 1){
                return new JsonResponse(array('name'=>$player[0]->getName(), 'age'=>$player[0]->getAge(), 'id'=>$player[0]->getId()), 200);
            }else{
                return new JsonResponse('Wrong id', 404);
            }
        }
        }

    #[Route('/user/{id}', name: 'udpate_user', method:['PATCH'])]
    public function updateUser(EntityManagerInterface $entityManager, $identifiant, Request $request): JsonResponse
    {
        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$identifiant]);

        if(count($player) == 1){

            if($request->getMethod() == 'PATCH'){
                $data = json_decode($request->getContent(), true);
                $form = $this->createFormBuilder()
                    ->add('nom', TextType::class, array(
                        'required'=>false
                    ))
                    ->add('age', NumberType::class, [
                        'required' => false
                    ])
                    ->getForm();

                $form->submit($data);
                if($form->isValid()) {

                    foreach($data as $key=>$value){
                        switch($key){
                            case 'nom':
                                $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
                                if(count($user) === 0){
                                    $player[0]->setName($data['nom']);
                                    $entityManager->flush();
                                }else{
                                    return new JsonResponse('Name already exists', 400);
                                }
                                break;
                            case 'age':
                                if($data['age'] > 21){
                                    $player[0]->setAge($data['age']);
                                    $entityManager->flush();
                                }else{
                                    return new JsonResponse('Wrong age', 400);
                                }
                                break;
                        }
                    }
                }else{
                    return new JsonResponse('Invalid form', 400);
                }
            }else{
                $data = json_decode($request->getContent(), true);
                return new JsonResponse('Wrong method', 405);
            }

            return new JsonResponse(array('name'=>$player[0]->getName(), 'age'=>$player[0]->getAge(), 'id'=>$player[0]->getId()), 200);
        }else{
            return new JsonResponse('Wrong id', 404);
        }    
    }

    //supprimer un utilisateur
    #[Route('/user/{id}', name: 'delete_user_by_ID', method:['DELETE'])]
    public function deleteUser($id, EntityManagerInterface $entityManager): JsonResponse | null
    {
        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
        if(count($player) == 1){
            try{
                $entityManager->remove($player[0]);
                $entityManager->flush();

                $userExist = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
    
                if(!empty($userExist)){
                    throw new \Exception("User not deleted");
                    return null;
                }else{
                    return new JsonResponse('', 204);
                }
            }catch(\Exception $e){
                return new JsonResponse($e->getMessage(), 500);
            }
        }else{
            return new JsonResponse('Wrong id', 404);
        }    
    }
}
