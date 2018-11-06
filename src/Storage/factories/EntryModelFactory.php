<?php

use Laravel\Telescope\EntryType;
use Laravel\Telescope\Storage\EntryModel;

/*
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

$factory->define(EntryModel::class, function (Faker\Generator $faker) {
    return [
        'sequence' => random_int(1, 10000),
        'uuid' => $faker->uuid,
        'batch_id' => $faker->uuid,
        'type' => $faker->randomElement([
            EntryType::CACHE, EntryType::COMMAND, EntryType::DUMP, EntryType::EVENT, EntryType::EXCEPTION,
            EntryType::JOB, EntryType::LOG, EntryType::MAIL, EntryType::MODEL, EntryType::NOTIFICATION,
            EntryType::QUERY, EntryType::REDIS, EntryType::REQUEST, EntryType::SCHEDULED_TASK,
        ]),
        'content' => [$faker->word => $faker->word],
    ];
});
