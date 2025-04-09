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

use pocketmine\world\particle\{
  DustParticle,
  FlameParticle,
  PortalParticle,
  EnchantmentTableParticle
};
use pocketmine\world\Position;
use pocketmine\player\Player;
use pocketmine\color\Color;

use AEDXDEV\TeleportTo\Main;

class AnimeTeleportEffect extends TeleportEffect{

  protected const MAX_STEPS = 40;

  public function start(): void{
    parent::start();
    $this->playSound("random.orb", 1.5);
  }

  public function tick(): bool{
    if (!parent::tick())return false;
    if ($this->tick < 10) {
      $this->handlePreTeleport();
    } elseif ($this->tick === 10) {
      $this->handleTeleport();
    } elseif ($this->tick < self::MAX_STEPS) {
      $this->handlePostTeleport();
    } else {
      $this->stop = true;
    }
    return !$this->stop;
  }

  public function end(): void{
    parent::end();
    $this->playSound("random.orb", 1.5);
  }
  
  private function handlePreTeleport(): void{
    $from = $this->getFrom();
    for ($i = 0; $i < 360; $i += 5) {
      $radius = 1 + ($this->tick * 0.1);
      $x = $radius * cos(deg2rad($i));
      $z = $radius * sin(deg2rad($i));
      $from->getWorld()->addParticle($from->asVector3()->add($x, 0.1, $z), new DustParticle(new Color(255, 50, 150)));
    }
    for ($y = 0; $y < 2; $y += 0.1) {
      $from->getWorld()->addParticle($from->asVector3()->add(0, $y, 0), new EnchantmentTableParticle());
    }
    if ($this->tick % 2 === 0) {
      $this->getPlayer()->knockBack(mt_rand(-1, 1) / 10, mt_rand(-1, 1) / 10, 0.1);
    }
  }

  private function handleTeleport(): void{
    $player = $this->getPlayer();
    $from = $this->getFrom();
    $to = $this->getTo();
    for ($i = 0; $i < 360; $i += 10) {
      $radius = 2;
      $x = $radius * cos(deg2rad($i));
      $z = $radius * sin(deg2rad($i));
      $from->getWorld()->addParticle($from->asVector3()->add($x, 1, $z), new PortalParticle());
    }
    for ($i = 0; $i < 10; $i++) {
      $from->getWorld()->addParticle($from->asVector3()->add(0, 1, 0), new DustParticle(new Color(255, 255, 255)));
    }
    $player->teleport($to);
    for ($i = 0; $i < 360; $i += 10) {
      $radius = 2;
      $x = $radius * cos(deg2rad($i));
      $z = $radius * sin(deg2rad($i));
      $to->getWorld()->addParticle($to->asVector3()->add($x, 1, $z), new FlameParticle());
    }
    $this->playSound("random.portal");
  }

  private function handlePostTeleport(): void{
    for ($y = 2; $y > 0; $y -= 0.1) {
      for ($i = 0; $i < 8; $i++) {
        $x = mt_rand(-10, 10) / 10;
        $z = mt_rand(-10, 10) / 10;
        $this->getTo()->getWorld()->addParticle($this->getTo()->asVector3()->add($x, $y, $z), new DustParticle(new Color(0, 255, 255)));
      }
    }
  }
}