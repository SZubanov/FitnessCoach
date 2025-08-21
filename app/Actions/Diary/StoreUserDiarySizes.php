<?php

namespace App\Actions\Diary;

use App\Contracts\Actions\Diary\StoreUserDiarySizesInterface;
use App\Contracts\Actions\Users\GetDefaultSizeUnitUserInterface;
use App\Dto\UserReport\DtoFactory;
use App\Dto\Web\Diary\DiarySizesStoreDto;
use App\Models\User;
use App\Repositories\UserReportRepository;
use Carbon\Carbon;

class StoreUserDiarySizes implements StoreUserDiarySizesInterface
{
    public function __construct(
        private readonly UserReportRepository $reportRepository,
        private readonly DtoFactory $dtoFactory,
        private readonly GetDefaultSizeUnitUserInterface $defaultSizeUnitUser
    ) {
    }

    public function __invoke(DiarySizesStoreDto $dto, User $user): void
    {
        $userSize = $this->dtoFactory
            ->createUserSizesDto(
                $user->id,
                Carbon::parse($dto->date),
                ($this->defaultSizeUnitUser)($user),
                $dto->neck,
                $dto->chest,
                $dto->waist,
                $dto->biceps,
                $dto->pelvis,
                $dto->thigh,
                $dto->tibia,
            );

        $this->reportRepository->createUserSize($userSize);
    }
}
