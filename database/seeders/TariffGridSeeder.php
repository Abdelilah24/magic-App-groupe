<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\TariffGrid;
use Illuminate\Database\Seeder;

/**
 * Crée les 7 grilles tarifaires standard pour chaque hôtel.
 * Peut être relancé sans risque (utilise updateOrCreate).
 */
class TariffGridSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Hotel::all() as $hotel) {
            $this->seedForHotel($hotel->id);
        }
    }

    public static function seedForHotel(int $hotelId): void
    {
        // 1. Grille de base NRF (saisie manuelle)
        $nrf = TariffGrid::updateOrCreate(
            ['hotel_id' => $hotelId, 'code' => 'NRF'],
            [
                'name'       => 'Site web NRF',
                'is_base'    => true,
                'operator'   => null,
                'operator_value' => null,
                'rounding'   => 'round',
                'sort_order' => 1,
                'is_active'  => true,
            ]
        );

        // 2. AVM = NRF ÷ 1.1
        $avm = TariffGrid::updateOrCreate(
            ['hotel_id' => $hotelId, 'code' => 'AVM'],
            [
                'name'           => 'AVM - FIT NRF',
                'is_base'        => false,
                'base_grid_id'   => $nrf->id,
                'operator'       => 'divide',
                'operator_value' => 1.1,
                'rounding'       => 'round',
                'sort_order'     => 2,
                'is_active'      => true,
            ]
        );

        // 3. Groupe direct = NRF − 4%
        TariffGrid::updateOrCreate(
            ['hotel_id' => $hotelId, 'code' => 'GROUPE_DIRECT'],
            [
                'name'           => 'Groupe direct',
                'is_base'        => false,
                'base_grid_id'   => $nrf->id,
                'operator'       => 'subtract_percent',
                'operator_value' => 4,
                'rounding'       => 'round',
                'sort_order'     => 3,
                'is_active'      => true,
            ]
        );

        // 4. Walk-in / site web flex = NRF ÷ 0.9
        TariffGrid::updateOrCreate(
            ['hotel_id' => $hotelId, 'code' => 'WALK_IN'],
            [
                'name'           => 'Walk-in - site web flex',
                'is_base'        => false,
                'base_grid_id'   => $nrf->id,
                'operator'       => 'divide',
                'operator_value' => 0.9,
                'rounding'       => 'round',
                'sort_order'     => 4,
                'is_active'      => true,
            ]
        );

        // 5. Fit Flex = AVM ÷ 0.9
        TariffGrid::updateOrCreate(
            ['hotel_id' => $hotelId, 'code' => 'FIT_FLEX'],
            [
                'name'           => 'Fit Flex',
                'is_base'        => false,
                'base_grid_id'   => $avm->id,
                'operator'       => 'divide',
                'operator_value' => 0.9,
                'rounding'       => 'round',
                'sort_order'     => 5,
                'is_active'      => true,
            ]
        );

        // 6. Bar Réel = AVM ÷ 0.9
        $barReel = TariffGrid::updateOrCreate(
            ['hotel_id' => $hotelId, 'code' => 'BAR_REEL'],
            [
                'name'           => 'Bar Réel',
                'is_base'        => false,
                'base_grid_id'   => $avm->id,
                'operator'       => 'divide',
                'operator_value' => 0.9,
                'rounding'       => 'round',
                'sort_order'     => 6,
                'is_active'      => true,
            ]
        );

        // 7. Bar Virtuel = Bar Réel ÷ 0.7
        TariffGrid::updateOrCreate(
            ['hotel_id' => $hotelId, 'code' => 'BAR_VIRTUEL'],
            [
                'name'           => 'Bar Virtuel',
                'is_base'        => false,
                'base_grid_id'   => $barReel->id,
                'operator'       => 'divide',
                'operator_value' => 0.7,
                'rounding'       => 'round',
                'sort_order'     => 7,
                'is_active'      => true,
            ]
        );
    }
}
