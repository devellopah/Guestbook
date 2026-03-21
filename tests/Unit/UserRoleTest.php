<?php

namespace App\Tests\Unit;

use Core\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
  public function testUserCanNotEditAnyMessage(): void
  {
    $this->assertFalse(UserRole::USER->canEditAnyMessage());
  }

  public function testAdminCanEditAnyMessage(): void
  {
    $this->assertTrue(UserRole::ADMIN->canEditAnyMessage());
  }

  public function testUserCanNotManageUsers(): void
  {
    $this->assertFalse(UserRole::USER->canManageUsers());
  }

  public function testAdminCanManageUsers(): void
  {
    $this->assertTrue(UserRole::ADMIN->canManageUsers());
  }

  public function testUserCanNotToggleMessageStatus(): void
  {
    $this->assertFalse(UserRole::USER->canToggleMessageStatus());
  }

  public function testAdminCanToggleMessageStatus(): void
  {
    $this->assertTrue(UserRole::ADMIN->canToggleMessageStatus());
  }

  public function testUserCanNotDeleteMessages(): void
  {
    $this->assertFalse(UserRole::USER->canDeleteMessages());
  }

  public function testAdminCanDeleteMessages(): void
  {
    $this->assertTrue(UserRole::ADMIN->canDeleteMessages());
  }

  public function testUserLabel(): void
  {
    $this->assertEquals('User', UserRole::USER->label());
  }

  public function testAdminLabel(): void
  {
    $this->assertEquals('Admin', UserRole::ADMIN->label());
  }

  public function testTryFromWithValidRole(): void
  {
    $role = UserRole::tryFrom(1);
    $this->assertInstanceOf(UserRole::class, $role);
    $this->assertEquals(UserRole::USER, $role);

    $adminRole = UserRole::tryFrom(2);
    $this->assertInstanceOf(UserRole::class, $adminRole);
    $this->assertEquals(UserRole::ADMIN, $adminRole);
  }

  public function testTryFromWithInvalidRole(): void
  {
    $result = UserRole::tryFrom(99);
    $this->assertNull($result);
  }
}
