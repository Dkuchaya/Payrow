<?php

namespace App\Services;

use App\Models\Company;

class CompanyContext
{
    protected ?Company $company = null;

    public function set(Company $company): void
    {
        $this->company = $company;
    }

    public function get(): ?Company
    {
        return $this->company;
    }

    public function id(): ?int
    {
        return $this->company?->id;
    }

    public function hasCompany(): bool
    {
        return $this->company !== null;
    }

    public function clear(): void
    {
        $this->company = null;
    }
}