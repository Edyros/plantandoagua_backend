<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlantingSeeder extends Seeder
{
    private const USER_COUNT = 500;

    private const PLANTING_COUNT = 2000;

    public function run(): void
    {
        $this->command?->info('Limpando plantios e usuários gerados anteriormente...');

        DB::table('users')
            ->where('email', 'like', 'planting-seed-%@reflorea.app')
            ->delete();

        $faker = fake('pt_BR');
        $now = now();
        $password = Hash::make('password');
        $cities = $this->cities();
        $species = $this->species();
        $suppliers = $this->suppliers();
        $locationNames = $this->locationNames();
        $observations = $this->observations();

        $this->command?->info('Criando '.self::USER_COUNT.' usuários...');

        $users = [];
        $userCities = [];

        for ($i = 1; $i <= self::USER_COUNT; $i++) {
            $place = $cities[($i - 1) % count($cities)];
            $userCities[$i] = $place;
            $cpfDigits = sprintf('%011d', $i);

            $users[] = [
                'uuid' => (string) Str::uuid(),
                'name' => $faker->name(),
                'email' => sprintf('planting-seed-%03d@reflorea.app', $i),
                'email_verified_at' => $now,
                'password' => $password,
                'phone' => sprintf(
                    '(%02d) 9%04d-%04d',
                    $faker->numberBetween(11, 99),
                    $faker->numberBetween(0, 9999),
                    $faker->numberBetween(0, 9999),
                ),
                'cpf' => substr($cpfDigits, 0, 3).'.'.substr($cpfDigits, 3, 3).'.'.substr($cpfDigits, 6, 3).'-'.substr($cpfDigits, 9, 2),
                'city' => $place['city'],
                'state' => $place['state'],
                'eco_points' => 0,
                'trees_planted' => 0,
                'profile_complete' => $faker->numberBetween(40, 100),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($users, 100) as $chunk) {
            DB::table('users')->insert($chunk);
        }

        $seededUsers = DB::table('users')
            ->where('email', 'like', 'planting-seed-%@reflorea.app')
            ->orderBy('id')
            ->get(['id', 'email']);

        $userIds = [];
        foreach ($seededUsers as $user) {
            preg_match('/planting-seed-(\d+)@/', $user->email, $matches);
            $userIds[(int) $matches[1]] = $user->id;
        }

        $plantingCounts = array_fill(1, self::USER_COUNT, 1);
        $remaining = self::PLANTING_COUNT - self::USER_COUNT;

        for ($i = 0; $i < $remaining; $i++) {
            $plantingCounts[random_int(1, self::USER_COUNT)]++;
        }

        $this->command?->info('Criando '.self::PLANTING_COUNT.' plantios...');

        $plantings = [];
        $treesByUser = [];
        $plantedFrom = now()->subYears(2)->timestamp;
        $plantedTo = now()->timestamp;

        for ($index = 1; $index <= self::USER_COUNT; $index++) {
            $userId = $userIds[$index];
            $place = $userCities[$index];
            $count = $plantingCounts[$index];
            $treesByUser[$userId] = 0;

            for ($n = 0; $n < $count; $n++) {
                $tree = $species[array_rand($species)];
                $quantity = random_int(1, 12);
                $treesByUser[$userId] += $quantity;
                $hasSupplier = random_int(1, 100) <= 65;
                $supplier = $hasSupplier ? $suppliers[array_rand($suppliers)] : null;

                $plantings[] = [
                    'id' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'species' => $tree['name'],
                    'scientific_name' => $tree['scientific'],
                    'quantity' => $quantity,
                    'planted_at' => date('Y-m-d H:i:s', random_int($plantedFrom, $plantedTo)),
                    'supplier_id' => $supplier['id'] ?? null,
                    'supplier_name' => $supplier['name'] ?? null,
                    'observations' => random_int(1, 100) <= 55
                        ? $observations[array_rand($observations)]
                        : null,
                    'latitude' => round($place['lat'] + $this->offset(), 7),
                    'longitude' => round($place['lng'] + $this->offset(), 7),
                    'location_name' => $locationNames[array_rand($locationNames)],
                    'city' => $place['city'],
                    'state' => $place['state'],
                    'photo_uris' => json_encode($this->photoUris()),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($plantings, 250) as $chunk) {
            DB::table('plantings')->insert($chunk);
        }

        $this->command?->info('Atualizando eco points e total de árvores...');

        $userUpdates = [];
        foreach ($treesByUser as $userId => $total) {
            $userUpdates[] = [
                'id' => $userId,
                'trees_planted' => $total,
                'eco_points' => $total * 12,
            ];
        }

        foreach (array_chunk($userUpdates, 100) as $chunk) {
            foreach ($chunk as $row) {
                DB::table('users')
                    ->where('id', $row['id'])
                    ->update([
                        'trees_planted' => $row['trees_planted'],
                        'eco_points' => $row['eco_points'],
                        'updated_at' => $now,
                    ]);
            }
        }

        $this->command?->info(self::USER_COUNT.' usuários e '.count($plantings).' plantios criados.');
    }

    private function offset(): float
    {
        return (random_int(-450, 450) / 10000);
    }

    /**
     * @return list<string>
     */
    private function photoUris(): array
    {
        $chance = random_int(1, 100);

        if ($chance <= 35) {
            return [];
        }

        $count = $chance <= 80 ? 1 : 2;
        $uris = [];

        for ($i = 0; $i < $count; $i++) {
            $uris[] = 'https://picsum.photos/seed/'.Str::lower(Str::random(8)).'/800/600';
        }

        return $uris;
    }

    /**
     * @return list<array{city: string, state: string, lat: float, lng: float}>
     */
    private function cities(): array
    {
        return [
            ['city' => 'São Paulo', 'state' => 'SP', 'lat' => -23.5505, 'lng' => -46.6333],
            ['city' => 'Rio de Janeiro', 'state' => 'RJ', 'lat' => -22.9068, 'lng' => -43.1729],
            ['city' => 'Belo Horizonte', 'state' => 'MG', 'lat' => -19.9167, 'lng' => -43.9345],
            ['city' => 'Curitiba', 'state' => 'PR', 'lat' => -25.4284, 'lng' => -49.2733],
            ['city' => 'Porto Alegre', 'state' => 'RS', 'lat' => -30.0346, 'lng' => -51.2177],
            ['city' => 'Brasília', 'state' => 'DF', 'lat' => -15.7939, 'lng' => -47.8828],
            ['city' => 'Salvador', 'state' => 'BA', 'lat' => -12.9714, 'lng' => -38.5014],
            ['city' => 'Recife', 'state' => 'PE', 'lat' => -8.0476, 'lng' => -34.8770],
            ['city' => 'Fortaleza', 'state' => 'CE', 'lat' => -3.7172, 'lng' => -38.5433],
            ['city' => 'Manaus', 'state' => 'AM', 'lat' => -3.1190, 'lng' => -60.0217],
            ['city' => 'Belém', 'state' => 'PA', 'lat' => -1.4558, 'lng' => -48.4902],
            ['city' => 'Goiânia', 'state' => 'GO', 'lat' => -16.6869, 'lng' => -49.2648],
            ['city' => 'Florianópolis', 'state' => 'SC', 'lat' => -27.5954, 'lng' => -48.5480],
            ['city' => 'Campinas', 'state' => 'SP', 'lat' => -22.9099, 'lng' => -47.0626],
            ['city' => 'Vitória', 'state' => 'ES', 'lat' => -20.3155, 'lng' => -40.3128],
            ['city' => 'Cuiabá', 'state' => 'MT', 'lat' => -15.6014, 'lng' => -56.0979],
            ['city' => 'Campo Grande', 'state' => 'MS', 'lat' => -20.4697, 'lng' => -54.6201],
            ['city' => 'Natal', 'state' => 'RN', 'lat' => -5.7945, 'lng' => -35.2110],
            ['city' => 'João Pessoa', 'state' => 'PB', 'lat' => -7.1195, 'lng' => -34.8450],
            ['city' => 'Maceió', 'state' => 'AL', 'lat' => -9.6498, 'lng' => -35.7089],
            ['city' => 'Teresina', 'state' => 'PI', 'lat' => -5.0892, 'lng' => -42.8019],
            ['city' => 'São Luís', 'state' => 'MA', 'lat' => -2.5307, 'lng' => -44.3068],
            ['city' => 'Palmas', 'state' => 'TO', 'lat' => -10.2491, 'lng' => -48.3243],
            ['city' => 'Boa Vista', 'state' => 'RR', 'lat' => 2.8235, 'lng' => -60.6758],
            ['city' => 'Macapá', 'state' => 'AP', 'lat' => 0.0349, 'lng' => -51.0694],
            ['city' => 'Porto Velho', 'state' => 'RO', 'lat' => -8.7612, 'lng' => -63.9004],
            ['city' => 'Rio Branco', 'state' => 'AC', 'lat' => -9.9740, 'lng' => -67.8243],
            ['city' => 'Uberlândia', 'state' => 'MG', 'lat' => -18.9186, 'lng' => -48.2772],
            ['city' => 'Londrina', 'state' => 'PR', 'lat' => -23.3045, 'lng' => -51.1696],
            ['city' => 'Ribeirão Preto', 'state' => 'SP', 'lat' => -21.1775, 'lng' => -47.8103],
            ['city' => 'Santarém', 'state' => 'PA', 'lat' => -2.4431, 'lng' => -54.7083],
            ['city' => 'Altamira', 'state' => 'PA', 'lat' => -3.2039, 'lng' => -52.2060],
            ['city' => 'Sinop', 'state' => 'MT', 'lat' => -11.8604, 'lng' => -55.5095],
            ['city' => 'Alta Floresta', 'state' => 'MT', 'lat' => -9.8661, 'lng' => -56.0861],
            ['city' => 'Marabá', 'state' => 'PA', 'lat' => -5.3686, 'lng' => -49.1178],
            ['city' => 'Ji-Paraná', 'state' => 'RO', 'lat' => -10.8777, 'lng' => -61.9322],
            ['city' => 'Ilhéus', 'state' => 'BA', 'lat' => -14.7930, 'lng' => -39.0460],
            ['city' => 'Montes Claros', 'state' => 'MG', 'lat' => -16.7282, 'lng' => -43.8578],
            ['city' => 'Petrolina', 'state' => 'PE', 'lat' => -9.3891, 'lng' => -40.5030],
            ['city' => 'Blumenau', 'state' => 'SC', 'lat' => -26.9194, 'lng' => -49.0661],
        ];
    }

    /**
     * @return list<array{name: string, scientific: string}>
     */
    private function species(): array
    {
        return [
            ['name' => 'Ipê-amarelo', 'scientific' => 'Handroanthus albus'],
            ['name' => 'Ipê-roxo', 'scientific' => 'Handroanthus impetiginosus'],
            ['name' => 'Ipê-rosa', 'scientific' => 'Handroanthus heptaphyllus'],
            ['name' => 'Pau-brasil', 'scientific' => 'Paubrasilia echinata'],
            ['name' => 'Jatobá', 'scientific' => 'Hymenaea courbaril'],
            ['name' => 'Aroeira', 'scientific' => 'Schinus terebinthifolia'],
            ['name' => 'Jacarandá-mimoso', 'scientific' => 'Jacaranda mimosifolia'],
            ['name' => 'Cedro', 'scientific' => 'Cedrela fissilis'],
            ['name' => 'Guapuruvu', 'scientific' => 'Schizolobium parahyba'],
            ['name' => 'Ingá', 'scientific' => 'Inga edulis'],
            ['name' => 'Pitangueira', 'scientific' => 'Eugenia uniflora'],
            ['name' => 'Jabuticabeira', 'scientific' => 'Plinia cauliflora'],
            ['name' => 'Araucária', 'scientific' => 'Araucaria angustifolia'],
            ['name' => 'Pau-ferro', 'scientific' => 'Libidibia ferrea'],
            ['name' => 'Sibipiruna', 'scientific' => 'Cenostigma pluviosum'],
            ['name' => 'Quaresmeira', 'scientific' => 'Tibouchina granulosa'],
            ['name' => 'Manacá-da-serra', 'scientific' => 'Tibouchina mutabilis'],
            ['name' => 'Copaíba', 'scientific' => 'Copaifera langsdorffii'],
            ['name' => 'Mogno-brasileiro', 'scientific' => 'Swietenia macrophylla'],
            ['name' => 'Andiroba', 'scientific' => 'Carapa guianensis'],
            ['name' => 'Castanheira', 'scientific' => 'Bertholletia excelsa'],
            ['name' => 'Jequitibá-rosa', 'scientific' => 'Cariniana legalis'],
            ['name' => 'Pau-formiga', 'scientific' => 'Triplaris americana'],
            ['name' => 'Embaúba', 'scientific' => 'Cecropia pachystachya'],
            ['name' => 'Angico-vermelho', 'scientific' => 'Anadenanthera colubrina'],
            ['name' => 'Baru', 'scientific' => 'Dipteryx alata'],
            ['name' => 'Pequi', 'scientific' => 'Caryocar brasiliense'],
            ['name' => 'Cajueiro', 'scientific' => 'Anacardium occidentale'],
            ['name' => 'Açaizeiro', 'scientific' => 'Euterpe oleracea'],
            ['name' => 'Juçara', 'scientific' => 'Euterpe edulis'],
        ];
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function suppliers(): array
    {
        return [
            ['id' => 'viveiro-municipal', 'name' => 'Viveiro Municipal'],
            ['id' => 'instituto-reflora', 'name' => 'Instituto Reflora'],
            ['id' => 'cooperflora', 'name' => 'Cooperflora'],
            ['id' => 'sementes-da-mata', 'name' => 'Sementes da Mata'],
            ['id' => 'viveiro-da-serra', 'name' => 'Viveiro da Serra'],
            ['id' => 'amigos-da-floresta', 'name' => 'Amigos da Floresta'],
            ['id' => 'nascentes-vivas', 'name' => 'Nascentes Vivas'],
            ['id' => 'mata-nativa', 'name' => 'Mata Nativa Mudas'],
        ];
    }

    /**
     * @return list<string>
     */
    private function locationNames(): array
    {
        return [
            'Parque Municipal',
            'Área de reflorestamento',
            'APP do rio',
            'Nascente recuperada',
            'Horta comunitária',
            'Escola Municipal',
            'Praça central',
            'Sítio São José',
            'Fazenda Esperança',
            'Corredor ecológico',
            'Margem de estrada vicinal',
            'Reserva particular',
            'Campus universitário',
            'Área degradada em recuperação',
            'Bosque da cidade',
        ];
    }

    /**
     * @return list<string>
     */
    private function observations(): array
    {
        return [
            'Plantio realizado com a comunidade local.',
            'Mudas doadas pelo viveiro municipal.',
            'Área de preservação permanente em recuperação.',
            'Ação de educação ambiental com estudantes.',
            'Substituição de pastagem por espécies nativas.',
            'Plantio em dia de mutirão.',
            'Mudas adaptadas ao bioma local.',
            'Primeira etapa do projeto de restauração.',
        ];
    }
}
