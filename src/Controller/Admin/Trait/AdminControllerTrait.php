<?php

namespace App\Controller\Admin\Trait;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Exception\ValidationFailedException;

trait AdminControllerTrait
{
    abstract protected function json(mixed $data, int $status = 200, array $headers = [], array $context = []): JsonResponse;

    protected function buildPaginationData(array $result, int $page, int $limit): array
    {
        $total = $result['total'];
        $totalPages = (int) ceil($total / $limit);

        return [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'hasNext' => $page < $totalPages,
            'hasPrev' => $page > 1,
        ];
    }

    protected function getJsonData(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('JSON invalide');
        }
        return $data ?? [];
    }

    protected function handleValidationErrors(ValidationFailedException $exception): JsonResponse
    {
        $errors = [];
        foreach ($exception->getViolations() as $violation) {
            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        $lines = [];
        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $lines[] = "$field: $message";
            }
        }

        return $this->json([
            'violations' => $errors,
            'error' => implode("\n", $lines),
        ], 422);
    }

    protected function handleError(\Exception $e, string $message, array $context = []): JsonResponse
    {
        $this->logger->error($message, array_merge($context, ['error' => $e->getMessage()]));

        if ($e instanceof \InvalidArgumentException) {
            return $this->json(['error' => $e->getMessage()], 422);
        }

        return $this->json(['error' => $message], 500);
    }
}
