<?php

namespace Tests\Unit;

use Core\Role;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
  public function testUserCanNotEditAnyMessage(): void
  {
    $this->assertFalse(Role::USER->canEditAnyMessage());
  }

  public function testAdminCanEditAnyMessage(): void
  {
    $this->assertTrue(Role::ADMIN->canEditAnyMessage());
  }

  public function testUserCanNotManageUsers(): void
  {
    $this->assertFalse(Role::USER->canManageUsers());
  }

  public function testAdminCanManageUsers(): void
  {
    $this->assertTrue(Role::ADMIN->canManageUsers());
  }

  public function testUserCanNotToggleMessageStatus(): void
  {
    $this->assertFalse(Role::USER->canToggleMessageStatus());
  }

  public function testAdminCanToggleMessageStatus(): void
  {
    $this->assertTrue(Role::ADMIN->canToggleMessageStatus());
  }

  public function testUserCanNotDeleteMessages(): void
  {
    $this->assertFalse(Role::USER->canDeleteMessages());
  }

  public function testAdminCanDeleteMessages(): void
  {
    $this->assertTrue(Role::ADMIN->canDeleteMessages());
  }

  public function testUserLabel(): void
  {
    $this->assertEquals('User', Role::USER->label());
  }

  public function testAdminLabel(): void
  {
    $this->assertEquals('Admin', Role::ADMIN->label());
  }

  public function testTryFromWithValidRole(): void
  {
    $role = Role::tryFrom(1);
    $this->assertInstanceOf(Role::class, $role);
    $this->assertEquals(Role::USER, $role);

    $adminRole = Role::tryFrom(2);
    $this->assertInstanceOf(Role::class, $adminRole);
    $this->assertEquals(Role::ADMIN, $adminRole);
  }

  public function testTryFromWithInvalidRole(): void
  {
    $result = Role::tryFrom(99);
    $this->assertNull($result);
  }
}
