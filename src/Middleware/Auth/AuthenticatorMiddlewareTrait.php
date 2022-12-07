<?php
namespace Pyncer\App\Middleware\Auth;

trait AuthenticatorMiddlewareTrait
{
    private string $userMapperAdaptorIdentifier;
    private string $realm;
    private bool $allowGuests;

    public function getUserMapperAdaptorIdentifier(): ?string
    {
        return $this->userMapperAdaptorIdentifier;
    }
    public function setUserMapperAdaptorIdentifier(string $value): static
    {
        $this->userMapperAdaptorIdentifier = $value;
        return $this;
    }

    protected function getRealm(): string
    {
        return $this->realm;
    }
    protected function setRealm(string $value): static
    {
        $this->realm = $value;
        return $this;
    }

    protected function getAllowGuests(): bool
    {
        return $this->allowGuests;
    }
    protected function setAllowGuests(bool $value): static
    {
        $this->allowGuests = $value;
        return $this;
    }
}
