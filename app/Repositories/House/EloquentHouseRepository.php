<?php

namespace App\Repositories\House;

use App\Models\House;
use App\Models\WebUser;

class EloquentHouseRepository implements HouseRepositoryInterface
{
    public function getAssignedTo(int $userId)
    {

        $webUser = WebUser::query()->findOrFail($userId);

        return $webUser->houses;
    }

    public function getUnassignedOrAssignedToOthers(int $webUserId)
    {
        return House::query()->where(function ($query) use ($webUserId) {
            $query->whereDoesntHave('webUsers')
            ->orWhereDoesntHave('webUsers', function ($subQuery) use ($webUserId) {
                $subQuery->where('web_users.id', $webUserId);
            });
        })->get();
    }
}
