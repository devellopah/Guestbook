<?php

namespace Queue;

interface Job
{
  public function handle(): void;
  public function getName(): string;
}
