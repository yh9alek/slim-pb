<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;
use Faker\Factory;

final class TaskSeeder extends AbstractSeed
{
    private const int COUNT = 20;

    public function run(): void
    {
        $faker = Factory::create('es_ES');

        $actions = [
            'Revisar', 'Enviar', 'Preparar', 'Actualizar', 'Llamar a',
            'Escribir', 'Planificar', 'Corregir', 'Documentar', 'Publicar',
        ];

        $rows = [];
        for ($i = 0; $i < self::COUNT; $i++) {
            $title = $faker->randomElement($actions)
                . ' ' . $faker->words($faker->numberBetween(1, 3), true);

            $rows[] = [
                'title' => mb_substr($title, 0, 255),
                'completed' => $faker->boolean(30) ? 1 : 0,   // ~30% completadas
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        $this->execute('DELETE FROM tasks');

        $this->table('tasks')->insert($rows)->saveData();
    }
}
