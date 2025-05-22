<?php
namespace GIG\Domain\Entities;

defined('_RUNKEY') or die;

class User
{
    public ?int $id = null;
    public ?string $login = null;
    public ?string $password = null;
    public ?string $email = null;
    public ?string $id_from_1c = null;
    public ?int $bage_number = null;
    public ?string $full_name = null;
    public ?string $first_name = null;
    public ?string $last_name = null;
    public ?string $middle_name = null;
    public ?string $birthday = null;
    public ?int $company_id = null;
    public ?int $division_id = null;
    public ?int $position_id = null;
    public string $source = 'manual'; // ENUM('ldap','perco','manual')
    public int $is_active = 1;
    public int $is_blocked = 0;
    public ?string $created_at = null;
    public int $is_fulfilled  = 0;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $property = strtolower($key);
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function toArray(array $only = []): array
    {
        $all = [
            'id'           => $this->id,
            'login'        => $this->login,
            'password'     => $this->password,
            'email'        => $this->email,
            'id_from_1c'   => $this->id_from_1c,
            'bage_number'  => $this->bage_number,
            'full_name'    => $this->full_name,
            'first_name'   => $this->first_name,
            'last_name'    => $this->last_name,
            'middle_name'  => $this->middle_name,
            'birthday'     => $this->birthday,
            'company_id'  => $this->company_id,
            'division_id'  => $this->division_id,
            'position_id'  => $this->position_id,
            'source'       => $this->source,
            'is_active'    => $this->is_active,
            'is_blocked'   => $this->is_blocked,
            'is_fulfilled' => $this->is_fulfilled,
            'created_at'   => $this->created_at,
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
        if (!empty($this->full_name)) {
            return $this->full_name;
        }
        $parts = array_filter([
            $this->last_name ?? null,
            $this->first_name ?? null,
            $this->middle_name ?? null,
        ]);
        if ($parts) {
            return trim(implode(' ', $parts));
        }
        return $this->login ?? '';
    }

    public function isActive(): bool
    {
        return $this->is_active && !$this->is_blocked;
    }
}
