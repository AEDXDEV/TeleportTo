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

namespace AEDXDEV\TeleportTo\task;

use AEDXDEV\TeleportTo\Main;
use pocketmine\scheduler\Task;
use pocketmine\block\utils\DyeColor;
use pocketmine\world\Position;
use pocketmine\world\particle\DustParticle;

class ParticleTask extends Task {
	
	public function __construct(){
	  // NOPE [;
	}
	
	public function onRun(): void{
  	foreach (Main::getInstance()->getDB()->getAll() as $id => $data){
  	  if (!$data["EnableParticles"])continue;
  	  $this->spawnParticles($data["From"], self::strToColor($data["From"]["Particle"]));
  	  $this->spawnParticles($data["To"], self::strToColor($data["To"]["Particle"]));
    }
	}
	
	private function spawnParticles(array $data, ?DyeColor $color): void{
	  if ($color == null)return;
	  $pos = Main::getInstance()->toPos($data);
	  //for ($i = 0; $i < 15; $i++) {
	  for ($i = 0; $i < 360; $i += 15) {
	    //$particlePos = $pos->add(
        //mt_rand(-5, 5) / 5,
  	    //mt_rand(-5, 5) / 3,
  	    //mt_rand(-5, 5) / 5
      //);
	    $particlePos = $pos->add(
        cos(deg2rad($i)) * 0.75,
  	    mt_rand(-5, 5) / 3,
  	    sin(deg2rad($i)) * 0.75
      );
	    $pos->getWorld()->addParticle($particlePos, new DustParticle($color->getRgbValue()));
	  }
	}
	
	// a bad function i didn't want use it ]:
	/*private function createParticleLine(Position $from, Position $to): void{
	  if ($from->getWorld()->getFolderName() !== $to->getWorld()->getFolderName())return;
	  for ($i = 0; $i < ($steps = max(5, floor($from->distance($to)))); $i++) {
	    $progress = $i / $steps;
	    $x = $from->x + ($to->x - $from->x) * $progress;
	    $y = $from->y + ($to->y - $from->y) * $progress;
	    $z = $from->z + ($to->z - $from->z) * $progress;
	    $particle = new DustParticle(new Color(
	      255 - (int)(progress * 255),
	      (int)(progress * 165),
	      0
	    ));
	    $from->getWorld()->addParticle(new Position($x, $y, $z, $from->getWorld()), $particle);
	  }
	}*/
	
	private static function strToColor(string $colorName): ?DyeColor{
	  if ($colorName == "Air")return null;
	  foreach (DyeColor::getAll() as $color) {
	    if (strtolower($color->getDisplayName()) == strtolower($colorName)) return $color;
	  }
	  return DyeColor::WHITE();
	}
}
