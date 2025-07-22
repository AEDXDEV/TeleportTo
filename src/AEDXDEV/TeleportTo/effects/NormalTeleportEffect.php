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

use pocketmine\player\Player;

use AEDXDEV\TeleportTo\Main;

class NormalTeleportEffect extends TeleportEffect{

  protected const MAX_STEPS = 10;

  public function start(): void{
    parent::start();
    $this->playSound("random.orb", 1.5);
  }

  public function tick(): bool{
    if (!parent::tick())return false;
    if ($this->tick >= self::MAX_STEPS) {
      $this->getPlayer()->teleport($this->getTo());
      $this->stop = true;
    }
    return !$this->stop;
  }

  public function end(): void{
    parent::end();
    $this->playSound("random.orb", 1.5);
  }
}