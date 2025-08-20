<?php

namespace App\FatSecret\Dto;

use Illuminate\Support\Collection;

class DtoFactory
{
    /**
     * @param string $oauthToken
     * @param string $oauthTokenSecret
     * @return OAuthTokenDto
     */
    public function createOAuthTokenDto(string $oauthToken, string $oauthTokenSecret): OAuthTokenDto
    {
        return new OAuthTokenDto($oauthToken, $oauthTokenSecret);
    }

    /**
     * @param array $rows
     * @return FoodEntryDto
     */
    public function createFoodEntryDto(array $rows): FoodEntryDto
    {
        return new FoodEntryDto(
            $this->createFoodMacronutrientDtoCollection($rows)
        );
    }

    /**
     * @param array $rows
     * @return Collection
     */
    public function createFoodMacronutrientDtoCollection(array $rows): Collection
    {
        return new Collection(
            array_map(
                function (array $row) {
                    return $this->createFoodMacronutrientDto($row);
                },
                $rows
            ));
    }

    /**
     * @param array $row
     * @return FoodMacronutrientDto
     */
    public function createFoodMacronutrientDto(array $row): FoodMacronutrientDto
    {
        return new FoodMacronutrientDto(
            $row['calories'],
            $row['protein'],
            $row['fat'],
            $row['carbohydrate']
        );
    }

    /**
     * @param array $rows
     * @param int $date
     * @return ?WeightDto
     */
    public function createWeightDto(array $rows, int $date): ?WeightDto
    {
        if (!isset($rows['month']['day'])) {
            return null;
        }

        $weights = $rows['month']['day'];

        foreach ($weights as $day) {
            if ((int)$day['date_int'] === $date) {
                return new WeightDto($day['weight_kg']);
            }
        }

        return null;
    }
}
