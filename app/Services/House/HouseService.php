<?php

namespace App\Services\House;

use App\Repositories\House\HouseRepositoryInterface;

class HouseService
{
    protected HouseRepositoryInterface $repository;

    public function __construct(HouseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAssignedTo(int $userId)
    {
        return $this->repository->getAssignedTo($userId);
    }

    public function getUnassignedOrAssignedToOthers(int $userId)
    {
        return $this->repository->getUnassignedOrAssignedToOthers($userId);
    }
}
