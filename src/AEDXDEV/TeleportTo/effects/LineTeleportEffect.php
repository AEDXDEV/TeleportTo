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

use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\player\Player;

use AEDXDEV\TeleportTo\Main;

use pocketmine\network\mcpe\protocol\CameraShakePacket;

class LineTeleportEffect extends TeleportEffect {

  private array $path = [];
  private int $currentStep = 0;
  private float $yOffset = 0;

  public function start(): void{
    parent::start();
    $this->generatePath();
    $this->getPlayer()->getNetworkSession()->sendDataPacket(CameraShakePacket::create(2.0, 1.0, CameraShakePacket::TYPE_POSITIONAL, CameraShakePacket::ACTION_ADD));
    $this->playSound("random.orb", 1.2);
  }

  public function tick(): bool{
    if (!parent::tick()) return false;
    $progress = $this->tick / 20;
    if ($progress < 1.5) {
      $this->handlePreTeleport();
    } elseif ($progress < 3.0) {
      $this->handleDescent();
    } elseif ($progress < 5.0) {
      $this->drawPath();
    } else {
      $this->handleAscent();
    }
    return $this->tick < 120;
  }

  private function handlePreTeleport(): void{
    $from = $this->getFrom();
    for ($i = 0; $i < 360; $i += 5) {
      $radius = 1 + ($this->tick * 0.1);
      $angle = deg2rad($i);
      $pos = $from->add(
        $radius * cos($angle),
        mt_rand(0, 10) / 10,
        $radius * sin($angle)
      );
      $this->spawnBlockParticle($pos);
    }
  }

  private function handleDescent(): void{
    $player = $this->getPlayer();
    $this->yOffset -= 0.1;
    $newPos = $player->getPosition()->add(0, $this->yOffset, 0);
    $player->teleport($newPos);
    for ($i = 0; $i < 5; $i++) {
      $offset = new Vector3(
        mt_rand(-5, 5) / 10,
        mt_rand(-5, 5) / 10,
        mt_rand(-5, 5) / 10
      );
      $this->spawnBlockParticle($newPos->addVector($offset));
    }
    $this->spawnBlockParticle($newPos->add(0, -1, 0));
  }

  private function drawPath(): void{
    if ($this->currentStep < count($this->path)) {
      $pos = $this->path[$this->currentStep];
      $pos = $pos->add(0, 1, 0);
      $this->spawnBlockParticle($pos);
      if (mt_rand(0, 2) === 0) {
        $this->spawnBlockParticle($pos->asVector3()->add(
          mt_rand(-5, 5) / 10,
          mt_rand(-5, 5) / 10,
          mt_rand(-5, 5) / 10
        ));
      }
      $this->currentStep++;
    }
  }

  private function handleAscent(): void{
    $player = $this->getPlayer();
    $this->yOffset += 0.15;
    $target = $this->getTo()->add(0, $this->yOffset, 0);
    $player->teleport($target);
    for ($i = 0; $i < 8; $i++) {
      $angle = deg2rad(mt_rand(0, 360));
      $radius = mt_rand(0, 10) / 10;
      $pos = $target->asVector3()->add(
        $radius * cos($angle),
        mt_rand(0, 10) / 10,
        $radius * sin($angle)
      );
      $this->spawnBlockParticle($pos);
    }
    if ($this->yOffset >= 1.5) {
      $player->getNetworkSession()->sendDataPacket(CameraShakePacket::create(3.0, 0.5, CameraShakePacket::TYPE_POSITIONAL, CameraShakePacket::ACTION_ADD));
      $this->spawnFinalExplosion();
      $player->teleport($this->getTo());
    }
  }

  private function generatePath(): void {
    $from = $this->getFrom()->asVector3();
    $to = $this->getTo()->asVector3();
    $steps = max(abs($to->x - $from->x), abs($to->y - $from->y), abs($to->z - $from->z));
    for ($i = 0; $i <= $steps; $i++) {
      $x = $from->x + (($to->x - $from->x) * ($i / $steps));
      $y = $from->y + (($to->y - $from->y) * ($i / $steps));
      $z = $from->z + (($to->z - $from->z) * ($i / $steps));
      $this->path[] = new Vector3($x, ($y - 1), $z);
    }
  }

  private function spawnBlockParticle(Vector3 $pos): void{
    $world = $this->getFrom()->getWorld();
    $block = $world->getBlock($pos->asVector3()->add(0, -1, 0));
    $world->addParticle($pos, new BlockBreakParticle($block));
  }

  private function spawnFinalExplosion(): void{
    for ($i = 0; $i < 360; $i += 5) {
      $angle = deg2rad($i);
      $radius = 1 + (mt_rand(0, 10) / 10);
      $pos = $this->getTo()->add(
        $radius * cos($angle),
        mt_rand(0, 10) / 10,
        $radius * sin($angle)
      );
      $this->spawnBlockParticle($pos);
    }
    $this->playSound("random.explode", 1.5);
  }
}