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

namespace AEDXDEV\TeleportTo\command;

use AEDXDEV\TeleportTo\Main;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\world\Position;
use pocketmine\nbt\tag\ListTag;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
// Using my lib ECMD
use AEDXDEV\ECMD\BaseCommand;
use AEDXDEV\ECMD\args\{
  RawStringArgument,
  IntegerArgument,
  Vector3Argument
};

class TpToCommand extends BaseCommand {

  public function __construct(
    private Main $plugin
  ){
    parent::__construct($plugin, "teleportto", "TeleportTo Main command", ["tpto"]);
    $this->setPermission("teleportto.use");
  }

  public function prepare(): void{
    $this->registerSubCommand("help", [], function(CommandSender $sender, string $aliasUsed, array $args): void{
		 $sender->sendMessage("§e========================");
	  	$sender->sendMessage("§a- /" . $aliasUsed . " item - give setup item");
		  $sender->sendMessage("§a- /" . $aliasUsed . " from - set the first position");
			$sender->sendMessage("§a- /" . $aliasUsed . " to - set the second position");
			$sender->sendMessage("§a- /" . $aliasUsed . " get - get id from click on teleport position");
			$sender->sendMessage("§a- /" . $aliasUsed . " remove - remove the teleport by id");
			$sender->sendMessage("§e========================");
    }, "teleportto.help");
    
    $this->registerSubCommand("item", [
      new RawStringArgument("player", true)
    ], function(CommandSender $sender, string $aliasUsed, array $args): void{
      $target = $sender;
      if (isset($args["player"])) {
        $target = $this->plugin->getServer()->getPlayerByPrefix($args["player"]);
      }
      if (!$target instanceof Player) {
        $sender->sendMessage(Main::cmdPrefix . "§cPlayer not found!");
        return;
      }
      $item = VanillaItems::DIAMOND_HOE()->setCustomName(" " . Main::prefix . " ")->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(-1)));
      $item->getNamedTag()->setString(Main::prefix, Main::prefix);
      $target->getInventory()->addItem($item);
      $target->sendMessage(Main::getInstance()->getMessage("TeleportItem"));
    }, "teleportto.item");
    
    $this->registerSubCommand("from", [
      new Vector3Argument("position", true)
    ], function(Player $sender, string $aliasUsed, array $args): void{
      $pos = $args["position"] ?? $sender->getPosition();
      $pos = Position::fromObject($pos->asVector3()->floor(), $sender->getWorld());
      $this->plugin->setInSetupMode($sender, $pos);
      $sender->sendMessage(Main::cmdPrefix . "§aFrom: §e" . implode(" ", [$pos->x, $pos->y, $pos->z]));
    }, "teleportto.from", BaseCommand::IN_GAME_CONSTRAINT);
    
    $this->registerSubCommand("to", [new Vector3Argument("position", true)], function(Player $sender, string $aliasUsed, array $args): void{
      $pos = $args["position"] ?? $sender->getPosition();
      $pos = Position::fromObject($pos->asVector3()->floor(), $sender->getWorld());
      if (!$this->plugin->isInSetupMode($sender)) {
			  $sender->sendMessage("use '/{$aliasUsed} from' before '/{$aliasUsed} to'");
			  return;
		  }
			$sender->sendMessage(Main::cmdPrefix . "§aTo: §e" . implode(" ", [$pos->x, $pos->y, $pos->z]));
			$this->plugin->addTeleportForm($sender, $this->plugin->getSetupModeValue($sender), $pos);
			$this->plugin->setInSetupMode($sender);
    }, "teleportto.to", BaseCommand::IN_GAME_CONSTRAINT);
    
    $this->registerSubCommand("get", [], function(Player $sender, string $aliasUsed, array $args): void{
      if ($this->plugin->isInRetrieveIdMode($sender)){
        $sender->sendMessage(Main::getInstance()->getMessage("InRetrieveIdMode"));
        return;
      }
		 $this->plugin->setInRetrieveIdMode($sender);
		 $sender->sendMessage(Main::getInstance()->getMessage("RetrieveIdModeActived"));
    }, "teleportto.get", BaseCommand::IN_GAME_CONSTRAINT);
    
    $this->registerSubCommand("remove", [
      new IntegerArgument("id")
    ], function(CommandSender $sender, string $aliasUsed, array $args): void{
      if ($this->plugin->isTeleportExists($args["id"])) {
        $this->plugin->removeTeleport($args["id"]);
        $sender->sendMessage(Main::getInstance()->getMessage("TeleportRemove", ["ID" => $args["id"]]));
      } else {
        $sender->sendMessage(Main::getInstance()->getMessage("TeleportNotFound", ["ID" => $args["id"]]));
      }
    }, "teleportto.remove");
  }

  public function onRun(CommandSender $sender, string $aliasUsed, array $args): void{
    $this->sendUsage();
  }
}