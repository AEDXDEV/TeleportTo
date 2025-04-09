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
use pocketmine\world\Position;
use AEDXDEV\TeleportTo\Main;

abstract class TeleportEffect {

  protected const MAX_STEPS = 0;

  protected int $tick = 0;

  protected bool $stop = false;

  public function __construct(
    private Player $player,
    private int $id
  ){
    // NOPE
  }

  public function start(): void{
    $this->player->sendMessage(Main::getInstance()->getMessage("TeleportStart"));
  }

  public function tick(): bool{
    if (!$this->player->isOnline()) {
      $this->stop = true;
      return false;
    }
    $this->tick++;
    return true;
  }

  public function end(): void{
    $this->player->sendMessage(Main::getInstance()->getMessage("TeleportEnd"));
  }

  public function getPlayer(): Player{
    return $this->player;
  }

  public function getFrom(): Position{
    return Main::getInstance()->toPos(Main::getInstance()->getDB()->get($this->id)["From"]);
  }

  public function getTo(): Position{
    return Main::getInstance()->toPos(Main::getInstance()->getDB()->get($this->id)["To"]);
  }

  protected function playSound(string $sound, float $volume = 1.0): void{
    Main::getInstance()->playSound($this->player, $sound, $volume);
  }

  public function onDamage(): bool{
    $this->stop = true;
    return true;
  }
}