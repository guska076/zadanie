<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use \App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RestController extends AbstractController
{
    /**
     * @Route("api/users", methods={"POST"})
     */
    public function new(Request $request, ValidatorInterface $validator, UserPasswordEncoderInterface $passwordEncoder)
    {
        $data = $request->getContent();

        /** @var \App\Entity\User $user */
        $user = $this->deserialize($data);
        $user->setPassword(
            $passwordEncoder->encodePassword(
                $user,
                $user->getPassword()
            )
        );
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorArr = [];
            foreach ($errors as $error) {
                $invalidProperty = $error->getPropertyPath();
                $errorArr[$invalidProperty] = $error->getMessage();
            }
            return $this->json(['validation_errors' => $errorArr, 400]);
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        return new JsonResponse($this->serialize($user), 201, [], true);
    }

    /**
     * @Route("api/users", methods={"GET"})
     */
    public function getAll()
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();
        $data = $this->serialize(['users' => $users]);

        return new JsonResponse($data, 200, [], true);
    }

    /**
     * @Route("api/users/{user}", methods={"GET"})
     */
    public function getOne(User $user)
    {
        $data = $this->serialize(['user' => $user]);

        return new JsonResponse($data, 200, [], true);
    }

    /**
     * @Route("api/users/{user}", methods={"PUT"})
     */
    public function update(User $user, Request $request, ValidatorInterface $validator, UserPasswordEncoderInterface $passwordEncoder)
    {
        $data = json_decode($request->getContent(), true);

        if(!empty($data['username'])) {
            $user->setUsername($data['username']);
        }
        if(!empty($data['password'])) {
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $data['password']
                )
            );
        }
        if(!empty($data['email'])) {
            $user->setEmail($data['email']);
        }
        if(!empty($data['api_token'])) {
            $user->setApiToken($data['api_token']);
        }
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorArr = [];
            foreach ($errors as $error) {
                $invalidProperty = $error->getPropertyPath();
                $errorArr[$invalidProperty] = $error->getMessage();
            }
            return $this->json(['validation_errors' => $errorArr], 400);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        return new JsonResponse($this->serialize($user), 200, [], true);
    }

    /**
     * @Route("api/users/{user}", methods={"DELETE"})
     */
    public function delete(User $user)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($user);
        $em->flush();

        return new Response(null, 204);
    }

    private function deserialize($data)
    {
        return $this->get('serializer')
            ->deserialize($data, User::class, 'json');
    }

    private function serialize($data)
    {
        return $this->get('serializer')
            ->serialize($data, 'json', ['groups' => ['group1']]);
    }
}