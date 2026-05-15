<?php

namespace Flute\Modules\PlayerPreferences\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Index;
use Cycle\ORM\Entity\Behavior;

#[Entity(table: 'player_preferences', role: 'player_preference')]
#[Behavior\CreatedAt(field: 'createdAt', column: 'created_at')]
#[Behavior\UpdatedAt(field: 'updatedAt', column: 'updated_at')]
#[Index(columns: ['user_id', 'server_id', 'setting_key'], unique: true)]
class PlayerPreference extends ActiveRecord
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'integer', name: 'user_id')]
    public int $userId;

    #[Column(type: 'integer', name: 'server_id')]
    public int $serverId;

    #[Column(type: 'string', size: 255, name: 'setting_key')]
    public string $key;

    #[Column(type: 'text')]
    public string $value;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $createdAt;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $updatedAt;
}
