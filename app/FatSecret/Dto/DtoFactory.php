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
     * @return Collection
     */
    public function createFoodEntryDtoCollection(array $rows): Collection
    {
        return new Collection(
            array_map(
                function (array $row) {
                    return $this->createFoodEntryDto($row);
                },
                $rows
            ));
    }

    /**
     * @param array $row
     * @return FoodMacronutrientDto
     */
    public function createFoodEntryDto(array $row): FoodMacronutrientDto
    {
        return new FoodMacronutrientDto(
            $row['calories'],
            $row['protein'],
            $row['fat'],
            $row['carbohydrate']
        );
    }
}
