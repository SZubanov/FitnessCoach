<?php

namespace App\Actions\Diary;

use App\Contracts\Actions\Diary\StoreUserDiaryWeightInterface;
use App\Dto\UserReport\DtoFactory;
use App\Dto\Web\Diary\DiaryWeightStoreDto;
use App\Helpers\MetricSystem;
use App\Models\User;
use App\Repositories\UserReportRepository;
use Carbon\Carbon;

class StoreUserDiaryWeight implements StoreUserDiaryWeightInterface
{
    public function __construct(
        private readonly UserReportRepository $reportRepository,
        private readonly DtoFactory $dtoFactory
    ) {
    }

    public function __invoke(DiaryWeightStoreDto $dto, User $user): void
    {
        $userWeight = $this->dtoFactory
            ->createUserWeightDto(
                $user->id,
                Carbon::parse($dto->date),
                $dto->weight,
                $dto->unit
            );

        $this->reportRepository->createUserWeight($userWeight);
    }
}
