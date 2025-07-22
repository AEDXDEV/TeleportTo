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

namespace AEDXDEV\TeleportTo;

use AEDXDEV\TeleportTo\command\TpToCommand;
use AEDXDEV\TeleportTo\effects\NormalTeleportEffect;
use AEDXDEV\TeleportTo\effects\AnimeTeleportEffect;
use AEDXDEV\TeleportTo\effects\LineTeleportEffect;
use AEDXDEV\TeleportTo\effects\FlameTeleportEffect;
use AEDXDEV\TeleportTo\task\ParticleTask;
use AEDXDEV\TeleportTo\task\EffectTask;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\item\Item;
use pocketmine\block\utils\DyeColor;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\item\ItemTypeIds;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\Enchantment;

use AEDXDEV\TeleportTo\libs\Vecnavium\FormsUI\CustomForm;
use AEDXDEV\TeleportTo\libs\Vecnavium\FormsUI\ModalForm;

class Main extends PluginBase implements Listener{
  
  use SingletonTrait;
  
  public const prefix = "§aTeleport§bTo";
  
  public const cmdPrefix = self::prefix . " §f>§9> ";
  
  private Config $config;
  
  private Config $db;
  
  private const effects = [
    "normal" => NormalTeleportEffect::class,
    "anime" => AnimeTeleportEffect::class,
    "line" => LineTeleportEffect::class,
    "flame" => FlameTeleportEffect::class
  ];
  
  private array $activedEffects = [];
  
  private array $setupMode = [];
  
  private array $retrieveIdMode = [];
  
  public function onEnable(): void{
    self::setInstance($this);
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getCommandMap()->register("teleportto", new TpToCommand($this));
    $this->db = new Config($this->getDataFolder() . "db.yml", 2, []);
    EnchantmentIdMap::getInstance()->register(-1, new Enchantment("Glow", 1, 65535, 1, 1));
    $this->config = new Config($this->getDataFolder() . "config.yml", 2, [
      "Messages" => [
        "TeleportStart" => "§aTelepott started!",
        "TeleportEnd" => "§aThe teleport is end!",
        "TeleportItem" => "§aYou got the TeleportTo item!",
        "TeleportPlace" => "§cYou can't add a Teleport here.",
        "SaveTeleport" => "§aThe Teleport §8[§7{ID}§8] §awas saved successfully!",
        "TeleportError" => "§cFailed to save Teleport.",
        "RetrieveIdModeActived" => "§aID Retrieval Mode Activated!\n§7Left-click any teleport block to get its ID!",
        "InRetrieveIdMode" => "§eYou're already in ID retrieval mode.",
        "TeleportRemove" => "§aThe Teleport §8[§7{ID}§8] §ahas been removed successfully!",
        "TeleportNotFound" => "§cTeleport[§4{ID}§c] is not found",
      ],
      "TeleportDistance" => 1.4,
      "TeleportEffect" => "normal",
      "DeleteForm" => [
        "teleports-enable" => true,
        "teleports-count" => 25,
      ]
    ]);
    $this->getScheduler()->scheduleRepeatingTask(new ParticleTask(), 11);
    $this->getScheduler()->scheduleRepeatingTask(new EffectTask(), 1);
	}
	
	public function onClick(PlayerInteractEvent $event): void{
	  $player = $event->getPlayer();
	  $name = $player->getName();
    $blockPos = $event->getBlock()->getPosition();
    $pos = Position::fromObject($blockPos->asVector3()->getSide($event->getFace()), $blockPos->getWorld());
	  // get Id
	  if ($this->isInRetrieveIdMode($player)) {
	    $id = array_search(true, array_map(fn($data) => $this->toPos($data["From"])->distance($pos) < 2 || $this->toPos($data["To"])->distance($pos) < 2, $this->getDB()->getAll()), true);
	    if ($id !== false) {
	      $player->sendMessage(self::cmdPrefix . "§aId: §e" . $id);
	      $this->setInRetrieveIdMode($player, true);
	    }
  	  /*foreach ($this->getDB()->getAll() as $id => $data){
  	    $from = $this->toPos($data["From"]);
  	    $to = $this->toPos($data["To"]);
  	    if ($from->distance($pos) < 2 || $to->distance($pos) < 2) {
  	      $player->sendMessage(self::cmdPrefix . "§aId: §e" . $id);
  	      $this->setInRetrieveIdMode($player, true);
  	    }
  	  }*/
  	  return;
	  }
	  // Item
	  $item = $event->getItem();
	  if (!$player->hasPermission("teleportto.item"))return;
	  if (
	    $item->getTypeId() !== ItemTypeIds::DIAMOND_HOE ||
	    $item->getNamedTag()->getString(self::prefix, "") == ""
    )return;
	  if ($player->isSneaking()) {
	    foreach ($this->getDB()->getAll() as $id => $data){
	      $from = $this->toPos($data["From"]);
        $to = $this->toPos($data["To"]);
        if ($from->distance($pos) < 2) {
          if ($this->isInSetupMode($player)) {
            $player->sendMessage($this->getMessage("TeleportPlace"));
          } else {
            $this->removeSureForm($player, $id);
          }
          return;
        }
      }
  	  if (!$this->isInSetupMode($player)) {
  	    // From
  	    $this->setInSetupMode($player, $pos);
  	    $player->sendMessage("§aFrom: §e" . implode(" ", [$pos->x, $pos->y, $pos->z]));
  	  } else {
  	    // To
  	    $player->sendMessage("§aTo: §e" . implode(" ", [$pos->x, $pos->y, $pos->z]));
  		  $this->addTeleportForm($player, $this->getSetupModeValue($player), $pos);
  		  $this->setInSetupMode($player);
  	  }
  	  return;
	  }
	  $this->addTeleportForm($player);
	}
	
	public function onMove(PlayerMoveEvent $event){
	  $player = $event->getPlayer();
	  if ($this->isInRetrieveIdMode($player)) {
  	  $nearset = $this->getNearestTeleports($player->getPosition(), 5);
  	  if (!empty($nearset)) {
  	    [$id, $distance] = [array_key_first($nearset), current($nearset)];
  	    $player->sendTip(" §aTeleport ID: §e{$id} \n §aDistance: §e{$distance} blocks ");
  	  }
    	return;
	  }
  	foreach ($this->getDB()->getAll() as $id => $data){
  	  $from = $this->toPos($data["From"]);
  	  if ($from->distance($player->getPosition()) < $this->config->get("TeleportDistance")){
  	    $effectClass = self::effects[$data["Effect"]];
  	    if (!isset($this->activedEffects[$player->getName()])) {
  	      $player->teleport($from);
  	      $effect = new $effectClass($player, $id);
  	      $effect->start();
  	      $this->activedEffects[$player->getName()] = $effect;
  	    }
  	  }
    }
	}
	
	public function getNearestTeleports(Position $pos, int $teleportDistance = 5, bool $toPos): array{
	  $teleports = [];
	  foreach ($this->getDB()->getAll() as $id => $data){
  	  $from = $this->toPos($data["From"]);
  	  $to = $this->toPos($data["To"]);
  	  if (($distance = $from->distance($pos)) <= $teleportDistance) {
  	    $teleports[$id] = $distance;
  	  } elseif ($toPos) {
  	    if (($distance = $to->distance($pos)) <= $teleportDistance) {
    	    $teleports[$id] = $distance;
  	    }
  	  }
	  }
	  asort($teleports);
	  return array_map(fn($distance) => round($distance, 1), $teleports);
	}
	
	public function getActivedEffects(): array{
	  return $this->activedEffects;
	}
	
	public function removeEffect(Player|string $player): void{
	  unset($this->activedEffects[$player instanceof Player ? $player->getName() : $player]);
	}
	
	public function isInSetupMode(Player $player): bool{
	  return isset($this->setupMode[$player->getName()]);
	}
	
	public function setInSetupMode(Player $player, ?Position $pos = null): void{
	  if ($pos == null) {
	    unset($this->setupMode[$player->getName()]);
	  } else {
	    $this->setupMode[$player->getName()] = $pos;
	  }
	}
	
	public function getSetupModeValue(Player $player): ?Position{
	  return $this->setupMode[$player->getName()];
	}
	
	public function isInRetrieveIdMode(Player $player): bool{
	  return isset($this->retrieveIdMode[$player->getName()]);
	}
	
	public function setInRetrieveIdMode(Player $player, bool $remove = false): void{
	  if ($remove) {
	    unset($this->retrieveIdMode[$player->getName()]);
	  } else {
	    $this->retrieveIdMode[$player->getName()] = "";
	  }
	}
	
	public function getDB(): Config{
	  $db = $this->db;
	  return $db;
	}
	
	public function addTeleportForm(Player $player, ?Position $from = null, ?Position $to = null) {
	  $colors = array_values(array_map(fn (DyeColor $color) => $color->getDisplayName(), DyeColor::getAll()));
	  array_unshift($colors, "Air");
    $form = new CustomForm(function(Player $player, ?array $data) use($colors): void{
      if ($data === null)return;
	    if (empty($data[0]) || empty($data[1])) {
	      $player->sendMessage($this->getMessage("TeleportError"));
	      return;
	    }
	    $id = self::newId();
	    $stringToPos = function (string $string, World $world): Position{
	      [$x, $y, $z] = explode(" ", $string);
	      return new Position($x, $y, $z, $world);
	    };
	    $from = $stringToPos($data[0], $player->getWorld());
	    $to = $stringToPos($data[1], $player->getWorld());
	    $this->addNewTeleport($id, $from, $to, array_keys(self::effects)[$data[2]], $data[3], $colors[$data[4]], $colors[$data[5]]);
	    $player->sendMessage($this->getMessage("SaveTeleport", ["ID" => $id]));
	  });
    $form->setTitle(self::prefix);
    $form->addInput("From", " from position", ($from !== null ? implode(" ", [$from->x, $from->y, $from->z]) : ""));
    $form->addInput("To", " to position", ($to !== null ? implode(" ", [$to->x, $to->y, $to->z]) : ""));
    $form->addStepSlider("Teleport Effect", array_map(fn (string $name) => ucfirst($name), array_keys(self::effects)));
    $form->addToggle("Enable Particles", true);
    $form->addStepSlider("From Particles", $colors);
    $form->addStepSlider("To Particles", $colors);
    $form->sendToPlayer($player);
  }
  
  public function removeTeleportForm(Player $player): void{
    $all = "";
    if ($this->config->getNested("DeleteForm.teleports-enable")) {
      foreach ($this->getNearestTeleports($player->getPosition(), $this->config->getNested("DeleteForm.teleports-count")) as $id => $distance) {
        $data = $this->getDB()->get($id);
        $all .= "  §aId: §e$id  §aFrom: §e" . implode(" ", $data["From"]) . "  §aTo: §e" . implode(" ", $data["To"]) . "  §eDistance: §e{$distance} blocks\n";
      }
    }
    $form = new CustomForm(function(Player $player, ?array $data): void{
      if ($data === null)return;
	    if (!isset($data[1]) || !$this->removeTeleport($data[1])) {
	      $player->sendMessage($this->getMessage("TeleportNotFound", ["ID" => $data[1] ?? -1]));
	      return;
	    }
	    $player->sendMessage($this->getMessage("TeleportRemove", ["ID" => $data[1]]));
	  });
    $form->setTitle(self::prefix);
    $form->addLabel($all);
    $form->addInput("Id", "teleport id");
    $form->sendToPlayer($player);
  }
  
  public function removeSureForm(Player $player, int $id): void{
    $form = new ModalForm(fn (Player $player, ?bool $data) => match ($data) {
      true => $this->removeTeleport($id),
      default => null
    });
    $form->setTitle(self::prefix);
    $form->setContent("§eAre you sure from removing the teleport§8[§7{$id}§8]§e?");
    $form->setButton1("§aYes");
    $form->setButton2("§cNo");
    $player->sendForm($form);
  }
  
  public function getMessage(string $key, array $replace = []): string{
    return self::cmdPrefix . str_replace(array_map(fn (string $str) => "{" . $str . "}", array_keys($replace)), array_values($replace), $this->config->getNested("Messages.{$key}", "§cMessage Not Found"));
  }
  
  public function toPos(array $data): Position{
    return new Position(($data["X"] + 0.5), ($data["Y"] + 0.5), ($data["Z"] + 0.5), $this->getWorld($data["World"]));
  }
  
  private function getWorld(string $name): World{
    $m = $this->getServer()->getWorldManager();
    if (!$m->isWorldLoaded($name)) {
      $m->loadWorld($name);
    }
    return $m->getWorldByName($name);
  }
	
	public function addNewTeleport(string $id, Position $from, Position $to, string $effect, bool $particle, string $fromColor, string $toColor){
	  $this->getDB()->set($id, [
	    "From" => [
	      "X" => floor($from->x),
	      "Y" => floor($from->y),
	      "Z" => floor($from->z),
	      "World" => $from->getWorld()->getFolderName(),
	      "Particle" => $fromColor
	    ],
	    "To" => [
	      "X" => floor($to->x),
	      "Y" => floor($to->y),
	      "Z" => floor($to->z),
	      "World" => $to->getWorld()->getFolderName(),
	      "Particle" => $toColor
	    ],
	    "Effect" => $effect,
	    "EnableParticles" => $particle
	  ]);
	  $this->getDB()->save();
	}
	
	public function isTeleportExists(int $id): bool{
	  return $this->getDB()->exists($id);
	}
	
	public function removeTeleport(int $id): bool{
	  if (!$this->isTeleportExists($id))return false;
	  $this->getDB()->remove($id);
	  $this->getDB()->save();
	  return true;
	}
	
	private static function newId(): string{
	  return (string) count(Main::getInstance()->getDB()->getAll());
	}
	
	public function playSound(Player $player, string $sound, float $volume = 1.0, float $pitch = 1): void{
	  $pos = $player->getPosition();
	  $player->getWorld()->broadcastPacketToViewers($pos, PlaySoundPacket::create($sound, $pos->getX(), $pos->getY(), $pos->getZ(), $volume, $pitch));
  }
}
