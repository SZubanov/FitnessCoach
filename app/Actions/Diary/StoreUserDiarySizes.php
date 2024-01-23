<?php

namespace App\Actions\Diary;

use App\Contracts\Actions\Diary\StoreUserDiarySizesInterface;
use App\Contracts\Actions\Users\GetDefaultSizeUnitUserInterface;
use App\Dto\UserReport\DtoFactory;
use App\Dto\Web\Diary\DiarySizesStoreDto;
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

    public function __invoke(DiarySizesStoreDto $dto): void
    {
        $authUser = \Auth::user();
        $userSize = $this->dtoFactory
            ->createUserSizesDto(
                $authUser->id,
                $dto->neck,
                $dto->chest,
                $dto->waist,
                $dto->biceps,
                $dto->pelvis,
                $dto->thigh,
                $dto->tibia,
                ($this->defaultSizeUnitUser)(),
                 Carbon::parse($dto->date)
            );

        $this->reportRepository->createUserSize($userSize);
    }
}
