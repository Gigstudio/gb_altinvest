<?php
namespace GIG\Domain\Entities;

defined('_RUNKEY') or die;

class PercoUser
{
    public ?int $id = null;
    public ?string $fio = null;
    public ?int $division_id = null;
    public ?string $division_name = null;
    public ?string $identifier = null;
    public int $is_block = 0;
    public ?string $updated_at = null;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $property = strtolower($key);
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /** Универсальный вход */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /** Фабрика для массового формата (fetchAllUsersFromList) */
    public static function fromApiArray(array $data): self
    {
        // cards - массив, берем только первый элемент для поля identifier
        $identifier = isset($data['cards'][0]) ? $data['cards'][0] : null;

        return new self([
            'id'             => isset($data['id']) ? (int)$data['id'] : null,
            'fio'            => isset($data['name']) ? trim($data['name']) : null,
            'division_id'    => isset($data['division_id']) ? (int)$data['division_id'] : null,
            'division_name'  => $data['division_name'] ?? null,
            'identifier'     => $identifier,
            'is_block'       => isset($data['is_block']) ? (int)$data['is_block'] : 0,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    /** Фабрика для детального формата (getUserInfoById) */
    public static function fromDetailedArray(array $data): self
    {
        // identifier: массив, берем первый ['identifier']
        $identifier = null;
        if (!empty($data['identifier'][0]['identifier'])) {
            $identifier = $data['identifier'][0]['identifier'];
        }

        // fio — склеиваем last_name, first_name, middle_name
        $fio = trim(
            ($data['last_name'] ?? '') . ' ' .
            ($data['first_name'] ?? '') . ' ' .
            ($data['middle_name'] ?? '')
        );
        $fio = preg_replace('/\s+/', ' ', $fio);

        // division: массив (id => name), берем первый
        $division_id = null;
        $division_name = null;
        if (!empty($data['division']) && is_array($data['division'])) {
            foreach ($data['division'] as $id => $name) {
                $division_id = (int)$id;
                $division_name = $name;
                break;
            }
        }

        return new self([
            'id'             => isset($data['id']) ? (int)$data['id'] : null,
            'fio'            => $fio,
            'division_id'    => $division_id,
            'division_name'  => $division_name,
            'identifier'     => $identifier,
            'is_block'       => isset($data['is_block']) ? (int)$data['is_block'] : 0,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    public function toArray(array $only = []): array
    {
        $all = [
            'id'             => $this->id,
            'fio'            => $this->fio,
            'division_id'    => $this->division_id,
            'division_name'  => $this->division_name,
            'identifier'     => $this->identifier,
            'is_block'       => $this->is_block,
            'updated_at'     => $this->updated_at,
        ];
        if (empty($only)) {
            return $all;
        }
        return array_filter(
            $all,
            fn($key) => in_array($key, $only, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function getFullName(): string
    {
        return $this->fio ?? '';
    }
}
