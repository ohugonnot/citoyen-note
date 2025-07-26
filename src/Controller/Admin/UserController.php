<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Helper\UserJsonHelper;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

const ROLE_ADMIN = "ROLE_USER";
#[Route('/api/admin/users', name: 'admin_user_')]
class UserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserRepository $userRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted(ROLE_ADMIN);

        $users = $userRepository->findAll();

        $data = array_map(fn(User $user) => UserJsonHelper::build($user), $users);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted(ROLE_ADMIN);

        return $this->json(UserJsonHelper::build($user));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, User $user, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted(ROLE_ADMIN);

        $data = json_decode($request->getContent(), true);

        $allowedFields = ['nom', 'prenom', 'telephone', 'dateNaissance','pseudo', 'isVerified','scoreFiabilite'];
        foreach ($allowedFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            // Convertir la date de naissance si nécessaire
            if ($field === 'dateNaissance' && is_string($value)) {
                try {
                    $value = new \DateTime($value);
                } catch (\Exception $e) {
                    return $this->json(['error' => 'Format de date invalide pour dateNaissance'], 400);
                }
            }

            $setter = 'set' . ucfirst($field);
            if (method_exists($user, $setter)) {
                $user->$setter($value);
            }
        }

        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        if (isset($data['statut']) && method_exists($user, 'setStatut')) {
            $enum = \App\Enum\Statut::tryFrom($data['statut']);
            if ($enum) {
                $user->setStatut($enum);
            }
        }

        $em->flush();

        return $this->json(UserJsonHelper::build($user));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(User $user, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted(ROLE_ADMIN);

        $em->remove($user);
        $em->flush();

        return $this->json(['message' => 'Utilisateur supprimé.']);
    }
}
