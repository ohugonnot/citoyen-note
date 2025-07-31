<?php

namespace App\Service;

use App\Dto\CreateEvaluationDto;
use App\Dto\UpdateEvaluationDto;
use App\Entity\Evaluation;
use App\Entity\ServicePublic;
use App\Entity\User;
use App\Enum\StatutEvaluation;
use App\Helper\EvaluationJsonHelper;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class EvaluationManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EvaluationRepository $repository,
    ) {}

    public function create(CreateEvaluationDto $dto): Evaluation
    {
        $evaluation = new Evaluation();

        $service = $this->em->getRepository(ServicePublic::class)->findOneBy(['id' => $dto->service_id]);
        if (!$service) {
            throw new \InvalidArgumentException("Service public non trouvé.");
        }

        $evaluation
            ->setNote($dto->note)
            ->setCommentaire($dto->commentaire)
            ->setCriteresSpecifiques($dto->criteres_specifiques)
            ->setEstAnonyme($dto->est_anonyme)
            ->setEstVerifie($dto->est_verifie)
            ->setStatut(StatutEvaluation::ACTIVE)
            ->setServicePublic($service);

        if ($dto->user_id) {
            $user = $this->em->getRepository(User::class)->findOneBy(['id' => $dto->user_id]);
            if ($user) {
                $evaluation->setUser($user);
            }
        }

        $this->em->persist($evaluation);
        $this->em->flush();

        return $evaluation;
    }

    public function update(Evaluation $evaluation, UpdateEvaluationDto $dto): Evaluation
    {
        $evaluation
            ->setNote($dto->note)
            ->setCommentaire($dto->commentaire)
            ->setCriteresSpecifiques($dto->criteres_specifiques)
            ->setEstAnonyme($dto->est_anonyme)
            ->setEstVerifie($dto->est_verifie);

        if (isset($dto->service_id)) {
            $service = $this->em->getRepository(ServicePublic::class)->findOneBy(['id' => $dto->service_id]);
            if (!$service) {
                throw new \InvalidArgumentException("Service public non trouvé.");
            }
            $evaluation->setServicePublic($service);
        }

        if (isset($dto->user_id)) {
            if ($dto->user_id) {
                $user = $this->em->getRepository(User::class)->findOneBy(['id' => $dto->user_id]);
                if (!$user) {
                    throw new \InvalidArgumentException("Utilisateur non trouvé.");
                }
                $evaluation->setUser($user);
            } else {
                $evaluation->setUser(null);
            }
        }

        $this->em->flush();

        return $evaluation;
    }

    public function delete(Evaluation $evaluation): void
    {
        $this->em->remove($evaluation);
        $this->em->flush();
    }

    public function bulkDelete(array $uuids): void
    {
        $uuidStrings = array_map(function($uuid) {
            return $uuid instanceof \Symfony\Component\Uid\Uuid ? $uuid->toRfc4122() : (string)$uuid;
        }, $uuids);
        
        // Suppression directe sans charger les entités
        $this->repository->createQueryBuilder('e')
            ->delete()
            ->where('e.uuid IN (:uuids)')
            ->setParameter('uuids', $uuidStrings)
            ->getQuery()
            ->execute();
    }

    public function getPublicServiceStats(ServicePublic $service): array
    {
        $qb = $this->repository->createQueryBuilder('e')
            ->where('e.servicePublic = :service')
            ->andWhere('e.statut = :statut')
          //  ->andWhere('e.estVerifie = true')
            ->setParameter('service', $service->getId()->toBinary())
            ->setParameter('statut', StatutEvaluation::ACTIVE)
            ->orderBy('e.createdAt', 'DESC')
            ->setMaxResults(20); 

        $evaluations = $qb->getQuery()->getResult();

        if (empty($evaluations)) {
            return [
                'moyenne' => 0,
                'total' => 0,
                'repartition' => [],
                'evaluations' => []
            ];
        }

        $moyenne = array_sum(array_map(fn($e) => $e->getNote(), $evaluations)) / count($evaluations);

        return [
            'moyenne' => round($moyenne, 1),
            'total' => count($evaluations),
            'repartition' => $this->calculateRepartition($evaluations),
            'evaluations' => array_slice($evaluations, 0, 10)
        ];
    }

    private function calculateRepartition(array $evaluations): array
    {
        $repartition = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0
        ];

        foreach ($evaluations as $evaluation) {
            $note = $evaluation->getNote();
            if ($note >= 1 && $note <= 5) {
                $repartition[$note]++;
            }
        }

        $total = count($evaluations);
        if ($total > 0) {
            foreach ($repartition as $note => $count) {
                $repartition[$note] = [
                    'count' => $count,
                    'percentage' => round(($count / $total) * 100, 1)
                ];
            }
        }

        return $repartition;
    }
}
