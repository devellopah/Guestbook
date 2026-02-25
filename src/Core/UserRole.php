<?php

namespace Core;

enum Role: int
{
  case USER = 1;
  case ADMIN = 2;

  public function label(): string
  {
    return match ($this) {
      self::USER => 'User',
      self::ADMIN => 'Admin',
    };
  }

  public function canEditAnyMessage(): bool
  {
    return $this === self::ADMIN;
  }

  public function canManageUsers(): bool
  {
    return $this === self::ADMIN;
  }

  public function canToggleMessageStatus(): bool
  {
    return $this === self::ADMIN;
  }

  public function canDeleteMessages(): bool
  {
    return $this === self::ADMIN;
  }
}
