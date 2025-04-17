<?php

namespace App\Repositories\House;

interface HouseRepositoryInterface
{
    public function getAssignedTo(int $userId);

    public function getUnassignedOrAssignedToOthers(int $webUserId);
}
