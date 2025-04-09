<?php

/**
  *  A free plugin for PocketMine-MP.
  *	
  *	Copyright (c) AEDXDEV
  *  
  *	Youtube: AEDX DEV 
  *	Discord: aedxdev
  *	Github: AEDXDEV
  *	Email: aedxdev@gmail.com
  *	Donate: https://paypal.me/AEDXDEV
  *   
  *        This program is free software: you can redistribute it and/or modify
  *        it under the terms of the GNU General Public License as published by
  *        the Free Software Foundation, either version 3 of the License, or
  *        (at your option) any later version.
  *
  *        This program is distributed in the hope that it will be useful,
  *        but WITHOUT ANY WARRANTY; without even the implied warranty of
  *        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *        GNU General Public License for more details.
  *
  *        You should have received a copy of the GNU General Public License
  *        along with this program.  If not, see <http://www.gnu.org/licenses/>.
  *         
  */

namespace AEDXDEV\TeleportTo\effects;

use pocketmine\math\Vector3;
use pocketmine\world\particle\{
  FlameParticle,
  ExplodeParticle
};
use pocketmine\world\Position;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class FlameTeleportEffect extends TeleportEffect{

  protected const MAX_STEPS = 40;

  public function start(): void{
    parent::start();
    $this->playSound("fire.ignite");
  }

  public function tick(): bool{
    if (!parent::tick()) return false;
    $player = $this->getPlayer();
    $from = $this->getFrom();
    $to = $this->getTo();
    if ($this->tick < self::MAX_STEPS / 2) {
      $this->createFlameSpiral($from, $this->tick);
      $this->createFallingFlames($to, $this->tick);
    }
    if ($this->tick === self::MAX_STEPS / 2) {
      $player->teleport($to);
      $this->playSound("random.fizz");
      $to->getWorld()->addParticle($to, new ExplodeParticle());
    }
    if ($this->tick >= self::MAX_STEPS) {
      $this->stop = true;
    }
    return !$this->stop;
  }

  public function end(): void{
    parent::end();
    $this->playSound("fire.extinguish");
  }

  private function createFlameSpiral(Position $pos, int $tick): void{
    $radius = 0.5 + ($tick * 0.05); 
    $angle = deg2rad($tick * 15);
    $x = $pos->x + $radius * cos($angle);
    $z = $pos->z + $radius * sin($angle);
    $pos->getWorld()->addParticle(new Vector3($x, ($pos->y + ($tick * 0.1)), $z), new FlameParticle());
  }

  private function createFallingFlames(Position $pos, int $tick): void{
    $pos->getWorld()->addParticle(new Vector3($pos->x, ($pos->y + 3 - ($tick * 0.1)), $pos->z), new FlameParticle());
  }
}